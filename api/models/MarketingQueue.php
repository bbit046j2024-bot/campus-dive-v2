<?php
/**
 * MarketingQueue Model
 */
class MarketingQueue {
    private static function db(): PDO {
        return Database::getInstance();
    }

    public static function addToQueue(int $campaignId, int $userId, string $recipientContact, string $content): bool {
        $stmt = self::db()->prepare('
            INSERT INTO marketing_queue (campaign_id, user_id, recipient_contact, message_content, status) 
            VALUES (?, ?, ?, ?, "pending")
        ');
        return $stmt->execute([$campaignId, $userId, $recipientContact, $content]);
    }

    public static function getPending(int $limit = 50): array {
        $stmt = self::db()->prepare('SELECT * FROM marketing_queue WHERE status = "pending" LIMIT ?');
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }

    public static function updateStatus(int $id, string $status, ?string $errorMessage = null): bool {
        $stmt = self::db()->prepare('UPDATE marketing_queue SET status = ?, sent_at = IF(? = "sent", NOW(), sent_at), error_message = ? WHERE id = ?');
        return $stmt->execute([$status, $status, $errorMessage, $id]);
    }
}
