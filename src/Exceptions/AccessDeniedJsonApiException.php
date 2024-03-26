<?php

namespace Brainstud\JsonApi\Exceptions;

class AccessDeniedJsonApiException extends JsonApiHttpException
{
    public function __construct(?string $title = 'Access denied', string $message = 'You do not have access to see or edit this information.', ?\Throwable $previous = null, int $code = 0, array $headers = [])
    {
        parent::__construct(
            __($title),
            403,
            __($message),
            $previous,
            $headers,
            $code
        );
    }
}
