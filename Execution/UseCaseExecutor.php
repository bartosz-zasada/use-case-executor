<?php

namespace Bamiz\UseCaseBundle\Execution;

use Bamiz\UseCaseBundle\Actor\ActorInterface;
use Bamiz\UseCaseBundle\Actor\ActorNotFoundException;
use Bamiz\UseCaseBundle\Actor\CompositeActorRecognizer;
use Bamiz\UseCaseBundle\Processor\Response\InputAwareResponseProcessor;
use Bamiz\UseCaseBundle\UseCase\RequestClassNotFoundException;

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
     * @var ActorInterface|null
     */
    private $actor;

    /**
     * @param UseCaseContextResolver   $contextResolver
     * @param CompositeActorRecognizer $actorRecognizer
     * @param ActorInterface           $actor
     */
    public function __construct(
        UseCaseContextResolver $contextResolver,
        CompositeActorRecognizer $actorRecognizer,
        ActorInterface $actor = null
    )
    {
        $this->contextResolver = $contextResolver;
        $this->actorRecognizer = $actorRecognizer;
        $this->actor = $actor;
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
        $this->checkIfActorCanExecuteUseCase($useCaseName);

        $context = $this->getUseCaseContext($useCaseName, $configuration);

        $useCase = $context->getUseCase();
        $request = $context->getUseCaseRequest();
        $inputProcessor = $context->getInputProcessor();
        $inputProcessorOptions = $context->getInputProcessorOptions();
        $responseProcessor = $context->getResponseProcessor();
        $responseProcessorOptions = $context->getResponseProcessorOptions();

        if ($responseProcessor instanceof InputAwareResponseProcessor) {
            $responseProcessor->setInput($input);
        }

        try {
            $inputProcessor->initializeRequest($request, $input, $inputProcessorOptions);
            $response = $useCase->execute($request);
            return $responseProcessor->processResponse($response, $responseProcessorOptions);
        } catch (\Exception $exception) {
            return $responseProcessor->handleException($exception, $responseProcessorOptions);
        }
    }

    /**
     * @param string $actorName
     *
     * @return UseCaseExecutor
     * @throws ActorNotFoundException
     */
    public function asActor($actorName)
    {
        $actor = $this->actorRecognizer->findActorByName($actorName);

        return new UseCaseExecutor($this->contextResolver, $this->actorRecognizer, $actor);
    }

    /**
     * @return ActorInterface|null
     */
    public function getActor()
    {
        return $this->actor;
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
     * @param $useCaseName
     *
     * @throws ActorCannotExecuteUseCaseException
     */
    private function checkIfActorCanExecuteUseCase($useCaseName)
    {
        if ($this->actor === null) {
            $this->actor = $this->actorRecognizer->recognizeActor();
        }

        if (!$this->actor->canExecute($useCaseName)) {
            throw new ActorCannotExecuteUseCaseException();
        }
    }
}
