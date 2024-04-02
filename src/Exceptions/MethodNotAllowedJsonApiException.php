<?php

namespace Brainstud\JsonApi\Exceptions;

use function PHPUnit\Framework\isEmpty;

class MethodNotAllowedJsonApiException extends JsonApiHttpException
{
    public function __construct(?string $title = 'Method not allowed', string $message = '', ?\Throwable $previous = null, int $code = 0, array $headers = [])
    {
        $message = isEmpty($message) ?
            __('The method :METHOD is not supported for :route', ['METHOD' => request()->method(), 'route' => request()->path()])
            : $message;
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
