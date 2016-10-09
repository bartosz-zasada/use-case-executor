<?php

namespace Lamudi\UseCaseBundle\Processor\Exception;

class UnsupportedInputException extends \Exception
{
    /**
     * @param string $inputProcessorName
     * @param string $supportedTypes
     * @param mixed  $actualInput
     */
    public function __construct($inputProcessorName, $supportedTypes, $actualInput)
    {
        if (is_object($actualInput)) {
            $type = get_class($actualInput);
        } else {
            $type = gettype($actualInput);
        }

        $message = sprintf(
            '%s Input Processor supports only instances of %s, instance of %s given.',
            $inputProcessorName, $supportedTypes, $type
        );

        parent::__construct($message);
    }
}