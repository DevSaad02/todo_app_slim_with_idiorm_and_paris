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

class GetTodosTest extends TestCase
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

    public function testGetTodos()
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
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $stream->shouldReceive('__toString')->andReturnUsing(function () use (&$bodyContent) {
            return $bodyContent;
        });

        // Mock the static method find_many using Mockery
        $todoMock = Mockery::mock('alias:' . Todo::class);
        $todoMock->shouldReceive('find_many')->andReturn([
            (object)['id' => 1, 'description' => 'Test Todo 1', 'item_position' => 1, 'is_done' => 0],
            (object)['id' => 2, 'description' => 'Test Todo 2', 'item_position' => 2, 'is_done' => 0]
        ]);

        // Mock the toCollectionArray method
        $todoMock->shouldReceive('toCollectionArray')->andReturn([
            ['id' => 1, 'description' => 'Test Todo 1', 'item_position' => 1, 'is_done' => 0],
            ['id' => 2, 'description' => 'Test Todo 2', 'item_position' => 2, 'is_done' => 0]
        ]);

        // Expect the logger to log the info message
        $this->logger->shouldReceive('info')
            ->once()
            ->with('Fetching all todos');

        // Expect the logger to log success with count
        $this->logger->shouldReceive('info')
            ->once()
            ->with('Todos fetched successfully', ['todos_count' => 2]);

        // Call the getTodos method
        $result = $this->controller->getTodos($request, $response);

        // Assert the response status code and body
        $this->assertEquals(200, $result->getStatusCode());
        $this->assertJsonStringEqualsJsonString(json_encode([
            'status' => 'success',
            'message' => 'All Todos List',
            'todos' => [
                ['id' => 1, 'description' => 'Test Todo 1', 'item_position' => 1, 'is_done' => 0],
                ['id' => 2, 'description' => 'Test Todo 2', 'item_position' => 2, 'is_done' => 0]
            ]
        ]), (string)$result->getBody());
    }

    public function testGetTodosEmptyList()
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
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $stream->shouldReceive('__toString')->andReturnUsing(function () use (&$bodyContent) {
            return $bodyContent;
        });

        // Mock the static method find_many using Mockery
        $todoMock = Mockery::mock('alias:' . Todo::class);
        $todoMock->shouldReceive('find_many')->andReturn([]);

        // Mock the toCollectionArray method
        $todoMock->shouldReceive('toCollectionArray')->andReturn([]);

        // Expect the logger to log the info message
        $this->logger->shouldReceive('info')
            ->once()
            ->with('Fetching all todos');

        // Expect the logger to log success with count
        $this->logger->shouldReceive('info')
            ->once()
            ->with('Todos fetched successfully', ['todos_count' => 0]);

        // Call the getTodos method
        $result = $this->controller->getTodos($request, $response);

        // Assert the response status code and body
        $this->assertEquals(200, $result->getStatusCode());
        $this->assertJsonStringEqualsJsonString(json_encode([
            'status' => 'success',
            'message' => 'All Todos List',
            'todos' => []
        ]), (string)$result->getBody());
    }

    public function testGetTodosException()
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

        // Mock the static method find_many to throw an exception
        $todoMock = Mockery::mock('alias:' . Todo::class);
        $todoMock->shouldReceive('find_many')->andThrow(new \Exception('Database error'));

        // Expect the logger to log the info message
        $this->logger->shouldReceive('info')
            ->once()
            ->with('Fetching all todos');

        // Expect the logger to log the error message
        $this->logger->shouldReceive('error')
            ->once()
            ->with('Failed to fetch todos', Mockery::on(function ($arg) {
                return isset($arg['exception']) && $arg['exception'] instanceof \Exception;
            }));

        // Call the getTodos method
        $result = $this->controller->getTodos($request, $response);

        // Assert the response status code and body
        $this->assertEquals(500, $result->getStatusCode());
        $this->assertJsonStringEqualsJsonString(json_encode([
            'status' => 'error',
            'message' => 'Failed to fetch todos: Database error'
        ]), (string)$result->getBody());
    }
}
