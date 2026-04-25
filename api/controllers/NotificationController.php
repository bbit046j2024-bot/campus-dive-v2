<?php
/**
 * Notification Controller
 */
class NotificationController {

    /** GET /api/notifications */
    public static function index(): void {
        $user = AuthMiddleware::handle();
        $limit = intval($_GET['limit'] ?? 20);
        $notifications = Notification::getByUserId($user['id'], $limit);
        $unread = Notification::getUnreadCount($user['id']);

        Response::success([
            'notifications' => $notifications,
            'unread_count'  => $unread,
        ]);
    }

    /** PUT /api/notifications/:id/read */
    public static function markRead(int $id): void {
        $user = AuthMiddleware::handle();
        Notification::markAsRead($id, $user['id']);
        Response::success(null, 'Notification marked as read.');
    }

    /** PUT /api/notifications/read-all */
    public static function markAllRead(): void {
        $user = AuthMiddleware::handle();
        Notification::markAllAsRead($user['id']);
        Response::success(null, 'All notifications marked as read.');
    }

    /** GET /api/notifications/unread-count */
    public static function unreadCount(): void {
        $user = AuthMiddleware::handle();
        $count = Notification::getUnreadCount($user['id']);
        Response::success(['count' => $count]);
    }
}
