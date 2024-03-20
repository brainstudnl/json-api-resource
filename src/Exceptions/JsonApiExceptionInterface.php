<?php

namespace Brainstud\JsonApi\Exceptions;

/**
 * JsonApiExceptionInterface
 * 
 * This interface makes sure that all children will have a `getTitle()` function declared.
 * 
 * @package Brainstud\JsonApi\Exceptions
 */
interface JsonApiExceptionInterface 
{
    public function getTitle(): string;
}