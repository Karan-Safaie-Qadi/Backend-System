<?php

namespace App\Services;

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
        if (!isset(self::$customMethods[$name])) throw new \RuntimeException("Method '$name' not registered.");
        return call_user_func_array(self::$customMethods[$name], $args);
    }

    public static function hasMethod(string $name): bool { return isset(self::$customMethods[$name]); }
    public static function getRegisteredMethods(): array { return array_keys(self::$customMethods); }
    public static function customQuery(string $sql, array $params = []): array { return Database::select($sql, $params); }

    public static function customExecute(string $sql, array $params = []): int
    {
        return Database::execute($sql, $params)->rowCount();
    }

    public static function runTransaction(callable $callback): bool
    {
        try {
            Database::beginTransaction();
            $callback();
            Database::commit();
            return true;
        } catch (\Exception $e) {
            Database::rollBack();
            throw $e;
        }
    }

    public static function getConfig(string $key, $default = null) { return Config::get($key, $default); }
    public static function setConfig(string $key, $value): void { Config::set($key, $value); }

    public static function generateSlug(string $text, string $sep = '-'): string
    {
        return trim(preg_replace('/-+/', $sep, preg_replace('/[^a-zA-Z0-9\-]/', $sep, strtolower(trim($text)))), $sep);
    }

    public static function sanitizeInput(string $input): string
    {
        return strip_tags(trim($input));
    }

    public static function generateToken(int $length = 32): string { return bin2hex(random_bytes($length)); }
    public static function dateFormat(string $date, string $format = 'Y-m-d H:i:s'): string { return date($format, strtotime($date)); }

    public static function timeAgo(string $datetime): string
    {
        $diff = time() - strtotime($datetime);
        if ($diff < 60) return 'just now';
        if ($diff < 3600) return floor($diff / 60) . 'm ago';
        if ($diff < 86400) return floor($diff / 3600) . 'h ago';
        if ($diff < 2592000) return floor($diff / 86400) . 'd ago';
        return floor($diff / 2592000) . 'mo ago';
    }

    public static function uploadFile(array $file, string $directory, array $allowedTypes = []): string
    {
        if ($file['error'] !== UPLOAD_ERR_OK) throw new \RuntimeException('Upload error.');
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($allowedTypes && !in_array($ext, $allowedTypes)) throw new \InvalidArgumentException("Type '$ext' not allowed.");
        $dir = __DIR__ . '/../../public/uploads/' . trim($directory, '/');
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $dest = $dir . '/' . uniqid() . '.' . $ext;
        if (!move_uploaded_file($file['tmp_name'], $dest)) throw new \RuntimeException('Failed to move file.');
        return 'uploads/' . trim($directory, '/') . '/' . basename($dest);
    }

    public static function deleteFile(string $path): bool
    {
        $full = __DIR__ . '/../../public/' . ltrim($path, '/');
        return file_exists($full) ? unlink($full) : false;
    }

    public static function jsonResponse($data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    public static function paginateRaw(string $table, int $page = 1, int $perPage = 20, string $where = '', array $params = []): array
    {
        $offset = ($page - 1) * $perPage;
        $wc = $where ? "WHERE $where" : '';
        $total = Database::selectOne("SELECT COUNT(*) as c FROM `$table` $wc", $params);
        $items = Database::select("SELECT * FROM `$table` $wc LIMIT $perPage OFFSET $offset", $params);
        return ['items' => $items, 'total' => (int)($total['c'] ?? 0), 'page' => $page, 'per_page' => $perPage, 'total_pages' => ceil(($total['c'] ?? 0) / $perPage)];
    }
}
