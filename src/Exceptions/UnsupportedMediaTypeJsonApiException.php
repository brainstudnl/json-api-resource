<?php

namespace Brainstud\JsonApi\Exceptions;

class UnsupportedMediaTypeJsonApiException extends JsonApiHttpException
{
    public function __construct(?string $title = 'Unsupported Media Type', string $message = 'The media type is not supported.', ?\Throwable $previous = null, int $code = 0, array $headers = [])
    {
        parent::__construct(
            __($title),
            415,
            __($message),
            $previous,
            $headers,
            $code
        );
    }
}
