<?php

namespace Lamudi\UseCaseBundle\Annotation;

/**
 * @Annotation
 * @Target({"CLASS"})
 */
class ResponseProcessor extends ProcessorAnnotation
{
    protected $type = 'Response';
}