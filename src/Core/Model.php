<?php

namespace App\Core;

class Model extends \Models\Model
{
    protected bool $useTimestamps = true;
    protected string $createdAtColumn = 'created_at';
    protected string $updatedAtColumn = 'updated_at';

    public static function findBy(string $column, $value): ?array
    {
        $instance = new static();
        $table = $instance->table;
        return self::selectOne("SELECT * FROM `$table` WHERE `$column` = ? LIMIT 1", [$value]);
    }

    public static function findByMultiple(array $conditions, string $operator = 'AND'): ?array
    {
        $instance = new static();
        $table = $instance->table;
        $where = [];
        $params = [];

        foreach ($conditions as $column => $value) {
            $where[] = "`$column` = ?";
            $params[] = $value;
        }

        $whereClause = implode(" $operator ", $where);
        return self::selectOne("SELECT * FROM `$table` WHERE $whereClause LIMIT 1", $params);
    }

    public static function where(string $column, $value, string $operator = '='): array
    {
        $instance = new static();
        $table = $instance->table;
        return self::select("SELECT * FROM `$table` WHERE `$column` $operator ?", [$value]);
    }

    public static function whereMultiple(array $conditions, string $operator = 'AND'): array
    {
        $instance = new static();
        $table = $instance->table;
        $where = [];
        $params = [];

        foreach ($conditions as $column => $value) {
            $where[] = "`$column` = ?";
            $params[] = $value;
        }

        $whereClause = implode(" $operator ", $where);
        return self::select("SELECT * FROM `$table` WHERE $whereClause", $params);
    }

    public static function count(): int
    {
        $instance = new static();
        $table = $instance->table;
        $result = self::selectOne("SELECT COUNT(*) as count FROM `$table`");
        return (int)($result['count'] ?? 0);
    }

    public static function exists(int $id): bool
    {
        return static::find($id) !== null;
    }

    public static function paginate(int $page = 1, int $perPage = 20): array
    {
        $instance = new static();
        $table = $instance->table;
        $offset = ($page - 1) * $perPage;

        $total = self::selectOne("SELECT COUNT(*) as count FROM `$table`");
        $totalCount = (int)($total['count'] ?? 0);

        $items = self::select("SELECT * FROM `$table` LIMIT $perPage OFFSET $offset");

        return [
            'items' => $items,
            'total' => $totalCount,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($totalCount / $perPage),
        ];
    }

    public static function latest(string $column = 'id'): array
    {
        $instance = new static();
        $table = $instance->table;
        return self::select("SELECT * FROM `$table` ORDER BY `$column` DESC");
    }

    public static function oldest(string $column = 'id'): array
    {
        $instance = new static();
        $table = $instance->table;
        return self::select("SELECT * FROM `$table` ORDER BY `$column` ASC");
    }

    public static function search(string $column, string $query): array
    {
        $instance = new static();
        $table = $instance->table;
        return self::select("SELECT * FROM `$table` WHERE `$column` LIKE ?", ["%$query%"]);
    }

    public static function pluck(string $column): array
    {
        $instance = new static();
        $table = $instance->table;
        $results = self::select("SELECT `$column` FROM `$table`");
        return array_column($results, $column);
    }

    public static function create(array $data): int|string
    {
        $instance = new static();

        if ($instance->useTimestamps) {
            $now = date('Y-m-d H:i:s');
            if (!isset($data[$instance->createdAtColumn])) {
                $data[$instance->createdAtColumn] = $now;
            }
            if (!isset($data[$instance->updatedAtColumn])) {
                $data[$instance->updatedAtColumn] = $now;
            }
        }

        $table = $instance->table;
        return self::insert($table, $data);
    }

    public static function updateRecord(int $id, array $data): int
    {
        $instance = new static();

        if ($instance->useTimestamps && !isset($data[$instance->updatedAtColumn])) {
            $data[$instance->updatedAtColumn] = date('Y-m-d H:i:s');
        }

        $table = $instance->table;
        $pk = $instance->primaryKey;
        return self::update($table, $data, [$pk => $id]);
    }

    public static function updateWhere(array $data, array $where): int
    {
        $instance = new static();

        if ($instance->useTimestamps && !isset($data[$instance->updatedAtColumn])) {
            $data[$instance->updatedAtColumn] = date('Y-m-d H:i:s');
        }

        $table = $instance->table;
        return self::update($table, $data, $where);
    }

    public static function deleteWhere(array $where): int
    {
        $instance = new static();
        $table = $instance->table;
        return self::delete($table, $where);
    }
}
