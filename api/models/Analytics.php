<?php
/**
 * Analytics Model
 */
class Analytics {
    private static function db(): PDO {
        return Database::getInstance();
    }

    public static function log(int $userId, string $action, ?string $details = null): bool {
        $stmt = self::db()->prepare('INSERT INTO analytics_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)');
        return $stmt->execute([
            $userId,
            $action,
            $details,
            $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'
        ]);
    }

    public static function getRecentActions(int $limit = 100): array {
        $stmt = self::db()->prepare('SELECT a.*, u.firstname, u.lastname FROM analytics_logs a LEFT JOIN users u ON a.user_id = u.id ORDER BY a.created_at DESC LIMIT ?');
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
}
