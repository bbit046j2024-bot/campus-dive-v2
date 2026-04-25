<?php
/**
 * Role-Based Access Control Middleware
 */
class RoleMiddleware {

    /**
     * Require specific role(s) — pass role IDs or role names
     */
    public static function require(array $allowedRoles, array $user): void {
        $userRoleId = $user['role_id'] ?? null;
        $userRoleName = $user['role_name'] ?? '';

        foreach ($allowedRoles as $role) {
            if (is_int($role) && $userRoleId == $role) return;
            if (is_string($role) && strtolower($userRoleName) === strtolower($role)) return;
        }

        Response::forbidden('You do not have permission to access this resource.');
    }

    /**
     * Require a specific permission slug via role_permissions
     */
    public static function requirePermission(string $permissionSlug, array $user): void {
        $db = Database::getInstance();
        $stmt = $db->prepare('
            SELECT COUNT(*) as count 
            FROM role_permissions rp 
            JOIN permissions p ON rp.permission_id = p.id 
            WHERE rp.role_id = ? AND p.slug = ?
        ');
        $stmt->execute([$user['role_id'], $permissionSlug]);
        $result = $stmt->fetch();

        if (!$result || $result['count'] == 0) {
            Response::forbidden('You do not have the required permission.');
        }
    }

    /**
     * Check (without blocking) if user has permission
     */
    public static function hasPermission(string $permissionSlug, array $user): bool {
        $db = Database::getInstance();
        $stmt = $db->prepare('
            SELECT COUNT(*) as count 
            FROM role_permissions rp 
            JOIN permissions p ON rp.permission_id = p.id 
            WHERE rp.role_id = ? AND p.slug = ?
        ');
        $stmt->execute([$user['role_id'], $permissionSlug]);
        $result = $stmt->fetch();
        return $result && $result['count'] > 0;
    }
}
