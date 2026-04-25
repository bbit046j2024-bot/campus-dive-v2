<?php
/**
 * RecruitmentLetter Model
 */
class RecruitmentLetter {
    private static function db(): PDO {
        return Database::getInstance();
    }

    public static function getByUserId(int $userId): array {
        $stmt = self::db()->prepare('SELECT * FROM recruitment_letters WHERE user_id = ? ORDER BY sent_at DESC');
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public static function create(int $userId, string $content, int $sentBy): int|false {
        $stmt = self::db()->prepare('INSERT INTO recruitment_letters (user_id, letter_content, sent_by) VALUES (?, ?, ?)');
        $stmt->execute([$userId, $content, $sentBy]);
        return self::db()->lastInsertId();
    }
}
