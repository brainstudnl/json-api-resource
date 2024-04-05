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
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/**
 * JsonApiExceptionHandler
 *
 * Handles the rendering of exceptions that occur on JSON requests. It maps
 * them to a JSON:API compliant error format.
 *
 * Initiated by app/Exceptions/Handler
 */
class JsonApiExceptionHandler extends ExceptionHandler
{
    public function register(): void
    {
        $this->map(function (ModelNotFoundException|RelationNotFoundException $exception) {
            $title = "{$this->getModelFromException($exception)} ".strtolower(__('Not found'));

            return new NotFoundJsonApiException(
                $title,
                $exception->getMessage(),
                $exception->getPrevious(),
                $exception->getCode(),
            );
        });

        $this->map(function (LazyLoadingViolationException $exception) {
            return (new BadRequestJsonApiException(
                'Lazy Loading Violation',
                $exception->getMessage(),
                $exception->getPrevious(),
                $exception->getCode(),
            ))->withErrorName('LAZY_LOADING_VIOLATION');
        });

        $this->renderable(function (JsonApiExceptionInterface $exception) {
            return ErrorResponse::make([new DefaultError(
                (isset($exception->errorName) ? $exception->errorName : ''),
                $exception->getTitle(),
                $exception->getMessage(),
                null,
                $exception->getStatusCode(),
                ['exception' => $this->convertExceptionToArray($exception)],
            )], $exception->getStatusCode());
        });

        $this->renderable(function (ValidationException $validationException) {
            $defaultErrors = collect($validationException->validator->errors()->messages())
                ->map(function ($value, $key) {
                    return new DefaultError(
                        'VALIDATION_ERROR',
                        __('Validation error'),
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
     * @return \Illuminate\Http\JsonResponse
     */
    protected function prepareJsonResponse($request, Throwable $e)
    {
        $statusCode = $e instanceof HttpExceptionInterface ? $e->getStatusCode() : 500;

        return ErrorResponse::make([
            new DefaultError(
                'UNKNOWN_ERROR',
                empty($e->getMessage()) ? __('Unknown error') : $e->getMessage(),
                $e->getMessage(),
                $e,
                $statusCode,
            ),
        ], $statusCode);
    }

    /**
     * Overwrite `parent::map` to support union types.
     */
    public function map($from, $to = null)
    {
        try {
            parent::map($from, $to);
        } catch (RuntimeException) {
            if (is_callable($from) && is_null($to)) {
                $from = $this->firstClosureParameterTypes($to = $from);
            }

            if (! $to instanceof \Closure) {
                throw new \InvalidArgumentException('Invalid exception mapping.');
            }

            array_map(fn ($f) => parent::map($f, $to), $from);
        }
    }

    /**
     * getModelFromException
     *
     * Returns the model of the exception for various Laravel Exceptions.
     *
     * Currently support:
     *  - ModelNotFoundException
     *  - RelationNotFoundException
     *  - LazyLoadingViolationException
     */
    private function getModelFromException(ModelNotFoundException|RelationNotFoundException|LazyLoadingViolationException $exception): string
    {
        return match ($exception::class) {
            ModelNotFoundException::class => $exception->getModel(),
            default => $exception->model,
        };
    }
}
