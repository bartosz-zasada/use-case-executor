<?php

namespace spec\Bamiz\UseCaseBundle\Execution;

use Bamiz\UseCaseBundle\Execution\UseCaseConfiguration;
use PhpSpec\ObjectBehavior;

/**
 * Class UseCaseConfigurationSpec
 *
 * @mixin \Bamiz\UseCaseBundle\Execution\UseCaseConfiguration
 */
class UseCaseConfigurationSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(UseCaseConfiguration::class);
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

    public function it_merges_another_configuration_into_itself()
    {
        $mergedConfiguration = new UseCaseConfiguration(['response' => 'twig']);
        $this->beConstructedWith(['input' => 'http']);
        $afterMerge = $this->merge($mergedConfiguration);

        $afterMerge->shouldHaveType(UseCaseConfiguration::class);
        $afterMerge->getInputProcessorName()->shouldBe('http');
        $afterMerge->getResponseProcessorName()->shouldBe('twig');
    }

    public function it_merges_multiple_processors_with_new_ones_taking_precedence()
    {
        $myConfig = [
            'input' => [
                'http' => ['headers' => ['foo', 'bar']],
                'form' => ['name' => 'contact_form']
            ],
            'response' => [
                'twig'  => ['template' => 'hello.html.twig'],
                'track' => ['cookie' => 'asd', 'type' => 'gtm']
            ]
        ];
        $this->beConstructedWith($myConfig);

        $mergedConfig = [
            'input' => [
                'http' => ['restrict' => 'GPC']
            ],
            'response' => [
                'track' => ['cookie' => 'qwe']
            ]
        ];
        $mergedConfiguration = new UseCaseConfiguration($mergedConfig);

        $afterMerge = $this->merge($mergedConfiguration);
        $afterMerge->getInputProcessorName()->shouldBe('composite');
        $afterMerge->getInputProcessorOptions()->shouldBe([
            'http' => ['restrict' => 'GPC'],
            'form' => ['name' => 'contact_form']
        ]);
        $afterMerge->getResponseProcessorName()->shouldBe('composite');
        $afterMerge->getResponseProcessorOptions()->shouldBe([
            'twig' => ['template' => 'hello.html.twig'],
            'track' => ['cookie' => 'qwe']
        ]);
    }

    public function it_overrides_its_configuration_with_new_processors()
    {
        $this->beConstructedWith(['input' => 'not important', 'response' => 'likewise']);
        $newConfiguration = new UseCaseConfiguration(['response' => 'devnull']);
        $afterOverride = $this->override($newConfiguration);

        $afterOverride->getInputProcessorName()->shouldBe('not important');
        $afterOverride->getResponseProcessorName()->shouldBe('devnull');

        $newConfiguration = new UseCaseConfiguration(['input' => ['json' => [], 'form' => ['name' => 'foo']]]);
        $afterOverride = $this->override($newConfiguration);
        $afterOverride->getInputProcessorName()->shouldBe('composite');
        $afterOverride->getInputProcessorOptions()->shouldBe([
            'json' => [],
            'form' => ['name' => 'foo']
        ]);
        $afterOverride->getResponseProcessorName()->shouldBe('likewise');
    }
}
