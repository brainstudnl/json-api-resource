<?php


namespace Brainstud\Packages\JsonApi\Traits;


use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Brainstud\Packages\JsonApi\Responses\ErrorResponse;
use Brainstud\Packages\JsonApi\Responses\Errors\DefaultError;

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
