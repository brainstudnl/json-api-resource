<?php

namespace Brainstud\JsonApi\Exceptions;

class UnprocessableContentJsonApiException extends JsonApiHttpException
{
    public function __construct(?string $title = "Unprocesssable Content", string $message = "The request can't be processed.", ?\Throwable $previous = null, int $code = 0, array $headers = [])
    {
        parent::__construct(
            $title,
            422,
            $message,
            $previous,
            $headers,
            $code
        );
    }
}
