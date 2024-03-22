<?php

namespace Brainstud\JsonApi\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class JsonApiHttpException extends HttpException implements JsonApiExceptionInterface
{
    protected string $title = "Json API Error";
    /** The name of the error as used in the application. */
    protected ?string $errorName;

    public function __construct(string $title, int $statusCode, string $message = '', ?\Throwable $previous = null, array $headers = [], int $code = 0){
        $this->title = $title;

        parent::__construct($statusCode, $message, $previous, $headers, $code);
    }

    public function getTitle(): string 
    {
        return $this->title;
    }

    /**
     * withErrorName
     * 
     * Sets the errorName attribute on the object.
     * Returns the object after setting the name.
     */
    public function withErrorName(string $name): self
    {
        $this->errorName = $name;

        return $this;
    }
}

