<?php


namespace Brainstud\Packages\JsonApi\Responses\Errors;


/**
 * Class DefaultError
 * Used to create a customisable error
 * @package Brainstud\Packages\JsonApi\Responses\Errors
 */
class DefaultError extends AbstractError
{
    /**
     * Create a new generic error
     * @param string $code The UnlimitED error code
     * @param string $title The error title
     * @param string $detail The error details
     * @param array|object|null $source A pointer to the cause of the error
     */
    public function __construct(string $code, string $title, string $detail, $source = null)
    {
        $this->code = $code;
        $this->title = $title;
        $this->detail = $detail;
        $this->source = $source;
    }
}
