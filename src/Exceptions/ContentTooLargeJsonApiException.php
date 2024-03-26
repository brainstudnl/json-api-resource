<?php

namespace Brainstud\JsonApi\Exceptions;

class ContentTooLarge extends JsonApiHttpException
{
    public function __construct(?string $title = 'Content Too Large', string $message = 'The request entity is too large.', ?\Throwable $previous = null, int $code = 0, array $headers = [])
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
