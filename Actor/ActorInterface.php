<?php

namespace Bamiz\UseCaseBundle\Actor;

interface ActorInterface
{
    /**
     * @param string $useCaseName
     *
     * @return bool
     */
    public function canExecute($useCaseName);
}