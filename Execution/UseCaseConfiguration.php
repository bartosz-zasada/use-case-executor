<?php

namespace Bamiz\UseCaseExecutor\Execution;

/**
 * Provides parameters necessary for the execution of the use case using the Use Case Executor.
 */
class UseCaseConfiguration
{
    /**
     * @var string
     */
    private $requestClassName;

    /**
     * @var string
     */
    private $inputProcessorName;

    /**
     * @var array
     */
    private $inputProcessorOptions = [];

    /**
     * @var string
     */
    private $responseProcessorName;

    /**
     * @var array
     */
    private $responseProcessorOptions = [];

    /**
     * Constructs a Use Case Configuration based on provided data. All parameters optional:
     * - input - string (Input Converter name) or array. In latter case, the Input Converter name must be provided under
     *   "type" key.
     * - response - string (Response Converter name) or array. In latter case, the Response Converter name
     *   must be provided under "type" key.
     *
     * @param array $data
     *
     * @throws InvalidConfigurationException
     */
    public function __construct(array $data = [])
    {
        if (isset($data['input'])) {
            $this->setConfiguration('input', $data['input']);
        }
        if (isset($data['response'])) {
            $this->setConfiguration('response', $data['response']);
        }
    }

    /**
     * @return string
     */
    public function getRequestClassName()
    {
        return $this->requestClassName;
    }

    /**
     * @param string $requestClassName
     *
     * @return UseCaseConfiguration
     */
    public function setRequestClassName($requestClassName)
    {
        $this->requestClassName = $requestClassName;
        return $this;
    }

    /**
     * @return string
     */
    public function getInputProcessorName()
    {
        return $this->inputProcessorName;
    }

    /**
     * @return array
     */
    public function getInputProcessorOptions()
    {
        return $this->inputProcessorOptions;
    }

    /**
     * @return string
     */
    public function getResponseProcessorName()
    {
        return $this->responseProcessorName;
    }

    /**
     * @return array
     */
    public function getResponseProcessorOptions()
    {
        return $this->responseProcessorOptions;
    }

    /**
     * Merges given use case configuration into this one, creating a new configuration object as a result.
     * The new configuration contains Input and Response Processors from both configurations.
     * In case the same Processor appears in both configurations, options from the new configuration are used.
     *
     * @param UseCaseConfiguration $configurationToMerge
     *
     * @return $this
     */
    public function merge(UseCaseConfiguration $configurationToMerge)
    {
        $thisArray = $this->toArray();
        $toMergeArray = $configurationToMerge->toArray();
        $newArray = $thisArray;

        foreach (['input', 'response'] as $item) {
            if (isset($newArray[$item])) {
                unset($newArray[$item]);
            }

            $processorArray = array_merge(
                empty($thisArray[$item]) ? [] : $this->normalizeProcessorConfiguration($thisArray[$item]),
                empty($toMergeArray[$item]) ? [] : $this->normalizeProcessorConfiguration($toMergeArray[$item])
            );

            if (!empty($processorArray)) {
                $newArray[$item] = $this->simplifyProcessorConfiguration($processorArray);
            }
        }

        return new static($newArray);
    }

    /**
     * Overrides the Input and/or Response Processors with the ones from given configuration.
     *
     * @param UseCaseConfiguration $newConfiguration
     *
     * @return UseCaseConfiguration
     */
    public function override(UseCaseConfiguration $newConfiguration)
    {
        return new static(array_merge($this->toArray(), $newConfiguration->toArray()));
    }

    /**
     * @param string       $field
     * @param string|array $configuration
     *
     * @throws InvalidConfigurationException
     */
    private function setConfiguration($field, $configuration)
    {
        $nameField = $field . 'ProcessorName';
        $optionsField = $field . 'ProcessorOptions';

        if (is_string($configuration)) {
            $this->$nameField = $configuration;
        } else {
            $this->$nameField = 'composite';
            $this->$optionsField = $configuration;
        }
    }

    /**
     * @return array
     */
    protected function toArray()
    {
        $array = [];
        if (!empty($this->inputProcessorName)) {
            $array['input'] = $this->inputProcessorOptions ?: $this->inputProcessorName;
        }
        if (!empty($this->responseProcessorName)) {
            $array['response'] = $this->responseProcessorOptions ?: $this->responseProcessorName;
        }

        return $array;
    }

    /**
     * @param string|array $processorConfiguration
     *
     * @return array
     */
    protected function normalizeProcessorConfiguration($processorConfiguration)
    {
        if (is_string($processorConfiguration)) {
            return [$processorConfiguration => []];
        } else {
            return $processorConfiguration;
        }
    }

    /**
     * @param array $processorConfiguration
     *
     * @return string|array
     */
    protected function simplifyProcessorConfiguration(array $processorConfiguration)
    {
        if (count($processorConfiguration) === 1 && array_values($processorConfiguration)[0] === []) {
            return array_keys($processorConfiguration)[0];
        } else {
            return $processorConfiguration;
        }
    }
}
