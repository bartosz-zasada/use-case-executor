<?php

namespace Bamiz\UseCaseBundle\Execution;

use Symfony\Component\OptionsResolver\OptionsResolver;

class UseCaseSpecificConfiguration extends UseCaseConfiguration
{
    /**
     * @var string
     */
    private $useCaseName;

    /**
     * @var string
     */
    private $useCaseRequestClass;

    /**
     * @param array $data
     *
     * @throws InvalidConfigurationException
     */
    public function __construct(array $data = [])
    {
        parent::__construct($data);

        try {
            $resolver = new OptionsResolver();
            $resolver->setDefaults(['input' => '', 'response' => '']);
            $resolver->setRequired(['use_case', 'request_class']);
            $options = $resolver->resolve($data);
        } catch (\InvalidArgumentException $e) {
            throw new InvalidConfigurationException($e->getMessage(), $e->getCode(), $e);
        }

        $this->useCaseName = $options['use_case'];
        $this->useCaseRequestClass = $options['request_class'];
    }

    /**
     * @return string
     */
    public function getUseCaseName()
    {
        return $this->useCaseName;
    }

    /**
     * @return string
     */
    public function getUseCaseRequestClass()
    {
        return $this->useCaseRequestClass;
    }

    /**
     * @return array
     */
    protected function toArray()
    {
        return array_merge(
            ['use_case' => $this->useCaseName, 'request_class' => $this->useCaseRequestClass],
            parent::toArray()
        );
    }


}