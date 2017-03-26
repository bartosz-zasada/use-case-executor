<?php

namespace Bamiz\UseCaseExecutor\Actor;

interface ActorInterface
{
    /**
     * @param string $useCaseName
     *
     * @return bool
     */
    public function canExecute($useCaseName);

    /**
     * @return string
     */
    public function getName();
}