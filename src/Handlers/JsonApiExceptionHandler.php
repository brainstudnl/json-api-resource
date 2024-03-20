<?php

namespace Brainstud\JsonApi\Handlers;

use Brainstud\JsonApi\Exceptions\JsonApiExceptionInterface;
use Brainstud\JsonApi\Responses\Errors\ForbiddenError;
use Brainstud\JsonApi\Responses\Errors\InternalServerError;
use Brainstud\JsonApi\Responses\Errors\MethodNotAllowedError;
use Brainstud\JsonApi\Responses\Errors\NotFoundError;
use Brainstud\JsonApi\Responses\Errors\UnauthorizedError;
use Brainstud\JsonApi\Responses\Errors\UnprocessableEntityError;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Throwable;

/**
 * Class JsonApiExceptionHandler
 * Handles the rendering of exceptions that occur on JSON requests.
 * Initiated by app/Exceptions/Handler
 */
class JsonApiExceptionHandler extends ExceptionHandler
{
    public function render($request, Throwable $exception)
    {
        list($title, $detail) = $this->parseException($exception);

        switch ($exception) {
            // Fall through to NotFoundError
            case $exception instanceof ModelNotFoundException:
            case $exception instanceof NotFoundHttpException:
                return (new NotFoundError($title, $detail))->response();

            case $exception instanceof AuthenticationException:
                return (new UnauthorizedError($title, $detail))->response();

            case $exception instanceof MethodNotAllowedHttpException:
                return (new MethodNotAllowedError($title, $detail))->response();

            // Fall through for AuthorizationException to ForbiddenError
            case $exception instanceof AuthorizationException:
            case $exception instanceof AccessDeniedHttpException:
                return (new ForbiddenError($title, $detail))->response();

            case $exception instanceof UnprocessableEntityHttpException:
                return (new UnprocessableEntityError($title, $detail))->response();

            // Let all other HttpExceptions fall through as is. (validation errors, errors from packages, ...)
            case $exception instanceof HttpException: 
            case $exception instanceof ValidationException:
                break;

            case $exception instanceof Exception:
                return (new InternalServerError($title, $detail))->response();
        }

        return parent::render($request, $exception);
    }

    /**
     * ParseException
     * 
     * Parse the given exception to a title and message.
     * Returns a tuple-like array [title, detail].
     *  
     * @return array<?string>
     */
    private function parseException(Throwable $exception): array
    {
        $title = ($exception instanceof JsonApiExceptionInterface)
            ? $exception->getTitle()
            : null;

        $message = $exception->getMessage();
        $detail = empty($message) ? null : $message;

        return [$title, $detail];
    }
}
