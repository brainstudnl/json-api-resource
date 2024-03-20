<?php

namespace Brainstud\JsonApi\Responses\Errors;

class InternalServerError extends AbstractError
{
    public function __construct(?string $detail = null)
    {
        $this->status = 500;
        $this->title = 'Internal Server Error';
        $this->detail = $detail;
    }
}
