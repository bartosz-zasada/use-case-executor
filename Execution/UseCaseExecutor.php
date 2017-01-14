<?php

namespace Bamiz\UseCaseBundle\Execution;

use Bamiz\UseCaseBundle\Actor\ActorInterface;
use Bamiz\UseCaseBundle\Actor\ActorNotFoundException;
use Bamiz\UseCaseBundle\Actor\CompositeActorRecognizer;
use Bamiz\UseCaseBundle\Container\ContainerInterface;
use Bamiz\UseCaseBundle\Container\ItemNotFoundException;
use Bamiz\UseCaseBundle\Processor\Response\InputAwareResponseProcessor;
use Bamiz\UseCaseBundle\UseCase\RequestClassNotFoundException;
use Bamiz\UseCaseBundle\UseCase\UseCaseInterface;

class UseCaseExecutor
{
    /**
     * @var UseCaseConfiguration[]
     */
    private $useCaseConfigurations = [];

    /**
     * @var ContainerInterface
     */
    private $useCaseContainer;

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
     * @param ContainerInterface       $useCaseContainer
     * @param UseCaseContextResolver   $contextResolver
     * @param CompositeActorRecognizer $actorRecognizer
     * @param ActorInterface           $actor
     */
    public function __construct(
        ContainerInterface $useCaseContainer,
        UseCaseContextResolver $contextResolver,
        CompositeActorRecognizer $actorRecognizer,
        ActorInterface $actor = null
    )
    {
        $this->contextResolver = $contextResolver;
        $this->useCaseContainer = $useCaseContainer;
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
     * @param string|array $context
     *
     * @return mixed
     * @throws ActorCannotExecuteUseCaseException
     * @throws RequestClassNotFoundException
     * @throws UseCaseNotFoundException
     */
    public function execute($useCaseName, $input = null, $context = null)
    {
        $this->checkIfActorCanExecuteUseCase($useCaseName);

        $useCase = $this->getUseCase($useCaseName);
        $context = $this->getUseCaseContext($useCaseName, $context);

        $request = $this->createUseCaseRequest($useCaseName);
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

        return new UseCaseExecutor($this->useCaseContainer, $this->contextResolver, $this->actorRecognizer, $actor);
    }

    /**
     * @return ActorInterface|null
     */
    public function getActor()
    {
        return $this->actor;
    }

    /**
     * Assigns a Use Case Request class to given Use Case.
     *
     * @param string $useCaseName
     * @param string $requestClassName
     */
    public function assignRequestClass($useCaseName, $requestClassName)
    {
        $this->getUseCaseConfiguration($useCaseName)->setRequestClassName($requestClassName);
    }

    /**
     * Assigns a Use Case Input Processor to given Use Case.
     *
     * @param string $useCaseName
     * @param string $processorName
     * @param array  $options
     */
    public function assignInputProcessor($useCaseName, $processorName, array $options = array())
    {
        $this->getUseCaseConfiguration($useCaseName)->setInputProcessorName($processorName);
        $this->getUseCaseConfiguration($useCaseName)->setInputProcessorOptions($options);
    }

    /**
     * Assigns a Use Case Response Processor to given Use Case.
     *
     * @param string $useCaseName
     * @param string $processorName
     * @param array  $options
     */
    public function assignResponseProcessor($useCaseName, $processorName, array $options = array())
    {
        $this->getUseCaseConfiguration($useCaseName)->setResponseProcessorName($processorName);
        $this->getUseCaseConfiguration($useCaseName)->setResponseProcessorOptions($options);
    }

    /**
     * @param string       $useCaseName
     * @param string|array $context
     *
     * @return UseCaseContext
     */
    private function getUseCaseContext($useCaseName, $context)
    {
        return $this->contextResolver->resolveContext($context ?: $this->getUseCaseConfiguration($useCaseName));
    }

    /**
     * @param string $name
     *
     * @return UseCaseInterface
     * @throws UseCaseNotFoundException
     */
    private function getUseCase($name)
    {
        try {
            return $this->useCaseContainer->get($name);
        } catch (ItemNotFoundException $e) {
            throw new UseCaseNotFoundException(sprintf('Use case "%s" not found.', $name));
        }
    }

    /**
     * @param string $useCaseName
     *
     * @return object
     * @throws RequestClassNotFoundException
     */
    private function createUseCaseRequest($useCaseName)
    {
        $requestClass = $this->getUseCaseConfiguration($useCaseName)->getRequestClassName();
        if (class_exists($requestClass)) {
            return new $requestClass;
        } else {
            throw new RequestClassNotFoundException(sprintf('Class "%s" not found.', $requestClass));
        }
    }

    /**
     * @param string $name
     *
     * @return UseCaseConfiguration
     */
    private function getUseCaseConfiguration($name)
    {
        if (!array_key_exists($name, $this->useCaseConfigurations)) {
            $this->useCaseConfigurations[$name] = new UseCaseConfiguration();
        }

        return $this->useCaseConfigurations[$name];
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
