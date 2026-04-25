<?php
/**
 * Authentication Middleware
 * Validates session and loads user data into the request
 */
class AuthMiddleware {

    public static function handle(): array {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user_id'])) {
            Response::unauthorized('Please log in to continue.');
        }

        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT u.*, r.name as role_name FROM users u LEFT JOIN roles r ON u.role_id = r.id WHERE u.id = ?');
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();

        if (!$user) {
            session_destroy();
            Response::unauthorized('Invalid session. Please log in again.');
        }

        return $user;
    }

    /**
     * Optional auth — returns user or null (doesn't block)
     */
    public static function optional(): ?array {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user_id'])) {
            return null;
        }

        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT u.*, r.name as role_name FROM users u LEFT JOIN roles r ON u.role_id = r.id WHERE u.id = ?');
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch() ?: null;
    }
}
