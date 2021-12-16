<?php

namespace Brainstud\JsonApi\Handlers;

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
 *
 * @package Brainstud\JsonApi\Handlers
 */
class JsonApiExceptionHandler extends ExceptionHandler
{
    public function render($request, Throwable $exception)
    {
        switch ($exception) {
            case $exception instanceof ModelNotFoundException:
                // Fall through to NotFoundError
            case $exception instanceof NotFoundHttpException:
                return (new NotFoundError)->response();

            case $exception instanceof AuthenticationException:
                return (new UnauthorizedError)->response();

            case $exception instanceof MethodNotAllowedHttpException:
                return (new MethodNotAllowedError)->response();

            case $exception instanceof AuthorizationException: // Fall through to ForbiddenError
            case $exception instanceof AccessDeniedHttpException:
                return (new ForbiddenError)->response();

            case $exception instanceof UnprocessableEntityHttpException:
                return (new UnprocessableEntityError)->response();

            case $exception instanceof HttpException: // Let all other HttpExceptions fall through as is. (validation errors, errors from packages, ...)
            case $exception instanceof ValidationException:
                break;

            case $exception instanceof Exception:
                return (new InternalServerError)->response();
        }

        return parent::render($request, $exception);
    }
}
