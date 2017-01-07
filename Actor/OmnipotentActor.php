<?php

namespace Bamiz\UseCaseBundle\Actor;

class OmnipotentActor implements ActorInterface
{
    /**
     * An omnipotent actor can execute any use case.
     *
     * @param string $useCaseName
     *
     * @return bool
     */
    public function canExecute($useCaseName)
    {
        return true;
    }
}