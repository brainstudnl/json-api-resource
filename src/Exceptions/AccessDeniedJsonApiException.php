<?php

namespace Brainstud\JsonApi\Exceptions;

use Symfony\Component\Finder\Exception\AccessDeniedException;

abstract class AccessDeniedJsonApiException extends AccessDeniedException implements JsonApiExceptionInterface
{
    protected string $title;

    public function getTitle(): string 
    { 
        return $this->title;
    }
}