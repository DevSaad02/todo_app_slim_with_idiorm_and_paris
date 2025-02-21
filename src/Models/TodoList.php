<?php
namespace App\Models;
use Model;
/**
 * Table name for Paris ORM
 * @var string
 * @property int $id
 * @property string $description
 * @property int $is_done
 * @property int $item_position
 * @property string $list_color
 */

class TodoList extends Model
{
     // Table name as a string
     public static $_table = 'todo_list';


     // Primary key column
     public static $_id_column = 'id';
}
