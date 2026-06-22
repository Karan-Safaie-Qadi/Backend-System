<?php

namespace App\Services;

use App\Core\Model;
use App\Core\Config;
use Database\Database;

class SystemService
{
    private static array $customMethods = [];

    public static function registerMethod(string $name, callable $callback): void
    {
        self::$customMethods[$name] = $callback;
    }

    public static function callMethod(string $name, ...$args)
    {
        if (!isset(self::$customMethods[$name])) {
            throw new \RuntimeException("Custom method '$name' not registered.");
        }

        return call_user_func_array(self::$customMethods[$name], $args);
    }

    public static function hasMethod(string $name): bool
    {
        return isset(self::$customMethods[$name]);
    }

    public static function getRegisteredMethods(): array
    {
        return array_keys(self::$customMethods);
    }

    public static function customQuery(string $sql, array $params = []): array
    {
        return Database::select($sql, $params);
    }

    public static function customExecute(string $sql, array $params = []): int
    {
        $stmt = Database::execute($sql, $params);
        return $stmt->rowCount();
    }

    public static function runTransaction(callable $callback): bool
    {
        try {
            Database::beginTransaction();
            $result = $callback();
            Database::commit();
            return true;
        } catch (\Exception $e) {
            Database::rollBack();
            throw $e;
        }
    }

    public static function getConfig(string $key, $default = null)
    {
        return Config::get($key, $default);
    }

    public static function setConfig(string $key, $value): void
    {
        Config::set($key, $value);
    }

    public static function generateSlug(string $text, string $separator = '-'): string
    {
        $slug = preg_replace('/[^a-zA-Z0-9\-]/', $separator, strtolower(trim($text)));
        $slug = preg_replace('/-+/', $separator, $slug);
        return trim($slug, $separator);
    }

    public static function sanitizeInput(string $input): string
    {
        return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
    }

    public static function generateToken(int $length = 32): string
    {
        return bin2hex(random_bytes($length));
    }

    public static function dateFormat(string $date, string $format = 'Y-m-d H:i:s'): string
    {
        return date($format, strtotime($date));
    }

    public static function timeAgo(string $datetime): string
    {
        $timestamp = strtotime($datetime);
        $diff = time() - $timestamp;

        if ($diff < 60) return 'just now';
        if ($diff < 3600) return floor($diff / 60) . ' minutes ago';
        if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
        if ($diff < 2592000) return floor($diff / 86400) . ' days ago';
        if ($diff < 31536000) return floor($diff / 2592000) . ' months ago';
        return floor($diff / 31536000) . ' years ago';
    }

    public static function uploadFile(array $file, string $directory, array $allowedTypes = []): string
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('File upload error.');
        }

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!empty($allowedTypes) && !in_array($extension, $allowedTypes)) {
            throw new \InvalidArgumentException("File type '$extension' not allowed.");
        }

        $uploadDir = __DIR__ . '/../../public/uploads/' . trim($directory, '/');
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $filename = uniqid() . '.' . $extension;
        $destination = $uploadDir . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new \RuntimeException('Failed to move uploaded file.');
        }

        return 'uploads/' . trim($directory, '/') . '/' . $filename;
    }

    public static function deleteFile(string $path): bool
    {
        $fullPath = __DIR__ . '/../../public/' . ltrim($path, '/');
        if (file_exists($fullPath)) {
            return unlink($fullPath);
        }
        return false;
    }

    public static function jsonResponse($data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    public static function paginateRaw(string $table, int $page = 1, int $perPage = 20,
                                       string $where = '', array $params = []): array
    {
        $offset = ($page - 1) * $perPage;
        $whereClause = $where ? "WHERE $where" : '';

        $total = Database::selectOne("SELECT COUNT(*) as count FROM `$table` $whereClause", $params);
        $totalCount = (int)($total['count'] ?? 0);

        $items = Database::select(
            "SELECT * FROM `$table` $whereClause LIMIT $perPage OFFSET $offset",
            $params
        );

        return [
            'items' => $items,
            'total' => $totalCount,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($totalCount / $perPage),
        ];
    }
}
