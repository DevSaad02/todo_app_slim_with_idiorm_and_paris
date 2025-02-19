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
use ORM;

class DeleteTest extends TestCase
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
        parent::tearDown();
    }

    public function testDeleteTodo()
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

        // Clear any existing mock for Todo class
        if (class_exists('Mock_Todo_' . md5(Todo::class))) {
            Mockery::close();
        }

        // Mock the static method find_one using Mockery
        $todoMock = Mockery::mock('alias:' . Todo::class);
        $todoMock->shouldReceive('find_one')->andReturn($todoMock);
        $todoMock->shouldReceive('delete')->andReturn(true);

        // Expect the logger to log messages
        $this->logger->shouldReceive('info')
            ->once()
            ->with('Deleting todo item', ['todo_id' => 1]);

        $this->logger->shouldReceive('info')
            ->once()
            ->with('Todo item deleted successfully', ['todo_id' => 1]);

        // Allow any error logs (to prevent test failures from unexpected error logs)
        $this->logger->shouldReceive('error')
            ->zeroOrMoreTimes();

        // Call the delete method
        $result = $this->controller->delete($request, $response, 1);

        // Assert the response status code and body
        $this->assertEquals(200, $result->getStatusCode());
        $this->assertJsonStringEqualsJsonString(json_encode([
            'status' => 'success',
            'message' => 'Item deleted successfully'
        ]), (string)$result->getBody());
    }

    public function testDeleteTodoNotFound()
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
        $response->shouldReceive('getStatusCode')->andReturn(404);
        $stream->shouldReceive('__toString')->andReturnUsing(function () use (&$bodyContent) {
            return $bodyContent;
        });

        // Mock the static method find_one using Mockery
        $todoMock = Mockery::mock('alias:' . Todo::class);
        $todoMock->shouldReceive('find_one')->andReturn(null);

        // Expect the logger to log the info message
        $this->logger->shouldReceive('info')
            ->once()
            ->with('Deleting todo item', ['todo_id' => 1]);

        // Expect the logger to log the warning message
        $this->logger->shouldReceive('warning')
            ->once()
            ->with('Todo not found', ['todo_id' => 1]);

        // Call the delete method
        $result = $this->controller->delete($request, $response, 1);

        // Assert the response status code and body
        $this->assertEquals(404, $result->getStatusCode());
        $this->assertJsonStringEqualsJsonString(json_encode([
            'status' => 'error',
            'message' => 'Todo not found'
        ]), (string)$result->getBody());
    }

    public function testDeleteTodoException()
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

        // Mock the static method find_one to throw an exception
        $todoMock = Mockery::mock('alias:' . Todo::class);
        $todoMock->shouldReceive('find_one')->andThrow(new \Exception('Database error'));

        // Expect the logger to log the info message
        $this->logger->shouldReceive('info')
            ->once()
            ->with('Deleting todo item', ['todo_id' => 1]);

        // Expect the logger to log the error message
        $this->logger->shouldReceive('error')
            ->once()
            ->with('An error occurred while deleting todo item', Mockery::on(function ($arg) {
                return isset($arg['exception']) && $arg['exception'] instanceof \Exception;
            }));

        // Call the delete method
        $result = $this->controller->delete($request, $response, 1);

        // Assert the response status code and body
        $this->assertEquals(500, $result->getStatusCode());
        $this->assertJsonStringEqualsJsonString(json_encode([
            'status' => 'error',
            'message' => 'An error occurred: Database error'
        ]), (string)$result->getBody());
    }
}

