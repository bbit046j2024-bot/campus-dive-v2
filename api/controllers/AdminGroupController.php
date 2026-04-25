<?php
/**
 * Admin Controller for Social Groups
 * Restricted to Campus Admins
 */
class AdminGroupController {

    /**
     * List all groups for admin
     */
    public static function index(): void {
        $user = AuthMiddleware::handle();
        RoleMiddleware::require([ROLE_ADMIN, 'Admin'], $user);
        
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT g.*, 
                   (SELECT COUNT(*) FROM group_members WHERE group_id = g.id) as member_count
            FROM social_groups g
            ORDER BY g.created_at DESC
        ");
        $stmt->execute();
        Response::success($stmt->fetchAll());
    }

    /**
     * Create a new group
     */
    public static function store(): void {
        $user = AuthMiddleware::handle();
        RoleMiddleware::require([ROLE_ADMIN, 'Admin'], $user);
        
        $input = json_decode(file_get_contents('php://input'), true);
        $db = Database::getInstance();

        $name = trim($input['name'] ?? '');
        $description = trim($input['description'] ?? '');
        $category = trim($input['category'] ?? 'General');
        $isPrivate = isset($input['is_private']) && $input['is_private'] ? 1 : 0;
        $managerId = isset($input['manager_id']) ? (int)$input['manager_id'] : null;

        if (empty($name)) {
            Response::error('Group name is required.');
        }

        // Generate slug
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));
        
        // Ensure slug uniqueness
        $stmt = $db->prepare("SELECT id FROM social_groups WHERE slug = ?");
        $stmt->execute([$slug]);
        if ($stmt->fetch()) {
            $slug .= '-' . substr(md5(uniqid()), 0, 5);
        }

        $stmt = $db->prepare("
            INSERT INTO social_groups (name, slug, description, category, avatar_url, is_private, created_by, manager_id) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$name, $slug, $description, $category, $input['avatar_url'] ?? null, $isPrivate, $user['id'], $managerId]);
        $groupId = $db->lastInsertId();

        // Auto-join the creator as a manager if no manager was explicitly assigned
        // OR if the manager assigned is NOT the creator, still ensure creator is a member/manager
        $finalManagerId = $managerId ?: $user['id'];
        
        $db->prepare("INSERT INTO group_members (group_id, user_id, role, status) 
                      VALUES (?, ?, 'manager', 'active') 
                      ON DUPLICATE KEY UPDATE role = 'manager', status = 'active'")
           ->execute([$groupId, $finalManagerId]);

        // If a different manager was assigned, also make sure they are in the group
        if ($managerId && $managerId != $user['id']) {
            $db->prepare("INSERT INTO group_members (group_id, user_id, role, status) 
                          VALUES (?, ?, 'manager', 'active') 
                          ON DUPLICATE KEY UPDATE role = 'manager', status = 'active'")
               ->execute([$groupId, $managerId]);
        }

        Response::success(['id' => $groupId, 'slug' => $slug], 'Group created successfully.');
    }

    /**
     * Delete a group
     */
    public static function destroy(): void {
        $user = AuthMiddleware::handle();
        RoleMiddleware::require([ROLE_ADMIN, 'Admin'], $user);
        
        $groupId = (int)($_GET['group_id'] ?? 0);

        if (!$groupId) {
            Response::error('Group ID is required.');
        }

        $db = Database::getInstance();
        $stmt = $db->prepare("DELETE FROM social_groups WHERE id = ?");
        $stmt->execute([$groupId]);

        Response::success(null, 'Group deleted.');
    }

    /**
     * Assign / Change Group Manager
     */
    public static function assignManager(): void {
        $user = AuthMiddleware::handle();
        RoleMiddleware::require([ROLE_ADMIN, 'Admin'], $user);
        
        $input = json_decode(file_get_contents('php://input'), true);
        $groupId = (int)($input['group_id'] ?? 0);
        $managerId = (int)($input['manager_id'] ?? 0);

        if (!$groupId || !$managerId) {
            Response::error('Group ID and Manager ID are required.');
        }

        $db = Database::getInstance();
        
        // 1. Remove old manager from group_members role
        $db->prepare("UPDATE group_members SET role = 'member' WHERE group_id = ? AND role = 'manager'")
           ->execute([$groupId]);

        // 2. Update group manager_id
        $db->prepare("UPDATE social_groups SET manager_id = ? WHERE id = ?")
           ->execute([$managerId, $groupId]);

        // 3. Add/Update new manager in group_members
        $db->prepare("INSERT INTO group_members (group_id, user_id, role, status) 
                      VALUES (?, ?, 'manager', 'active') 
                      ON DUPLICATE KEY UPDATE role = 'manager', status = 'active'")
           ->execute([$groupId, $managerId]);

        Response::success(null, 'Group manager assigned.');
    }
}
