<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class ActivityLog extends Model
{
    protected string $table = 'activity_logs';
    protected string $primaryKey = 'id';
    protected bool $useTimestamps = false;

    public static function log(?int $userId, string $action, ?string $entityType = null, ?int $entityId = null, ?string $description = null): int|string
    {
        return self::create([
            'user_id' => $userId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'description' => $description,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
        ]);
    }

    public static function getByUser(int $userId, int $limit = 50): array
    {
        return self::select("SELECT * FROM activity_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT ?", [$userId, $limit]);
    }

    public static function getByEntity(string $entityType, int $entityId, int $limit = 50): array
    {
        return self::select("SELECT * FROM activity_logs WHERE entity_type = ? AND entity_id = ? ORDER BY created_at DESC LIMIT ?", [$entityType, $entityId, $limit]);
    }

    public static function getRecent(int $limit = 50): array
    {
        return self::select("SELECT al.*, u.username, u.display_name FROM activity_logs al LEFT JOIN users u ON al.user_id = u.id ORDER BY al.created_at DESC LIMIT ?", [$limit]);
    }

    public static function getByAction(string $action, int $limit = 50): array
    {
        return self::select("SELECT * FROM activity_logs WHERE action = ? ORDER BY created_at DESC LIMIT ?", [$action, $limit]);
    }

    public static function countToday(): int
    {
        $r = self::selectOne("SELECT COUNT(*) as c FROM activity_logs WHERE DATE(created_at) = CURDATE()");
        return (int)($r['c'] ?? 0);
    }

    public static function cleanOld(int $days = 90): int
    {
        $stmt = self::execute("DELETE FROM activity_logs WHERE created_at < NOW() - INTERVAL ? DAY", [$days]);
        return $stmt->rowCount();
    }
}
