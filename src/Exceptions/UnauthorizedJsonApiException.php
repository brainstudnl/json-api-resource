<?php

namespace Brainstud\JsonApi\Exceptions;

class UnauthorizedJsonApiException extends JsonApiHttpException
{
    public function __construct(?string $title = 'Unauthorized', string $message = 'You need to be authenticated to access this information.', ?\Throwable $previous = null, int $code = 0, array $headers = [])
    {
        parent::__construct(
            __($title),
            401,
            __($message),
            $previous,
            $headers,
            $code
        );
    }
}
