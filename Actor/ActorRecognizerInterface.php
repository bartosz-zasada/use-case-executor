<?php

namespace Bamiz\UseCaseExecutor\Actor;

interface ActorRecognizerInterface
{
    /**
     * @param object $useCaseRequest
     *
     * @return ActorInterface
     */
    public function recognizeActor($useCaseRequest);
}