<?php


namespace Brainstud\JsonApi\Responses\Errors;

/**
 * Class MethodNotAllowedError
 * Used when an API call is made on a method that is not allowed
 * @package Brainstud\JsonApi\Responses\Errors
 */
class MethodNotAllowedError extends AbstractError
{
    public function __construct()
    {
        $requestMethod = strtoupper(request()->method());
        $this->status = 405;
        $this->title = "Method Not Allowed";
        $this->detail = "The {$requestMethod} method is not supported for this route.";
    }
}
