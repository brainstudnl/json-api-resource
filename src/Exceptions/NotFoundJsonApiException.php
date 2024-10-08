<?php

namespace Brainstud\JsonApi\Exceptions;

class NotFoundJsonApiException extends JsonApiHttpException
{
    public function __construct(?string $title = 'Not found', string $message = 'The requested information could not be found.', ?\Throwable $previous = null, int $code = 0, array $headers = [])
    {
        parent::__construct(
            __($title),
            404,
            __($message),
            $previous,
            $headers,
            $code
        );
    }
}
