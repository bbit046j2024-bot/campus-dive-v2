<?php
/**
 * DocumentVersion Model
 */
class DocumentVersion {
    private static function db(): PDO {
        return Database::getInstance();
    }

    public static function getByDocumentId(int $docId): array {
        $stmt = self::db()->prepare('SELECT * FROM document_versions WHERE document_id = ? ORDER BY version_num DESC');
        $stmt->execute([$docId]);
        return $stmt->fetchAll();
    }

    public static function create(array $data): int|false {
        $stmt = self::db()->prepare('
            INSERT INTO document_versions (document_id, file_path, original_name, file_size, version_num) 
            VALUES (?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $data['document_id'],
            $data['file_path'],
            $data['original_name'],
            $data['file_size'],
            $data['version_num'] ?? 1
        ]);
        return self::db()->lastInsertId();
    }
}
