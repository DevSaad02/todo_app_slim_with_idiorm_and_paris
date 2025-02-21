<?php
namespace App\Controllers;

use ORM;
use Model;
use Slim\Views\Twig;
use Psr\Log\LoggerInterface;
use App\Services\ArrayConversionService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class TodoController extends HomeController
{
    protected $view;
    protected $logger;
    private $arrayConversionService;

    public function __construct(Twig $view, LoggerInterface $logger, ArrayConversionService $arrayConversionService)
    {
        $this->view = $view;
        $this->logger = $logger;
        $this->arrayConversionService = $arrayConversionService;
    }

    public function index(Request $request, Response $response)
    {
        $this->logger->info("Rendering todo view");
        try{
        return $this->view->render($response, 'todo.html.twig');
        }catch(\Exception $e){
            $this->logger->error("An error occurred while rendering todo view", ['exception' => $e]);
            $data = [
                'status' => 'error',
                'message' => 'An error occurred: ' . $e->getMessage()
            ];
            return $this->response($response, $data, 500);
        }
    }

    public function getTodos(Request $request, Response $response)
    {
        $this->logger->info("Fetching all todos");
        try {
            $todos = Model::factory('TodoList')->find_many(); // Fetch all todos from database
            $data = [
                'status' => 'success',
                'message' => 'All Todos List',
                'todos' => $this->arrayConversionService->convertCollectionToArray($todos)
            ];
            $this->logger->info("Todos fetched successfully", ['todos_count' => count($todos)]);
            return $this->response($response, $data);
        } catch (\Exception $e) {
            $this->logger->error("Failed to fetch todos", ['exception' => $e]);
            $data = [
                'status' => 'error',
                'message' => 'Failed to fetch todos: ' . $e->getMessage()
            ];
            return $this->response($response, $data, 500);
        }
    }

    public function store(Request $request, Response $response)
    {
        $this->logger->info("Storing a new todo item");
        try {
            $data = $request->getParsedBody();
            $description = trim($data['new-list-item-text'] ?? '');

            // Validate input
            if (empty($description)) {
                $this->logger->warning("Description cannot be empty");
                $data = [
                    'status' => 'error',
                    'message' => 'Description cannot be empty'
                ];
                return $this->response($response, $data, 400);
            }

            // Get the max position from the existing records
            $maxPosition = Model::factory('TodoList')->max('item_position') ?? 0;
            $newPosition = $maxPosition + 1;

            // Create a new Todo item using ORM
            $todo = Model::factory('TodoList')->create();
            $todo->description = $description;
            $todo->item_position = $newPosition;
            $todo->save();

            $data = [
                'status' => 'success',
                'message' => 'Item added successfully',
                'todo' => $todo ? $this->arrayConversionService->convertToArray($todo) : null
            ];
            $this->logger->info("Todo item stored successfully", ['todo_id' => $todo->id]);
            return $this->response($response, $data, 201);
        } catch (\Exception $e) {
            $this->logger->error("Failed to store todo item", ['exception' => $e]);
            $data = [
                'status' => 'error',
                'message' => 'An error occurred: ' . $e->getMessage()
            ];
            return $this->response($response, $data, 500);
        }
    }

    public function update(Request $request, Response $response, $id)
    {
        $this->logger->info("Updating todo item", ['todo_id' => $id]);
        try {
            // Get request data
            $data = $request->getParsedBody();
            $newText = trim($data['description'] ?? '');

            // Validate input
            if (empty($newText)) {
                $this->logger->warning("Description cannot be empty");
                $data = [
                    'status' => 'error',
                    'message' => 'Description cannot be empty'
                ];
                return $this->response($response, $data, 400);
            }
            if (empty($id)) {
                $this->logger->warning("Id cannot be empty");
                $data = [
                    'status' => 'error',
                    'message' => 'Id cannot be empty'
                ];
                return $this->response($response, $data, 400);
            }
            if (is_array($id)) {
                $id = reset($id); // Get the first value of the array
                $id = (int) $id;
            }

            // Find the todo item
            $todo = Model::factory('TodoList')->find_one($id);
            if (!$todo) {
                $this->logger->warning("Todo not found", ['todo_id' => $id]);
                $data = [
                    'status' => 'error',
                    'message' => 'Todo not found'
                ];
                return $this->response($response, $data, 404);
            }

            // Update the description
            $todo->description = $newText;
            if ($todo->save()) {
                $data = [
                    'status' => 'success',
                    'message' => 'Item updated successfully',
                    'todo' => $todo ? $this->arrayConversionService->convertToArray($todo) : null
                ];
                $this->logger->info("Todo item updated successfully", ['todo_id' => $id]);
                return $this->response($response, $data, 200);
            } else {
                $this->logger->error("Failed to update todo item", ['todo_id' => $id]);
                $data = [
                    'status' => 'error',
                    'message' => 'Failed to update item'
                ];
                return $this->response($response, $data, 500);
            }
        } catch (\Exception $e) {
            $this->logger->error("An error occurred while updating todo item", ['exception' => $e]);
            $data = [
                'status' => 'error',
                'message' => 'An error occurred: ' . $e->getMessage()
            ];
            return $this->response($response, $data, 500);
        }
    }

    public function markDone(Request $request, Response $response, $id)
    {
        $this->logger->info("Marking todo item as done", ['todo_id' => $id]);
        try {
            // Check id
            if (empty($id)) {
                $this->logger->warning("Id cannot be empty");
                $data = [
                    'status' => 'error',
                    'message' => 'Id cannot be empty'
                ];
                return $this->response($response, $data, 400);
            }

            if (is_array($id)) {
                $id = reset($id); // Get the first value of the array
                $id = (int) $id;
            }

            // Find the todo item
            $todo = Model::factory('TodoList')->find_one($id);
            if (!$todo) {
                $this->logger->warning("Todo not found", ['todo_id' => $id]);
                $data = [
                    'status' => 'error',
                    'message' => 'Todo not found'
                ];
                return $this->response($response, $data, 404);
            }

            // Mark as done
            $todo->is_done = 1;
            if ($todo->save()) {
                $data = [
                    'status' => 'success',
                    'message' => 'Item marked as done'
                ];
                $this->logger->info("Todo item marked as done", ['todo_id' => $id]);
                return $this->response($response, $data, 200);
            } else {
                $this->logger->error("Failed to mark todo item as done", ['todo_id' => $id]);
                $data = [
                    'status' => 'error',
                    'message' => 'Failed to update'
                ];
                return $this->response($response, $data, 500);
            }
        } catch (\Exception $e) {
            $this->logger->error("An error occurred while marking todo item as done", ['exception' => $e]);
            $data = [
                'status' => 'error',
                'message' => 'An error occurred: ' . $e->getMessage()
            ];
            return $this->response($response, $data, 500);
        }
    }

    public function updateColor(Request $request, Response $response, $id)
    {
        $this->logger->info("Updating color of todo item", ['todo_id' => $id]);
        try {
            if (empty($id)) {
                $this->logger->warning("Id cannot be empty");
                $data = [
                    'status' => 'error',
                    'message' => 'Id cannot be empty'
                ];
                return $this->response($response, $data, 400);
            }

            if (is_array($id)) {
                $id = reset($id); // Get the first value of the array
                $id = (int) $id;
            }

            $data = $request->getParsedBody();
            $color = trim($data['color'] ?? '');

            if (empty($color)) {
                $this->logger->warning("Color cannot be empty");
                $data = [
                    'status' => 'error',
                    'message' => 'Color cannot be empty'
                ];
                return $this->response($response, $data, 400);
            }

            $todo = Model::factory('TodoList')->find_one($id);
            if (!$todo) {
                $this->logger->warning("Todo not found", ['todo_id' => $id]);
                $data = [
                    'status' => 'error',
                    'message' => 'Todo not found'
                ];
                return $this->response($response, $data, 404);
            }

            $todo->list_color = $color;
            if ($todo->save()) {
                $data = [
                    'status' => 'success',
                    'message' => 'Color updated successfully'
                ];
                $this->logger->info("Color of todo item updated successfully", ['todo_id' => $id]);
                return $this->response($response, $data, 200);
            } else {
                $this->logger->error("Failed to update color of todo item", ['todo_id' => $id]);
                $data = [
                    'status' => 'error',
                    'message' => 'Failed to update color'
                ];
                return $this->response($response, $data, 500);
            }
        } catch (\Exception $e) {
            $this->logger->error("An error occurred while updating color of todo item", ['exception' => $e]);
            $data = [
                'status' => 'error',
                'message' => 'An error occurred: ' . $e->getMessage()
            ];
            return $this->response($response, $data, 500);
        }
    }

    public function updatePositions(Request $request, Response $response)
    {
        $this->logger->info("Updating positions of todo items");
        try {
            $data = json_decode($request->getBody()->getContents(), true);
            if (!isset($data["order"]) || !is_array($data["order"])) {

                $this->logger->warning("Invalid request for updating positions");
                $data = [
                    'status' => 'error',
                    'message' => 'Invalid request'
                ];
                return $this->response($response, $data, 400);
            }

            // Start transaction
            ORM::get_db()->beginTransaction();

            // Process items in batches
            $batchSize = 100;
            $batches = array_chunk($data["order"], $batchSize);

            foreach ($batches as $batch) {
                $ids = array_column($batch, 'id');
                $todos = Model::factory('TodoList')->where_in('id', $ids)->find_many();

                foreach ($batch as $item) {
                    $id = (int)($item["id"] ?? 0);
                    $position = (int)($item["position"] ?? 0);

                    if ($id > 0 && isset($todos[$id])) {
                        $todo = $todos[$id];
                        $todo->item_position = $position;
                        $todo->save();
                    }
                }
            }

            // Commit transaction
            ORM::get_db()->commit();

            $data = [
                'status' => 'success',
                'message' => 'Positions updated'
            ];
            $this->logger->info("Positions of todo items updated successfully");
            return $this->response($response, $data, 200);
        } catch (\Exception $e) {
            // Rollback transaction on error
            ORM::get_db()->rollBack();
            $this->logger->error("An error occurred while updating positions of todo items", ['exception' => $e]);
            $data = [
                'status' => 'error',
                'message' => 'Error: ' . $e->getMessage()
            ];
            return $this->response($response, $data, 500);
        }
    }

    public function delete(Request $request, Response $response, $id)
    {
        $this->logger->info("Deleting todo item", ['todo_id' => $id]);
        try {
            if (is_array($id)) {
                $id = reset($id);
                $id = (int) $id;
            }

            ORM::get_db()->beginTransaction(); // Start transaction

            // Find the todo item
            $todo = Model::factory('TodoList')->find_one($id);
            if (!$todo) {
                $this->logger->warning("Todo item not found", ['todo_id' => $id]);
                return $this->response($response, [
                    'status' => 'error',
                    'message' => 'Item not found'
                ], 404);
            }

            // Get the deleted item's position
            $deletedPosition = $todo->item_position;

            // Delete the item
            if (!$todo->delete()) {
                throw new \Exception("Error deleting task");
            }

            // Shift positions down for items that had a higher position than the deleted item
            $todos = Model::factory('TodoList')->where_gt('item_position', $deletedPosition)->find_many();
            foreach ($todos as $item) {
                $item->item_position -= 1;
                $item->save();
            }

            ORM::get_db()->commit(); // Commit transaction

            $this->logger->info("Todo item deleted and positions updated", ['todo_id' => $id]);
            return $this->response($response, [
                'status' => 'success',
                'message' => 'Task deleted and positions updated'
            ], 200);
        } catch (\Exception $e) {
            ORM::get_db()->rollBack(); // Rollback on error
            $this->logger->error("An error occurred while deleting todo item", ['exception' => $e]);
            return $this->response($response, [
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
        
    }
}
