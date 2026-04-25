<?php
/**
 * Message Controller
 */
class MessageController {

    /** GET /api/messages/conversations */
    public static function conversations(): void {
        $user = AuthMiddleware::handle();
        $conversations = Message::getConversations($user['id']);
        Response::success($conversations);
    }

    /** GET /api/messages/thread/:userId */
    public static function thread(int $otherUserId): void {
        $user = AuthMiddleware::handle();

        $messages = Message::getThread($user['id'], $otherUserId);

        // Mark messages from other user as read
        Message::markThreadAsRead($otherUserId, $user['id']);

        // Get other user's info
        $otherUser = User::findById($otherUserId);
        if ($otherUser) {
            unset($otherUser['password'], $otherUser['verification_token'], $otherUser['reset_token'], $otherUser['reset_token_expires']);
        }

        Response::success([
            'messages'   => $messages,
            'other_user' => $otherUser,
        ]);
    }

    /** POST /api/messages */
    public static function send(): void {
        $user = AuthMiddleware::handle();
        CsrfMiddleware::validate();

        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $v = Validator::make($input)
            ->required('receiver_id')
            ->numeric('receiver_id')
            ->required('message');

        if ($v->fails()) Response::validationError($v->errors());

        // Handle file attachment
        $attachmentPath = null;
        if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
            $fileData = FileService::uploadDocument($_FILES['attachment'], $user['id']);
            $attachmentPath = 'uploads/' . $fileData['filename'];
        }

        $msgId = Message::send([
            'sender_id'       => $user['id'],
            'receiver_id'     => intval($input['receiver_id']),
            'subject'         => $input['subject'] ?? '',
            'message'         => $input['message'],
            'attachment_path' => $attachmentPath,
            'type'            => $attachmentPath ? 'file' : 'text',
        ]);

        if (!$msgId) {
            Response::error('Failed to send message.', 500);
        }

        // Notify recipient
        $senderName = ($user['firstname'] ?? '') . ' ' . ($user['lastname'] ?? '');
        Notification::create(
            intval($input['receiver_id']),
            'New Message',
            "You have a new message from {$senderName}.",
            'message'
        );

        Response::success(['id' => $msgId], 'Message sent.', 201);
    }

    /** PUT /api/messages/:id/read */
    public static function markRead(int $id): void {
        $user = AuthMiddleware::handle();
        Message::markAsRead($id, $user['id']);
        Response::success(null, 'Message marked as read.');
    }

    /** GET /api/messages/unread-count */
    public static function unreadCount(): void {
        $user = AuthMiddleware::handle();
        $count = Message::getUnreadCount($user['id']);
        Response::success(['count' => $count]);
    }

    /** DELETE /api/messages/conversation/:userId */
    public static function deleteConversation(int $otherUserId): void {
        $user = AuthMiddleware::handle();
        Message::deleteConversation($user['id'], $otherUserId);
        Response::success(null, 'Conversation deleted.');
    }

    /** GET /api/messages/users — list of users available to message */
    public static function getUsers(): void {
        $user = AuthMiddleware::handle();
        $users = Message::getUsers($user['id']);
        Response::success($users);
    }
}
