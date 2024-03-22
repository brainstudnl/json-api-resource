<?php

namespace Brainstud\JsonApi\Exceptions;

class UnauthorizedJsonApiException extends JsonApiHttpException
{
    public function __construct(?string $title = "Unauthorized action", string $message = "The requested requires authentication.", ?\Throwable $previous = null, int $code = 0, array $headers = [])
    {
        parent::__construct(
            $title,
            401,
            $message,
            $previous,
            $headers,
            $code
        );
    }
}
