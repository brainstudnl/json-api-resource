<?php

namespace Brainstud\JsonApi\Tests\Unit;

use Brainstud\JsonApi\Exceptions\JsonApiHttpException;
use Brainstud\JsonApi\Exceptions\MethodNotAllowedJsonApiException;
use Brainstud\JsonApi\Exceptions\PaymentRequiredJsonApiException;
use Brainstud\JsonApi\Handlers\JsonApiExceptionHandler;
use Brainstud\JsonApi\Tests\TestCase;
use ErrorException;
use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Validation\Factory;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class JsonApiExceptionHandlerTest extends TestCase
{
    private Container $container;

    private JsonApiExceptionHandler $handler;

    protected function setup(): void
    {
        parent::setUp();
        $this->container = Container::getInstance();
        $this->handler = new JsonApiExceptionHandler($this->container);
    }

    private function makeJsonRequest()
    {
        return Request::create('/', server: ['HTTP_ACCEPT' => 'application/json']);
    }

    public function test_json_api_http_exception()
    {
        $request = $this->makeJsonRequest();
        $exception = new JsonApiHttpException(
            'title',
            400,
            'message'
        );

        $response = $this->handler->render($request, $exception);

        $errorContent = $this->parseErrorResponse($response)[0];

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals('title', $errorContent->title);
        $this->assertEquals('message', $errorContent->detail);
    }

    public function test_json_api_http_exception_implementation_with_defaults()
    {
        $request = $this->makeJsonRequest();
        $exception = new PaymentRequiredJsonApiException;

        $response = $this->handler->render($request, $exception);

        $errorContent = $this->parseErrorResponse($response)[0];

        $this->assertEquals(402, $response->getStatusCode());
        $this->assertEquals('A payment is required to access this information.', $errorContent->detail);
        $this->assertEquals('Payment required', $errorContent->title);
    }

    public function test_json_api_http_exception_implementation_with_translations()
    {
        App::setLocale('nl');
        $request = $this->makeJsonRequest();
        $exception = new MethodNotAllowedJsonApiException;

        $response = $this->handler->render($request, $exception);

        $errorContent = $this->parseErrorResponse($response)[0];

        $this->assertEquals(405, $response->getStatusCode());
        $this->assertEquals('Actie niet toegestaan', $errorContent->title);
        $this->assertEquals(
            'Deze actie ('.request()->method().') is niet ondersteund voor '.request()->path().'.',
            $errorContent->detail
        );
    }

    public function test_model_not_found_exception()
    {
        $request = $this->makeJsonRequest();
        $exception = (new ModelNotFoundException)->setModel('TestModel');

        $response = $this->handler->render($request, $exception);

        $errorContent = $this->parseErrorResponse($response)[0];

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals('No query results for model [TestModel].', $errorContent->detail);
        $this->assertEquals('TestModel not found', $errorContent->title);
    }

    public function test_generic_exception_results_in_internal_server_error()
    {
        $request = $this->makeJsonRequest();
        $exception = new \Exception('An error message');

        $response = $this->handler->render($request, $exception);

        $errorContent = $this->parseErrorResponse($response)[0];

        $this->assertEquals('500', $response->getStatusCode());
        $this->assertEquals('An error message', $errorContent->title);
        $this->assertEquals('An error message', $errorContent->detail);
    }

    public function test_validation_exception()
    {
        $request = $this->makeJsonRequest();
        $fac = $this->container->make(Factory::class);
        $val = $fac->make([], [], [], []);
        $val->errors()->add('field', 'isInvalidMessage');

        $exception = new ValidationException($val);

        $response = $this->handler->render($request, $exception);

        $errorContent = $this->parseErrorResponse($response);

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertEquals($errorContent[0]->source->pointer, 'field');
        $this->assertEquals($errorContent[0]->detail, 'isInvalidMessage');
    }

    public function test_error_response_has_meta_filled_with_debug_true()
    {
        Config::set('app.debug', true);
        $request = $this->makeJsonRequest();
        $exception = new PaymentRequiredJsonApiException;

        $response = $this->handler->render($request, $exception);

        $errorContent = $this->parseErrorResponse($response)[0];

        $this->assertNotNull($errorContent->meta->exception->message);
        $this->assertNotNull($errorContent->meta->exception->file);
    }

    public function test_error_response_has_meta_filled_with_debug_false()
    {
        Config::set('app.debug', false);
        $request = $this->makeJsonRequest();
        $exception = new PaymentRequiredJsonApiException;

        $response = $this->handler->render($request, $exception);

        $errorContent = $this->parseErrorResponse($response)[0];

        $this->assertNotNull($errorContent->meta->exception->message);

        // Since it does not exist on the parsed response, an ErrorException('property does not exist') is thrown.
        $this->assertThrows(fn () => $errorContent->meta->exception->file, ErrorException::class);
    }

    /**
     * ParseErrorResponse
     *
     * Parses an error response into the code, title and detail.
     */
    private function parseErrorResponse(Response $response): mixed
    {
        $content = json_decode($response->getContent());
        $error = $content->errors;

        return $error;
    }
}
