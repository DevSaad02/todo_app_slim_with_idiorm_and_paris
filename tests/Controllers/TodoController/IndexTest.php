<?php

namespace Tests\Controllers\TodoController;

use Mockery;
use Slim\Views\Twig;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;
use App\Controllers\TodoController;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class IndexTest extends TestCase
{
    protected $view;
    protected $logger;
    protected $controller;

    protected function setUp(): void
    {
        // Mock the Twig view using Mockery
        $this->view = Mockery::mock(Twig::class);

        // Mock the Logger
        $this->logger = Mockery::mock(LoggerInterface::class);

        // Instantiate the controller with the mocked dependencies
        $this->controller = new TodoController($this->view, $this->logger);
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testIndex()
    {
        // Mock the request and response
        $request = Mockery::mock(ServerRequestInterface::class);
        $response = Mockery::mock(ResponseInterface::class);

        // Expect the logger to log the info message
        $this->logger->shouldReceive('info')
            ->once()
            ->with('Rendering todo view');

        // Mock the view render method
        $this->view->shouldReceive('render')
            ->once()
            ->with($response, 'todo.html.twig')
            ->andReturn($response);

        // Call the index method
        $result = $this->controller->index($request, $response);

        // Assert the response
        $this->assertSame($response, $result);
    }

    public function testIndexException()
    {
        // Mock the request and response
        $request = Mockery::mock(ServerRequestInterface::class);
        $response = Mockery::mock(ResponseInterface::class);
        $stream = Mockery::mock(StreamInterface::class);

        $response->shouldReceive('getBody')->andReturn($stream);
        $stream->shouldReceive('write')->andReturnUsing(function ($string) use (&$bodyContent) {
            $bodyContent = $string;
            return strlen($string);
        });
        $response->shouldReceive('withHeader')->andReturnSelf();
        $response->shouldReceive('withStatus')->andReturnSelf();
        $response->shouldReceive('getStatusCode')->andReturn(500);
        $stream->shouldReceive('__toString')->andReturnUsing(function () use (&$bodyContent) {
            return $bodyContent;
        });

        // Expect the logger to log the info message
        $this->logger->shouldReceive('info')
            ->once()
            ->with('Rendering todo view');

        // Mock the view render method to throw an exception
        $this->view->shouldReceive('render')
            ->once()
            ->with($response, 'todo.html.twig')
            ->andThrow(new \Exception('Rendering error'));

        // Expect the logger to log the error message
        $this->logger->shouldReceive('error')
            ->once()
            ->with('An error occurred while rendering todo view', Mockery::on(function ($arg) {
                return isset($arg['exception']) && $arg['exception'] instanceof \Exception;
            }));

        // Call the index method
        $result = $this->controller->index($request, $response);

        // Assert the response status code and body
        $this->assertEquals(500, $result->getStatusCode());
        $this->assertJsonStringEqualsJsonString(json_encode([
            'status' => 'error',
            'message' => 'An error occurred: Rendering error'
        ]), (string)$result->getBody());
    }
}
