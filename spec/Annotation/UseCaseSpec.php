<?php

namespace spec\Bamiz\UseCaseBundle\Annotation;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

/**
 * @mixin \Bamiz\UseCaseBundle\Annotation\UseCase
 */
class UseCaseSpec extends ObjectBehavior
{
    public function let()
    {
        $this->beConstructedWith(['value' => 'use_case']);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Bamiz\UseCaseBundle\Annotation\UseCase');
    }

    public function it_creates_use_case_configuration()
    {
        $this->beConstructedWith([
            'value' => 'uc',
            'input' => 'form',
            'response' => 'json'
        ]);

        $this->getName()->shouldBe('uc');
        $this->getConfiguration()->getInputProcessorName()->shouldBe('form');
        $this->getConfiguration()->getInputProcessorOptions()->shouldBe([]);
        $this->getConfiguration()->getResponseProcessorName()->shouldBe('json');
        $this->getConfiguration()->getResponseProcessorOptions()->shouldBe([]);
    }

    public function it_throws_an_exception_if_an_unsupported_option_was_used()
    {
        $this->beConstructedWith([
            'value' => 'use_case',
            'input' => 'http',
            'response' => 'twig',
            'output' => 'this is deprecated',
            'foo' => 'this is just silly'
        ]);
        $this->shouldThrow(new \InvalidArgumentException('Unsupported options on UseCase annotation: output, foo'))
            ->duringInstantiation();
    }
}
