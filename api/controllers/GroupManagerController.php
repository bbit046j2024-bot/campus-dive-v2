<?php
/**
 * Controller for Group Managers to manage their group
 */
class GroupManagerController {

    private static function checkAccess(int $groupId): array {
        $user = AuthMiddleware::handle();
        $db = Database::getInstance();

        // Admin can manage any group
        if (in_array($user['role'], ['Admin']) || $user['role_id'] == ROLE_ADMIN) {
            return ['user' => $user, 'is_admin' => true];
        }

        $stmt = $db->prepare("SELECT role FROM group_members WHERE group_id = ? AND user_id = ? AND role = 'manager'");
        $stmt->execute([$groupId, $user['id']]);
        if (!$stmt->fetch()) {
            Response::forbidden('You do not have management permissions for this group.');
        }

        return ['user' => $user, 'is_admin' => false];
    }

    /**
     * Update group settings
     */
    public static function updateSettings(): void {
        $groupId = (int)($_GET['group_id'] ?? 0);
        self::checkAccess($groupId);
        
        $input = json_decode(file_get_contents('php://input'), true);
        $db = Database::getInstance();

        $fields = [
            'description' => $input['description'] ?? null,
            'category' => $input['category'] ?? null,
            'cover_color' => $input['cover_color'] ?? null,
            'cover_color_end' => $input['cover_color_end'] ?? null,
            'banner_url' => $input['banner_url'] ?? null,
            'welcome_message' => $input['welcome_message'] ?? null,
            'rules' => $input['rules'] ?? null,
            'allow_member_posts' => isset($input['allow_member_posts']) ? (int)$input['allow_member_posts'] : null,
            'post_approval_required' => isset($input['post_approval_required']) ? (int)$input['post_approval_required'] : null,
            'avatar_url' => $input['avatar_url'] ?? null,
        ];

        $updates = [];
        $params = [];
        foreach ($fields as $key => $val) {
            if ($val !== null) {
                $updates[] = "$key = ?";
                $params[] = $val;
            }
        }

        if (empty($updates)) {
            Response::error('No fields to update.');
        }

        $params[] = $groupId;
        $sql = "UPDATE social_groups SET " . implode(', ', $updates) . " WHERE id = ?";
        $db->prepare($sql)->execute($params);

        Response::success(null, 'Group settings updated.');
    }

    /**
     * Manage member status (approve, ban, make moderator)
     */
    public static function updateMemberStatus(): void {
        $input = json_decode(file_get_contents('php://input'), true);
        $groupId = (int)($input['group_id'] ?? 0);
        
        self::checkAccess($groupId);
        $db = Database::getInstance();

        $userId = (int)($input['user_id'] ?? 0);
        $status = $input['status'] ?? null; // active, banned, pending
        $role = $input['role'] ?? null; // member, moderator

        if (!$userId) Response::error('User ID required.');

        $updates = [];
        $params = [];
        if ($status) { $updates[] = "status = ?"; $params[] = $status; }
        if ($role) { $updates[] = "role = ?"; $params[] = $role; }

        if (empty($updates)) Response::error('No updates provided.');

        $params[] = $groupId;
        $params[] = $userId;
        
        // Cannot update another manager
        $sql = "UPDATE group_members SET " . implode(', ', $updates) . " WHERE group_id = ? AND user_id = ? AND role != 'manager'";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        if ($stmt->rowCount() > 0) {
            Response::success(null, 'Member updated.');
        } else {
            Response::error('Could not update member. They might be a manager or not in the group.');
        }
    }

    /**
     * Get pending posts for approval
     */
    public static function getPendingPosts(): void {
        $groupId = (int)($_GET['group_id'] ?? 0);
        self::checkAccess($groupId);
        
        $db = Database::getInstance();

        $stmt = $db->prepare("
            SELECT p.*, u.firstname, u.lastname, u.avatar_image
            FROM group_posts p
            JOIN users u ON p.user_id = u.id
            WHERE p.group_id = ? AND p.status = 'pending'
            ORDER BY p.created_at ASC
        ");
        $stmt->execute([$groupId]);
        Response::success($stmt->fetchAll());
    }

    /**
     * Approve or Reject a post
     */
    public static function moderatePost(): void {
        $input = json_decode(file_get_contents('php://input'), true);
        $postId = (int)($input['post_id'] ?? 0);
        $action = $input['action'] ?? 'approve'; // approve, reject
        
        $db = Database::getInstance();
        
        // Find group first to check access
        $stmt = $db->prepare("SELECT group_id FROM group_posts WHERE id = ?");
        $stmt->execute([$postId]);
        $post = $stmt->fetch();
        if (!$post) Response::notFound('Post not found.');

        self::checkAccess($post['group_id']);
        
        $status = ($action === 'approve') ? 'published' : 'rejected';
        
        $db->prepare("UPDATE group_posts SET status = ? WHERE id = ?")
           ->execute([$status, $postId]);

        Response::success(null, "Post " . ($action === 'approve' ? 'approved' : 'rejected') . ".");
    }
}
