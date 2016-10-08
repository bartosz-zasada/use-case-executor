<?php

namespace Lamudi\UseCaseBundle\Annotation;

/**
 * @Annotation
 * @Target({"CLASS"})
 */
class InputProcessor
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var array
     */
    private $options;

    /**
     * @param array $data
     */
    public function __construct(array $data)
    {
        if (!isset($data['value'])) {
            throw new \InvalidArgumentException('Input Processor name must be specified.');
        }

        $this->name = $data['value'];
        unset($data['value']);
        $this->options = $data;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }
}