<?php
/**
 * ApplicationStage Model
 */
class ApplicationStage {
    private static function db(): PDO {
        return Database::getInstance();
    }

    public static function getByUserId(int $userId): array {
        $stmt = self::db()->prepare('SELECT * FROM application_stages WHERE user_id = ? ORDER BY entered_at ASC');
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public static function create(int $userId, string $stageName): bool {
        $stmt = self::db()->prepare('INSERT INTO application_stages (user_id, stage_name) VALUES (?, ?)');
        return $stmt->execute([$userId, $stageName]);
    }

    public static function getLatestStage(int $userId): ?array {
        $stmt = self::db()->prepare('SELECT * FROM application_stages WHERE user_id = ? ORDER BY entered_at DESC LIMIT 1');
        $stmt->execute([$userId]);
        return $stmt->fetch() ?: null;
    }
}
