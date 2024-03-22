<?php

namespace Brainstud\JsonApi\Responses\Errors;

/**
 * Class UnauthorizedError
 * Used when an API call requires authentication
 */
class UnauthorizedError extends AbstractError
{
    public function __construct()
    {
        $this->status = 401;
        $this->title = 'Unauthorized';
        $this->detail = 'The requested requires authentication.';
    }
}
