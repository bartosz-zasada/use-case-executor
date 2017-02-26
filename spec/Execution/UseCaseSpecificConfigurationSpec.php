<?php

namespace spec\Bamiz\UseCaseBundle\Execution;

use Bamiz\UseCaseBundle\Execution\InvalidConfigurationException;
use PhpSpec\ObjectBehavior;
use Bamiz\UseCaseBundle\Execution\UseCaseSpecificConfiguration;

class UseCaseSpecificConfigurationSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(UseCaseSpecificConfiguration::class);
    }

    public function it_is_initialized_with_use_case_name_and_request_class()
    {
        $this->beConstructedWith([
            'use_case'      => 'conquer the world',
            'request_class' => 'Foo\Bar\Baz'
        ]);
        $this->getUseCaseName()->shouldBe('conquer the world');
        $this->getUseCaseRequestClass()->shouldBe('Foo\Bar\Baz');
    }

    public function it_throws_an_exception_if_use_case_or_request_class_are_missing()
    {
        $this->beConstructedWith(['request_class' => 'Foo\Bar\Baz']);
        $this->shouldThrow(InvalidConfigurationException::class)->duringInstantiation();

        $this->beConstructedWith(['use_case' => 'make everyone happy']);
        $this->shouldThrow(InvalidConfigurationException::class)->duringInstantiation();
    }
}
