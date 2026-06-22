<?php

namespace App\Services;

use App\Models\User;
use App\Models\ActivityLog;
use App\Models\Product;
use App\Models\Article;
use App\Auth\AccessControl;

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

    public static function getAllUsers(int $page = 1, int $perPage = 20): array
    {
        return User::paginate($page, $perPage);
    }

    public static function getUser(int $id): array
    {
        return UserService::getUser($id);
    }

    public static function updateUser(int $id, array $data, int $actorId): array
    {
        $actor = User::find($actorId);
        if (!$actor) {
            throw new \RuntimeException('Actor not found.');
        }

        return UserService::updateUser($id, $data, $actor['access_level']);
    }

    public static function deleteUser(int $id, int $actorId): void
    {
        $actor = User::find($actorId);
        if (!$actor) {
            throw new \RuntimeException('Actor not found.');
        }

        UserService::deleteUser($id, $actor['access_level']);
    }

    public static function getAdmins(): array
    {
        return User::getAdmins();
    }

    public static function addAdmin(int $userId, int $actorId): array
    {
        $actor = User::find($actorId);
        if (!$actor) {
            throw new \RuntimeException('Actor not found.');
        }

        AccessControl::requireLevel($actor['access_level'], 'owner');

        $user = User::find($userId);
        if (!$user) {
            throw new \RuntimeException('User not found.');
        }

        User::updateRecord($userId, ['access_level' => 2]);

        ActivityLog::log($actorId, 'add_admin', 'user', $userId, "Admin privileges granted to '{$user['username']}'");

        return User::find($userId);
    }

    public static function removeAdmin(int $userId, int $actorId): array
    {
        $actor = User::find($actorId);
        if (!$actor) {
            throw new \RuntimeException('Actor not found.');
        }

        AccessControl::requireLevel($actor['access_level'], 'owner');

        $user = User::find($userId);
        if (!$user) {
            throw new \RuntimeException('User not found.');
        }

        if ($user['id'] === $actorId) {
            throw new \RuntimeException('You cannot remove your own admin privileges.');
        }

        if ($user['access_level'] >= 3) {
            throw new \RuntimeException('Cannot remove owner privileges.');
        }

        User::updateRecord($userId, ['access_level' => 1]);

        ActivityLog::log($actorId, 'remove_admin', 'user', $userId, "Admin privileges removed from '{$user['username']}'");

        return User::find($userId);
    }

    public static function transferOwnership(int $userId, int $actorId): array
    {
        $actor = User::find($actorId);
        if (!$actor) {
            throw new \RuntimeException('Actor not found.');
        }

        AccessControl::requireLevel($actor['access_level'], 'owner');

        if ($actorId === $userId) {
            throw new \RuntimeException('You are already the owner.');
        }

        $newOwner = UserService::getUser($userId);
        if (!$newOwner) {
            throw new \RuntimeException('Target user not found.');
        }

        User::updateRecord($actorId, ['access_level' => 2]);
        User::updateRecord($userId, ['access_level' => 3]);

        ActivityLog::log($actorId, 'transfer_ownership', 'user', $userId,
            "Ownership transferred to '{$newOwner['username']}'");

        return User::find($userId);
    }

    public static function getActivityLogs(int $page = 1, int $perPage = 50): array
    {
        $offset = ($page - 1) * $perPage;

        $total = ActivityLog::selectOne("SELECT COUNT(*) as count FROM activity_logs");
        $totalCount = (int)($total['count'] ?? 0);

        $items = ActivityLog::getRecent($perPage);

        return [
            'items' => $items,
            'total' => $totalCount,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($totalCount / $perPage),
        ];
    }

    public static function getSystemInfo(): array
    {
        return [
            'php_version' => PHP_VERSION,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown',
            'database' => self::getDatabaseInfo(),
            'app_config' => [
                'registration_mode' => \App\Core\Config::get('auth.registration_mode'),
                'debug' => \App\Core\Config::get('app.debug'),
                'access_levels' => AccessControl::getLevels(),
            ],
        ];
    }

    private static function getDatabaseInfo(): array
    {
        try {
            $conn = \Database\Database::getConnection();
            $stmt = $conn->query("SELECT VERSION() as version");
            $row = $stmt->fetch();
            return [
                'connected' => true,
                'version' => $row['version'] ?? 'unknown',
            ];
        } catch (\Exception $e) {
            return [
                'connected' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
