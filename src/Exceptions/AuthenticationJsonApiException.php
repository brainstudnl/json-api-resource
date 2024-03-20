<?php

namespace Brainstud\JsonApi\Exceptions;

use Illuminate\Auth\AuthenticationException;

abstract class AuthenticationJsonApiException extends AuthenticationException implements JsonApiExceptionInterface
{
    protected string $title;

    public function getTitle(): string
    {
        return $this->title;
    }
}
