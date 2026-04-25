<?php
/**
 * Role Model
 */
class Role {
    private static function db(): PDO {
        return Database::getInstance();
    }

    public static function getAll(): array {
        return self::db()->query('SELECT * FROM roles ORDER BY id')->fetchAll();
    }

    public static function findById(int $id): ?array {
        $stmt = self::db()->prepare('SELECT * FROM roles WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function getPermissions(int $roleId): array {
        $stmt = self::db()->prepare('
            SELECT p.* FROM permissions p
            JOIN role_permissions rp ON rp.permission_id = p.id
            WHERE rp.role_id = ?
            ORDER BY p.category, p.name
        ');
        $stmt->execute([$roleId]);
        return $stmt->fetchAll();
    }

    public static function getAllPermissions(): array {
        return self::db()->query('SELECT * FROM permissions ORDER BY category, name')->fetchAll();
    }

    public static function syncPermissions(int $roleId, array $permissionIds): bool {
        $db = self::db();
        $db->beginTransaction();
        try {
            $db->prepare('DELETE FROM role_permissions WHERE role_id = ?')->execute([$roleId]);
            
            $stmt = $db->prepare('INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)');
            foreach ($permissionIds as $permId) {
                $stmt->execute([$roleId, $permId]);
            }
            $db->commit();
            return true;
        } catch (Exception $e) {
            $db->rollBack();
            return false;
        }
    }
}
