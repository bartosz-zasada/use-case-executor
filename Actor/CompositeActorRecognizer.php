<?php

namespace Bamiz\UseCaseBundle\Actor;

class CompositeActorRecognizer implements ActorRecognizerInterface
{
    /**
     * @var ActorRecognizerInterface[]
     */
    private $actorRecognizers = [];

    /**
     * @return ActorInterface
     */
    public function recognizeActor()
    {
        if (count($this->actorRecognizers) > 0) {
            $ableActors = $this->findAbleActors();
            return $this->createActor($ableActors);
        }

        return new OmnipotentActor();
    }

    /**
     * @param string $actorName
     *
     * @return ActorInterface
     * @throws ActorNotFoundException
     */
    public function findActorByName($actorName)
    {
        foreach ($this->actorRecognizers as $actorRecognizer) {
            $actor = $actorRecognizer->recognizeActor();
            if ($actorName === $actor->getName()) {
                return $actor;
            }
        }

        throw new ActorNotFoundException(sprintf('Actor "%s" not found.', $actorName));
    }

    /**
     * @param ActorRecognizerInterface $actorRecognizer
     */
    public function addActorRecognizer(ActorRecognizerInterface $actorRecognizer)
    {
        $this->actorRecognizers[] = $actorRecognizer;
    }

    /**
     * @return ActorInterface[]
     */
    private function findAbleActors()
    {
        $ableActors = [];
        foreach ($this->actorRecognizers as $actorRecognizer) {
            $actor = $actorRecognizer->recognizeActor();
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
