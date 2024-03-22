<?php

namespace Brainstud\JsonApi\Handlers;

use Brainstud\JsonApi\Exceptions\BadRequestJsonApiException;
use Brainstud\JsonApi\Exceptions\JsonApiExceptionInterface;
use Brainstud\JsonApi\Exceptions\NotFoundJsonApiException;
use Brainstud\JsonApi\Responses\ErrorResponse;
use Brainstud\JsonApi\Responses\Errors\DefaultError;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\RelationNotFoundException;
use Illuminate\Database\LazyLoadingViolationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use RuntimeException;
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
        $this->map(function (ModelNotFoundException|RelationNotFoundException $exception) {
            $title = "{$this->getModelForException($exception)} Not Found";
            return new NotFoundJsonApiException(
                $title,
                $exception->getMessage(),
                $exception->getPrevious(),
                $exception->getCode(),
            );
        });

        $this->map(function (LazyLoadingViolationException $exception) {
            return (new BadRequestJsonApiException(
                "Lazy Loading Violation",
                $exception->getMessage(),
                $exception->getPrevious(),
                $exception->getCode(),
            ))->withErrorName("LAZY_LOADING_VIOLATION");
        });

        $this->renderable(function (JsonApiExceptionInterface $exception) {
            return ErrorResponse::make([new DefaultError(
                (isset($exception->errorName) ? $exception->errorName : ''),
                $exception->getTitle(), 
                $exception->getMessage(),
                $exception,
                $exception->getStatusCode(),
                )], $exception->getStatusCode());
        });

        $this->renderable(function (ValidationException $validationException) {
            $defaultErrors = collect($validationException->validator->errors()->messages())
                ->map(function ($value, $key) {
                    return new DefaultError(
                        'VALIDATION_ERROR',
                        'Validation error',
                        $value[0],
                        ['pointer' => $key]
                    );
                })->toArray();
            return ErrorResponse::make($defaultErrors, 422);
        });
    }

    /**
     * Prepare a JSON response for the given exception.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $e
     * @return \Illuminate\Http\JsonResponse
     */
    protected function prepareJsonResponse($request, Throwable $e)
    {
        return ErrorResponse::make([
            new DefaultError(
                'UNKNOWN_ERROR',
                $this->defaultIfEmpty($e->getMessage(), "Unknown Error"),
                $e->getMessage(),
                $e,
                $this->isHttpException($e) ? $e->getStatusCode() : 500,
            )
        ], $this->isHttpException($e) ? $e->getStatusCode() : 500);
    }


    public function map($from, $to = null)
    {
        try {
            parent::map($from, $to);
        } catch (RuntimeException) {
            if (is_callable($from) && is_null($to)) {
                $from = $this->firstClosureParameterTypes($to = $from);
            }

            if (!$to instanceof \Closure) {
                throw new \InvalidArgumentException('Invalid exception mapping.');
            }

            array_map(fn ($f) => parent::map($f, $to), $from);
        }
    }

    private function getModelForException(ModelNotFoundException|RelationNotFoundException|LazyLoadingViolationException $exception): string
    {
        return match ($exception::class) {
            ModelNotFoundException::class => $exception->getModel(),
            default => $exception->model,
        };
    }

    private function defaultIfEmpty(string $s, string $default): string 
    {
        return empty($s) ? $default : $s;
    }
}