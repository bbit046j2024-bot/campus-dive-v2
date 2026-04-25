<?php
/**
 * CSRF Protection Middleware
 */
class CsrfMiddleware {

    /**
     * Generate and store a CSRF token in the session
     */
    public static function generateToken(): string {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $token = bin2hex(random_bytes(32));
        $_SESSION[CSRF_TOKEN_NAME] = $token;
        return $token;
    }

    /**
     * Validate CSRF token from request header or body
     */
    public static function validate(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Skip for GET/OPTIONS/HEAD requests
        $method = $_SERVER['REQUEST_METHOD'];
        if (in_array($method, ['GET', 'OPTIONS', 'HEAD'])) {
            return;
        }

        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] 
            ?? $_POST[CSRF_TOKEN_NAME] 
            ?? null;

        if (!$token || !isset($_SESSION[CSRF_TOKEN_NAME]) || !hash_equals($_SESSION[CSRF_TOKEN_NAME], $token)) {
            Response::error('Invalid CSRF token.', 403);
        }
    }

    /**
     * Get current token (generate if not exists)
     */
    public static function getToken(): string {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
            return self::generateToken();
        }
        return $_SESSION[CSRF_TOKEN_NAME];
    }
}
