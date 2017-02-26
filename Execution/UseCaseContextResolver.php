<?php

namespace Bamiz\UseCaseBundle\Execution;

use Bamiz\UseCaseBundle\Container\ContainerInterface;
use Bamiz\UseCaseBundle\Container\ItemNotFoundException;
use Bamiz\UseCaseBundle\Processor\Input\InputProcessorInterface;
use Bamiz\UseCaseBundle\Processor\Response\ResponseProcessorInterface;
use Bamiz\UseCaseBundle\UseCase\RequestClassNotFoundException;
use Bamiz\UseCaseBundle\UseCase\UseCaseInterface;

/**
 * Creates the context for the Use Case execution.
 */
class UseCaseContextResolver
{
    const DEFAULT_INPUT_PROCESSOR = 'array';
    const DEFAULT_RESPONSE_PROCESSOR = 'identity';

    /**
     * @var string
     */
    private $defaultContextName = 'default';

    /**
     * @var ContainerInterface
     */
    private $useCaseContainer;

    /**
     * @var ContainerInterface
     */
    private $inputProcessorContainer;

    /**
     * @var ContainerInterface
     */
    private $responseProcessorContainer;

    /**
     * @var UseCaseConfiguration[]
     */
    private $configurations = [];

    /**
     * @var UseCaseConfiguration[]
     */
    private $useCaseSpecificConfigurations = [];

    /**
     * @param ContainerInterface $useCaseContainer
     * @param ContainerInterface $inputProcessorContainer
     * @param ContainerInterface $responseProcessorContainer
     */
    public function __construct(
        ContainerInterface $useCaseContainer,
        ContainerInterface $inputProcessorContainer,
        ContainerInterface $responseProcessorContainer
    )
    {
        $this->useCaseContainer = $useCaseContainer;
        $this->inputProcessorContainer = $inputProcessorContainer;
        $this->responseProcessorContainer = $responseProcessorContainer;

        $this->configurations[$this->defaultContextName] = new UseCaseConfiguration([
            'input' => self::DEFAULT_INPUT_PROCESSOR, 'response' => self::DEFAULT_RESPONSE_PROCESSOR
        ]);
    }

    /**
     * Creates a Use Case context based on the specified context configuration. The following configuration formats
     * are supported:
     * - string - resolved to a named Context created using addContextDefinition() method. The default
     *   Processors' options will be overriden by those belonging to the Context.
     * - array or UseCaseConfiguration object - specify the Input Processor, the Response Processor and their options.
     *   An array should come in the same format as the argument to UseCaseConfiguration constructor. In this case,
     *   the Input and Response processor options will be merged with the respective default options. The processors
     *   themselves will fall back to default if not specified.
     *
     * @param string                            $useCaseName
     * @param string|array|UseCaseConfiguration $customConfiguration
     *
     * @return UseCaseContext
     * @throws InputProcessorNotFoundException
     * @throws InvalidConfigurationException
     * @throws ResponseProcessorNotFoundException
     * @throws UseCaseNotFoundException
     */
    public function resolveContext($useCaseName, $customConfiguration = [])
    {
        $defaultConfig = $this->getDefaultConfiguration();
        $config = $this->resolveConfiguration($useCaseName, $customConfiguration);

        $useCaseRequest = $config->getUseCaseRequestClass();
        $inputProcessorName = $config->getInputProcessorName() ?: $defaultConfig->getInputProcessorName();
        $inputProcessorOptions = $config->getInputProcessorOptions() ?: $defaultConfig->getInputProcessorOptions();
        $responseProcessorName = $config->getResponseProcessorName() ?: $defaultConfig->getResponseProcessorName();
        $responseProcessorOptions = $config->getResponseProcessorOptions() ?: $defaultConfig->getResponseProcessorOptions();

        $context = new UseCaseContext();
        $context->setUseCase($this->getUseCase($useCaseName));
        $context->setUseCaseRequest($this->createUseCaseRequest($useCaseRequest));
        $context->setInputProcessor($this->getInputProcessor($inputProcessorName));
        $context->setResponseProcessor($this->getResponseProcessor($responseProcessorName));
        $context->setInputProcessorOptions($inputProcessorOptions);
        $context->setResponseProcessorOptions($responseProcessorOptions);

        return $context;
    }

    /**
     * @param array|UseCaseSpecificConfiguration $configuration
     *
     * @throws InvalidConfigurationException
     */
    public function addUseCaseConfiguration($configuration)
    {
        if (is_array($configuration)) {
            $configuration = new UseCaseSpecificConfiguration($configuration);
        }
        $this->useCaseSpecificConfigurations[$configuration->getUseCaseName()] = $configuration;
    }

    /**
     * Saves a named context configuration. Both Input Processor and Response Processor configurations
     * are optional and will fall back to default if not specified.
     *
     * @param string $contextName
     * @param array  $configuration
     *
     * @throws InvalidConfigurationException
     */
    public function addContextDefinition($contextName, array $configuration = [])
    {
        $this->configurations[$contextName] = new UseCaseConfiguration($configuration);
    }

    /**
     * Determines which predefined context configuration will be used as a fallback for a lack of more specific settings.
     *
     * @param string $contextName
     */
    public function setDefaultContextName($contextName)
    {
        $this->defaultContextName = $contextName;
    }

    /**
     * Returns the default Use Case Configuration.
     *
     * @return UseCaseConfiguration
     * @throws InvalidConfigurationException
     */
    public function getDefaultConfiguration()
    {
        return $this->getConfigurationByName($this->defaultContextName);
    }

    /**
     * @param string $useCaseName
     *
     * @return UseCaseInterface
     * @throws UseCaseNotFoundException
     */
    private function getUseCase($useCaseName)
    {
        try {
            /** @noinspection PhpIncompatibleReturnTypeInspection until php introduces generics */
            return $this->useCaseContainer->get($useCaseName);
        } catch (ItemNotFoundException $e) {
            throw new UseCaseNotFoundException(sprintf('Use case "%s" not found.', $useCaseName));
        }
    }

    /**
     * @param string $inputProcessorName
     *
     * @return InputProcessorInterface
     * @throws InputProcessorNotFoundException
     */
    private function getInputProcessor($inputProcessorName)
    {
        try {
            /** @noinspection PhpIncompatibleReturnTypeInspection until php introduces generics */
            return $this->inputProcessorContainer->get($inputProcessorName);
        } catch (ItemNotFoundException $e) {
            throw new InputProcessorNotFoundException(sprintf('Input processor "%s" not found.', $inputProcessorName));
        }
    }

    /**
     * @param string $responseProcessorName
     *
     * @return ResponseProcessorInterface
     * @throws ResponseProcessorNotFoundException
     */
    private function getResponseProcessor($responseProcessorName)
    {
        try {
            /** @noinspection PhpIncompatibleReturnTypeInspection until php introduces generics */
            return $this->responseProcessorContainer->get($responseProcessorName);
        } catch (ItemNotFoundException $e) {
            throw new ResponseProcessorNotFoundException(sprintf('Response processor "%s" not found.', $responseProcessorName));
        }
    }

    /**
     * @param string                            $useCaseName
     * @param string|array|UseCaseConfiguration $customConfiguration
     *
     * @return UseCaseSpecificConfiguration
     * @throws InvalidConfigurationException
     * @throws UseCaseNotFoundException
     */
    private function resolveConfiguration($useCaseName, $customConfiguration)
    {
        if (is_string($customConfiguration)) {
            $configuration = $this->getConfigurationByName($customConfiguration);
        } elseif (is_array($customConfiguration)) {
            $configuration = new UseCaseConfiguration($customConfiguration);
        } elseif ($customConfiguration instanceof UseCaseConfiguration) {
            $configuration = clone $customConfiguration;
        } else {
            throw new InvalidConfigurationException(
                'A context configuration must be a string, an array, or an instance of UseCaseConfiguration.'
            );
        }

        if (isset($this->useCaseSpecificConfigurations[$useCaseName])) {
            return $this->useCaseSpecificConfigurations[$useCaseName]->merge($configuration);
        } else {
            throw new UseCaseNotFoundException(sprintf('Use case "%s" has not been registered.', $useCaseName));
        }
    }

    /**
     * @param string $contextConfiguration
     *
     * @return UseCaseConfiguration
     * @throws InvalidConfigurationException
     */
    private function getConfigurationByName($contextConfiguration)
    {
        if (array_key_exists($contextConfiguration, $this->configurations)) {
            return $this->configurations[$contextConfiguration];
        } else {
            throw new InvalidConfigurationException(
                sprintf('Context "%s" has not been defined.', $contextConfiguration)
            );
        }
    }

    /**
     * @param string $useCaseRequestClass
     *
     * @return object
     * @throws RequestClassNotFoundException
     */
    private function createUseCaseRequest($useCaseRequestClass)
    {
        if (class_exists($useCaseRequestClass)) {
            return new $useCaseRequestClass;
        } else {
            throw new RequestClassNotFoundException(sprintf('Class "%s" not found.', $useCaseRequestClass));
        }
    }
}
