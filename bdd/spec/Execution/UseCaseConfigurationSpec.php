<?php

namespace spec\Lamudi\UseCaseBundle\Execution;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

/**
 * Class UseCaseConfigurationSpec
 *
 * @mixin \Lamudi\UseCaseBundle\Execution\UseCaseConfiguration
 */
class UseCaseConfigurationSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('Lamudi\UseCaseBundle\Execution\UseCaseConfiguration');
    }

    public function it_creates_processor_configuration_based_on_array()
    {
        $this->beConstructedWith(['input' => 'http', 'response' => 'cli']);
        $this->getInputProcessorName()->shouldBe('http');
        $this->getResponseProcessorName()->shouldBe('cli');
    }

    public function it_uses_composite_processors_if_input_and_response_options_are_arrays()
    {
        $this->beConstructedWith([
            'value'    => 'uc',
            'input'    => [
                'form' => [
                    'name'   => 'search_form',
                    'method' => 'DELETE'
                ]
            ],
            'response' => [
                'twig'    => [
                    'template' => 'base.html.twig',
                    'form'     => 'DumberForm',
                    'css'      => 'none'
                ],
                'cookies' => [
                    'some' => 'cookie'
                ]
            ]
        ]);

        $this->getInputProcessorName()->shouldBe('composite');
        $this->getInputProcessorOptions()->shouldBe([
            'form' => [
                'name'   => 'search_form',
                'method' => 'DELETE'
            ]
        ]);
        $this->getResponseProcessorName()->shouldBe('composite');
        $this->getResponseProcessorOptions()->shouldBe([
            'twig'    => [
                'template' => 'base.html.twig',
                'form'     => 'DumberForm',
                'css'      => 'none'
            ],
            'cookies' => [
                'some' => 'cookie'
            ]
        ]);
    }

    public function it_changes_processors_to_composite_if_another_processor_is_added()
    {
        $this->beConstructedWith(['input' => 'form', 'response' => 'json']);
        $this->getInputProcessorName()->shouldBe('form');
        $this->getResponseProcessorName()->shouldBe('json');

        $this->addInputProcessor('http');
        $this->addResponseProcessor('twig', ['template' => 'index.html.twig']);
        $this->getInputProcessorName()->shouldBe('composite');
        $this->getInputProcessorOptions()->shouldBe(['form' => [], 'http' => []]);
        $this->getResponseProcessorName()->shouldBe('composite');
        $this->getResponseProcessorOptions()->shouldBe(['json' => [], 'twig' => ['template' => 'index.html.twig']]);

        $this->addInputProcessor('cli', ['format' => 'ansi']);
        $this->addResponseProcessor('dump');
        $this->getInputProcessorName()->shouldBe('composite');
        $this->getInputProcessorOptions()->shouldBe(['form' => [], 'http' => [], 'cli' => ['format' => 'ansi']]);
        $this->getResponseProcessorName()->shouldBe('composite');
        $this->getResponseProcessorOptions()->shouldBe(['json' => [], 'twig' => ['template' => 'index.html.twig'], 'dump' => []]);
    }
}
