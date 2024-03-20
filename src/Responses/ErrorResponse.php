<?php

namespace Brainstud\JsonApi\Responses;

use Illuminate\Http\JsonResponse;

/**
 * Class ErrorResponse
 * Create an error response
 */
class ErrorResponse extends AbstractResponse
{
    private static $defaultHttpStatusCode = 400;

    /**
     * Create and return the error response
     *
     * @param  mixed  $errors  A single Error instance or an array of Error instances
     * @param  int  $httpStatusCode  The HTTP Status code
     */
    public static function make($errors, $httpStatusCode = null): JsonResponse
    {
        $self = new self();
        $self->httpStatusCode = $httpStatusCode ?? self::$defaultHttpStatusCode;
        if (is_array($errors)) {
            $self->errors = array_map(function ($error) {
                return $error->toObject();
            }, $errors);
        } else {
            $self->errors[] = $errors->toObject();
        }

        return $self->makeResponse();
    }
}
