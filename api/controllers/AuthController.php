<?php
/**
 * Auth Controller
 */
class AuthController {

    /** POST /api/auth/login */
    public static function login(): void {
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $v = Validator::make($input)
            ->required('email')
            ->email('email')
            ->required('password');

        if ($v->fails()) {
            Response::validationError($v->errors());
        }

        $user = User::findByEmail($v->sanitized('email'));
        if (!$user) {
            Response::error('Invalid credentials.', 401);
        }

        if (!password_verify($input['password'], $user['password'])) {
            Response::error('Invalid credentials.', 401);
        }

        // Check email verification (skip for admin)
        if (($user['role'] ?? '') !== 'admin' && isset($user['email_verified']) && !$user['email_verified']) {
            Response::error('Please verify your email before logging in.', 403);
        }

        // Start session
        if (session_status() === PHP_SESSION_NONE) session_start();
        $_SESSION['user_id'] = $user['id'];

        // Remove sensitive fields
        unset($user['password'], $user['verification_token'], $user['reset_token'], $user['reset_token_expires']);

        Response::success([
            'user'       => $user,
            'csrf_token' => CsrfMiddleware::getToken(),
        ], 'Login successful.');
    }

    /** POST /api/auth/register */
    public static function register(): void {
        error_log("REGISTER: Start");
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        error_log("REGISTER: Input parsed");

        $v = Validator::make($input)
            ->required('firstname')
            ->required('lastname')
            ->required('email')
            ->email('email')
            ->required('phone')
            ->phone('phone')
            ->required('student_id', 'Student ID')
            ->required('department')
            ->required('password')
            ->minLength('password', 6)
            ->required('confirm_password')
            ->matches('confirm_password', 'password', 'Confirm password', 'Password');

        if ($v->fails()) {
            error_log("REGISTER: Validation failed");
            Response::validationError($v->errors());
        }

        // Check duplicate email
        if (User::findByEmail($v->sanitized('email'))) {
            error_log("REGISTER: Duplicate email");
            Response::error('Email already registered.', 409);
        }

        $token = bin2hex(random_bytes(32));
        error_log("REGISTER: Creating user");
        
        try {
            $userId = User::create([
                'firstname'          => $v->sanitized('firstname'),
                'lastname'           => $v->sanitized('lastname'),
                'email'              => $v->sanitized('email'),
                'phone'              => $v->sanitized('phone'),
                'student_id'         => $v->sanitized('student_id'),
                'department'         => $v->sanitized('department'),
                'password'           => $input['password'],
                'verification_token' => $token,
            ]);
            error_log("REGISTER: User created ID: " . ($userId ?: 'null'));
        } catch (\Exception $e) {
            error_log("REGISTER: User model CRASH: " . $e->getMessage());
            throw $e;
        }

        if (!$userId) {
            Response::error('Registration failed. Please try again.', 500);
        }

        // Send verification email
        error_log("REGISTER: Sending email to " . $v->sanitized('email'));
        $emailSent = EmailService::sendVerification(
            $v->sanitized('email'),
            $v->sanitized('firstname'),
            $token
        );
        error_log("REGISTER: Email sent status: " . ($emailSent ? 'success' : 'failed'));

        // Create welcome notification
        try {
            Notification::create(
                $userId,
                'Welcome!',
                'Your account has been created. Please verify your email to get started.',
                'success'
            );
        } catch (\Exception $e) {}

        // AUTO-JOIN SELECTED GROUPS
        if (!empty($input['selectedGroups']) && is_array($input['selectedGroups'])) {
            $db = Database::getInstance();
            foreach ($input['selectedGroups'] as $groupId) {
                try {
                    $db->prepare("INSERT INTO group_members (group_id, user_id, status) VALUES (?, ?, 'active')")
                       ->execute([(int)$groupId, $userId]);
                } catch (\Exception $e) {
                    // Fallback for old schema
                    try {
                        $db->prepare("INSERT INTO group_members (group_id, user_id) VALUES (?, ?)")
                           ->execute([(int)$groupId, $userId]);
                    } catch (\Exception $ex) {}
                }
            }
        }

        Response::success(
            null,
            $emailSent 
                ? 'Registration successful! Please check your email to verify your account.'
                : 'Registration successful! However, we could not send the verification email. Please contact support.',
            201
        );
    }

    /** GET /api/auth/verify-email */
    public static function verifyEmail(): void {
        $token = $_GET['token'] ?? '';
        if (!$token) {
            Response::error('Invalid verification link.', 400);
        }

        if (User::verifyEmail($token)) {
            $origins = explode(',', CORS_ORIGIN);
            $redirectUrl = trim($origins[0]);
            header('Location: ' . $redirectUrl . '/#/login?verified=true');
            exit;
        }

        Response::error('Invalid or expired verification link.', 400);
    }

    /** POST /api/auth/forgot-password */
    public static function forgotPassword(): void {
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $v = Validator::make($input)->required('email')->email('email');
        if ($v->fails()) {
            Response::validationError($v->errors());
        }

        $user = User::findByEmail($v->sanitized('email'));

        // Always return success to prevent email enumeration
        if ($user) {
            $token = bin2hex(random_bytes(32));
            User::setResetToken($user['id'], $token);
            try {
                EmailService::sendPasswordReset($user['email'], $user['firstname'], $token);
            } catch (Exception $e) {
                error_log('Password reset email failed: ' . $e->getMessage());
            }
        }

        Response::success(null, 'If an account with that email exists, a reset link has been sent.');
    }

    /** POST /api/auth/reset-password */
    public static function resetPassword(): void {
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $v = Validator::make($input)
            ->required('token')
            ->required('password')
            ->minLength('password', 6)
            ->required('confirm_password')
            ->matches('confirm_password', 'password', 'Confirm password', 'Password');

        if ($v->fails()) {
            Response::validationError($v->errors());
        }

        $user = User::findByResetToken($input['token']);
        if (!$user) {
            Response::error('Invalid or expired reset token.', 400);
        }

        User::updatePassword($user['id'], $input['password']);
        Response::success(null, 'Password has been reset successfully.');
    }

    /** GET /api/auth/me */
    public static function me(): void {
        $user = AuthMiddleware::handle();
        unset($user['password'], $user['verification_token'], $user['reset_token'], $user['reset_token_expires']);

        Response::success([
            'user'       => $user,
            'csrf_token' => CsrfMiddleware::getToken(),
        ]);
    }

    /** POST /api/auth/logout */
    public static function logout(): void {
        if (session_status() === PHP_SESSION_NONE) session_start();
        session_destroy();
        Response::success(null, 'Logged out successfully.');
    }

    /** POST /api/auth/google - Initiation */
    public static function googleLogin(): void {
        // Root path detection for config
        $rootPath = dirname(dirname(__DIR__));
        $googleConfigPath = $rootPath . '/google_config.php';
        if (!file_exists($googleConfigPath)) {
            $googleConfigPath = dirname(__DIR__) . '/google_config.php';
        }
        
        require_once $googleConfigPath;
        
        $url = getGoogleLoginUrl();
        Response::success(['url' => $url], 'Google authentication initiated.');
    }

    /** GET /api/auth/google-callback */
    public static function googleCallback(): void {
        // Standardize path - AuthController is in api/controllers/
        $rootPath = dirname(dirname(__DIR__));
        $googleConfigPath = $rootPath . '/google_config.php';
        
        if (!file_exists($googleConfigPath)) {
            $googleConfigPath = dirname(__DIR__) . '/google_config.php';
        }

        require_once $googleConfigPath;
        
        $code = $_GET['code'] ?? '';
        if (!$code) {
            Response::error('No code provided', 400);
        }

        $result = handleGoogleCallback($code);

        if ($result['success']) {
            $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
            $frontend_url = str_ends_with($origin, '.vercel.app') ? $origin : (getenv('FRONTEND_URL') ?: 'https://campus-dive-v2.vercel.app');
            $params = isset($result['new_user']) ? '?welcome=true' : '';
            header("Location: " . rtrim($frontend_url, '/') . "/dashboard" . $params);
            exit;
        } else {
            // Redirect back to login with error
            $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
            $frontend_url = str_ends_with($origin, '.vercel.app') ? $origin : (getenv('FRONTEND_URL') ?: 'https://campus-dive-v2.vercel.app');
            header("Location: " . rtrim($frontend_url, '/') . "/login?error=" . urlencode($result['error']));
            exit;
        }
    }
}