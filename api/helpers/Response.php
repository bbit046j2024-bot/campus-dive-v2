<?php
/**
 * Standardized JSON Response Helper
 */
class Response {

    public static function json(mixed $data, int $status = 200): void {
        ob_clean(); // ← CLEAR ANY STRAY OUTPUT BEFORE RESPONDING
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function success(mixed $data = null, string $message = 'Success', int $status = 200): void {
        self::json([
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ], $status);
    }

    public static function error(string $message = 'Error', int $status = 400, mixed $errors = null): void {
        $payload = [
            'success' => false,
            'message' => $message,
        ];
        if ($errors !== null) {
            $payload['errors'] = $errors;
        }
        self::json($payload, $status);
    }

    public static function unauthorized(string $message = 'Unauthorized'): void {
        self::error($message, 401);
    }

    public static function forbidden(string $message = 'Forbidden'): void {
        self::error($message, 403);
    }

    public static function notFound(string $message = 'Not found'): void {
        self::error($message, 404);
    }

    public static function validationError(array $errors): void {
        self::error('Validation failed', 422, $errors);
    }
}