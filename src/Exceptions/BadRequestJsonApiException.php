<?php

namespace Brainstud\JsonApi\Exceptions;

class BadRequestJsonApiException extends JsonApiHttpException
{
    public function __construct(?string $title = 'Bad Request', string $message = '', ?\Throwable $previous = null, int $code = 0, array $headers = [])
    {
        parent::__construct(
            $title,
            400,
            $message,
            $previous,
            $headers,
            $code
        );
    }
}
