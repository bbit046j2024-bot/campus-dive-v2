<?php
/**
 * DocumentContent Model
 */
class DocumentContent {
    private static function db(): PDO {
        return Database::getInstance();
    }

    public static function getByDocumentId(int $docId): ?array {
        $stmt = self::db()->prepare('SELECT * FROM document_content WHERE document_id = ?');
        $stmt->execute([$docId]);
        return $stmt->fetch() ?: null;
    }

    public static function upsert(int $docId, string $content): bool {
        $stmt = self::db()->prepare('
            INSERT INTO document_content (document_id, content) 
            VALUES (?, ?) 
            ON DUPLICATE KEY UPDATE content = VALUES(content), extracted_at = CURRENT_TIMESTAMP
        ');
        return $stmt->execute([$docId, $content]);
    }
}
