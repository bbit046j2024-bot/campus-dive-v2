<?php
/**
 * MarketingTemplate Model
 */
class MarketingTemplate {
    private static function db(): PDO {
        return Database::getInstance();
    }

    public static function getAll(): array {
        $stmt = self::db()->prepare('SELECT * FROM marketing_templates ORDER BY created_at DESC');
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function findById(int $id): ?array {
        $stmt = self::db()->prepare('SELECT * FROM marketing_templates WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function create(array $data): int|false {
        $stmt = self::db()->prepare('INSERT INTO marketing_templates (name, subject, body_content, type) VALUES (?, ?, ?, ?)');
        $stmt->execute([
            $data['name'],
            $data['subject'] ?? null,
            $data['body_content'],
            $data['type'] ?? 'email'
        ]);
        return self::db()->lastInsertId();
    }
}
