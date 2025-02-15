<?php

namespace App\Controllers;

use App\Models\Todo;
use Slim\Views\Twig;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use ORM;


class TodoController extends HomeController
{
    protected $view;

    public function __construct(Twig $view)
    {
        $this->view = $view;
    }

    public function index(Request $request, Response $response)
    {
        return $this->view->render($response, 'todo.html.twig');
    }

    public function getTodos(Request $request, Response $response)
    {
        $todos = Todo::find_many(); //Fetch all todos from database
        $data = [
            'status' => 'success',
            'message' => 'All Todos List',
            'todos' => Todo::toCollectionArray($todos)
        ];
        return $this->response($response, $data);
    }

    public function store(Request $request, Response $response)
    {
        $data = $request->getParsedBody();

        $description = trim($data['new-list-item-text'] ?? '');

        // Validate input
        if (empty($description)) {
            $data = [
                'status' => 'error',
                'message' => 'Description cannot be empty'
            ];
            return $this->response($response, $data, 400);
        }

        // Get the max position from the existing records
        $maxPosition = Todo::max('item_position') ?? 0;
        $newPosition = $maxPosition + 1;

        // Create a new Todo item using ORM
        $todo = Todo::create();

        $todo->description = $description;
        $todo->item_position = $newPosition;
        $todo->save();

        $data = [
            'status' => 'success',
            'message' => 'Item added successfully',
            'todo' => $todo ? $todo->toArray() : null
        ];
        return $this->response($response, $data, 201);
    }

    public function update(Request $request, Response $response, $id)
    {
        // Get request data
        $data = $request->getParsedBody();
        $newText = trim($data['description'] ?? '');
        // Validate input
        if (empty($newText)) {
            $data = [
                'status' => 'error',
                'message' => 'Description cannot be empty'
            ];
            return $this->response($response, $data, 400);
        }
        if (empty($id)) {
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
        $todo = Todo::find_one($id);
  
        if (!$todo) {
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
                'todo' => $todo ? $todo->toArray() : null
            ];
            return $this->response($response, $data, 200);
        } else {
            $data = [
                'status' => 'error',
                'message' => 'Failed to update item'
            ];
            return $this->response($response, $data, 500);
        }
    }

    public function markDone(Request $request, Response $response, $id)
    {
        // check id
        if (empty($id)) {
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
        $todo = Todo::find_one($id);

        if (!$todo) {
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
            return $this->response($response, $data, 200);
        } else {
            $data = [
                'status' => 'error',
                'message' => 'Failed to update'
            ];
            return $this->response($response, $data, 500);
        }
    }

    public function updateColor(Request $request, Response $response, $id)
    {
        if (empty($id)) {
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
            $data = [
                'status' => 'error',
                'message' => 'Color cannot be empty'
            ];
            return $this->response($response, $data, 400);
        }

        $todo = Todo::find_one($id);

        if (!$todo) {
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
            return $this->response($response, $data, 200);
        } else {
            $data = [
                'status' => 'error',
                'message' => 'Failed to update color'
            ];
            return $this->response($response, $data, 500);
        }
    }

    public function updatePositions(Request $request, Response $response)
    {
        $data = json_decode($request->getBody()->getContents(), true);
        if (!isset($data["order"]) || !is_array($data["order"])) {
            $data = [
                'status' => 'error',
                'message' => 'Invalid request'
            ];
            return $this->response($response, $data, 400);
        }

        try {
            // Start transaction
            ORM::get_db()->beginTransaction();

            foreach ($data["order"] as $item) {
                $id = (int)($item["id"] ?? 0);
                $position = (int)($item["position"] ?? 0);
    
                if ($id > 0) {
                    $todo = Todo::find_one($id);
                    if ($todo) {
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
            return $this->response($response, $data, 200);
        } catch (\Exception $e) {
            // Rollback transaction on error
            ORM::get_db()->rollBack();

            $data = [
                'status' => 'error',
                'message' => 'Error: ' . $e->getMessage()
            ];
            return $this->response($response, $data, 500);
        }
    }

    public function delete(Request $request, Response $response, $id)
    {
        try {
            if(is_array($id)){
                $id = reset($id);
                $id = (int) $id;
            }
            ORM::get_db()->beginTransaction(); // Start transaction
            // Find the todo item
            $todo = Todo::find_one($id);
    
            if (!$todo) {
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
            $todos = Todo::where_gt('item_position', $deletedPosition)->find_many();
            foreach ($todos as $item) {
                $item->item_position -= 1;
                $item->save();
            }
    
            ORM::get_db()->commit(); // Commit transaction
    
            return $this->response($response, [
                'status' => 'success',
                'message' => 'Task deleted and positions updated'
            ], 200);
        } catch (\Exception $e) {
            ORM::get_db()->rollBack(); // Rollback on error
    
            return $this->response($response, [
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
        
    }
}
