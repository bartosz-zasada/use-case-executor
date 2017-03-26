<?php

namespace spec\Bamiz\UseCaseExecutor\Actor;

use Bamiz\UseCaseExecutor\Actor\ActorInterface;
use Bamiz\UseCaseExecutor\Actor\UnrecognizedActorException;
use Bamiz\UseCaseExecutor\Actor\ActorRecognizerInterface;
use Bamiz\UseCaseExecutor\Actor\CompositeActor;
use Bamiz\UseCaseExecutor\Actor\OmnipotentActor;
use Bamiz\UseCaseExecutor\Actor\UnableActor;
use PhpSpec\ObjectBehavior;
use Bamiz\UseCaseExecutor\Actor\CompositeActorRecognizer;

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
        $useCaseRequest = new \stdClass();
        $this->recognizeActor($useCaseRequest)->shouldHaveType(OmnipotentActor::class);
    }

    public function it_returns_the_unable_actor_if_no_actors_have_been_recognized(
        ActorRecognizerInterface $recognizer1,
        ActorRecognizerInterface $recognizer2
    )
    {
        $useCaseRequest = new \stdClass();
        $recognizer1->recognizeActor($useCaseRequest)->willReturn(new UnableActor());
        $recognizer2->recognizeActor($useCaseRequest)->willReturn(new UnableActor());

        $this->addActorRecognizer($recognizer1);
        $this->addActorRecognizer($recognizer2);

        $this->recognizeActor($useCaseRequest)->shouldHaveType(UnableActor::class);
    }

    public function it_returns_the_recognized_actor_instance_if_only_one_is_recognized(
        ActorRecognizerInterface $recognizer1,
        ActorRecognizerInterface $recognizer2,
        ActorRecognizerInterface $recognizer3
    )
    {
        $useCaseRequest = new \stdClass();
        $recognizer1->recognizeActor($useCaseRequest)->willReturn(new UnableActor());
        $recognizer2->recognizeActor($useCaseRequest)->willReturn(new Jedi());
        $recognizer3->recognizeActor($useCaseRequest)->willReturn(new UnableActor());

        $this->addActorRecognizer($recognizer1);
        $this->addActorRecognizer($recognizer2);
        $this->addActorRecognizer($recognizer3);

        $this->recognizeActor($useCaseRequest)->shouldHaveType(Jedi::class);
    }

    public function it_returns_the_composite_actor_if_many_actors_have_been_recognized(
        ActorRecognizerInterface $recognizer1,
        ActorRecognizerInterface $recognizer2,
        ActorRecognizerInterface $recognizer3
    )
    {
        $useCaseRequest = new \stdClass();
        $recognizer1->recognizeActor($useCaseRequest)->willReturn(new CookieMonster());
        $recognizer2->recognizeActor($useCaseRequest)->willReturn(new Jedi());
        $recognizer3->recognizeActor($useCaseRequest)->willReturn(new UnableActor());

        $this->addActorRecognizer($recognizer1);
        $this->addActorRecognizer($recognizer2);
        $this->addActorRecognizer($recognizer3);

        $actor = $this->recognizeActor($useCaseRequest);
        $actor->shouldHaveType(CompositeActor::class);
        $actor->canExecute('use the force')->shouldBe(true);
        $actor->canExecute('eat cookies')->shouldBe(true);
        $actor->canExecute('count to ten')->shouldBe(false);
    }

    public function it_finds_registered_actors_by_name(
        ActorRecognizerInterface $recognizer1,
        ActorRecognizerInterface $recognizer2
    )
    {
        $cookieMonster = new CookieMonster();
        $jedi = new Jedi();

        $useCaseRequest = new \stdClass();
        $recognizer1->recognizeActor($useCaseRequest)->willReturn($cookieMonster);
        $recognizer2->recognizeActor($useCaseRequest)->willReturn($jedi);

        $this->addActorRecognizer($recognizer1);
        $this->addActorRecognizer($recognizer2);

        $this->recognizeActorByName('jedi', $useCaseRequest)->shouldBe($jedi);
        $this->recognizeActorByName('cookie monster', $useCaseRequest)->shouldBe($cookieMonster);
    }

    public function it_throws_an_exception_if_actor_cannot_be_found_by_name()
    {
        $this->shouldThrow(UnrecognizedActorException::class)->duringRecognizeActorByName('no such actor here', null);
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

    /**
     * @return string
     */
    public function getName()
    {
        return 'jedi';
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

    /**
     * @return string
     */
    public function getName()
    {
        return 'cookie monster';
    }
}
