<?php

namespace spec\Lamudi\UseCaseBundle\DependencyInjection;

use Doctrine\Common\Annotations\AnnotationReader;
use Lamudi\UseCaseBundle\Annotation\UseCase as UseCaseAnnotation;
use Lamudi\UseCaseBundle\Annotation\InputProcessor as InputAnnotation;
use Lamudi\UseCaseBundle\Annotation\ResponseProcessor as ResponseAnnotation;
use Lamudi\UseCaseBundle\Container\Container;
use Lamudi\UseCaseBundle\Container\ReferenceAcceptingContainerInterface;
use Lamudi\UseCaseBundle\DependencyInjection\InvalidUseCase;
use Lamudi\UseCaseBundle\Execution\UseCaseConfiguration;
use Lamudi\UseCaseBundle\UseCase\RequestResolver;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @mixin \Lamudi\UseCaseBundle\DependencyInjection\UseCaseCompilerPass
 */
class UseCaseCompilerPassSpec extends ObjectBehavior
{
    public function let(
        AnnotationReader $annotationReader, RequestResolver $requestResolver,
        ContainerBuilder $containerBuilder, Definition $useCaseExecutorDefinition,
        Definition $useCaseContainerDefinition, Definition $inputProcessorContainerDefinition,
        Definition $responseProcessorContainerDefinition, Definition $contextResolverDefinition
    )
    {
        $this->beConstructedWith($annotationReader, $requestResolver);

        $containerBuilder->findDefinition('lamudi_use_case.executor')->willReturn($useCaseExecutorDefinition);
        $containerBuilder->findDefinition('lamudi_use_case.context_resolver')->willReturn($contextResolverDefinition);
        $containerBuilder->findDefinition('lamudi_use_case.container.use_case')->willReturn($useCaseContainerDefinition);
        $containerBuilder->findDefinition('lamudi_use_case.container.input_processor')->willReturn($inputProcessorContainerDefinition);
        $containerBuilder->findDefinition('lamudi_use_case.container.response_processor')->willReturn($responseProcessorContainerDefinition);
        $containerBuilder->getParameter('lamudi_use_case.default_context')->willReturn('default');
        $containerBuilder->getParameter('lamudi_use_case.contexts')->willReturn([]);
        $containerBuilder->has('lamudi_use_case.executor')->willReturn(true);
        $useCaseContainerDefinition->getClass()->willReturn(Container::class);
        $inputProcessorContainerDefinition->getClass()->willReturn(Container::class);
        $responseProcessorContainerDefinition->getClass()->willReturn(Container::class);

        $containerBuilder->findTaggedServiceIds(Argument::any())->willReturn([]);
        $containerBuilder->getDefinitions()->willReturn([]);
        $useCaseExecutorDefinition->addMethodCall(Argument::cetera())->willReturn();
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Lamudi\UseCaseBundle\DependencyInjection\UseCaseCompilerPass');
    }

    public function it_does_nothing_if_use_case_executor_is_not_registered(ContainerBuilder $containerBuilder)
    {
        $containerBuilder->has('lamudi_use_case.executor')->willReturn(false);
        $containerBuilder->findDefinition('lamudi_use_case.executor')->shouldNotBeCalled();
        $containerBuilder->findTaggedServiceIds('use_case')->shouldNotBeCalled();
        $this->process($containerBuilder);
    }

    public function it_adds_annotated_services_to_the_use_case_container(
        ContainerBuilder $containerBuilder, AnnotationReader $annotationReader, Definition $useCaseContainerDefinition,
        UseCaseAnnotation $useCaseAnnotation, UseCaseConfiguration $useCaseConfiguration,
        Definition $useCaseExecutorDefinition
    )
    {
        $useCaseAnnotation->getName()->willReturn('use_case_1');
        $useCaseAnnotation->getConfiguration()->willReturn($useCaseConfiguration);
        $useCaseConfiguration->getInputProcessorName()->willReturn('form');
        $useCaseConfiguration->getInputProcessorOptions()->willReturn([]);
        $useCaseConfiguration->getResponseProcessorName()->willReturn('twig');
        $useCaseConfiguration->getResponseProcessorOptions()->willReturn([]);

        $containerBuilder->getDefinitions()->willReturn(['uc1' => new Definition(UseCase1::class)]);
        $annotationReader->getClassAnnotations(new \ReflectionClass(UseCase1::class))->willReturn([$useCaseAnnotation]);

        $useCaseContainerDefinition->addMethodCall('set', ['use_case_1', new Reference('uc1')])->shouldBeCalled();
        $useCaseExecutorDefinition->addMethodCall('assignInputProcessor', ['use_case_1', 'form', []])->shouldBeCalled();
        $useCaseExecutorDefinition->addMethodCall('assignResponseProcessor', ['use_case_1', 'twig', []])->shouldBeCalled();

        $this->process($containerBuilder);
    }

    public function it_adds_the_same_service_multiple_times_if_multiple_use_case_annotations_were_found(
        ContainerBuilder $containerBuilder, AnnotationReader $annotationReader, Definition $useCaseContainerDefinition,
        UseCaseAnnotation $useCaseAnnotation1, UseCaseConfiguration $useCaseConfiguration1,
        UseCaseAnnotation $useCaseAnnotation2, UseCaseConfiguration $useCaseConfiguration2,
        Definition $useCaseExecutorDefinition
    )
    {
        $containerBuilder->getDefinitions()->willReturn(['uc2' => new Definition(UseCase2::class)]);
        $useCaseAnnotation1->getName()->willReturn('use_case_2');
        $useCaseAnnotation2->getName()->willReturn('use_case_2_alias');
        $useCaseAnnotation1->getConfiguration()->willReturn($useCaseConfiguration1);
        $useCaseAnnotation2->getConfiguration()->willReturn($useCaseConfiguration2);

        $useCaseConfiguration1->getInputProcessorName()->willReturn('');
        $useCaseConfiguration2->getInputProcessorName()->willReturn('');
        $useCaseConfiguration1->getResponseProcessorName()->willReturn('twig');
        $useCaseConfiguration2->getResponseProcessorName()->willReturn('json');
        $useCaseConfiguration1->getResponseProcessorOptions()->willReturn([]);
        $useCaseConfiguration2->getResponseProcessorOptions()->willReturn([]);

        $annotationReader->getClassAnnotations(new \ReflectionClass(UseCase2::class))->willReturn([
            $useCaseAnnotation1, $useCaseAnnotation2
        ]);

        $useCaseContainerDefinition->addMethodCall('set', ['use_case_2', new Reference('uc2')])->shouldBeCalled();
        $useCaseContainerDefinition->addMethodCall('set', ['use_case_2_alias', new Reference('uc2')])->shouldBeCalled();
        $useCaseExecutorDefinition->addMethodCall('assignResponseProcessor', ['use_case_2', 'twig', []])->shouldBeCalled();
        $useCaseExecutorDefinition->addMethodCall('assignResponseProcessor', ['use_case_2_alias', 'json', []])->shouldBeCalled();

        $this->process($containerBuilder);
    }

    public function it_adds_input_processors_to_container_under_an_alias(
        ContainerBuilder $containerBuilder, Definition $inputProcessorContainerDefinition
    )
    {
        $inputProcessorsWithTags = [
            'input_processor_1' => [['alias' => 'foo']],
            'input_processor_2' => [['alias' => 'bar']]
        ];
        $containerBuilder->findTaggedServiceIds('use_case_input_processor')->willReturn($inputProcessorsWithTags);

        $inputProcessorContainerDefinition->addMethodCall('set', ['foo', new Reference('input_processor_1')])->shouldBeCalled();
        $inputProcessorContainerDefinition->addMethodCall('set', ['bar', new Reference('input_processor_2')])->shouldBeCalled();

        $this->process($containerBuilder);
    }

    public function it_adds_response_processors_to_container_under_an_alias(
        ContainerBuilder $containerBuilder, Definition $responseProcessorContainerDefinition
    )
    {
        $responseProcessorsWithTags = [
            'response_processor_1' => [['alias' => 'faz']],
            'response_processor_2' => [['alias' => 'baz']]
        ];
        $containerBuilder->findTaggedServiceIds('use_case_response_processor')->willReturn($responseProcessorsWithTags);

        $responseProcessorContainerDefinition
            ->addMethodCall('set', ['faz', new Reference('response_processor_1')])
            ->shouldBeCalled();
        $responseProcessorContainerDefinition
            ->addMethodCall('set', ['baz', new Reference('response_processor_2')])
            ->shouldBeCalled();

        $this->process($containerBuilder);
    }

    public function it_uses_request_resolver_to_add_use_case_request_class_config_to_the_container(
        ContainerBuilder $containerBuilder, AnnotationReader $annotationReader, RequestResolver $requestResolver,
        Definition $useCaseExecutorDefinition, Definition $useCaseContainerDefinition,
        UseCaseAnnotation $useCaseAnnotation, UseCaseConfiguration $useCaseConfiguration
    )
    {
        $useCaseAnnotation->getName()->willReturn('use_case_1');
        $useCaseAnnotation->getConfiguration()->willReturn($useCaseConfiguration);

        $containerBuilder->getDefinitions()->willReturn(['uc1' => new Definition(UseCase1::class)]);
        $annotationReader->getClassAnnotations(new \ReflectionClass(UseCase1::class))->willReturn([$useCaseAnnotation]);
        $requestResolver->resolve(UseCase1::class)->willReturn('UseCase1Request');

        $useCaseContainerDefinition->addMethodCall('set', ['use_case_1', new Reference('uc1')])->shouldBeCalled();
        $useCaseExecutorDefinition->addMethodCall('assignRequestClass', ['use_case_1', 'UseCase1Request'])->shouldBeCalled();

        $this->process($containerBuilder);
    }

    public function it_throws_an_exception_when_an_annotated_class_does_not_contain_execute_method(
        ContainerBuilder $containerBuilder, AnnotationReader $annotationReader
    )
    {
        $useCaseAnnotation = new UseCaseAnnotation(['value' => 'use_case']);
        $containerBuilder->getDefinitions()->willReturn([
            'not_a_use_case' => new Definition(NotAUseCase::class)
        ]);
        $annotationReader->getClassAnnotations(new \ReflectionClass(NotAUseCase::class))->willReturn([$useCaseAnnotation]);

        $this->shouldThrow(InvalidUseCase::class)->duringProcess($containerBuilder);
    }

    public function it_adds_service_names_instead_of_references_to_container_that_accepts_references(
        AnnotationReader $annotationReader, ContainerBuilder $containerBuilder, Definition $useCaseContainerDefinition,
        Definition $inputProcessorContainerDefinition, Definition $responseProcessorContainerDefinition
    )
    {
        $useCaseContainerDefinition->getClass()->willReturn(ContainerThatAcceptsReferences::class);
        $inputProcessorContainerDefinition->getClass()->willReturn(ContainerThatAcceptsReferences::class);
        $responseProcessorContainerDefinition->getClass()->willReturn(ContainerThatAcceptsReferences::class);

        $containerBuilder->getDefinitions()->willReturn(['service.use_case_1' => new Definition(UseCase1::class)]);
        $useCaseAnnotation = new UseCaseAnnotation(['value' => 'use_case_1']);
        $annotationReader->getClassAnnotations(new \ReflectionClass(UseCase1::class))->willReturn([$useCaseAnnotation]);

        $inputProcessorsWithTags = ['service.input_processor' => [['alias' => 'input']]];
        $responseProcessorsWithTags = ['service.response_processor' => [['alias' => 'response']]];
        $containerBuilder->findTaggedServiceIds('use_case_input_processor')->willReturn($inputProcessorsWithTags);
        $containerBuilder->findTaggedServiceIds('use_case_response_processor')->willReturn($responseProcessorsWithTags);

        $useCaseContainerDefinition->addMethodCall('set', Argument::is(['use_case_1', 'service.use_case_1']))->shouldBeCalled();
        $inputProcessorContainerDefinition->addMethodCall('set', Argument::is(['input', 'service.input_processor']))->shouldBeCalled();
        $responseProcessorContainerDefinition->addMethodCall('set', Argument::is(['response', 'service.response_processor']))->shouldBeCalled();

        $this->process($containerBuilder);
    }

    public function it_registers_a_use_case_under_its_snake_cased_class_name_when_name_not_specified(
        ContainerBuilder $containerBuilder, AnnotationReader $annotationReader, Definition $useCaseContainerDefinition
    )
    {
        $emptyAnnotation = new UseCaseAnnotation([]);
        $containerBuilder->getDefinitions()->willReturn([
            'my_app.use_case' => new Definition(UseCase1::class),
            'my_app.use_case_2' => new Definition(DoImportantStuff::class),
        ]);
        $annotationReader->getClassAnnotations(new \ReflectionClass(UseCase1::class))->willReturn([$emptyAnnotation]);
        $annotationReader->getClassAnnotations(new \ReflectionClass(DoImportantStuff::class))->willReturn([$emptyAnnotation]);

        $useCaseContainerDefinition->addMethodCall('set', ['use_case_1', new Reference('my_app.use_case')])->shouldBeCalled();
        $useCaseContainerDefinition->addMethodCall('set', ['do_important_stuff', new Reference('my_app.use_case_2')])->shouldBeCalled();

        $this->process($containerBuilder);
    }

    public function it_adds_context_definitions_to_the_resolver(
        ContainerBuilder $containerBuilder, Definition $contextResolverDefinition
    )
    {
        $contexts = [
            'my_default_context' => ['response' => 'json'],
            'my_other_context' => ['input' => 'array'],
            'web' => ['input' => ['type' => 'http', 'accept' => 'json'], 'response' => 'twig']
        ];
        $containerBuilder->getParameter('lamudi_use_case.default_context')->willReturn('my_default_context');
        $containerBuilder->getParameter('lamudi_use_case.contexts')->willReturn($contexts);

        $contextResolverDefinition->addMethodCall('setDefaultContextName', ['my_default_context'])->shouldBeCalled();
        $contextResolverDefinition->addMethodCall('addContextDefinition', ['my_default_context', null, 'json'])->shouldBeCalled();
        $contextResolverDefinition->addMethodCall('addContextDefinition', ['my_other_context', 'array', null])->shouldBeCalled();
        $contextResolverDefinition->addMethodCall('addContextDefinition', ['web', ['type' => 'http', 'accept' => 'json'], 'twig'])->shouldBeCalled();

        $this->process($containerBuilder);
    }

    public function it_configures_input_processors_using_separate_annotations(
        ContainerBuilder $containerBuilder, AnnotationReader $annotationReader,
        Definition $useCaseExecutorDefinition, Definition $useCaseContainerDefinition,
        UseCaseAnnotation $useCaseAnnotation, UseCaseConfiguration $useCaseConfiguration,
        InputAnnotation $inputAnnotation
    )
    {
        $useCaseAnnotation->getName()->willReturn('use_case');
        $useCaseAnnotation->getConfiguration()->willReturn($useCaseConfiguration);

        $inputAnnotation->getName()->willReturn('http');
        $inputAnnotation->getOptions()->willReturn(['order' => 'GPC']);

        $containerBuilder->getDefinitions()->willReturn(['uc1' => new Definition(UseCase1::class)]);
        $annotationReader->getClassAnnotations(new \ReflectionClass(UseCase1::class))->willReturn([
            $useCaseAnnotation, $inputAnnotation
        ]);

        $useCaseConfiguration->addInputProcessor('http', ['order' => 'GPC'])->shouldBeCalled();
        $useCaseConfiguration->getInputProcessorName()->willReturn('composite');
        $useCaseConfiguration->getInputProcessorOptions()->willReturn(['json' => [], 'http' => ['order' => 'GPC']]);
        $useCaseConfiguration->getResponseProcessorName()->willReturn('');

        $useCaseContainerDefinition->addMethodCall('set', ['use_case', new Reference('uc1')])->shouldBeCalled();
        $useCaseExecutorDefinition->addMethodCall(
            'assignInputProcessor', ['use_case', 'composite', ['json' => [], 'http' => ['order' => 'GPC']]]
        )->shouldBeCalled();

        $this->process($containerBuilder);
    }

    public function it_configures_response_processors_using_separate_annotations(
        ContainerBuilder $containerBuilder, AnnotationReader $annotationReader,
        Definition $useCaseExecutorDefinition, Definition $useCaseContainerDefinition,
        UseCaseAnnotation $useCaseAnnotation, UseCaseConfiguration $useCaseConfiguration,
        ResponseAnnotation $responseAnnotation
    )
    {
        $useCaseAnnotation->getName()->willReturn('use_case');
        $useCaseAnnotation->getConfiguration()->willReturn($useCaseConfiguration);

        $processorName = 'twig';
        $processorOptions = ['template' => 'index.html.twig'];
        $responseAnnotation->getName()->willReturn($processorName);
        $responseAnnotation->getOptions()->willReturn($processorOptions);

        $containerBuilder->getDefinitions()->willReturn(['uc1' => new Definition(UseCase1::class)]);
        $annotationReader->getClassAnnotations(new \ReflectionClass(UseCase1::class))->willReturn([
            $useCaseAnnotation, $responseAnnotation
        ]);

        $useCaseConfiguration->addResponseProcessor($processorName, $processorOptions)->shouldBeCalled();
        $useCaseConfiguration->getInputProcessorName()->willReturn('');
        $useCaseConfiguration->getResponseProcessorName()->willReturn($processorName);
        $useCaseConfiguration->getResponseProcessorOptions()->willReturn($processorOptions);

        $useCaseContainerDefinition->addMethodCall('set', ['use_case', new Reference('uc1')])->shouldBeCalled();
        $useCaseExecutorDefinition->addMethodCall(
            'assignResponseProcessor', ['use_case', $processorName, $processorOptions]
        )->shouldBeCalled();

        $this->process($containerBuilder);
    }

    public function it_does_not_accept_processor_annotations_if_many_use_case_annotations_are_used(
        ContainerBuilder $containerBuilder, AnnotationReader $annotationReader,
        UseCaseAnnotation $useCaseAnnotation1, UseCaseAnnotation $useCaseAnnotation2,
        InputAnnotation $inputAnnotation, ResponseAnnotation $responseAnnotation
    )
    {
        $containerBuilder->getDefinitions()->willReturn(['uc1' => new Definition(UseCase1::class)]);

        $annotationReader->getClassAnnotations(new \ReflectionClass(UseCase1::class))->willReturn([
            $useCaseAnnotation1, $useCaseAnnotation2, $inputAnnotation
        ]);
        $this->shouldThrow(\InvalidArgumentException::class)->duringProcess($containerBuilder);

        $annotationReader->getClassAnnotations(new \ReflectionClass(UseCase1::class))->willReturn([
            $useCaseAnnotation1, $useCaseAnnotation2, $responseAnnotation
        ]);
        $this->shouldThrow(\InvalidArgumentException::class)->duringProcess($containerBuilder);
    }
}

class ContainerThatAcceptsReferences implements ReferenceAcceptingContainerInterface {
    public function set($name, $item) { }
    public function get($name) { }
}

class UseCase1
{
    public function execute()
    {
    }
}

class UseCase2
{
    public function execute()
    {
    }
}

class UseCase3
{
    public function execute()
    {
    }
}

class DoImportantStuff
{
    public function execute()
    {
    }
}

class NotAUseCase
{
    public function doNothing()
    {
    }
}
