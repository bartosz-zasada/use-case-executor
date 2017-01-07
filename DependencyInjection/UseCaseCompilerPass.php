<?php

namespace Bamiz\UseCaseBundle\DependencyInjection;

use Doctrine\Common\Annotations\AnnotationReader;
use Bamiz\UseCaseBundle\Annotation\InputProcessor as InputAnnotation;
use Bamiz\UseCaseBundle\Annotation\ProcessorAnnotation;
use Bamiz\UseCaseBundle\Annotation\ResponseProcessor;
use Bamiz\UseCaseBundle\Annotation\UseCase as UseCaseAnnotation;
use Bamiz\UseCaseBundle\Container\ReferenceAcceptingContainerInterface;
use Bamiz\UseCaseBundle\UseCase\RequestResolver;
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
        $executorDefinition = $container->findDefinition('bamiz_use_case.executor');
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
                throw new \LogicException(sprintf('Could not load annotations for class %s: %s', $serviceClass, $e->getMessage()));
            }

            foreach ($annotations as $annotation) {
                if ($annotation instanceof UseCaseAnnotation) {
                    $this->validateUseCase($useCaseReflection);
                    $this->validateAnnotations($annotations, $serviceClass);
                    $this->registerUseCase(
                        $id, $serviceClass, $annotation, $annotations, $executorDefinition, $useCaseContainerDefinition
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

        $resolverDefinition->addMethodCall('setDefaultContextName', [$defaultContextName]);
        foreach ($contexts as $name => $context) {
            $input = isset($context['input']) ? $context['input'] : null;
            $response = isset($context['response']) ? $context['response'] : null;
            $resolverDefinition->addMethodCall('addContextDefinition', [$name, $input, $response]);
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
     * @param string $useCaseClassName
     *
     * @throws \InvalidArgumentException
     */
    private function validateAnnotations($annotations, $useCaseClassName)
    {
        $useCaseAnnotationCount = 0;
        $processorAnnotationCount = 0;
        foreach ($annotations as $annotation) {
            if ($annotation instanceof UseCaseAnnotation) {
                $useCaseAnnotationCount++;
            }
            if ($annotation instanceof ProcessorAnnotation) {
                $processorAnnotationCount++;
            }
        }
        
        if ($useCaseAnnotationCount > 1 && $processorAnnotationCount > 0) {
            throw new \InvalidArgumentException(sprintf(
                'It is not possible to use @InputProcessor or @ResponseProcessor annotations while registering ' .
                'class %s as more than one Use Case. Please configure the Use Case contexts using parameters ' .
                'in the respective @UseCase annotations.'
            , $useCaseClassName));
        }
    }

    /**
     * @param string            $serviceId
     * @param string            $serviceClass
     * @param UseCaseAnnotation $useCaseAnnotation
     * @param array             $annotations
     * @param Definition        $executorDefinition
     * @param Definition        $containerDefinition
     *
     * @throws \Bamiz\UseCaseBundle\UseCase\RequestClassNotFoundException
     */
    private function registerUseCase($serviceId, $serviceClass, $useCaseAnnotation, $annotations, $executorDefinition, $containerDefinition)
    {
        $useCaseName = $useCaseAnnotation->getName() ?: $this->fqnToUseCaseName($serviceClass);

        $this->addUseCaseToUseCaseContainer($containerDefinition, $useCaseName, $serviceId);
        $this->assignInputProcessorToUseCase($executorDefinition, $useCaseName, $useCaseAnnotation, $annotations);
        $this->assignResponseProcessorToUseCase($executorDefinition, $useCaseName, $useCaseAnnotation, $annotations);
        $this->resolveUseCaseRequestClassName($executorDefinition, $useCaseName, $serviceClass);
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

    /**
     * @param Definition        $executorDefinition
     * @param string            $useCaseName
     * @param UseCaseAnnotation $useCaseAnnotation
     * @param array             $annotations
     *
     * @throws \Symfony\Component\DependencyInjection\Exception\InvalidArgumentException
     */
    private function assignInputProcessorToUseCase($executorDefinition, $useCaseName, $useCaseAnnotation, $annotations)
    {
        $useCaseConfig = $useCaseAnnotation->getConfiguration();
        foreach ($annotations as $annotation) {
            if ($annotation instanceof InputAnnotation) {
                $useCaseConfig->addInputProcessor($annotation->getName(), $annotation->getOptions());
            }
        }

        if ($useCaseConfig->getInputProcessorName()) {
            $executorDefinition->addMethodCall(
                'assignInputProcessor',
                [$useCaseName, $useCaseConfig->getInputProcessorName(), $useCaseConfig->getInputProcessorOptions()]
            );
        }
    }

    /**
     * @param Definition        $executorDefinition
     * @param string            $useCaseName
     * @param UseCaseAnnotation $useCaseAnnotations
     * @param array             $annotations
     *
     * @throws \Symfony\Component\DependencyInjection\Exception\InvalidArgumentException
     */
    private function assignResponseProcessorToUseCase($executorDefinition, $useCaseName, $useCaseAnnotations, $annotations)
    {
        $useCaseConfig = $useCaseAnnotations->getConfiguration();
        foreach ($annotations as $annotation) {
            if ($annotation instanceof ResponseProcessor) {
                $useCaseConfig->addResponseProcessor($annotation->getName(), $annotation->getOptions());
            }
        }

        if ($useCaseConfig->getResponseProcessorName()) {
            $executorDefinition->addMethodCall(
                'assignResponseProcessor',
                [$useCaseName, $useCaseConfig->getResponseProcessorName(), $useCaseConfig->getResponseProcessorOptions()]
            );
        }
    }

    /**
     * @param Definition $executorDefinition
     * @param string     $useCaseName
     * @param string     $useCaseClassName
     *
     * @throws \Bamiz\UseCaseBundle\UseCase\RequestClassNotFoundException
     */
    private function resolveUseCaseRequestClassName($executorDefinition, $useCaseName, $useCaseClassName)
    {
        $requestClassName = $this->requestResolver->resolve($useCaseClassName);
        $executorDefinition->addMethodCall('assignRequestClass', [$useCaseName, $requestClassName]);
    }
}
