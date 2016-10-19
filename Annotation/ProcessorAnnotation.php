<?php

namespace Bamiz\UseCaseBundle\Annotation;

abstract class ProcessorAnnotation
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var array
     */
    protected $options;

    /**
     * @var string
     */
    protected $type;

    /**
     * @param array $data
     */
    public function __construct(array $data)
    {
        if (!isset($data['value'])) {
            throw new \InvalidArgumentException(sprintf('%s Processor name must be specified.', $this->type));
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