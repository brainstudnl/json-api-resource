<?php

namespace Brainstud\JsonApi\Exceptions;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

abstract class NotFoundJsonApiException extends NotFoundHttpException implements JsonApiExceptionInterface
{
    protected string $title;

    public function getTitle(): string
    {
        return $this->title;
    }
}
