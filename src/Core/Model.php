<?php

declare(strict_types=1);

namespace App\Core;

abstract class Model extends \Models\Model
{
    protected bool $useTimestamps = true;
    protected string $createdAtColumn = 'created_at';
    protected string $updatedAtColumn = 'updated_at';

    public static function findBy(string $column, $value): ?array
    {
        $table = (new static())->table;
        return self::selectOne("SELECT * FROM `$table` WHERE `$column` = ? LIMIT 1", [$value]);
    }

    public static function findByMultiple(array $conditions, string $operator = 'AND'): ?array
    {
        $table = (new static())->table;
        $where = [];
        $params = [];
        foreach ($conditions as $col => $val) {
            if ($val === null) {
                $where[] = "`$col` IS NULL";
            } else {
                $where[] = "`$col` = ?";
                $params[] = $val;
            }
        }
        return self::selectOne("SELECT * FROM `$table` WHERE " . implode(" $operator ", $where) . " LIMIT 1", $params);
    }

    public static function where(string $column, $value, string $operator = '='): array
    {
        $table = (new static())->table;
        if ($value === null && in_array(strtoupper($operator), ['IS', 'IS NOT'], true)) {
            return self::select("SELECT * FROM `$table` WHERE `$column` $operator NULL");
        }
        return self::select("SELECT * FROM `$table` WHERE `$column` $operator ?", [$value]);
    }

    public static function whereMultiple(array $conditions, string $operator = 'AND'): array
    {
        $table = (new static())->table;
        $where = [];
        $params = [];
        foreach ($conditions as $col => $val) {
            $where[] = "`$col` = ?";
            $params[] = $val;
        }
        return self::select("SELECT * FROM `$table` WHERE " . implode(" $operator ", $where), $params);
    }

    public static function count(): int
    {
        $result = self::selectOne("SELECT COUNT(*) as count FROM `" . (new static())->table . "`");
        return (int)($result['count'] ?? 0);
    }

    public static function exists(int $id): bool
    {
        return static::find($id) !== null;
    }

    public static function paginate(int $page = 1, int $perPage = 20): array
    {
        $table = (new static())->table;
        $offset = ($page - 1) * $perPage;
        $total = self::selectOne("SELECT COUNT(*) as count FROM `$table`");
        $items = self::select("SELECT * FROM `$table` LIMIT $perPage OFFSET $offset");
        return [
            'items' => $items,
            'total' => (int)($total['count'] ?? 0),
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil(($total['count'] ?? 0) / $perPage),
        ];
    }

    public static function latest(string $column = 'id'): array
    {
        return self::select("SELECT * FROM `" . (new static())->table . "` ORDER BY `$column` DESC");
    }

    public static function oldest(string $column = 'id'): array
    {
        return self::select("SELECT * FROM `" . (new static())->table . "` ORDER BY `$column` ASC");
    }

    public static function search(string $column, string $query): array
    {
        $table = (new static())->table;
        return self::select("SELECT * FROM `$table` WHERE `$column` LIKE ?", ["%$query%"]);
    }

    public static function pluck(string $column): array
    {
        $results = self::select("SELECT `$column` FROM `" . (new static())->table . "`");
        return array_column($results, $column);
    }

    public static function create(array $data): int|string
    {
        $instance = new static();
        if ($instance->useTimestamps) {
            $now = date('Y-m-d H:i:s');
            $data[$instance->createdAtColumn] ??= $now;
            $data[$instance->updatedAtColumn] ??= $now;
        }
        return self::insert($instance->table, $data);
    }

    public static function updateRecord(int $id, array $data): int
    {
        $instance = new static();
        if ($instance->useTimestamps) {
            $data[$instance->updatedAtColumn] ??= date('Y-m-d H:i:s');
        }
        return self::update($instance->table, $data, [$instance->primaryKey => $id]);
    }

    public static function updateWhere(array $data, array $where): int
    {
        $instance = new static();
        if ($instance->useTimestamps) {
            $data[$instance->updatedAtColumn] ??= date('Y-m-d H:i:s');
        }
        return self::update($instance->table, $data, $where);
    }

    public static function deleteWhere(array $where): int
    {
        return self::delete((new static())->table, $where);
    }
}
