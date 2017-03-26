<?php

namespace Bamiz\UseCaseExecutor\Actor;

class CompositeActorRecognizer implements ActorRecognizerInterface
{
    /**
     * @var ActorRecognizerInterface[]
     */
    private $actorRecognizers = [];

    /**
     * @param object $useCaseRequest
     *
     * @return ActorInterface
     */
    public function recognizeActor($useCaseRequest)
    {
        if (count($this->actorRecognizers) > 0) {
            $ableActors = $this->findAbleActors($useCaseRequest);
            return $this->createActor($ableActors);
        }

        return new OmnipotentActor();
    }

    /**
     * @param string $actorName
     * @param object $useCaseRequest
     *
     * @return ActorInterface
     * @throws UnrecognizedActorException
     */
    public function recognizeActorByName($actorName, $useCaseRequest)
    {
        foreach ($this->actorRecognizers as $actorRecognizer) {
            $actor = $actorRecognizer->recognizeActor($useCaseRequest);
            if ($actorName === $actor->getName()) {
                return $actor;
            }
        }

        throw new UnrecognizedActorException(sprintf('No Actor has been recognized as "%s".', $actorName));
    }

    /**
     * @param ActorRecognizerInterface $actorRecognizer
     */
    public function addActorRecognizer(ActorRecognizerInterface $actorRecognizer)
    {
        $this->actorRecognizers[] = $actorRecognizer;
    }

    /**
     * @param object $useCaseRequest
     *
     * @return ActorInterface[]
     */
    private function findAbleActors($useCaseRequest)
    {
        $ableActors = [];
        foreach ($this->actorRecognizers as $actorRecognizer) {
            $actor = $actorRecognizer->recognizeActor($useCaseRequest);
            if (!($actor instanceof UnableActor)) {
                $ableActors[] = $actor;
            }
        }

        return $ableActors;
    }

    /**
     * @param ActorInterface[] $ableActors
     *
     * @return ActorInterface
     */
    private function createActor($ableActors)
    {
        switch (count($ableActors)) {
            case 0:
                return new UnableActor();
            case 1:
                return $ableActors[0];
            default:
                return new CompositeActor($ableActors);
        }
    }
}
