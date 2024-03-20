<?php

namespace Brainstud\JsonApi\Tests\Unit;

use Brainstud\JsonApi\Handlers\JsonApiExceptionHandler;
use Brainstud\JsonApi\Tests\Models\TestNotFoundException;
use Brainstud\JsonApi\Tests\TestCase;
use Illuminate\Container\Container;
use Symfony\Component\HttpFoundation\Response;
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

    public function testNoAbstractImplementationUsesStdTitleAndDetail(): void
    {
        $request = $this->get('/');
        $exception = new NotFoundHttpException();

        $response = $this->handler->render($request, $exception);

        list($code, $title, $detail) = $this->parseErrorResponse($response);
        
        $this->assertEquals("404", $code);
        $this->assertEquals("Not Found", $title);
        $this->assertEquals("The requested resource could not be found.", $detail);
    }

    public function testAbstractImplemetation() 
    {
        $request = $this->get('/');
        $exception = new TestNotFoundException('With a custom message');

        $response = $this->handler->render($request, $exception);

        list($code, $title, $detail) = $this->parseErrorResponse($response);

        $this->assertEquals("404", $code);
        $this->assertEquals($exception->getTitle(), $title);
        $this->assertEquals($exception->getMessage(), $detail);
    }

    public function testEmptyMessageUsesBaseMessage()
    {
        $request = $this->get('/');
        $exception = new TestNotFoundException();

        $response = $this->handler->render($request, $exception);

        list($code, $title, $detail) = $this->parseErrorResponse($response);

        $this->assertEquals("404", $code);
        $this->assertEquals($exception->getTitle(), $title);
        $this->assertEquals("The requested resource could not be found.", $detail);
    }

    public function testGenericExceptionResultsInInternalServerError()
    {
        $request = $this->get('/');
        $exception = new \Exception();

        $response = $this->handler->render($request, $exception);

        list($code, $title, $detail) = $this->parseErrorResponse($response);

        $this->assertEquals("500", $code);
        $this->assertEquals('Internal Server Error', $title);
        $this->assertEquals('', $detail);
    }
    
    /**
     * ParseErrorResponse
     * 
     * Parses an error response into the code, title and detail.
     * 
     * @param Response $response 
     * @return array<?string> 
     */
    private function parseErrorResponse(Response $response): array
    {
        $content = json_decode($response->getContent());
        $error = $content->errors[0];

        ray($error);

        return [$error->status, $error->title, $error->detail ?? null];
    }
}