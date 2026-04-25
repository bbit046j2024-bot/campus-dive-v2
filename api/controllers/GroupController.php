<?php
/**
 * Controller for managing Social Groups
 */
class GroupController {

    /**
     * List all active groups
     */
    public static function index(): void {
        $user = AuthMiddleware::handle();
        $db = Database::getInstance();

        $stmt = $db->prepare("
            SELECT g.*, 
                   (SELECT COUNT(*) FROM group_members WHERE group_id = g.id) as member_count,
                   (SELECT role FROM group_members WHERE group_id = g.id AND user_id = ?) as user_role
            FROM social_groups g
            WHERE g.status = 'active'
            ORDER BY member_count DESC
        ");
        $stmt->execute([$user['id']]);
        Response::success($stmt->fetchAll());
    }

    /**
     * Get single group details
     */
    public static function show(): void {
        $user = AuthMiddleware::handle();
        $slug = $_GET['slug'] ?? '';
        $db = Database::getInstance();

        if (empty($slug)) {
            Response::error('Slug is required.');
        }

        $stmt = $db->prepare("
            SELECT g.*, 
                   (SELECT COUNT(*) FROM group_members WHERE group_id = g.id) as member_count,
                   (SELECT role FROM group_members WHERE group_id = g.id AND user_id = ?) as user_role
            FROM social_groups g
            WHERE g.slug = ? AND g.status = 'active'
        ");
        $stmt->execute([$user['id'], $slug]);
        $group = $stmt->fetch();

        if (!$group) {
            Response::notFound('Group not found.');
        }

        Response::success($group);
    }

    /**
     * Join a group
     */
    public static function join(): void {
        $user = AuthMiddleware::handle();
        $input = json_decode(file_get_contents('php://input'), true);
        $groupId = (int)($input['group_id'] ?? 0);
        
        $db = Database::getInstance();

        if (!$groupId) {
            Response::error('Group ID is required.');
        }

        // Check if group exists
        $stmt = $db->prepare("SELECT is_private FROM social_groups WHERE id = ?");
        $stmt->execute([$groupId]);
        $group = $stmt->fetch();

        if (!$group) {
            Response::notFound('Group not found.');
        }

        $status = $group['is_private'] ? 'pending' : 'active';

        try {
            $stmt = $db->prepare("INSERT INTO group_members (group_id, user_id, status) VALUES (?, ?, ?)");
            $stmt->execute([$groupId, $user['id'], $status]);
            
            Response::success(null, $status === 'active' ? 'Joined successfully!' : 'Request sent to group manager.');
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                Response::error('You are already a member or have a pending request.');
            }
            throw $e;
        }
    }

    /**
     * Leave a group
     */
    public static function leave(): void {
        $user = AuthMiddleware::handle();
        $input = json_decode(file_get_contents('php://input'), true);
        $groupId = (int)($input['group_id'] ?? 0);

        $db = Database::getInstance();

        if (!$groupId) {
            Response::error('Group ID is required.');
        }

        $stmt = $db->prepare("DELETE FROM group_members WHERE group_id = ? AND user_id = ? AND role != 'manager'");
        $stmt->execute([$groupId, $user['id']]);

        if ($stmt->rowCount() > 0) {
            Response::success(null, 'Left group successfully.');
        } else {
            Response::error('Could not leave group. Managers cannot leave unless they delete the group or transfer ownership.');
        }
    }

    /**
     * Get members of a group
     */
    public static function getMembers(): void {
        AuthMiddleware::handle();
        $groupId = (int)($_GET['group_id'] ?? 0);
        $db = Database::getInstance();

        if (!$groupId) {
            Response::error('Group ID is required.');
        }

        $stmt = $db->prepare("
            SELECT u.id, u.firstname, u.lastname, u.avatar_image, gm.role, gm.joined_at
            FROM group_members gm
            JOIN users u ON gm.user_id = u.id
            WHERE gm.group_id = ? AND gm.status = 'active'
            ORDER BY gm.role = 'manager' DESC, gm.joined_at ASC
            LIMIT 50
        ");
        $stmt->execute([$groupId]);
        Response::success($stmt->fetchAll());
    }
}
