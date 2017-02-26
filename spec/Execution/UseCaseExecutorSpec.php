<?php

namespace spec\Bamiz\UseCaseBundle\Execution;

use Bamiz\UseCaseBundle\Actor\ActorInterface;
use Bamiz\UseCaseBundle\Actor\CompositeActorRecognizer;
use Bamiz\UseCaseBundle\Execution\ActorCannotExecuteUseCaseException;
use Bamiz\UseCaseBundle\Execution\UseCaseConfiguration;
use Bamiz\UseCaseBundle\Execution\UseCaseContext;
use Bamiz\UseCaseBundle\Execution\UseCaseContextResolver;
use Bamiz\UseCaseBundle\Exception\AlternativeCourseException;
use Bamiz\UseCaseBundle\Execution\UseCaseNotFoundException;
use Bamiz\UseCaseBundle\Processor\Input\InputProcessorInterface;
use Bamiz\UseCaseBundle\Processor\Response\InputAwareResponseProcessor;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Bamiz\UseCaseBundle\Processor\Response\ResponseProcessorInterface;
use Bamiz\UseCaseBundle\UseCase\UseCaseInterface;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Bamiz\UseCaseBundle\Execution\UseCaseExecutor;

/**
 * @mixin \Bamiz\UseCaseBundle\Execution\UseCaseExecutor
 */
class UseCaseExecutorSpec extends ObjectBehavior
{
    public function let(
        UseCaseInterface $useCase,
        InputProcessorInterface $defaultInputProcessor,
        ResponseProcessorInterface $defaultResponseProcessor,
        UseCaseContextResolver $contextResolver,
        UseCaseContext $context,
        CompositeActorRecognizer $actorRecognizer,
        ActorInterface $actor
    ) {
        $this->beConstructedWith($contextResolver, $actorRecognizer);

        $actorRecognizer->recognizeActor()->willReturn($actor);
        $actor->canExecute('use_case')->willReturn(true);

        $contextResolver->resolveContext('use_case', Argument::any())->willReturn($context);
        $context->getUseCase()->willReturn($useCase);
        $context->getUseCaseRequest()->willReturn(new SomeUseCaseRequest());
        $context->getInputProcessor()->willReturn($defaultInputProcessor);
        $context->getInputProcessorOptions()->willReturn([]);
        $context->getResponseProcessor()->willReturn($defaultResponseProcessor);
        $context->getResponseProcessorOptions()->willReturn([]);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(UseCaseExecutor::class);
    }

    public function it_throws_an_exception_if_actor_cannot_execute_use_case(ActorInterface $actor)
    {
        $actor->canExecute('cant_be_done')->willReturn(false);

        $this->shouldThrow(ActorCannotExecuteUseCaseException::class)->duringExecute('cant_be_done');
    }

    public function it_throws_exception_when_no_use_case_by_given_name_exists(
        UseCaseContextResolver $contextResolver,
        ActorInterface $actor
    )
    {
        $actor->canExecute('no_such_use_case_here')->willReturn(true);

        $exception = new UseCaseNotFoundException('Use case "no_such_use_case_here" not found.');
        $contextResolver->resolveContext('no_such_use_case_here', [])->willThrow($exception);

        $this->shouldThrow($exception)->duringExecute('no_such_use_case_here', []);
    }

    public function it_does_not_use_actor_recognizer_if_actor_is_set(
        UseCaseContextResolver $contextResolver,
        CompositeActorRecognizer $actorRecognizer,
        ActorInterface $actor
    )
    {
        $this->beConstructedWith($contextResolver, $actorRecognizer, $actor);

        $actorRecognizer->recognizeActor()->shouldNotBeCalled();

        $this->execute('use_case');
    }

    public function it_creates_request_instance_based_on_use_case_configuration_and_passes_it_into_input_processor(
        InputProcessorInterface $inputProcessor,
        UseCaseInterface $useCase,
        UseCaseContext $context,
        UseCaseContextResolver $contextResolver
    )
    {
        $inputProcessorOptions = ['name' => 'contact_form', 'style' => 'blue'];
        $context->getInputProcessor()->willReturn($inputProcessor);
        $context->getInputProcessorOptions()->willReturn($inputProcessorOptions);
        $contextResolver->resolveContext('', Argument::which('getInputProcessorOptions', $inputProcessorOptions))
            ->willReturn($context);

        $context->getInputProcessor()->willReturn($inputProcessor);

        $input = ['foo' => 'bar', 'key' => 'value'];
        $this->execute('use_case', $input);

        $useCase->execute(Argument::type(SomeUseCaseRequest::class))->shouldHaveBeenCalled();
        $inputProcessor->initializeRequest(Argument::type(SomeUseCaseRequest::class), $input, $inputProcessorOptions)
            ->shouldHaveBeenCalled();
    }

    public function it_registers_response_processor_for_use_case_with_options(
        UseCaseContextResolver $contextResolver, UseCaseContext $context, ResponseProcessorInterface $responseProcessor,
        UseCaseInterface $useCase, \stdClass $useCaseResponse, HttpResponse $output
    )
    {
        $responseProcessorOptions = ['template' => 'HelloBundle:hello:index.html.twig'];
        $context->getResponseProcessor()->willReturn($responseProcessor);
        $context->getResponseProcessorOptions()->willReturn($responseProcessorOptions);
        $contextResolver->resolveContext('', Argument::which('getResponseProcessorOptions', $responseProcessorOptions))
            ->willReturn($context);

        $responseProcessor->processResponse($useCaseResponse, $responseProcessorOptions)->willReturn($output);

        $context->getResponseProcessor()->willReturn($responseProcessor);
        $useCase->execute(Argument::type(SomeUseCaseRequest::class))->willReturn($useCaseResponse);
        $this->execute('use_case', [])->shouldBe($output);
    }

    public function it_uses_context_resolver_to_fetch_the_use_case_context(
        UseCaseInterface $useCase,
        UseCaseContextResolver $contextResolver,
        UseCaseContext $context,
        \stdClass $response,
        InputProcessorInterface $formInputProcessor,
        ResponseProcessorInterface $twigResponseProcessor,
        HttpResponse $httpResponse
    )
    {
        $config = new UseCaseConfiguration([
            'input'    => 'form',
            'response' => 'twig'
        ]);
        $contextResolver->resolveContext('use_case', $config)->willReturn($context);

        $context->getInputProcessor()->willReturn($formInputProcessor);
        $context->getInputProcessorOptions()->willReturn(['name' => 'default_form']);
        $context->getResponseProcessor()->willReturn($twigResponseProcessor);
        $context->getResponseProcessorOptions()->willReturn(['template' => 'AppBundle:hello:default.html.twig']);

        $input = ['form_data' => ['name' => 'John'], 'user_id' => 665, 'action' => 'update'];
        $formInputProcessor
            ->initializeRequest(Argument::type(SomeUseCaseRequest::class), $input, ['name' => 'default_form'])
            ->shouldBeCalled();
        $useCase->execute(Argument::type(SomeUseCaseRequest::class))->willReturn($response);
        $twigResponseProcessor->processResponse($response, ['template' => 'AppBundle:hello:default.html.twig'])
            ->willReturn($httpResponse);

        $this->execute('use_case', $input)->shouldBe($httpResponse);
    }

    public function it_uses_the_registered_response_processor_to_handle_errors(
        UseCaseContextResolver $contextResolver,
        UseCaseContext $context,
        ResponseProcessorInterface $responseProcessor,
        HttpResponse $httpResponse,
        UseCaseInterface $useCase
    )
    {
        $responseProcessorOptions = [
            'template' => 'HelloBundle:hello:index.html.twig',
            'error_template' => 'HelloBundle:goodbye:epic_fail.html.twig'
        ];
        $context->getResponseProcessor()->willReturn($responseProcessor);
        $context->getResponseProcessorOptions()->willReturn($responseProcessorOptions);
        $contextResolver
            ->resolveContext('', Argument::which('getResponseProcessorOptions', $responseProcessorOptions))
            ->willReturn($context);

        $exception = new AlternativeCourseException();
        $useCase->execute(Argument::any())->willThrow($exception);
        $responseProcessor->handleException($exception, $responseProcessorOptions)->willReturn($httpResponse);

        $this->execute('use_case', [])->shouldReturn($httpResponse);
    }

    public function it_passes_the_input_to_the_response_processor_if_its_input_aware(
        UseCaseContextResolver $contextResolver,
        UseCaseContext $context,
        InputProcessorInterface $inputProcessor,
        InputAwareResponseProcessor $inputAwareResponseProcessor
    )
    {
        $input = ['some' => 'input', 'for' => 'the use case'];
        $contextResolver->resolveContext('use_case', 'input_aware')->willReturn($context);

        $context->getInputProcessor()->willReturn($inputProcessor);
        $context->getInputProcessorOptions()->willReturn([]);
        $context->getResponseProcessor()->willReturn($inputAwareResponseProcessor);
        $context->getResponseProcessorOptions()->willReturn([]);

        $inputAwareResponseProcessor->setInput($input)->shouldBeCalled();
        $inputAwareResponseProcessor->processResponse(Argument::any(), [])->shouldBeCalled();

        $this->execute('use_case', $input, 'input_aware');
    }

    public function it_recognizes_actor_by_name_and_returns_a_new_instance_of_use_case_executor(
        CompositeActorRecognizer $actorRecognizer, ActorInterface $somethingDoer
    )
    {
        $actorRecognizer->findActorByName('something_doer')->willReturn($somethingDoer);

        $this->asActor('something_doer')->shouldHaveType(UseCaseExecutor::class);
        $this->asActor('something_doer')->getActor()->shouldBe($somethingDoer);
    }
}

class SomeUseCaseRequest {}
