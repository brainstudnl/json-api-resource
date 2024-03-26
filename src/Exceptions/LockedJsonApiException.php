<?php

namespace Brainstud\JsonApi\Exceptions;

class LockedJsonApiException extends JsonApiHttpException
{
    public function __construct(?string $title = 'Locked', string $message = 'The requested resource is locked.', ?\Throwable $previous = null, int $code = 0, array $headers = [])
    {
        parent::__construct(
            __($title),
            423,
            __($message),
            $previous,
            $headers,
            $code
        );
    }
}
