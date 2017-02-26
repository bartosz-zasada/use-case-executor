<?php

namespace Bamiz\UseCaseBundle\Annotation;

use Bamiz\UseCaseBundle\Execution\UseCaseConfiguration;

/**
 * @Annotation
 * @Target({"CLASS"})
 */
class UseCase
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var UseCaseConfiguration
     */
    private $configuration;

    /**
     * @param array $data
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(array $data)
    {
        if (isset($data['value'])) {
            $this->name = $data['value'];
        }

        $validOptions = ['value', 'input', 'response'];
        $invalidOptions = array_diff(array_keys($data), $validOptions);
        if (count($invalidOptions) > 0) {
            throw new \InvalidArgumentException(sprintf(
                'Unsupported options on UseCase annotation: %s', implode(', ', $invalidOptions)
            ));
        }

        $this->configuration = new UseCaseConfiguration($data);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return UseCaseConfiguration
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }
}
