<?php
/**
 * Controller for Group Chat and Member DMs
 */
class GroupMessageController {

    /**
     * Get messages for a group
     */
    public static function index(): void {
        $user = AuthMiddleware::handle();
        $groupId = (int)($_GET['group_id'] ?? 0);
        $db = Database::getInstance();

        if (!$groupId) {
            Response::error('Group ID is required.');
        }

        // Check membership
        $stmt = $db->prepare("SELECT role FROM group_members WHERE group_id = ? AND user_id = ? AND status = 'active'");
        $stmt->execute([$groupId, $user['id']]);
        if (!$stmt->fetch()) {
            Response::forbidden('Only active members can access chat.');
        }

        $stmt = $db->prepare("
            SELECT m.*, u.firstname, u.lastname, u.avatar_image
            FROM group_messages m
            JOIN users u ON m.sender_id = u.id
            WHERE m.group_id = ?
            ORDER BY m.created_at ASC
            LIMIT 100
        ");
        $stmt->execute([$groupId]);
        
        // Mark as read
        $db->prepare("INSERT INTO group_message_reads (group_id, user_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE last_read_at = CURRENT_TIMESTAMP")
           ->execute([$groupId, $user['id']]);

        Response::success($stmt->fetchAll());
    }

    /**
     * Send a message to group
     */
    public static function store(): void {
        $user = AuthMiddleware::handle();
        $input = json_decode(file_get_contents('php://input'), true);
        $db = Database::getInstance();

        $groupId = (int)($input['group_id'] ?? 0);
        $content = trim($input['content'] ?? '');
        $type = $input['type'] ?? 'text';

        // Check membership and role for announcements
        $stmt = $db->prepare("SELECT role FROM group_members WHERE group_id = ? AND user_id = ? AND status = 'active'");
        $stmt->execute([$groupId, $user['id']]);
        $member = $stmt->fetch();

        if (!$member) {
            Response::forbidden('Not a member.');
        }

        $isAnnouncement = ($type === 'announcement' && in_array($member['role'], ['manager', 'moderator'])) ? 1 : 0;

        $stmt = $db->prepare("INSERT INTO group_messages (group_id, sender_id, content, type, is_announcement) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$groupId, $user['id'], $content, $type, $isAnnouncement]);

        Response::success(['id' => $db->lastInsertId()]);
    }
}
