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
    // Use short table name
    public static $_table_use_short_name = true;

    // Table name
    public const TABLE_NAME = 'todo_list';
    public static $_table = self::TABLE_NAME;

    // Primary key column
    public const ID_COLUMN = 'id';
    public static $_id_column = self::ID_COLUMN;

    /**
     * Convert a single record to an array
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->as_array();
    }

    /**
     * Convert a collection of records to an array
     *
     * @param array $records
     * @return array
     */
    public static function toCollectionArray(array $records): array
    {
        return array_map(fn($record) => $record->toArray(), $records);
    }
}
