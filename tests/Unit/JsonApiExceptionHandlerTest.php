<?php

namespace Brainstud\JsonApi\Tests\Unit;

use Brainstud\JsonApi\Exceptions\JsonApiHttpException;
use Brainstud\JsonApi\Exceptions\PaymentRequiredJsonApiException;
use Brainstud\JsonApi\Handlers\JsonApiExceptionHandler;
use Brainstud\JsonApi\Tests\Models\TestNotFoundException;
use Brainstud\JsonApi\Tests\TestCase;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Validation\Factory;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class JsonApiExceptionHandlerTest extends TestCase 
{
    private Container $container;
    private JsonApiExceptionHandler $handler;

    public function setup():void 
    {
        parent::setUp();
        $this->container = Container::getInstance();
        $this->handler = new JsonApiExceptionHandler($this->container);
    }

    private function makeJsonRequest()
    {
        return Request::create('/', server: ["HTTP_ACCEPT" => "application/json"]);
    }

    public function testJsonApiHttpException()
    {
        $request = $this->makeJsonRequest();
        $exception = new JsonApiHttpException(
            "title",
            400,
            "message"
        );

        $response = $this->handler->render($request, $exception);

        $errorContent = $this->parseErrorResponse($response)[0];

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals('title', $errorContent->title);
        $this->assertEquals('message', $errorContent->detail);
    }

    public function testJsonApiHttpExceptionImplementationWithDefaults()
    {
        $request = $this->makeJsonRequest();
        $exception = new PaymentRequiredJsonApiException();

        $response = $this->handler->render($request, $exception);

        $errorContent = $this->parseErrorResponse($response)[0];

        $this->assertEquals(402, $response->getStatusCode());
        $this->assertEquals("A payment is required to access the resource.", $errorContent->detail);
        $this->assertEquals("Payment Required", $errorContent->title);
    }

    public function testModelNotFoundException()
    {
        $request = $this->makeJsonRequest();
        $exception = (new ModelNotFoundException())->setModel("TestModel");

        $response = $this->handler->render($request, $exception);

        $errorContent = $this->parseErrorResponse($response)[0];

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals("No query results for model [TestModel].", $errorContent->detail);
        $this->assertEquals("TestModel Not Found", $errorContent->title);
    }

    public function testGenericExceptionResultsInInternalServerError()
    {
        $request = $this->makeJsonRequest();
        $exception = new \Exception("An error message");

        $response = $this->handler->render($request, $exception);

        $errorContent = $this->parseErrorResponse($response)[0];
        ray($response);

        $this->assertEquals("500", $response->getStatusCode());
        $this->assertEquals('An error message', $errorContent->title);
        $this->assertEquals('An error message', $errorContent->detail);
    }

    public function testValidationException()
    {
        $request = $this->makeJsonRequest();
        $fac = $this->container->make(Factory::class);
        $val = $fac->make([], [], [], []);
        $val->errors()->add("field", "isInvalidMessage");

        $exception = new ValidationException($val);

        $response = $this->handler->render($request, $exception);

        $errorContent = $this->parseErrorResponse($response);

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertEquals($errorContent->field->source->pointer, "field");
        $this->assertEquals($errorContent->field->detail, "isInvalidMessage");
    }
    
    /**
     * ParseErrorResponse
     * 
     * Parses an error response into the code, title and detail.
     * 
     * @param Response $response 
    //  * @return array<?string> 
     */
    private function parseErrorResponse(Response $response): mixed
    {
        $content = json_decode($response->getContent());
        $error = $content->errors;

        return $error;
    }
}