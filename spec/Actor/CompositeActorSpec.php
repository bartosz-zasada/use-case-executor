<?php

namespace spec\Bamiz\UseCaseBundle\Actor;

use Bamiz\UseCaseBundle\Actor\ActorInterface;
use PhpSpec\ObjectBehavior;
use Bamiz\UseCaseBundle\Actor\CompositeActor;

class CompositeActorSpec extends ObjectBehavior
{
    public function let(ActorInterface $actor1, ActorInterface $actor2)
    {
        $actor1->getName()->willReturn('superman');
        $actor2->getName()->willReturn('batman');

        $actor1->canExecute('drive a car')->willReturn(true);
        $actor2->canExecute('drive a car')->willReturn(false);
        $actor1->canExecute('fly a plane')->willReturn(false);
        $actor2->canExecute('fly a plane')->willReturn(true);
        $actor1->canExecute('sail a boat')->willReturn(false);
        $actor2->canExecute('sail a boat')->willReturn(false);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(CompositeActor::class);
    }

    public function it_is_an_actor()
    {
        $this->shouldHaveType(ActorInterface::class);
    }

    public function it_can_do_everything_that_added_actors_can(ActorInterface $actor1, ActorInterface $actor2)
    {
        $this->addActor($actor1);
        $this->addActor($actor2);

        $this->canExecute('drive a car')->shouldBe(true);
        $this->canExecute('fly a plane')->shouldBe(true);
        $this->canExecute('sail a boat')->shouldBe(false);
    }

    public function it_adds_actors_through_the_constructor(ActorInterface $actor1, ActorInterface $actor2)
    {
        $this->beConstructedWith([$actor1, $actor2]);

        $this->canExecute('drive a car')->shouldBe(true);
        $this->canExecute('fly a plane')->shouldBe(true);
        $this->canExecute('sail a boat')->shouldBe(false);
    }

    public function it_returns_contained_actor_by_name(ActorInterface $actor1, ActorInterface $actor2)
    {
        $this->addActor($actor1);
        $this->addActor($actor2);

        $this->getActorByName('superman')->shouldBe($actor1);
        $this->getActorByName('batman')->shouldBe($actor2);
        $this->getActorByName('spiderman')->shouldBe(null);
    }

    public function it_checks_if_actor_by_given_name_has_been_added(ActorInterface $actor1, ActorInterface $actor2)
    {
        $this->addActor($actor1);
        $this->addActor($actor2);

        $this->hasActor('superman')->shouldBe(true);
        $this->hasActor('batman')->shouldBe(true);
        $this->hasActor('spiderman')->shouldBe(false);
    }
}
