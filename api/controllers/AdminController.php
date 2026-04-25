<?php
/**
 * Admin Controller
 */
class AdminController {

    /** GET /api/admin/dashboard */
    public static function dashboard(): void {
        $user = AuthMiddleware::handle();
        RoleMiddleware::require([ROLE_ADMIN, ROLE_MANAGER, 'Admin', 'Manager'], $user);

        $db = Database::getInstance();
        
        // 1. Get stats and unread counts in parallel where possible
        $stats = User::getStats();
        
        // Combine unread counts into one query
        $countsStmt = $db->prepare("
            SELECT 
                (SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0) as unread_messages,
                (SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0) as unread_notifications
        ");
        $countsStmt->execute([$user['id'], $user['id']]);
        $counts = $countsStmt->fetch();

        // 2. Recent applications (last 10) - Use prepared statement
        $recentStmt = $db->prepare("
            SELECT id, firstname, lastname, email, status, created_at 
            FROM users 
            WHERE (role != 'admin' AND (role_id IS NULL OR role_id != ?)) 
            ORDER BY created_at DESC 
            LIMIT 10
        ");
        $recentStmt->execute([ROLE_ADMIN]);
        $recent = $recentStmt->fetchAll();

        // 3. Monthly application trends (last 6 months) - Use prepared statement
        $trendsStmt = $db->prepare("
            SELECT 
                DATE_FORMAT(created_at, '%Y-%m') as month,
                COUNT(*) as count,
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
            FROM users 
            WHERE (role != 'admin' AND (role_id IS NULL OR role_id != ?))
            AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
            ORDER BY month ASC
        ");
        $trendsStmt->execute([ROLE_ADMIN]);
        $trends = $trendsStmt->fetchAll();

        $notifications = Notification::getByUserId($user['id'], 5);

        Response::success([
            'stats'                => $stats,
            'unread_messages'      => (int)$counts['unread_messages'],
            'unread_notifications' => (int)$counts['unread_notifications'],
            'notifications'        => $notifications,
            'recent_students'      => $recent,
            'trends'               => $trends,
        ]);
    }

    /** GET /api/admin/students */
    public static function students(): void {
        $user = AuthMiddleware::handle();
        RoleMiddleware::require([ROLE_ADMIN, ROLE_MANAGER, 'Admin', 'Manager'], $user);

        $filters = [
            'status' => $_GET['status'] ?? '',
            'search' => $_GET['search'] ?? '',
            'page'   => $_GET['page'] ?? 1,
            'limit'  => $_GET['limit'] ?? 20,
        ];

        $result = User::getAllStudents($filters);

        // Strip passwords
        foreach ($result['data'] as &$s) {
            unset($s['password'], $s['verification_token'], $s['reset_token'], $s['reset_token_expires']);
        }

        Response::success($result);
    }

    /** GET /api/admin/students/:id */
    public static function studentDetail(int $id): void {
        $user = AuthMiddleware::handle();
        RoleMiddleware::require([ROLE_ADMIN, ROLE_MANAGER, ROLE_INTERVIEWER, 'Admin', 'Manager', 'Interviewer'], $user);

        $student = User::findById($id);
        if (!$student) Response::notFound('Student not found.');

        unset($student['password'], $student['verification_token'], $student['reset_token'], $student['reset_token_expires']);

        $documents = Document::getByUserId($id);
        $interviews = InterviewSlot::getByUserId($id);
        $stages = ApplicationStage::getByUserId($id);

        Response::success([
            'student'    => $student,
            'documents'  => $documents,
            'interviews' => $interviews,
            'stages'     => $stages,
        ]);
    }

    /** PUT /api/admin/students/:id/status */
    public static function updateStudentStatus(int $id): void {
        $user = AuthMiddleware::handle();
        RoleMiddleware::require([ROLE_ADMIN, ROLE_MANAGER, 'Admin', 'Manager'], $user);
        CsrfMiddleware::validate();

        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $v = Validator::make($input)
            ->required('status')
            ->in('status', [STATUS_SUBMITTED, STATUS_PENDING, STATUS_DOCS_UPLOADED, STATUS_UNDER_REVIEW, STATUS_INTERVIEW_SCHEDULED, STATUS_APPROVED, STATUS_REJECTED]);

        if ($v->fails()) Response::validationError($v->errors());

        $student = User::findById($id);
        if (!$student) Response::notFound('Student not found.');

        User::update($id, ['status' => $input['status']]);

        // Log stage transition
        ApplicationStage::create($id, $input['status']);

        // Notify student
        $statusLabels = [
            STATUS_APPROVED => 'Your application has been approved! 🎉',
            STATUS_REJECTED => 'Your application status has been updated.',
            STATUS_UNDER_REVIEW => 'Your application is now under review.',
            STATUS_INTERVIEW_SCHEDULED => 'An interview has been scheduled for you.',
        ];
        $msg = $statusLabels[$input['status']] ?? 'Your application status has been updated to: ' . $input['status'];
        Notification::create($id, 'Status Update', $msg, $input['status'] === STATUS_APPROVED ? 'success' : 'info');

        Response::success(null, 'Student status updated.');
    }

    /** POST /api/admin/students/bulk-action */
    public static function bulkAction(): void {
        $user = AuthMiddleware::handle();
        RoleMiddleware::require([ROLE_ADMIN, 'Admin'], $user);
        CsrfMiddleware::validate();

        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $v = Validator::make($input)
            ->required('action')
            ->in('action', ['approve', 'reject', 'delete'])
            ->required('student_ids');

        if ($v->fails()) Response::validationError($v->errors());

        $ids = $input['student_ids'];
        if (!is_array($ids) || empty($ids)) {
            Response::error('No students selected.', 400);
        }

        $count = 0;
        foreach ($ids as $id) {
            $id = intval($id);
            switch ($input['action']) {
                case 'approve':
                    User::update($id, ['status' => STATUS_APPROVED]);
                    Notification::create($id, 'Application Approved', 'Your application has been approved! 🎉', 'success');
                    $count++;
                    break;
                case 'reject':
                    User::update($id, ['status' => STATUS_REJECTED]);
                    Notification::create($id, 'Application Update', 'Your application status has been updated.', 'info');
                    $count++;
                    break;
                case 'delete':
                    User::delete($id);
                    $count++;
                    break;
            }
        }

        Response::success(['affected' => $count], "Bulk action completed on {$count} students.");
    }

    /** GET /api/admin/roles */
    public static function roles(): void {
        $user = AuthMiddleware::handle();
        RoleMiddleware::require([ROLE_ADMIN, 'Admin'], $user);

        $roles = Role::getAll();
        $allPermissions = Permission::getAll();

        foreach ($roles as &$role) {
            $role['permissions'] = Permission::getByRoleId($role['id']);
        }

        Response::success([
            'roles'           => $roles,
            'all_permissions' => $allPermissions,
        ]);
    }

    /** PUT /api/admin/roles/:id */
    public static function updateRole(int $id): void {
        $user = AuthMiddleware::handle();
        RoleMiddleware::require([ROLE_ADMIN, 'Admin'], $user);
        CsrfMiddleware::validate();

        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        if (!isset($input['permission_ids']) || !is_array($input['permission_ids'])) {
            Response::error('Permission IDs required.', 400);
        }

        if (Permission::syncForRole($id, $input['permission_ids'])) {
            Response::success(null, 'Role permissions updated.');
        } else {
            Response::error('Failed to update role permissions.', 500);
        }
    }
    /** GET /api/admin/users - Search all users for assignment */
    public static function users(): void {
        $user = AuthMiddleware::handle();
        RoleMiddleware::require([ROLE_ADMIN, 'Admin'], $user);

        $search = $_GET['search'] ?? '';
        $db = Database::getInstance();
        
        $sql = "SELECT id, firstname, lastname, email, role, role_id FROM users WHERE 1=1";
        $params = [];

        if (!empty($search)) {
            $sql .= " AND (firstname LIKE ? OR lastname LIKE ? OR email LIKE ?)";
            $s = "%$search%";
            $params = [$s, $s, $s];
        }

        $sql .= " ORDER BY firstname ASC LIMIT 20";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        
        Response::success($stmt->fetchAll());
    }

    /** PUT /api/admin/users/:id/role */
    public static function updateUserRole(int $id): void {
        
        $user = AuthMiddleware::handle();
        RoleMiddleware::require([ROLE_ADMIN, 'Admin'], $user);
        CsrfMiddleware::validate();

        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $v = Validator::make($input)
            ->required('role_id');

        if ($v->fails()) Response::validationError($v->errors());

        $targetUser = User::findById($id);
        if (!$targetUser) Response::notFound('User not found.');

        // Update role_id and the legacy role string
        $roleId = intval($input['role_id']);
        $roleName = 'user';
        if ($roleId === ROLE_ADMIN) $roleName = 'admin';
        else if ($roleId === ROLE_MANAGER) $roleName = 'manager';
        else if ($roleId === ROLE_INTERVIEWER) $roleName = 'interviewer';

        User::update($id, [
            'role_id' => $roleId,
            'role' => $roleName
        ]);

        Response::success(null, 'User role updated successfully.');
    }
}
