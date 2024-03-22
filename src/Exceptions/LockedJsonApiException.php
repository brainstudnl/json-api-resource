<?php

namespace Brainstud\JsonApi\Exceptions;

class LockedJsonApiException extends JsonApiHttpException
{
    public function __construct(?string $title = 'Locked', string $message = 'The requested resource is locked.', ?\Throwable $previous = null, int $code = 0, array $headers = [])
    {
        parent::__construct(
            $title,
            423,
            $message,
            $previous,
            $headers,
            $code
        );
    }
}
