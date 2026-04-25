<?php
/**
 * Document Model
 */
class Document {
    private static function db(): PDO {
        return Database::getInstance();
    }

    public static function findById(int $id): ?array {
        $stmt = self::db()->prepare('SELECT * FROM documents WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function getByUserId(int $userId): array {
        $stmt = self::db()->prepare('SELECT * FROM documents WHERE user_id = ? ORDER BY uploaded_at DESC');
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public static function create(array $data): int|false {
        $stmt = self::db()->prepare('
            INSERT INTO documents (user_id, filename, original_name, file_type, file_size, document_name, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $data['user_id'],
            $data['filename'],
            $data['original_name'],
            $data['file_type'],
            $data['file_size'],
            $data['document_name'] ?? $data['original_name'],
            'pending',
        ]);
        return self::db()->lastInsertId();
    }

    public static function updateStatus(int $id, string $status): bool {
        $stmt = self::db()->prepare('UPDATE documents SET status = ? WHERE id = ?');
        $stmt->execute([$status, $id]);
        return $stmt->rowCount() > 0;
    }

    public static function delete(int $id): ?string {
        $doc = self::findById($id);
        if (!$doc) return null;
        
        $stmt = self::db()->prepare('DELETE FROM documents WHERE id = ?');
        $stmt->execute([$id]);
        return $doc['filename']; // Return filename so caller can delete the file
    }

    public static function countByUser(int $userId): int {
        $stmt = self::db()->prepare('SELECT COUNT(*) as c FROM documents WHERE user_id = ?');
        $stmt->execute([$userId]);
        return $stmt->fetch()['c'];
    }
}
