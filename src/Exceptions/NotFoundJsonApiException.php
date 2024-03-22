<?php

namespace Brainstud\JsonApi\Exceptions;

class NotFoundJsonApiException extends JsonApiHttpException
{
    public function __construct(?string $title = "Not Found", string $message = "The requested resource could not be found.", ?\Throwable $previous = null, int $code = 0, array $headers = [])
    {
        parent::__construct(
            $title, 
            404,
            $message,
            $previous,
            $headers,
            $code
        );
    }
}
