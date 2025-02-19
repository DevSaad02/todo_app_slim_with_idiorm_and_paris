<?php

namespace Tests\Controllers\TodoController;

use App\Controllers\TodoController;
use App\Models\Todo;
use PHPUnit\Framework\TestCase;
use Slim\Views\Twig;
use Psr\Log\LoggerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Mockery;

class StoreTodoTest extends TestCase
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

    public function testStoreTodo()
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
        $response->shouldReceive('getStatusCode')->andReturn(201);
        $stream->shouldReceive('__toString')->andReturnUsing(function () use (&$bodyContent) {
            return $bodyContent;
        });

        // Mock the request data
        $request->shouldReceive('getParsedBody')->andReturn([
            'new-list-item-text' => 'Test Todo'
        ]);

        // Mock the static method max using Mockery
        $todoMock = Mockery::mock('alias:' . Todo::class);
        $todoMock->shouldReceive('max')->andReturn(1);

        // Mock the static method create using Mockery
        $todoMock->shouldReceive('create')->andReturn($todoMock);
        $todoMock->shouldReceive('save')->andReturn(true);
        $todoMock->shouldReceive('toArray')->andReturn([
            'id' => 1,
            'description' => 'Test Todo',
            'item_position' => 2,
            'is_done' => 0
        ]);

        // Explicitly set the id property
        $todoMock->id = 1;

        // Expect the logger to log the info message
        $this->logger->shouldReceive('info')
            ->once()
            ->with('Storing a new todo item');

        // Expect the logger to log success with todo_id
        $this->logger->shouldReceive('info')
            ->once()
            ->with('Todo item stored successfully', ['todo_id' => 1]);
            $this->logger->shouldReceive('error')
            ->zeroOrMoreTimes();
        // Call the store method
        $result = $this->controller->store($request, $response);

        // Assert the response status code and body
        $this->assertEquals(201, $result->getStatusCode());
        $this->assertJsonStringEqualsJsonString(json_encode([
            'status' => 'success',
            'message' => 'Item added successfully',
            'todo' => [
                'id' => 1,
                'description' => 'Test Todo',
                'item_position' => 2,
                'is_done' => 0
            ]
        ]), (string)$result->getBody());
    }

    public function testStoreTodoEmptyDescription()
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
        $response->shouldReceive('getStatusCode')->andReturn(400);
        $stream->shouldReceive('__toString')->andReturnUsing(function () use (&$bodyContent) {
            return $bodyContent;
        });

        // Mock the request data
        $request->shouldReceive('getParsedBody')->andReturn([
            'new-list-item-text' => ''
        ]);

        // Expect the logger to log the info message
        $this->logger->shouldReceive('info')
            ->once()
            ->with('Storing a new todo item');

        // Expect the logger to log the warning message
        $this->logger->shouldReceive('warning')
            ->once()
            ->with('Description cannot be empty');

        // Call the store method
        $result = $this->controller->store($request, $response);

        // Assert the response status code and body
        $this->assertEquals(400, $result->getStatusCode());
        $this->assertJsonStringEqualsJsonString(json_encode([
            'status' => 'error',
            'message' => 'Description cannot be empty'
        ]), (string)$result->getBody());
    }

    public function testStoreTodoException()
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

        // Mock the request data
        $request->shouldReceive('getParsedBody')->andReturn([
            'new-list-item-text' => 'Test Todo'
        ]);

        // Mock the static method max using Mockery
        $todoMock = Mockery::mock('alias:' . Todo::class);
        $todoMock->shouldReceive('max')->andThrow(new \Exception('Database error'));

        // Expect the logger to log the info message
        $this->logger->shouldReceive('info')
            ->once()
            ->with('Storing a new todo item');

        // Expect the logger to log the error message
        $this->logger->shouldReceive('error')
            ->once()
            ->with('Failed to store todo item', Mockery::on(function ($arg) {
                return isset($arg['exception']) && $arg['exception'] instanceof \Exception;
            }));

        // Call the store method
        $result = $this->controller->store($request, $response);

        // Assert the response status code and body
        $this->assertEquals(500, $result->getStatusCode());
        $this->assertJsonStringEqualsJsonString(json_encode([
            'status' => 'error',
            'message' => 'An error occurred: Database error'
        ]), (string)$result->getBody());
    }
}
