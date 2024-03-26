<?php

namespace Brainstud\JsonApi\Exceptions;

class UnprocessableContentJsonApiException extends JsonApiHttpException
{
    public function __construct(?string $title = 'Unprocesssable Content', string $message = "The request can't be processed.", ?\Throwable $previous = null, int $code = 0, array $headers = [])
    {
        parent::__construct(
            __($title),
            422,
            __($message),
            $previous,
            $headers,
            $code
        );
    }
}
