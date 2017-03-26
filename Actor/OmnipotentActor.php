<?php

namespace Bamiz\UseCaseExecutor\Actor;

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

    /**
     * @return string
     */
    public function getName()
    {
        return 'omnipotent';
    }
}