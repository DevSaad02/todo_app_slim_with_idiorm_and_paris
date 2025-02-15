<?php
namespace App\Models;
use Model;
/**
 * @property int $id
 * @property string $description
 * @property int $is_done
 * @property int $item_position
 * @property string $list_color
 */

class Todo extends Model
{
    public static $_table_use_short_name = true;

    public static $_table = 'todo_list';

    public static $_id_column = 'id';

       // Convert a single record to an array
       public function toArray(): array
       {
           return $this->as_array();
       }
   
       // Convert a collection of records to an array
       public static function toCollectionArray($records): array
       {
           return array_map(fn($record) => $record->toArray(), $records);
       }
}
