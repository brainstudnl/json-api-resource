<?php

namespace Brainstud\JsonApi\Responses\Errors;

/**
 * Class NotFoundError
 * Used when the resource could not be found
 */
class NotFoundError extends AbstractError
{
    public function __construct()
    {
        $this->status = 404;
        $this->title = 'Not Found';
        $this->detail = 'The requested resource could not be found.';
    }
}
