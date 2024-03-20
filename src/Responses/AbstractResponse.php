<?php

namespace Brainstud\JsonApi\Responses;

use Illuminate\Http\JsonResponse;

/**
 * Class AbstractResponse
 * Used to implement json responses
 */
abstract class AbstractResponse
{
    /** @var array|object|null Data on this response */
    protected $data;

    /** @var array|null Errors on this response */
    protected $errors;

    /** @var object|null Metadata on this response */
    protected $meta;

    /** @var int The HTTP status code */
    protected $httpStatusCode;

    /** @var object|null */
    protected $links;

    /** @var array|null Included data on this response */
    protected $included;

    /**
     * Create and return a JSON response with the given data and status code
     *
     * @param  mixed  $contents  The contents for the response
     * @param  int  $httpStatusCode  The HTTP status code
     */
    abstract public static function make($contents, int $httpStatusCode): JsonResponse;

    /**
     * Create and return a JSON response
     */
    protected function makeResponse(): JsonResponse
    {
        return response()->json(
            array_filter([
                'data' => $this->data,
                'errors' => $this->errors,
                'meta' => isset($this->meta) ? (object) $this->meta : null,
                'links' => isset($this->links) ? (object) $this->links : null,
                'included' => $this->included,
            ]),
            $this->httpStatusCode
        );
    }
}
