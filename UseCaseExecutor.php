<?php

namespace Bamiz\UseCaseExecutor;

use Bamiz\UseCaseExecutor\Actor\CompositeActorRecognizer;
use Bamiz\UseCaseExecutor\Actor\UnableActor;
use Bamiz\UseCaseExecutor\Execution\ActorCannotExecuteUseCaseException;
use Bamiz\UseCaseExecutor\Execution\UseCaseContext;
use Bamiz\UseCaseExecutor\Execution\UseCaseContextResolver;
use Bamiz\UseCaseExecutor\Execution\UseCaseNotFoundException;
use Bamiz\UseCaseExecutor\Processor\Response\InputAwareResponseProcessor;
use Bamiz\UseCaseExecutor\UseCase\RequestClassNotFoundException;

class UseCaseExecutor
{
    /**
     * @var UseCaseContextResolver
     */
    private $contextResolver;

    /**
     * @var CompositeActorRecognizer
     */
    private $actorRecognizer;

    /**
     * @var string
     */
    private $actorName;

    /**
     * @param UseCaseContextResolver   $contextResolver
     * @param CompositeActorRecognizer $actorRecognizer
     */
    public function __construct(
        UseCaseContextResolver $contextResolver,
        CompositeActorRecognizer $actorRecognizer
    )
    {
        $this->contextResolver = $contextResolver;
        $this->actorRecognizer = $actorRecognizer;
    }

    /**
     * Executes the Use Case with the specified input data. By default, a Use Case-specific Context will be used,
     * falling back to a default one in case none was configured. Optionally, a custom Context can be specified
     * by passing either the name of a preconfigured Context, or an array containing Context configuration.
     * An instance of UseCaseConfiguration class can also be provided, but in this case it must contain the name
     * of the proper Use Case Request class.
     *
     * @param string       $useCaseName
     * @param mixed        $input
     * @param string|array $configuration
     *
     * @return mixed
     * @throws ActorCannotExecuteUseCaseException
     * @throws RequestClassNotFoundException
     * @throws UseCaseNotFoundException
     */
    public function execute($useCaseName, $input = null, $configuration = [])
    {
        $context = $this->getUseCaseContext($useCaseName, $configuration);

        $useCase = $context->getUseCase();
        $request = $context->getUseCaseRequest();
        $inputProcessor = $context->getInputProcessor();
        $responseProcessor = $context->getResponseProcessor();

        if ($responseProcessor instanceof InputAwareResponseProcessor) {
            $responseProcessor->setInput($input);
        }

        $inputProcessor->initializeRequest($request, $input, $context->getInputProcessorOptions());
        $this->checkIfActorCanExecuteUseCase($useCaseName, $request);

        try {
            $response = $useCase->execute($request);
        } catch (\Exception $exception) {
            return $responseProcessor->handleException($exception, $context->getResponseProcessorOptions());
        }

        return $responseProcessor->processResponse($response, $context->getResponseProcessorOptions());
    }

    /**
     * @param string $actorName
     *
     * @return UseCaseExecutor
     */
    public function asActor($actorName)
    {
        $this->actorName = $actorName;
        return $this;
    }

    /**
     * @param string       $useCaseName
     * @param string|array $context
     *
     * @return UseCaseContext
     */
    private function getUseCaseContext($useCaseName, $context)
    {
        return $this->contextResolver->resolveContext($useCaseName, $context);
    }

    /**
     * @param string $useCaseName
     * @param object $useCaseRequest
     *
     * @throws ActorCannotExecuteUseCaseException
     */
    private function checkIfActorCanExecuteUseCase($useCaseName, $useCaseRequest)
    {
        $actor = $this->actorRecognizer->recognizeActor($useCaseRequest);

        if (!$actor->canExecute($useCaseName)) {
            if ($actor instanceof UnableActor) {
                $message = sprintf('No Actor can execute "%s" Use Case.', $useCaseName);
            } else {
                $message = sprintf('Actor "%s" cannot execute "%s" Use Case.', $actor->getName(), $useCaseName);
            }

            throw new ActorCannotExecuteUseCaseException($message);
        }
    }
}
