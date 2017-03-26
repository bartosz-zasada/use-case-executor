<?php

namespace spec\Bamiz\UseCaseExecutor\Execution;

use Bamiz\UseCaseExecutor\Container\ContainerInterface;
use Bamiz\UseCaseExecutor\Execution\InvalidConfigurationException;
use Bamiz\UseCaseExecutor\Execution\UseCaseConfiguration;
use Bamiz\UseCaseExecutor\Execution\UseCaseContext;
use Bamiz\UseCaseExecutor\Execution\UseCaseContextResolver;
use Bamiz\UseCaseExecutor\Execution\InputProcessorNotFoundException;
use Bamiz\UseCaseExecutor\Execution\ResponseProcessorNotFoundException;
use Bamiz\UseCaseExecutor\Container\ItemNotFoundException;
use Bamiz\UseCaseExecutor\Execution\UseCaseNotFoundException;
use Bamiz\UseCaseExecutor\Processor\Input\InputProcessorInterface;
use Bamiz\UseCaseExecutor\Processor\Response\ResponseProcessorInterface;
use Bamiz\UseCaseExecutor\UseCase\UseCaseInterface;
use PhpSpec\ObjectBehavior;

class UseCaseContextResolverSpec extends ObjectBehavior
{
    public function let(
        ContainerInterface $useCaseContainer,
        UseCaseInterface $useCase,
        ContainerInterface $inputProcessorContainer,
        ContainerInterface $responseProcessorContainer,
        InputProcessorInterface $defaultInputProcessor,
        ResponseProcessorInterface $defaultResponseProcessor,
        InputProcessorInterface $httpInputProcessor,
        ResponseProcessorInterface $twigResponseProcessor,
        InputProcessorInterface $cliInputProcessor,
        ResponseProcessorInterface $cliResponseProcessor,
        InputProcessorInterface $compositeInputProcessor,
        ResponseProcessorInterface $compositeResponseProcessor
    )
    {
        $this->beConstructedWith($useCaseContainer, $inputProcessorContainer, $responseProcessorContainer);

        $useCaseContainer->get('use_case')->willReturn($useCase);
        $inputProcessorContainer->get(UseCaseContextResolver::DEFAULT_INPUT_PROCESSOR)->willReturn($defaultInputProcessor);
        $inputProcessorContainer->get('http')->willReturn($httpInputProcessor);
        $inputProcessorContainer->get('cli')->willReturn($cliInputProcessor);
        $inputProcessorContainer->get('composite')->willReturn($compositeInputProcessor);
        $responseProcessorContainer->get(UseCaseContextResolver::DEFAULT_RESPONSE_PROCESSOR)->willReturn($defaultResponseProcessor);
        $responseProcessorContainer->get('twig')->willReturn($twigResponseProcessor);
        $responseProcessorContainer->get('cli')->willReturn($cliResponseProcessor);
        $responseProcessorContainer->get('composite')->willReturn($compositeResponseProcessor);

        $this->addUseCaseConfiguration([
            'use_case'      => 'use_case',
            'request_class' => \stdClass::class
        ]);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(UseCaseContextResolver::class);
    }

    public function it_resolves_registered_use_cases(ContainerInterface $useCaseContainer, UseCaseInterface $useCase)
    {
        $useCaseContainer->get('do_awesome_things')->willReturn($useCase);

        $this->addUseCaseConfiguration([
            'use_case'      => 'do_awesome_things',
            'request_class' => \stdClass::class
        ]);
        $context = $this->resolveContext('do_awesome_things', []);

        $context->getUseCase()->shouldBe($useCase);
        $context->getUseCaseRequest()->shouldBeAnInstanceOf(\stdClass::class);
    }

    public function it_throws_an_exception_if_use_case_has_not_been_registered(ContainerInterface $useCaseContainer)
    {
        $exception = new UseCaseNotFoundException('Use case "unregistered_use_case" has not been registered.');
        $this->shouldThrow($exception)->duringResolveContext('unregistered_use_case');
    }

    public function it_throws_an_exception_if_use_case_does_not_exist(ContainerInterface $useCaseContainer)
    {
        $useCaseContainer->get('no_such_use_case_here')->willThrow(ItemNotFoundException::class);
        $exception = new UseCaseNotFoundException('Use case "no_such_use_case_here" not found.');

        $this->addUseCaseConfiguration(['use_case' => 'no_such_use_case_here', 'request_class' => \stdClass::class]);
        $this->shouldThrow($exception)->duringResolveContext('no_such_use_case_here');
    }

    public function it_resolves_use_case_specific_contexts(
        UseCaseInterface $useCase,
        InputProcessorInterface $httpInputProcessor,
        ResponseProcessorInterface $twigResponseProcessor
    )
    {
        $this->addUseCaseConfiguration([
            'use_case'      => 'use_case',
            'request_class' => \stdClass::class,
            'input'         => 'http',
            'response'      => 'twig'
        ]);

        $context = $this->resolveContext('use_case');
        $context->getUseCase()->shouldBe($useCase);
        $context->getInputProcessor()->shouldBe($httpInputProcessor);
        $context->getResponseProcessor()->shouldBe($twigResponseProcessor);
    }

    public function it_resolves_named_contexts(
        InputProcessorInterface $httpInputProcessor,
        InputProcessorInterface $cliInputProcessor,
        ResponseProcessorInterface $twigResponseProcessor,
        ResponseProcessorInterface $cliResponseProcessor
    )
    {
        $this->addContextDefinition('web', ['input' => 'http', 'response' => 'twig']);
        $this->addContextDefinition('console', ['input' => 'cli', 'response' => 'cli']);

        $webContext = $this->resolveContext('use_case', 'web');
        $webContext->shouldHaveType(UseCaseContext::class);
        $webContext->getInputProcessor()->shouldBe($httpInputProcessor);
        $webContext->getResponseProcessor()->shouldBe($twigResponseProcessor);

        $consoleContext = $this->resolveContext('use_case', 'console');
        $consoleContext->shouldHaveType(UseCaseContext::class);
        $consoleContext->getInputProcessor()->shouldBe($cliInputProcessor);
        $consoleContext->getResponseProcessor()->shouldBe($cliResponseProcessor);
    }

    public function it_throws_an_exception_if_named_context_does_not_exist()
    {
        $this->shouldThrow(InvalidConfigurationException::class)->duringResolveContext('use_case', 'no_such_context');
        $this->setDefaultContextName('nothing_here');
        $this->shouldThrow(InvalidConfigurationException::class)->duringGetDefaultConfiguration();
    }

    public function it_resolves_context_with_options(
        InputProcessorInterface $compositeInputProcessor,
        ResponseProcessorInterface $compositeResponseProcessor
    )
    {
        $configuration = [
            'input'    => ['http' => ['accept' => 'text/html']],
            'response' => ['twig' => ['template' => 'none']]
        ];
        $this->addContextDefinition('web', $configuration);

        $webContext = $this->resolveContext('use_case', 'web');
        $webContext->shouldHaveType(UseCaseContext::class);
        $webContext->getInputProcessor()->shouldBe($compositeInputProcessor);
        $webContext->getInputProcessorOptions()->shouldBe(['http' => ['accept' => 'text/html']]);
        $webContext->getResponseProcessor()->shouldBe($compositeResponseProcessor);
        $webContext->getResponseProcessorOptions()->shouldBe(['twig' => ['template' => 'none']]);
    }

    public function it_falls_back_to_default_configuration_when_context_is_incomplete(
        InputProcessorInterface $defaultInputProcessor,
        ResponseProcessorInterface $defaultResponseProcessor,
        InputProcessorInterface $httpInputProcessor,
        ResponseProcessorInterface $twigResponseProcessor
    )
    {
        $this->addContextDefinition('only_input', ['input' => 'http']);
        $this->addContextDefinition('only_response', ['response' => 'twig']);

        $onlyInputContext = $this->resolveContext('use_case', 'only_input');
        $onlyInputContext->getInputProcessor()->shouldBe($httpInputProcessor);
        $onlyInputContext->getResponseProcessor()->shouldBe($defaultResponseProcessor);
        $onlyResponseContext = $this->resolveContext('use_case', 'only_response');
        $onlyResponseContext->getInputProcessor()->shouldBe($defaultInputProcessor);
        $onlyResponseContext->getResponseProcessor()->shouldBe($twigResponseProcessor);
        $useCaseContext = $this->resolveContext('use_case');
        $useCaseContext->getInputProcessor()->shouldBe($defaultInputProcessor);
        $useCaseContext->getResponseProcessor()->shouldBe($defaultResponseProcessor);
    }

    public function it_allows_to_set_the_name_of_the_default_context(
        InputProcessorInterface $httpInputProcessor, ResponseProcessorInterface $twigResponseProcessor,
        InputProcessorInterface $cliInputProcessor, ResponseProcessorInterface $cliResponseProcessor
    )
    {
        $this->addContextDefinition('web', ['input' => 'http', 'response' => 'twig']);
        $this->addContextDefinition('only_input', ['input' => 'cli']);
        $this->addContextDefinition('only_response', ['response' => 'cli']);
        $this->setDefaultContextName('web');

        $onlyInputContext = $this->resolveContext('use_case', 'only_input');
        $onlyInputContext->getInputProcessor()->shouldBe($cliInputProcessor);
        $onlyInputContext->getResponseProcessor()->shouldBe($twigResponseProcessor);
        $onlyResponseContext = $this->resolveContext('use_case', 'only_response');
        $onlyResponseContext->getInputProcessor()->shouldBe($httpInputProcessor);
        $onlyResponseContext->getResponseProcessor()->shouldBe($cliResponseProcessor);
    }

    public function it_overrides_the_options_of_the_default_context(
        InputProcessorInterface $compositeInputProcessor, ResponseProcessorInterface $compositeResponseProcessor
    )
    {
        $this->addContextDefinition(
            'default',
            [
                'input'    => [UseCaseContextResolver::DEFAULT_INPUT_PROCESSOR => ['option' => 'foo']],
                'response' => [UseCaseContextResolver::DEFAULT_RESPONSE_PROCESSOR => ['foo' => 'bar']]
            ]
        );
        $this->addContextDefinition('only_input', ['input' => ['http' => ['accept' => 'text/html']]]);
        $this->addContextDefinition('only_response', ['response' => ['twig' => ['template' => 'none']]]);

        $onlyInputContext = $this->resolveContext('use_case', 'only_input');
        $onlyInputContext->getInputProcessor()->shouldBe($compositeInputProcessor);
        $onlyInputContext->getInputProcessorOptions()->shouldBe(['http' => ['accept' => 'text/html']]);
        $onlyInputContext->getResponseProcessor()->shouldBe($compositeResponseProcessor);
        $onlyInputContext->getResponseProcessorOptions()->shouldBe(
            [UseCaseContextResolver::DEFAULT_RESPONSE_PROCESSOR => ['foo' => 'bar']]
        );

        $onlyResponseContext = $this->resolveContext('use_case', 'only_response');
        $onlyResponseContext->getInputProcessor()->shouldBe($compositeInputProcessor);
        $onlyResponseContext->getInputProcessorOptions()->shouldBe(
            [UseCaseContextResolver::DEFAULT_INPUT_PROCESSOR => ['option' => 'foo']]
        );
        $onlyResponseContext->getResponseProcessor()->shouldBe($compositeResponseProcessor);
        $onlyResponseContext->getResponseProcessorOptions()->shouldBe(['twig' => ['template' => 'none']]);
    }

    public function it_resolves_contexts_from_configuration_array(
        InputProcessorInterface $defaultInputProcessor,
        ResponseProcessorInterface $defaultResponseProcessor,
        InputProcessorInterface $httpInputProcessor,
        ResponseProcessorInterface $twigResponseProcessor
    )
    {
        $defaultContext = $this->resolveContext(
            'use_case',
            [
                'input'    => UseCaseContextResolver::DEFAULT_INPUT_PROCESSOR,
                'response' => UseCaseContextResolver::DEFAULT_RESPONSE_PROCESSOR
            ]
        );
        $defaultContext->getInputProcessor()->shouldBe($defaultInputProcessor);
        $defaultContext->getResponseProcessor()->shouldBe($defaultResponseProcessor);

        $webContext = $this->resolveContext('use_case', ['input' => 'http', 'response' => 'twig']);
        $webContext->getInputProcessor()->shouldBe($httpInputProcessor);
        $webContext->getResponseProcessor()->shouldBe($twigResponseProcessor);
    }

    public function it_works_with_instances_of_use_case_configuration(
        InputProcessorInterface $compositeInputProcessor,
        ResponseProcessorInterface $compositeResponseProcessor
    )
    {
        $config = new UseCaseConfiguration([
            'input' => [
                'http' => ['accept' => 'application/json']
            ],
            'response' => [
                'cli' => ['width' => 80, 'height' => 25]
            ]
        ]);

        $context = $this->resolveContext('use_case', $config);
        $context->getInputProcessor()->shouldBe($compositeInputProcessor);
        $context->getInputProcessorOptions()->shouldBe(['http' => ['accept' => 'application/json']]);
        $context->getResponseProcessor()->shouldBe($compositeResponseProcessor);
        $context->getResponseProcessorOptions()->shouldBe(['cli' => ['width' => 80, 'height' => 25]]);
    }

    public function it_throws_an_exception_when_argument_type_is_invalid()
    {
        $this->shouldThrow(InvalidConfigurationException::class)->duringResolveContext('use_case', false);
        $this->shouldThrow(InvalidConfigurationException::class)->duringResolveContext('use_case', new \DateTime());
        $this->shouldThrow(InvalidConfigurationException::class)->duringResolveContext('use_case', 3.14);
    }

    public function it_throws_an_exception_if_input_processor_does_not_exist(ContainerInterface $inputProcessorContainer)
    {
        $inputProcessorContainer->get('no_such_processor')->willThrow(ItemNotFoundException::class);
        $this->addContextDefinition('broken_context', ['input' => 'no_such_processor']);
        $this->shouldThrow(InputProcessorNotFoundException::class)->duringResolveContext('use_case', 'broken_context');
    }

    public function it_throws_an_exception_if_response_processor_does_not_exist(ContainerInterface $responseProcessorContainer)
    {
        $responseProcessorContainer->get('no_such_processor')->willThrow(ItemNotFoundException::class);
        $this->addContextDefinition('broken_context', ['response' => 'no_such_processor']);
        $this->shouldThrow(ResponseProcessorNotFoundException::class)->duringResolveContext('use_case', 'broken_context');
    }
}
