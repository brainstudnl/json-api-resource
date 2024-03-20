<?php

namespace Brainstud\JsonApi\Exceptions;

use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

abstract class UnprocessableEntityJsonApiException extends UnprocessableEntityHttpException implements JsonApiExceptionInterface
{
    protected string $title;

    public function getTitle(): string
    {
        return $this->title;
    }
}
