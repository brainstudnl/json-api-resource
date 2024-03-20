<?php

namespace Brainstud\JsonApi\Exceptions;

use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

abstract class MethodNotAllowedJsonApiException extends MethodNotAllowedHttpException implements JsonApiExceptionInterface
{
    protected string $title;

    public function getTitle(): string
    {
        return $this->title;
    }
}
