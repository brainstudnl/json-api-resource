<?php

namespace Brainstud\JsonApi\Exceptions;

class ContentTooLargeJsonApiException extends JsonApiHttpException
{
    public function __construct(?string $title = 'Content too large', string $message = 'The request is too large to process.', ?\Throwable $previous = null, int $code = 0, array $headers = [])
    {
        parent::__construct(
            __($title),
            413,
            __($message),
            $previous,
            $headers,
            $code
        );
    }
}
