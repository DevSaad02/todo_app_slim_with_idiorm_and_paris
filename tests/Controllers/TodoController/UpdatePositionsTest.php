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

class UpdatePositionsTest extends TestCase
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

    public function testUpdatePositions()
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

        // Mock the request data
        $request->shouldReceive('getBody')->andReturnSelf();
        $request->shouldReceive('getContents')->andReturn(json_encode([
            'order' => [
                ['id' => 1, 'position' => 2],
                ['id' => 2, 'position' => 1]
            ]
        ]));

        // Mock the static method find_one using Mockery
        $todoMock = Mockery::mock('alias:' . Todo::class);
        $todoMock->shouldReceive('find_one')->andReturn($todoMock);
        $todoMock->shouldReceive('save')->andReturn(true);

        // Mock the ORM transaction methods
        ORM::shouldReceive('get_db->beginTransaction')->once();
        ORM::shouldReceive('get_db->commit')->once();

        // Expect the logger to log the info message
        $this->logger->shouldReceive('info')
            ->once()
            ->with('Updating positions of todo items');

        // Expect the logger to log success
        $this->logger->shouldReceive('info')
            ->once()
            ->with('Positions of todo items updated successfully');

        // Call the updatePositions method
        $result = $this->controller->updatePositions($request, $response);

        // Assert the response status code and body
        $this->assertEquals(200, $result->getStatusCode());
        $this->assertJsonStringEqualsJsonString(json_encode([
            'status' => 'success',
            'message' => 'Positions updated'
        ]), (string)$result->getBody());
    }

    public function testUpdatePositionsInvalidRequest()
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
        $request->shouldReceive('getBody')->andReturnSelf();
        $request->shouldReceive('getContents')->andReturn(json_encode([
            'invalid' => 'data'
        ]));

        // Expect the logger to log the info message
        $this->logger->shouldReceive('info')
            ->once()
            ->with('Updating positions of todo items');

        // Expect the logger to log the warning message
        $this->logger->shouldReceive('warning')
            ->once()
            ->with('Invalid request for updating positions');

        // Call the updatePositions method
        $result = $this->controller->updatePositions($request, $response);

        // Assert the response status code and body
        $this->assertEquals(400, $result->getStatusCode());
        $this->assertJsonStringEqualsJsonString(json_encode([
            'status' => 'error',
            'message' => 'Invalid request'
        ]), (string)$result->getBody());
    }

    public function testUpdatePositionsException()
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
        $request->shouldReceive('getBody')->andReturnSelf();
        $request->shouldReceive('getContents')->andReturn(json_encode([
            'order' => [
                ['id' => 1, 'position' => 2],
                ['id' => 2, 'position' => 1]
            ]
        ]));

        // Mock the static method find_one to throw an exception
        $todoMock = Mockery::mock('alias:' . Todo::class);
        $todoMock->shouldReceive('find_one')->andThrow(new \Exception('Database error'));

        // Mock the ORM transaction methods
        ORM::shouldReceive('get_db->beginTransaction')->once();
        ORM::shouldReceive('get_db->rollBack')->once();

        // Expect the logger to log the info message
        $this->logger->shouldReceive('info')
            ->once()
            ->with('Updating positions of todo items');

        // Expect the logger to log the error message
        $this->logger->shouldReceive('error')
            ->once()
            ->with('An error occurred while updating positions of todo items', Mockery::on(function ($arg) {
                return isset($arg['exception']) && $arg['exception'] instanceof \Exception;
            }));

        // Call the updatePositions method
        $result = $this->controller->updatePositions($request, $response);

        // Assert the response status code and body
        $this->assertEquals(500, $result->getStatusCode());
        $this->assertJsonStringEqualsJsonString(json_encode([
            'status' => 'error',
            'message' => 'Error: Database error'
        ]), (string)$result->getBody());
    }
}
