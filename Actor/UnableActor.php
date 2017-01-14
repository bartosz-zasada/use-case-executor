<?php

namespace Bamiz\UseCaseBundle\Actor;

class UnableActor implements ActorInterface
{
    /**
     * @param string $useCaseName
     *
     * @return bool
     */
    public function canExecute($useCaseName)
    {
        return false;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'unable';
    }
}