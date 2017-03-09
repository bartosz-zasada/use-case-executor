<?php

namespace Bamiz\UseCaseBundle\Actor;

interface ActorRecognizerInterface
{
    /**
     * @param object $useCaseRequest
     *
     * @return ActorInterface
     */
    public function recognizeActor($useCaseRequest);
}