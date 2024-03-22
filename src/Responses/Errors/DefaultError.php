<?php

namespace Brainstud\JsonApi\Responses\Errors;

/**
 * Class DefaultError
 * Used to create a customisable error
 */
class DefaultError extends AbstractError
{
    /**
     * Create a new generic error
     *
     * @param  string  $code  The error code
     * @param  string  $title  The error title
     * @param  string  $detail  The error details
     * @param  ?int  $httpStatus  The HTTP Status code
     * @param  array|object|null  $source  A pointer to the cause of the error
     */
    public function __construct(string $code, string $title, string $detail, $source = null, ?int $httpStatusCode = null)
    {
        $this->code = $code;
        $this->title = $title;
        $this->detail = $detail;
        $this->source = $source;
        $this->status = $httpStatusCode;
    }
}
