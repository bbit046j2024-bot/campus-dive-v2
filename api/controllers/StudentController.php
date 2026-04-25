<?php
/**
 * Student Controller
 */
class StudentController {

    /** GET /api/student/dashboard */
    public static function dashboard(): void {
        $user = AuthMiddleware::handle();
        $db = Database::getInstance();

        // 1. Get stats and unread counts in one go
        $countsStmt = $db->prepare("
            SELECT 
                (SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0) as unread_messages,
                (SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0) as unread_notifications
        ");
        $countsStmt->execute([$user['id'], $user['id']]);
        $counts = $countsStmt->fetch();

        $documents = Document::getByUserId($user['id']);
        $notifications = Notification::getByUserId($user['id'], 10);

        // Application stages for progress tracker
        $stages = ApplicationStage::getByUserId($user['id']);

        unset($user['password'], $user['verification_token'], $user['reset_token'], $user['reset_token_expires']);

        Response::success([
            'user'                 => $user,
            'documents'            => $documents,
            'document_count'       => count($documents),
            'notifications'        => $notifications,
            'unread_messages'      => (int)$counts['unread_messages'],
            'unread_notifications' => (int)$counts['unread_notifications'],
            'stages'               => $stages,
            'application_status'   => $user['status'] ?? 'submitted',
        ]);
    }

    /** GET /api/student/documents */
    public static function documents(): void {
        $user = AuthMiddleware::handle();
        $documents = Document::getByUserId($user['id']);
        Response::success($documents);
    }

    /** POST /api/student/documents */
    public static function uploadDocument(): void {
        $user = AuthMiddleware::handle();
        CsrfMiddleware::validate();

        if (!isset($_FILES['document'])) {
            Response::error('No file provided.', 400);
        }

        $fileData = FileService::uploadDocument($_FILES['document'], $user['id']);
        $fileData['user_id'] = $user['id'];
        $fileData['document_name'] = $_POST['document_name'] ?? $fileData['original_name'];

        $docId = Document::create($fileData);
        if (!$docId) {
            Response::error('Failed to save document.', 500);
        }

        // Update user status if first document
        if (Document::countByUser($user['id']) === 1) {
            User::update($user['id'], ['status' => STATUS_DOCS_UPLOADED]);
        }

        Notification::create($user['id'], 'Document Uploaded', "Your document '{$fileData['document_name']}' has been uploaded successfully.", 'success');

        Response::success(Document::findById($docId), 'Document uploaded successfully.', 201);
    }

    /** DELETE /api/student/documents/:id */
    public static function deleteDocument(int $id): void {
        $user = AuthMiddleware::handle();
        CsrfMiddleware::validate();

        $doc = Document::findById($id);
        if (!$doc || $doc['user_id'] !== $user['id']) {
            Response::notFound('Document not found.');
        }

        $filename = Document::delete($id);
        if ($filename) {
            FileService::deleteFile($filename);
        }

        Response::success(null, 'Document deleted.');
    }

    /** PUT /api/student/profile */
    public static function updateProfile(): void {
        $user = AuthMiddleware::handle();
        CsrfMiddleware::validate();
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $v = Validator::make($input)
            ->required('firstname')
            ->required('lastname')
            ->required('phone')
            ->phone('phone');

        if ($v->fails()) {
            Response::validationError($v->errors());
        }

        $avatar = strtoupper(substr($v->sanitized('firstname'), 0, 1) . substr($v->sanitized('lastname'), 0, 1));

        User::update($user['id'], [
            'firstname' => $v->sanitized('firstname'),
            'lastname'  => $v->sanitized('lastname'),
            'phone'     => $v->sanitized('phone'),
            'bio'       => $input['bio'] ?? null,
            'location'  => $input['location'] ?? null,
            'avatar'    => $avatar,
        ]);

        $updated = User::findById($user['id']);
        unset($updated['password'], $updated['verification_token'], $updated['reset_token'], $updated['reset_token_expires']);

        Response::success($updated, 'Profile updated successfully.');
    }

    /** PUT /api/student/password */
    public static function changePassword(): void {
        $user = AuthMiddleware::handle();
        CsrfMiddleware::validate();
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $v = Validator::make($input)
            ->required('current_password')
            ->required('new_password')
            ->minLength('new_password', 6)
            ->required('confirm_password')
            ->matches('confirm_password', 'new_password', 'Confirm password', 'New password');

        if ($v->fails()) {
            Response::validationError($v->errors());
        }

        if (!password_verify($input['current_password'], $user['password'])) {
            Response::error('Current password is incorrect.', 400);
        }

        User::updatePassword($user['id'], $input['new_password']);
        Response::success(null, 'Password changed successfully.');
    }

    /** POST /api/student/avatar */
    public static function uploadAvatar(): void {
        $user = AuthMiddleware::handle();
        CsrfMiddleware::validate();

        if (!isset($_FILES['avatar'])) {
            Response::error('No file provided.', 400);
        }

        $path = FileService::uploadAvatar($_FILES['avatar'], $user['id']);
        User::update($user['id'], ['avatar_image' => $path]);

        Response::success(['avatar_path' => $path], 'Avatar updated.');
    }
}
