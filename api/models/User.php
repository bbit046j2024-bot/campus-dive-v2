<?php
/**
 * User Model
 */
class User {
    private static function db(): PDO {
        return Database::getInstance();
    }

    public static function findById(int $id): ?array {
        $stmt = self::db()->prepare('SELECT u.*, r.name as role_name FROM users u LEFT JOIN roles r ON u.role_id = r.id WHERE u.id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function findByEmail(string $email): ?array {
        $stmt = self::db()->prepare('SELECT u.*, r.name as role_name FROM users u LEFT JOIN roles r ON u.role_id = r.id WHERE u.email = ?');
        $stmt->execute([$email]);
        return $stmt->fetch() ?: null;
    }

    public static function create(array $data): int|false {
        $stmt = self::db()->prepare('
            INSERT INTO users (firstname, lastname, email, phone, student_id, department, password, role, role_id, avatar, status, verification_token, email_verified) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');
        $avatar = strtoupper(substr($data['firstname'], 0, 1) . substr($data['lastname'], 0, 1));
        $stmt->execute([
            $data['firstname'],
            $data['lastname'],
            $data['email'],
            $data['phone'] ?? '',
            $data['student_id'] ?? null,
            $data['department'] ?? null,
            password_hash($data['password'], PASSWORD_DEFAULT),
            'user',
            ROLE_STUDENT,
            $avatar,
            STATUS_SUBMITTED,
            $data['verification_token'] ?? null,
            0
        ]);
        return self::db()->lastInsertId();
    }

    public static function update(int $id, array $data): bool {
        $fields = [];
        $values = [];
        foreach ($data as $key => $value) {
            $fields[] = "{$key} = ?";
            $values[] = $value;
        }
        $values[] = $id;
        $stmt = self::db()->prepare('UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = ?');
        return $stmt->execute($values);
    }

    public static function verifyEmail(string $token): bool {
        $stmt = self::db()->prepare('UPDATE users SET email_verified = 1, verification_token = NULL WHERE verification_token = ?');
        $stmt->execute([$token]);
        return $stmt->rowCount() > 0;
    }

    public static function setResetToken(int $id, string $token): bool {
        $stmt = self::db()->prepare('UPDATE users SET reset_token = ?, reset_token_expires = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE id = ?');
        $stmt->execute([$token, $id]);
        return $stmt->rowCount() > 0;
    }

    public static function findByResetToken(string $token): ?array {
        $stmt = self::db()->prepare('SELECT * FROM users WHERE reset_token = ? AND reset_token_expires > NOW()');
        $stmt->execute([$token]);
        return $stmt->fetch() ?: null;
    }

    public static function updatePassword(int $id, string $password): bool {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = self::db()->prepare('UPDATE users SET password = ?, reset_token = NULL, reset_token_expires = NULL WHERE id = ?');
        $stmt->execute([$hash, $id]);
        return $stmt->rowCount() > 0;
    }

    public static function getAllStudents(array $filters = []): array {
        $sql = "SELECT u.*, r.name as role_name FROM users u LEFT JOIN roles r ON u.role_id = r.id WHERE (u.role != 'admin' AND (u.role_id IS NULL OR u.role_id != ?))";
        $params = [ROLE_ADMIN];

        if (!empty($filters['status'])) {
            $sql .= ' AND u.status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['search'])) {
            $sql .= ' AND (u.firstname LIKE ? OR u.lastname LIKE ? OR u.email LIKE ?)';
            $search = '%' . $filters['search'] . '%';
            $params = array_merge($params, [$search, $search, $search]);
        }

        $sql .= ' ORDER BY u.created_at DESC';

        // Pagination
        $page = max(1, intval($filters['page'] ?? 1));
        $limit = max(1, min(100, intval($filters['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;

        // Get total count
        $countSql = str_replace('SELECT u.*, r.name as role_name', 'SELECT COUNT(*) as total', $sql);
        $countStmt = self::db()->prepare($countSql);
        $countStmt->execute($params);
        $total = $countStmt->fetch()['total'];

        $sql .= " LIMIT {$limit} OFFSET {$offset}";
        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll();

        error_log("GEt_ALL_STUDENTS: Count=" . count($data) . " Query=" . $sql . " Params=" . json_encode($params));

        return [
            'data' => $data,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => ceil($total / $limit),
            ]
        ];
    }

    public static function getStats(): array {
        $db = self::db();
        
        $sql = "SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status IN ('submitted', 'pending') THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
            SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
            SUM(CASE WHEN status = 'under_review' THEN 1 ELSE 0 END) as under_review
        FROM users 
        WHERE role != 'admin' AND (role_id IS NULL OR role_id != ?)";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([ROLE_ADMIN]);
        $stats = $stmt->fetch();

        return [
            'total_students' => (int)($stats['total'] ?? 0),
            'pending' => (int)($stats['pending'] ?? 0),
            'approved' => (int)($stats['approved'] ?? 0),
            'rejected' => (int)($stats['rejected'] ?? 0),
            'under_review' => (int)($stats['under_review'] ?? 0),
        ];
    }

    public static function delete(int $id): bool {
        $stmt = self::db()->prepare('DELETE FROM users WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }
}
