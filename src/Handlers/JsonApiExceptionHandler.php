<?php

namespace Brainstud\JsonApi\Handlers;

use Brainstud\JsonApi\Exceptions\JsonApiExceptionInterface;
use Brainstud\JsonApi\Responses\ErrorResponse;
use Brainstud\JsonApi\Responses\Errors\DefaultError;
use Error;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
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
    public function register(): void
    {
        $this->renderable(function (JsonApiExceptionInterface $exception) {
            list($title, $detail) = $this->parseException($exception);

            return ErrorResponse::make(new DefaultError('JSON_API_ERROR', $title, $detail, httpStatusCode: $exception->getStatusCode()));
        });

        $this->renderable(function (NotFoundHttpException|AuthenticationException|MethodNotAllowedHttpException|AuthorizationException|UnprocessableEntityHttpException|Throwable $exception) {
            list($title, $detail) = $this->parseException($exception);
            $code = method_exists($exception, 'getStatusCode') ? $exception->getStatusCode() : 500;

            return ErrorResponse::make(new DefaultError('JSON_API_ERROR', $title, $detail, httpStatusCode: $code));
        });
    }

    /**
     * ParseException
     * 
     * Parse the given exception to a title and message.
     * Returns a tuple-like array [title, detail].
     *  
     * @return array<string>
     */
    private function parseException(Throwable $exception): array
    {
        $title = ($exception instanceof JsonApiExceptionInterface)
            ? $exception->getTitle()
            : $this->getExceptionTitle($exception);

        $message = $exception->getMessage();

        $detail = empty($message) 
            ? $this->getExceptionMessage($exception) 
            : $message;

        return [$title, $detail];
    }

    private function getExceptionTitle(Throwable $exception): string 
    {
        return match (true) {
            $exception instanceof NotFoundHttpException => "Not Found",
            $exception instanceof AuthenticationException => "Unauthorized",
            $exception instanceof MethodNotAllowedHttpException => "Method Not Allowed",
            $exception instanceof AuthorizationException => "Forbidden",
            $exception instanceof UnprocessableEntityHttpException => "Unprocessable Entity",
            $exception instanceof Throwable => "Internal Server Error",
        };
    }

    private function getExceptionMessage(Throwable $exception): string
    {
        return match (true) {
            $exception instanceof NotFoundHttpException => "The requested resource could not be found.",
            $exception instanceof AuthenticationException => "The requested requires authentication.",
            $exception instanceof MethodNotAllowedHttpException => "Method Not Allowed",
            $exception instanceof AuthorizationException => "This action is unauthorized.",
            $exception instanceof UnprocessableEntityHttpException => "The request can't be processed.",
            $exception instanceof Throwable => "",
        };
    }
}