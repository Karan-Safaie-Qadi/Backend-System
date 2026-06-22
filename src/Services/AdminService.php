<?php

namespace App\Services;

use App\Models\User;
use App\Models\ActivityLog;
use App\Models\Product;
use App\Models\Article;
use App\Auth\AccessControl;
use Database\Database;

class AdminService
{
    public static function getDashboardStats(): array
    {
        return [
            'users' => UserService::getStats(),
            'products' => ProductService::getStats(),
            'articles' => ArticleService::getStats(),
            'recent_activities' => ActivityLog::getRecent(20),
            'recent_users' => User::getRecentUsers(10),
            'recent_articles' => Article::getRecent(5),
            'low_stock_products' => Product::getLowStock(10),
        ];
    }

    public static function getAllUsers(int $page = 1, int $perPage = 20): array { return User::paginate($page, $perPage); }
    public static function getUser(int $id): array { return UserService::getUser($id); }

    public static function updateUser(int $id, array $data, int $actorId): array
    {
        return UserService::updateUser($id, $data, User::find($actorId)['access_level'] ?? 0);
    }

    public static function deleteUser(int $id, int $actorId): void
    {
        UserService::deleteUser($id, User::find($actorId)['access_level'] ?? 0);
    }

    public static function getAdmins(): array { return User::getAdmins(); }

    public static function addAdmin(int $userId, int $actorId): array
    {
        $actor = User::find($actorId);
        if (!$actor) throw new \RuntimeException('Actor not found.');
        AccessControl::requireLevel($actor['access_level'], 'owner');
        $user = User::find($userId);
        if (!$user) throw new \RuntimeException('User not found.');
        User::updateRecord($userId, ['access_level' => 2]);
        ActivityLog::log($actorId, 'add_admin', 'user', $userId, "Admin granted to '{$user['username']}'");
        return User::find($userId);
    }

    public static function removeAdmin(int $userId, int $actorId): array
    {
        $actor = User::find($actorId);
        if (!$actor) throw new \RuntimeException('Actor not found.');
        AccessControl::requireLevel($actor['access_level'], 'owner');
        $user = User::find($userId);
        if (!$user) throw new \RuntimeException('User not found.');
        if ($user['id'] === $actorId) throw new \RuntimeException('Cannot remove your own admin.');
        if ($user['access_level'] >= 3) throw new \RuntimeException('Cannot remove owner.');
        User::updateRecord($userId, ['access_level' => 1]);
        ActivityLog::log($actorId, 'remove_admin', 'user', $userId, "Admin removed from '{$user['username']}'");
        return User::find($userId);
    }

    public static function transferOwnership(int $userId, int $actorId): array
    {
        $actor = User::find($actorId);
        if (!$actor) throw new \RuntimeException('Actor not found.');
        AccessControl::requireLevel($actor['access_level'], 'owner');
        if ($actorId === $userId) throw new \RuntimeException('Already owner.');
        $newOwner = UserService::getUser($userId);
        User::updateRecord($actorId, ['access_level' => 2]);
        User::updateRecord($userId, ['access_level' => 3]);
        ActivityLog::log($actorId, 'transfer_ownership', 'user', $userId, "Ownership to '{$newOwner['username']}'");
        return User::find($userId);
    }

    public static function getActivityLogs(int $page = 1, int $perPage = 50): array
    {
        $total = ActivityLog::selectOne("SELECT COUNT(*) as c FROM activity_logs");
        $items = ActivityLog::getRecent($perPage);
        return ['items' => $items, 'total' => (int)($total['c'] ?? 0), 'page' => $page, 'per_page' => $perPage, 'total_pages' => ceil(($total['c'] ?? 0) / $perPage)];
    }

    public static function getSystemInfo(): array
    {
        $conn = null;
        try { $conn = Database::getConnection(); $v = $conn->query("SELECT VERSION() as v")->fetch(); } catch (\Exception $e) {}
        return [
            'php_version' => PHP_VERSION,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown',
            'database' => $conn ? ['connected' => true, 'version' => $v['v'] ?? 'unknown'] : ['connected' => false],
            'app_config' => [
                'registration_mode' => \App\Core\Config::get('auth.registration_mode'),
                'debug' => \App\Core\Config::get('app.debug'),
                'access_levels' => AccessControl::getLevels(),
            ],
        ];
    }
}
