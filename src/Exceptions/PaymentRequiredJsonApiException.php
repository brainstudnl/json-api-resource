<?php

namespace Brainstud\JsonApi\Exceptions;

class PaymentRequiredJsonApiException extends JsonApiHttpException
{
    public function __construct(?string $title = 'Payment required', string $message = 'A payment is required to access this information.', ?\Throwable $previous = null, int $code = 0, array $headers = [])
    {
        parent::__construct(
            __($title),
            402,
            __($message),
            $previous,
            $headers,
            $code
        );
    }
}
