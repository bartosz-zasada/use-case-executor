<?php

namespace spec\Bamiz\UseCaseExecutor\Actor;

use Bamiz\UseCaseExecutor\Actor\ActorInterface;
use PhpSpec\ObjectBehavior;
use Bamiz\UseCaseExecutor\Actor\OmnipotentActor;

class OmnipotentActorSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(OmnipotentActor::class);
    }

    public function it_is_an_actor()
    {
        $this->shouldHaveType(ActorInterface::class);
    }

    public function it_can_execute_any_use_case()
    {
        $this->canExecute('cure_cancer')->shouldBe(true);
        $this->canExecute('turn_lead_into_gold')->shouldBe(true);
        $this->canExecute('provide_world_peace')->shouldBe(true);
        $this->canExecute(str_shuffle('something something'))->shouldBe(true);
    }
}
