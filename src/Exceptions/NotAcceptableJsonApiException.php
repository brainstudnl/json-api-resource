<?php

namespace Brainstud\JsonApi\Exceptions;

class NotAcceptableJsonApiException extends JsonApiHttpException
{
    public function __construct(?string $title = "Not Acceptable", string $message = "Cannot produce a response matching the acceptable values", ?\Throwable $previous = null, int $code = 0, array $headers = [])
    {
        parent::__construct(
            $title,
            406,
            $message,
            $previous,
            $headers,
            $code
        );
    }
}
