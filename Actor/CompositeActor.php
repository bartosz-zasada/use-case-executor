<?php

namespace Bamiz\UseCaseBundle\Actor;

class CompositeActor implements ActorInterface
{
    /**
     * @var ActorInterface[]
     */
    private $actors;

    /**
     * @param ActorInterface[] $actors
     */
    public function __construct(array $actors = [])
    {
        foreach ($actors as $actor) {
            $this->addActor($actor);
        }
    }

    /**
     * @param string $useCaseName
     *
     * @return bool
     */
    public function canExecute($useCaseName)
    {
        foreach ($this->actors as $actor) {
            if ($actor->canExecute($useCaseName)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param ActorInterface $actor
     */
    public function addActor(ActorInterface $actor)
    {
        $this->actors[] = $actor;
    }
}