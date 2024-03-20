<?php

namespace Brainstud\JsonApi\Responses\Errors;

/**
 * Class ForbiddenError
 * Used when the user doesn't have permission to execute the requested operation
 */
class ForbiddenError extends AbstractError
{
    public function __construct()
    {
        $this->status = 403;
        $this->title = 'Forbidden';
        $this->detail = 'This action is unauthorized.';
    }
}
