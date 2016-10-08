<?php

namespace Lamudi\UseCaseBundle\Execution;

/**
 * Provides parameters necessary for the execution of the use case using the Use Case Executor.
 *
 * @package Lamudi\UseCaseBundle\Execution
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
    public function __construct($data = [])
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
     * @param string $inputProcessorName
     *
     * @return UseCaseConfiguration
     */
    public function setInputProcessorName($inputProcessorName)
    {
        $this->inputProcessorName = $inputProcessorName;
        return $this;
    }

    /**
     * @return array
     */
    public function getInputProcessorOptions()
    {
        return $this->inputProcessorOptions;
    }

    /**
     * @param array $inputProcessorOptions
     *
     * @return UseCaseConfiguration
     */
    public function setInputProcessorOptions($inputProcessorOptions)
    {
        $this->inputProcessorOptions = $inputProcessorOptions;
        return $this;
    }

    /**
     * @return string
     */
    public function getResponseProcessorName()
    {
        return $this->responseProcessorName;
    }

    /**
     * @param string $responseProcessorName
     *
     * @return UseCaseConfiguration
     */
    public function setResponseProcessorName($responseProcessorName)
    {
        $this->responseProcessorName = $responseProcessorName;
        return $this;
    }

    /**
     * @return array
     */
    public function getResponseProcessorOptions()
    {
        return $this->responseProcessorOptions;
    }

    /**
     * @param array $responseProcessorOptions
     *
     * @return UseCaseConfiguration
     */
    public function setResponseProcessorOptions($responseProcessorOptions)
    {
        $this->responseProcessorOptions = $responseProcessorOptions;
        return $this;
    }

    /**
     * @param string $name
     * @param array  $options
     */
    public function addInputProcessor($name, array $options = [])
    {
        if ($this->inputProcessorName === 'composite') {
            $this->inputProcessorOptions[$name] = $options;
        } else {
            $config = [$this->inputProcessorName => $this->inputProcessorOptions, $name => $options];
            $this->setConfiguration('input', $config);
        }
    }

    /**
     * @param string $name
     * @param array  $options
     */
    public function addResponseProcessor($name, array $options = [])
    {
        if ($this->responseProcessorName === 'composite') {
            $this->responseProcessorOptions[$name] = $options;
        } else {
            $config = [$this->responseProcessorName => $this->responseProcessorOptions, $name => $options];
            $this->setConfiguration('response', $config);
        }
    }

    /**
     * @param string       $field
     * @param string|array $configuration
     *
     * @return string
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
}
