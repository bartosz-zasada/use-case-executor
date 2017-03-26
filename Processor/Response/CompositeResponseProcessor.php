<?php

namespace Bamiz\UseCaseExecutor\Processor\Response;

use Bamiz\UseCaseExecutor\Container\ContainerInterface;
use Bamiz\UseCaseExecutor\Processor\Exception\EmptyCompositeProcessorException;

class CompositeResponseProcessor implements ResponseProcessorInterface
{
    /**
     * @var ContainerInterface
     */
    private $responseProcessorContainer;

    /**
     * @param ContainerInterface $responseProcessorContainer
     */
    public function __construct(ContainerInterface $responseProcessorContainer)
    {
        $this->responseProcessorContainer = $responseProcessorContainer;
    }

    /**
     * Executes a chain of Response Processors, passing the result of the previous processing
     * as a Response to the next processor.
     *
     * @param object $response The Use Case Response object.
     * @param array  $options  An associative array where keys are processor names and values are arrays of options.
     *
     * @return mixed
     */
    public function processResponse($response, array $options = [])
    {
        $this->throwIfNoProcessorsAdded($options);

        foreach ($options as $responseProcessorName => $responseProcessorOptions) {
            if (is_int($responseProcessorName) && is_string($responseProcessorOptions)) {
                $responseProcessorName = $responseProcessorOptions;
                $responseProcessorOptions = [];
            }

            $responseProcessor = $this->responseProcessorContainer->get($responseProcessorName);
            $response = $responseProcessor->processResponse($response, $responseProcessorOptions);
        }

        return $response;
    }

    /**
     * Uses the first Response Processor to handle the Exception thrown by the Use Case,
     * then processes the Output using remaining Processors.
     *
     * @param \Exception $exception
     * @param array      $options
     *
     * @return mixed
     * @throws \Exception
     */
    public function handleException(\Exception $exception, array $options = [])
    {
        $this->throwIfNoProcessorsAdded($options);

        $output = null;
        foreach ($options as $responseProcessorName => $responseProcessorOptions) {
            if (is_int($responseProcessorName) && is_string($responseProcessorOptions)) {
                $responseProcessorName = $responseProcessorOptions;
                $responseProcessorOptions = [];
            }

            $responseProcessor = $this->responseProcessorContainer->get($responseProcessorName);
            if ($exception !== null) {
                try {
                    $output = $responseProcessor->handleException($exception, $responseProcessorOptions);
                    $exception = null;
                } catch (\Exception $e) {
                    $exception = $e;
                }
            } else {
                $output = $responseProcessor->processResponse($output, $responseProcessorOptions);
            }
        }

        if ($exception === null) {
            return $output;
        } else {
            throw $exception;
        }
    }

    /**
     * @param array $options
     *
     * @throws EmptyCompositeProcessorException
     */
    private function throwIfNoProcessorsAdded(array $options)
    {
        if (count($options) === 0) {
            throw new EmptyCompositeProcessorException('No Response Processors have been added.');
        }
    }
}
