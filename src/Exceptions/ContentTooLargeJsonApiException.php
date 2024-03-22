<?php

namespace Brainstud\JsonApi\Exceptions;

class ContentTooLarge extends JsonApiHttpException
{
    public function __construct(?string $title = 'Content Too Large', string $message = 'The request entity is too large.', ?\Throwable $previous = null, int $code = 0, array $headers = [])
    {
        parent::__construct(
            $title,
            413,
            $message,
            $previous,
            $headers,
            $code
        );
    }
}
