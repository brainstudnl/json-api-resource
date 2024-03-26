<?php

namespace Brainstud\JsonApi\Exceptions;

use function PHPUnit\Framework\isEmpty;

class MethodNotAllowedJsonApiException extends JsonApiHttpException
{
    public function __construct(?string $title = 'Method Not Allowed', string $message = '', ?\Throwable $previous = null, int $code = 0, array $headers = [])
    {
        $message = isEmpty($message) ? 'The method'.strtoupper(request()->method()).' is not supported for '.request()->route() : $message;
        parent::__construct(
            __($title),
            405,
            __($message),
            $previous,
            $headers,
            $code
        );
    }
}
