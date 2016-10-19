<?php

namespace Bamiz\UseCaseBundle\Annotation;

/**
 * @Annotation
 * @Target({"CLASS"})
 */
class InputProcessor extends ProcessorAnnotation
{
    protected $type = 'Input';
}