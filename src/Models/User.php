<?php

namespace App\Models;

use App\Core\Model;

class User extends Model
{
    protected string $table = 'users';
    protected string $primaryKey = 'id';

    public static function findByUsername(string $username): ?array
    {
        return self::findBy('username', $username);
    }

    public static function findByEmail(string $email): ?array
    {
        return self::findBy('email', $email);
    }

    public static function findByPhone(string $phone): ?array
    {
        return self::findBy('phone', $phone);
    }

    public static function findByRememberToken(string $token): ?array
    {
        return self::findBy('remember_token', $token);
    }

    public static function findByPasswordResetToken(string $token): ?array
    {
        return self::selectOne(
            "SELECT * FROM users WHERE password_reset_token = ? AND password_reset_expires > NOW()",
            [$token]
        );
    }

    public static function getByAccessLevel(int $level): array
    {
        return self::where('access_level', $level);
    }

    public static function getAdmins(): array
    {
        return self::select("SELECT * FROM users WHERE access_level >= 2 ORDER BY access_level DESC");
    }

    public static function getRegularUsers(): array
    {
        return self::where('access_level', 1);
    }

    public static function searchUsers(string $query): array
    {
        return self::select(
            "SELECT * FROM users WHERE username LIKE ? OR email LIKE ? OR phone LIKE ? OR display_name LIKE ?",
            array_fill(0, 4, "%$query%")
        );
    }

    public static function updateLastLogin(int $id): void
    {
        self::updateRecord($id, ['last_login_at' => date('Y-m-d H:i:s')]);
    }

    public static function setRememberToken(int $id, string $token): void
    {
        self::updateRecord($id, ['remember_token' => $token]);
    }

    public static function clearRememberToken(int $id): void
    {
        self::updateRecord($id, ['remember_token' => null]);
    }

    public static function setPasswordResetToken(string $email, string $token): void
    {
        $user = self::findByEmail($email);
        if ($user) {
            self::updateRecord($user['id'], [
                'password_reset_token' => $token,
                'password_reset_expires' => date('Y-m-d H:i:s', time() + 3600),
            ]);
        }
    }

    public static function clearPasswordResetToken(int $id): void
    {
        self::updateRecord($id, ['password_reset_token' => null, 'password_reset_expires' => null]);
    }

    public static function updatePassword(int $id, string $hashedPassword): void
    {
        self::updateRecord($id, ['password' => $hashedPassword]);
    }

    public static function verifyEmail(int $id): void
    {
        self::updateRecord($id, ['email_verified_at' => date('Y-m-d H:i:s')]);
    }

    public static function verifyPhone(int $id): void
    {
        self::updateRecord($id, ['phone_verified_at' => date('Y-m-d H:i:s')]);
    }

    public static function isEmailVerified(array $user): bool
    {
        return $user['email_verified_at'] !== null;
    }

    public static function isPhoneVerified(array $user): bool
    {
        return $user['phone_verified_at'] !== null;
    }

    public static function isActive(array $user): bool
    {
        return (bool)($user['is_active'] ?? true);
    }

    public static function countByAccessLevel(int $level): int
    {
        $r = self::selectOne("SELECT COUNT(*) as c FROM users WHERE access_level = ?", [$level]);
        return (int)($r['c'] ?? 0);
    }

    public static function getRecentUsers(int $limit = 10): array
    {
        return self::select("SELECT * FROM users ORDER BY created_at DESC LIMIT ?", [$limit]);
    }
}
