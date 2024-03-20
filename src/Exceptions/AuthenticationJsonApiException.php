<?php

namespace Brainstud\JsonApi\Exceptions;

use Illuminate\Auth\AuthenticationException;

abstract class AuthenticationJsonApiException extends AuthenticationException implements JsonApiExceptionInterface
{
    protected string $title;
    protected int $statusCode = 401;

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
