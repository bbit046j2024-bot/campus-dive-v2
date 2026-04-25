<?php
/**
 * Permission Model
 */
class Permission {
    private static function db(): PDO {
        return Database::getInstance();
    }

    public static function getAll(): array {
        $stmt = self::db()->prepare('SELECT * FROM permissions ORDER BY category, name');
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function getByRoleId(int $roleId): array {
        $stmt = self::db()->prepare('
            SELECT p.* FROM permissions p 
            JOIN role_permissions rp ON p.id = rp.permission_id 
            WHERE rp.role_id = ?
        ');
        $stmt->execute([$roleId]);
        return $stmt->fetchAll();
    }

    public static function syncForRole(int $roleId, array $permissionIds): bool {
        $db = self::db();
        try {
            $db->beginTransaction();
            
            // Delete old
            $stmt = $db->prepare('DELETE FROM role_permissions WHERE role_id = ?');
            $stmt->execute([$roleId]);
            
            // Insert new
            if (!empty($permissionIds)) {
                $stmt = $db->prepare('INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)');
                foreach ($permissionIds as $pId) {
                    $stmt->execute([$roleId, $pId]);
                }
            }
            
            return $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            return false;
        }
    }
}
