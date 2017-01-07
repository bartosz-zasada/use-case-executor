<?php

namespace Bamiz\UseCaseBundle\Actor;

interface ActorRecognizerInterface
{
    /**
     * @return ActorInterface
     */
    public function recognizeActor();
}