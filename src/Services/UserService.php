<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Auth\AccessControl;

class UserService
{
    public static function getUser(int $id): array
    {
        $user = User::find($id);
        if (!$user) throw new \RuntimeException('User not found.');
        return $user;
    }

    public static function getByUsername(string $username): ?array: ?array { return User::findByUsername($username); }
    public static function getByEmail(string $email): ?array: ?array { return User::findByEmail($email); }
    public static function getByPhone(string $phone): ?array: ?array { return User::findByPhone($phone); }

    public static function getAllUsers(int $page = 1, int $perPage = 20): array
    {
        return User::paginate($page, $perPage);
    }

    public static function searchUsers(string $query): array
    {
        return User::searchUsers($query);
    }

    public static function createUser(array $data, int $actorLevel): array
    {
        if (isset($data['access_level']) && $data['access_level'] > 1) {
            AccessControl::requireLevel($actorLevel, 'owner');
        }
        if (User::findByUsername($data['username'])) throw new \InvalidArgumentException('Username already exists.');

        $userData = [
            'username' => $data['username'],
            'password' => password_hash($data['password'] ?? bin2hex(random_bytes(8)), PASSWORD_DEFAULT),
            'display_name' => $data['display_name'] ?? $data['username'],
            'access_level' => $data['access_level'] ?? 1,
            'is_active' => $data['is_active'] ?? 1,
        ];
        foreach (['email', 'phone', 'avatar'] as $f) {
            if (!empty($data[$f])) $userData[$f] = $data[$f];
        }
        return User::find(User::create($userData));
    }

    public static function updateUser(int $id, array $data, int $actorLevel): array
    {
        $user = self::getUser($id);
        if (isset($data['access_level']) && $data['access_level'] >= 2 && $user['access_level'] >= 2) {
            AccessControl::requireLevel($actorLevel, 'owner');
        }
        $update = [];
        foreach (['display_name', 'email', 'phone', 'avatar', 'is_active', 'access_level'] as $f) {
            if (isset($data[$f])) $update[$f] = $data[$f];
        }
        if ($update) User::updateRecord($id, $update);
        return User::find($id);
    }

    public static function deleteUser(int $id, int $actorLevel): void
    {
        $user = self::getUser($id);
        if ($user['access_level'] >= 2) AccessControl::requireLevel($actorLevel, 'owner');
        if ($id === $actorLevel) throw new \RuntimeException('You cannot delete yourself.');
        User::deleteRecord($id);
    }

    public static function getAdmins(): array
    {
        return User::getAdmins();
    }

    public static function getStats(): array
    {
        return [
            'total' => User::count(),
            'users' => User::countByAccessLevel(1),
            'admins' => User::countByAccessLevel(2) + User::countByAccessLevel(3),
            'owners' => User::countByAccessLevel(3),
            'active_today' => count(User::where('last_login_at', date('Y-m-d') . '%', 'LIKE')),
        ];
    }
}
