<?php
/**
 * Message Model
 */
class Message {
    private static function db(): PDO {
        return Database::getInstance();
    }

    public static function getConversations(int $userId): array {
        $stmt = self::db()->prepare("
            SELECT 
                CASE WHEN m.sender_id = ? THEN m.receiver_id ELSE m.sender_id END as other_user_id,
                u.firstname, u.lastname, u.avatar, u.avatar_image,
                m.message as last_message,
                m.created_at as last_message_at,
                m.type as last_message_type,
                (SELECT COUNT(*) FROM messages m2 WHERE m2.sender_id = u.id AND m2.receiver_id = ? AND m2.is_read = 0) as unread_count
            FROM messages m
            JOIN users u ON u.id = CASE WHEN m.sender_id = ? THEN m.receiver_id ELSE m.sender_id END
            WHERE m.id IN (
                SELECT MAX(id) FROM messages 
                WHERE sender_id = ? OR receiver_id = ? 
                GROUP BY CASE WHEN sender_id = ? THEN receiver_id ELSE sender_id END
            )
            ORDER BY m.created_at DESC
        ");
        $stmt->execute([$userId, $userId, $userId, $userId, $userId, $userId]);
        return $stmt->fetchAll();
    }

    public static function getThread(int $userId, int $otherUserId, int $limit = 50, int $offset = 0): array {
        $stmt = self::db()->prepare("
            SELECT m.*, 
                   s.firstname as sender_firstname, s.lastname as sender_lastname, s.avatar as sender_avatar,
                   r.firstname as receiver_firstname, r.lastname as receiver_lastname
            FROM messages m
            JOIN users s ON s.id = m.sender_id
            JOIN users r ON r.id = m.receiver_id
            WHERE (m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?)
            ORDER BY m.created_at ASC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$userId, $otherUserId, $otherUserId, $userId, $limit, $offset]);
        return $stmt->fetchAll();
    }

    public static function send(array $data): int|false {
        $stmt = self::db()->prepare("
            INSERT INTO messages (sender_id, receiver_id, subject, message, attachment_path, type, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $data['sender_id'],
            $data['receiver_id'],
            $data['subject'] ?? '',
            $data['message'],
            $data['attachment_path'] ?? null,
            $data['type'] ?? 'text',
        ]);
        return self::db()->lastInsertId();
    }

    public static function markAsRead(int $messageId, int $userId): bool {
        $stmt = self::db()->prepare('UPDATE messages SET is_read = 1 WHERE id = ? AND receiver_id = ?');
        $stmt->execute([$messageId, $userId]);
        return $stmt->rowCount() > 0;
    }

    public static function markThreadAsRead(int $senderId, int $receiverId): bool {
        $stmt = self::db()->prepare('UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ? AND is_read = 0');
        $stmt->execute([$senderId, $receiverId]);
        return true;
    }

    public static function getUnreadCount(int $userId): int {
        $stmt = self::db()->prepare('SELECT COUNT(*) as c FROM messages WHERE receiver_id = ? AND is_read = 0');
        $stmt->execute([$userId]);
        return $stmt->fetch()['c'];
    }

    public static function deleteConversation(int $userId, int $otherUserId): bool {
        $stmt = self::db()->prepare('
            DELETE FROM messages
            WHERE (sender_id = ? AND receiver_id = ?)
               OR (sender_id = ? AND receiver_id = ?)
        ');
        $stmt->execute([$userId, $otherUserId, $otherUserId, $userId]);
        return true;
    }

    /**
     * Get all users except the given user (for new message modal).
     * Returns id, firstname, lastname, email, role, avatar, avatar_image.
     */
    public static function getUsers(int $exceptUserId): array {
        $stmt = self::db()->prepare('
            SELECT id, firstname, lastname, email, role, avatar, avatar_image
            FROM users
            WHERE id != ? AND status != \'banned\'
            ORDER BY firstname ASC
        ');
        $stmt->execute([$exceptUserId]);
        return $stmt->fetchAll();
    }
}
