<?php

namespace Brainstud\JsonApi\Exceptions;

class NotAcceptableJsonApiException extends JsonApiHttpException
{
    public function __construct(?string $title = 'Not acceptable', string $message = 'Cannot produce a response matching the acceptable values.', ?\Throwable $previous = null, int $code = 0, array $headers = [])
    {
        parent::__construct(
            __($title),
            406,
            __($message),
            $previous,
            $headers,
            $code
        );
    }
}
