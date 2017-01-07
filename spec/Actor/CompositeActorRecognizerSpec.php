<?php

namespace spec\Bamiz\UseCaseBundle\Actor;

use Bamiz\UseCaseBundle\Actor\ActorInterface;
use Bamiz\UseCaseBundle\Actor\ActorRecognizerInterface;
use Bamiz\UseCaseBundle\Actor\CompositeActor;
use Bamiz\UseCaseBundle\Actor\OmnipotentActor;
use Bamiz\UseCaseBundle\Actor\UnableActor;
use PhpSpec\ObjectBehavior;
use Bamiz\UseCaseBundle\Actor\CompositeActorRecognizer;

class CompositeActorRecognizerSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(CompositeActorRecognizer::class);
    }

    public function it_is_an_actor_recognizer()
    {
        $this->shouldHaveType(ActorRecognizerInterface::class);
    }

    public function it_returns_the_omnipotent_actor_if_no_recognizers_have_been_added()
    {
        $this->recognizeActor()->shouldHaveType(OmnipotentActor::class);
    }

    public function it_returns_the_unable_actor_if_no_actors_have_been_recognized(
        ActorRecognizerInterface $recognizer1,
        ActorRecognizerInterface $recognizer2
    )
    {
        $recognizer1->recognizeActor()->willReturn(new UnableActor());
        $recognizer2->recognizeActor()->willReturn(new UnableActor());

        $this->addActorRecognizer($recognizer1);
        $this->addActorRecognizer($recognizer2);

        $this->recognizeActor()->shouldHaveType(UnableActor::class);
    }

    public function it_returns_the_recognized_actor_instance_if_only_one_is_recognized(
        ActorRecognizerInterface $recognizer1,
        ActorRecognizerInterface $recognizer2,
        ActorRecognizerInterface $recognizer3
    )
    {
        $recognizer1->recognizeActor()->willReturn(new UnableActor());
        $recognizer2->recognizeActor()->willReturn(new Jedi());
        $recognizer3->recognizeActor()->willReturn(new UnableActor());

        $this->addActorRecognizer($recognizer1);
        $this->addActorRecognizer($recognizer2);
        $this->addActorRecognizer($recognizer3);

        $this->recognizeActor()->shouldHaveType(Jedi::class);
    }

    public function it_returns_the_composite_actor_if_many_actors_have_been_recognized(
        ActorRecognizerInterface $recognizer1,
        ActorRecognizerInterface $recognizer2,
        ActorRecognizerInterface $recognizer3
    )
    {
        $recognizer1->recognizeActor()->willReturn(new CookieMonster());
        $recognizer2->recognizeActor()->willReturn(new Jedi());
        $recognizer3->recognizeActor()->willReturn(new UnableActor());

        $this->addActorRecognizer($recognizer1);
        $this->addActorRecognizer($recognizer2);
        $this->addActorRecognizer($recognizer3);

        $this->recognizeActor()->shouldHaveType(CompositeActor::class);
        $this->recognizeActor()->canExecute('use the force')->shouldBe(true);
        $this->recognizeActor()->canExecute('eat cookies')->shouldBe(true);
        $this->recognizeActor()->canExecute('count to ten')->shouldBe(false);
    }
}

class Jedi implements ActorInterface
{
    /**
     * @param string $useCaseName
     *
     * @return bool
     */
    public function canExecute($useCaseName)
    {
        return $useCaseName === 'use the force';
    }
}

class CookieMonster implements ActorInterface
{
    /**
     * @param string $useCaseName
     *
     * @return bool
     */
    public function canExecute($useCaseName)
    {
        return $useCaseName === 'eat cookies';
    }
}
