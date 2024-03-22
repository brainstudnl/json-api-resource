<?php

namespace Brainstud\JsonApi\Traits;

use Brainstud\JsonApi\Responses\ErrorResponse;
use Brainstud\JsonApi\Responses\Errors\DefaultError;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

/**
 * Add this trait to the FormRequest to respond with a JSON validation error on an invalid request
 */
trait JsonErrorResponseOnFailedValidation
{
    protected function failedValidation(Validator $validator)
    {
        $defaultErrors = [];
        $errors = $validator->errors()->messages();
        foreach ($errors as $key => $error) {
            $defaultErrors[] = new DefaultError(
                'VALIDATION_ERROR',
                'Validation error',
                $error[0],
                ['pointer' => $key]
            );
        }

        throw new ValidationException($validator, ErrorResponse::make($defaultErrors, Response::HTTP_UNPROCESSABLE_ENTITY));
    }
}
