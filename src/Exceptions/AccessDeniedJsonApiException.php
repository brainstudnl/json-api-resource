<?php

namespace Brainstud\JsonApi\Exceptions;

class AccessDeniedJsonApiException extends JsonApiHttpException
{
    public function __construct(?string $title = 'Access Denied', string $message = 'No access to the requested resource.', ?\Throwable $previous = null, int $code = 0, array $headers = [])
    {
        parent::__construct(
            $title,
            403,
            $message,
            $previous,
            $headers,
            $code
        );
    }
}
