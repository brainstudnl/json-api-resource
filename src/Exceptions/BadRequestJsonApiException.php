<?php

namespace Brainstud\JsonApi\Exceptions;

class BadRequestJsonApiException extends JsonApiHttpException
{
    public function __construct(?string $title = 'Bad request', string $message = '', ?\Throwable $previous = null, int $code = 0, array $headers = [])
    {
        parent::__construct(
            __($title),
            400,
            __($message),
            $previous,
            $headers,
            $code
        );
    }
}
