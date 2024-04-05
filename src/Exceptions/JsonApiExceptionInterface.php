<?php

namespace Brainstud\JsonApi\Exceptions;

use Throwable;

/**
 * JsonApiExceptionInterface
 *
 * This interface makes sure that all children will have a `getTitle()` and `getStatusCode()` function declared.
 */
interface JsonApiExceptionInterface extends Throwable
{
    public function getTitle(): string;

    public function getStatusCode(): int;
}
