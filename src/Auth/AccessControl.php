<?php

namespace App\Auth;

use App\Core\Config;

class AccessControl
{
    private static ?array $levels = null;

    public static function init(): void
    {
        self::$levels = Config::get('access_levels', [
            1 => 'user',
            2 => 'admin',
            3 => 'owner',
        ]);
    }

    public static function getLevels(): array
    {
        if (self::$levels === null) {
            self::init();
        }
        return self::$levels;
    }

    public static function getLevelName(int $level): string
    {
        $levels = self::getLevels();
        return $levels[$level] ?? 'unknown';
    }

    public static function getLevelValue(string $name): ?int
    {
        $levels = self::getLevels();
        $flipped = array_flip($levels);
        return $flipped[strtolower($name)] ?? null;
    }

    public static function hasAccess(int $userLevel, $requiredLevel): bool
    {
        if (is_string($requiredLevel)) {
            $requiredLevel = self::getLevelValue($requiredLevel);
        }

        if ($requiredLevel === null) {
            return false;
        }

        return $userLevel >= $requiredLevel;
    }

    public static function isAdmin(int $userLevel): bool
    {
        $adminLevel = self::getLevelValue('admin');
        return $userLevel >= ($adminLevel ?? 2);
    }

    public static function isOwner(int $userLevel): bool
    {
        $ownerLevel = self::getLevelValue('owner');
        return $userLevel >= ($ownerLevel ?? 3);
    }

    public static function canManageAdmins(int $userLevel): bool
    {
        return self::isOwner($userLevel);
    }

    public static function requireLevel(int $userLevel, $requiredLevel): void
    {
        if (!self::hasAccess($userLevel, $requiredLevel)) {
            throw new \RuntimeException('Access denied. Insufficient permissions.');
        }
    }
}
