<?php

namespace App\Auth;

use App\Core\Config;

class AccessControl
{
    private static ?array $levels = null;

    public static function init(): void
    {
        self::$levels = Config::get('access_levels', [
            1 => 'user', 2 => 'admin', 3 => 'owner',
        ]);
    }

    public static function getLevels(): array
    {
        if (self::$levels === null) self::init();
        return self::$levels;
    }

    public static function getLevelName(int $level): string
    {
        return self::getLevels()[$level] ?? 'unknown';
    }

    public static function getLevelValue(string $name): ?int
    {
        return array_flip(self::getLevels())[strtolower($name)] ?? null;
    }

    public static function hasAccess(int $userLevel, $requiredLevel): bool
    {
        if (is_string($requiredLevel)) $requiredLevel = self::getLevelValue($requiredLevel);
        return $requiredLevel !== null && $userLevel >= $requiredLevel;
    }

    public static function isAdmin(int $userLevel): bool
    {
        return $userLevel >= (self::getLevelValue('admin') ?? 2);
    }

    public static function isOwner(int $userLevel): bool
    {
        return $userLevel >= (self::getLevelValue('owner') ?? 3);
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
