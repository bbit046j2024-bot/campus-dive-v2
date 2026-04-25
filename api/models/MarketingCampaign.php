<?php
/**
 * MarketingCampaign Model
 */
class MarketingCampaign {
    private static function db(): PDO {
        return Database::getInstance();
    }

    public static function getAll(): array {
        $stmt = self::db()->prepare('SELECT c.*, t.name as template_name FROM marketing_campaigns c JOIN marketing_templates t ON c.template_id = t.id ORDER BY c.created_at DESC');
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function create(array $data): int|false {
        $stmt = self::db()->prepare('
            INSERT INTO marketing_campaigns (name, template_id, type, segment_criteria, scheduled_at, status, created_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $data['name'],
            $data['template_id'],
            $data['type'] ?? 'email',
            json_encode($data['segment_criteria'] ?? []),
            $data['scheduled_at'] ?? null,
            $data['status'] ?? 'draft',
            $data['created_by']
        ]);
        return self::db()->lastInsertId();
    }
}
