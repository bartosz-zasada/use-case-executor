<?php

namespace spec\Bamiz\UseCaseExecutor\Actor;

use Bamiz\UseCaseExecutor\Actor\ActorInterface;
use PhpSpec\ObjectBehavior;
use Bamiz\UseCaseExecutor\Actor\UnableActor;

class UnableActorSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(UnableActor::class);
    }

    public function it_is_an_actor()
    {
        $this->shouldHaveType(ActorInterface::class);
    }

    public function it_cannot_execute_any_use_case()
    {
        $this->canExecute('add two numbers')->shouldBe(false);
        $this->canExecute('say hello')->shouldBe(false);
        $this->canExecute('do anything at all')->shouldBe(false);
        $this->canExecute(str_shuffle('anything, really'))->shouldBe(false);
    }
}
