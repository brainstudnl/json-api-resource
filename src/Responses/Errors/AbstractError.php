<?php

namespace Brainstud\JsonApi\Responses\Errors;

use Brainstud\JsonApi\Responses\ErrorResponse;
use Illuminate\Http\JsonResponse;

abstract class AbstractError
{
    /** @var int The application specific error code */
    protected $code;

    /** @var int HTTP status code (optional) */
    protected $status;

    /** @var array Source information about this error */
    protected $source = [];

    /** @var string Title of this error */
    protected $title;

    /** @var string Detail about this error */
    protected $detail;

    protected array $meta;

    /**
     * Set the status code
     *
     * @return $this
     */
    public function setStatusCode(int $status): self
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Convert the error to an object
     */
    public function toObject(): object
    {
        return (object) array_filter([
            'code' => (string) $this->code,
            'status' => (string) $this->status,
            'source' => (! empty($this->source)) ? (object) $this->source : null,
            'title' => $this->title,
            'detail' => $this->detail,
            'meta' => $this->meta,
        ]);
    }

    /**
     * Return an ErrorResponse
     */
    public function response(): JsonResponse
    {
        return ErrorResponse::make($this, $this->status);
    }
}
