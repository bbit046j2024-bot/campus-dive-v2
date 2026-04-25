<?php
/**
 * Notification Model
 */
class Notification {
    private static function db(): PDO {
        return Database::getInstance();
    }

    public static function getByUserId(int $userId, int $limit = 20): array {
        $stmt = self::db()->prepare('SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?');
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll();
    }

    public static function create(int $userId, string $title, string $message, string $type = 'info'): int|false {
        $stmt = self::db()->prepare('INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)');
        $stmt->execute([$userId, $title, $message, $type]);
        return self::db()->lastInsertId();
    }

    public static function markAsRead(int $id, int $userId): bool {
        $stmt = self::db()->prepare('UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?');
        $stmt->execute([$id, $userId]);
        return $stmt->rowCount() > 0;
    }

    public static function markAllAsRead(int $userId): bool {
        $stmt = self::db()->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0');
        $stmt->execute([$userId]);
        return true;
    }

    public static function getUnreadCount(int $userId): int {
        $stmt = self::db()->prepare('SELECT COUNT(*) as c FROM notifications WHERE user_id = ? AND is_read = 0');
        $stmt->execute([$userId]);
        return $stmt->fetch()['c'];
    }
}
