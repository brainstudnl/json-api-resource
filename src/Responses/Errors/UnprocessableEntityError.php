<?php

namespace Brainstud\JsonApi\Responses\Errors;

/**
 * Class UnprocessableEntityError
 * Used the given data cannot be processed
 */
class UnprocessableEntityError extends AbstractError
{
    public function __construct()
    {
        $this->status = 422;
        $this->title = 'Unprocessable Entity';
        $this->detail = "The request can't be processed.";
    }
}
