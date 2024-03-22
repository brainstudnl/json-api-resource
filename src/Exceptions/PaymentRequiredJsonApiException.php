<?php

namespace Brainstud\JsonApi\Exceptions;

class PaymentRequiredJsonApiException extends JsonApiHttpException
{
    public function __construct(?string $title = "Payment Required", string $message = "A payment is required to access the resource.", ?\Throwable $previous = null, int $code = 0, array $headers = [])
    {
        parent::__construct(
            $title,
            402,
            $message,
            $previous,
            $headers,
            $code
        );
    }
}
