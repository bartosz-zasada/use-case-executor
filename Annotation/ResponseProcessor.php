<?php

namespace Bamiz\UseCaseBundle\Annotation;

/**
 * @Annotation
 * @Target({"CLASS"})
 */
class ResponseProcessor extends ProcessorAnnotation
{
    /**
     * @return string
     */
    public function getType()
    {
        return 'response';
    }
}