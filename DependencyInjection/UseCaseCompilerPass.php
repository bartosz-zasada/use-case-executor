<?php

namespace Bamiz\UseCaseBundle\DependencyInjection;

use Bamiz\UseCaseBundle\Annotation\ProcessorAnnotation;
use Bamiz\UseCaseBundle\Annotation\UseCase as UseCaseAnnotation;
use Bamiz\UseCaseBundle\Container\ReferenceAcceptingContainerInterface;
use Bamiz\UseCaseBundle\UseCase\RequestResolver;
use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class UseCaseCompilerPass implements CompilerPassInterface
{
    /**
     * @var AnnotationReader
     */
    private $annotationReader;

    /**
     * @var RequestResolver
     */
    private $requestResolver;

    /**
     * @param AnnotationReader $annotationReader
     * @param RequestResolver  $requestResolver
     */
    public function __construct(AnnotationReader $annotationReader = null, RequestResolver $requestResolver = null)
    {
        $this->annotationReader = $annotationReader ?: new AnnotationReader();
        $this->requestResolver = $requestResolver ?: new RequestResolver();
    }

    /**
     * You can modify the container here before it is dumped to PHP code.
     *
     * @param ContainerBuilder $container
     *
     * @throws \Exception
     * @api
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->has('bamiz_use_case.executor')) {
            return;
        }

        $this->addInputProcessorsToContainer($container);
        $this->addResponseProcessorsToContainer($container);
        $this->addUseCasesToContainer($container);
        $this->addContextsToResolver($container);
    }

    /**
     * @param ContainerBuilder $container
     *
     * @throws \Exception
     */
    private function addUseCasesToContainer(ContainerBuilder $container)
    {
        $resolverDefinition = $container->findDefinition('bamiz_use_case.context_resolver');
        $useCaseContainerDefinition = $container->findDefinition('bamiz_use_case.container.use_case');
        $services = $container->getDefinitions();

        foreach ($services as $id => $serviceDefinition) {
            $serviceClass = $serviceDefinition->getClass();
            if (!class_exists($serviceClass)) {
                continue;
            }

            $useCaseReflection = new \ReflectionClass($serviceClass);
            try {
                $annotations = $this->annotationReader->getClassAnnotations($useCaseReflection);
            } catch (\InvalidArgumentException $e) {
                throw new \LogicException(
                    sprintf('Could not load annotations for class %s: %s', $serviceClass, $e->getMessage())
                );
            }

            foreach ($annotations as $annotation) {
                if ($annotation instanceof UseCaseAnnotation) {
                    $this->validateUseCase($useCaseReflection);
                    $this->validateAnnotations($annotations, $serviceClass);
                    $this->registerUseCase(
                        $id, $serviceClass, $annotations, $resolverDefinition, $useCaseContainerDefinition
                    );
                }
            }
        }
    }

    /**
     * @param ContainerBuilder $containerBuilder
     *
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     * @throws \Symfony\Component\DependencyInjection\Exception\InvalidArgumentException
     */
    private function addInputProcessorsToContainer(ContainerBuilder $containerBuilder)
    {
        $processorContainerDefinition = $containerBuilder->findDefinition('bamiz_use_case.container.input_processor');
        $inputProcessors = $containerBuilder->findTaggedServiceIds('use_case_input_processor');
        /**
         * @var string $id
         * @var array  $tags
         */
        foreach ($inputProcessors as $id => $tags) {
            foreach ($tags as $attributes) {
                if ($this->containerAcceptsReferences($processorContainerDefinition)) {
                    $processorContainerDefinition->addMethodCall('set', [$attributes['alias'], $id]);
                } else {
                    $processorContainerDefinition->addMethodCall('set', [$attributes['alias'], new Reference($id)]);
                }
            }
        }
    }

    /**
     * @param ContainerBuilder $containerBuilder
     *
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     */
    private function addResponseProcessorsToContainer(ContainerBuilder $containerBuilder)
    {
        $processorContainerDefinition = $containerBuilder->findDefinition(
            'bamiz_use_case.container.response_processor'
        );
        $responseProcessors = $containerBuilder->findTaggedServiceIds('use_case_response_processor');

        foreach ($responseProcessors as $id => $tags) {
            foreach ($tags as $attributes) {
                if ($this->containerAcceptsReferences($processorContainerDefinition)) {
                    $processorContainerDefinition->addMethodCall('set', [$attributes['alias'], $id]);
                } else {
                    $processorContainerDefinition->addMethodCall('set', [$attributes['alias'], new Reference($id)]);
                }
            }
        }
    }

    /**
     * @param ContainerBuilder $containerBuilder
     */
    private function addContextsToResolver(ContainerBuilder $containerBuilder)
    {
        $resolverDefinition = $containerBuilder->findDefinition('bamiz_use_case.context_resolver');
        $defaultContextName = $containerBuilder->getParameter('bamiz_use_case.default_context');
        $contexts = (array)$containerBuilder->getParameter('bamiz_use_case.contexts');

        $resolverDefinition->addMethodCall('setDefaultContextName', [$defaultContextName, []]);
        foreach ($contexts as $name => $contextConfiguration) {
            $resolverDefinition->addMethodCall('addContextDefinition', [$name, $contextConfiguration]);
        }
    }

    /**
     * @param \ReflectionClass $useCaseReflection
     *
     * @throws InvalidUseCase
     */
    private function validateUseCase($useCaseReflection)
    {
        if (!$useCaseReflection->hasMethod('execute')) {
            throw new InvalidUseCase(sprintf(
                'Class "%s" has been annotated as a Use Case, but does not contain execute() method.',
                $useCaseReflection->getName()
            ));
        }
    }

    /**
     * @param array  $annotations
     * @param string $serviceClass
     *
     * @throws \InvalidArgumentException
     */
    private function validateAnnotations($annotations, $serviceClass)
    {
        $useCaseAnnotationCount = 0;
        foreach ($annotations as $annotation) {
            if ($annotation instanceof UseCaseAnnotation) {
                $useCaseAnnotationCount++;
            }
        }
        
        if ($useCaseAnnotationCount > 1) {
            throw new \InvalidArgumentException(sprintf(
                'It is not possible for a class to be more than one Use Case. ' .
                'Please remove the excessive @UseCase annotations from class %s',
                $serviceClass
            ));
        }
    }

    /**
     * @param string            $serviceId
     * @param string            $serviceClass
     * @param array             $annotations
     * @param Definition        $resolverDefinition
     * @param Definition        $containerDefinition
     *
     * @throws \Bamiz\UseCaseBundle\UseCase\RequestClassNotFoundException
     */
    private function registerUseCase($serviceId, $serviceClass, $annotations, $resolverDefinition, $containerDefinition)
    {
        $configuration = [
            'request_class' => $this->requestResolver->resolve($serviceClass)
        ];

        foreach ($annotations as $annotation) {
            if ($annotation instanceof UseCaseAnnotation) {
                $configuration['use_case'] = $annotation->getName() ?: $this->fqnToUseCaseName($serviceClass);
            }
            if ($annotation instanceof ProcessorAnnotation) {
                $configuration[$annotation->getType()][$annotation->getName()] = $annotation->getOptions();
            }
        }

        $this->addUseCaseToUseCaseContainer($containerDefinition, $configuration['use_case'], $serviceId);
        $resolverDefinition->addMethodCall('addUseCaseConfiguration', [$configuration]);
    }

    /**
     * @param Definition $containerDefinition
     *
     * @return bool
     */
    private function containerAcceptsReferences($containerDefinition)
    {
        $interfaces = class_implements($containerDefinition->getClass());
        if (is_array($interfaces)) {
            return in_array(ReferenceAcceptingContainerInterface::class, $interfaces);
        } else {
            return false;
        }
    }

    /**
     * @param string $fqn
     *
     * @return string
     */
    private function fqnToUseCaseName($fqn)
    {
        $unqualifiedName = substr($fqn, strrpos($fqn, '\\') + 1);
        return ltrim(strtolower(preg_replace('/[A-Z0-9]/', '_$0', $unqualifiedName)), '_');
    }

    /**
     * @param Definition $containerDefinition
     * @param string     $useCaseName
     * @param string     $serviceId
     */
    private function addUseCaseToUseCaseContainer($containerDefinition, $useCaseName, $serviceId)
    {
        if ($this->containerAcceptsReferences($containerDefinition)) {
            $containerDefinition->addMethodCall('set', [$useCaseName, $serviceId]);
        } else {
            $containerDefinition->addMethodCall('set', [$useCaseName, new Reference($serviceId)]);
        }
    }
}
