<?php
/**
 * Campus Dive V2 - Production Router
 * All API requests funnel through here.
 * [Redeploy Trigger: v2.0.2]
 */

// --- SESSION COOKIES ---
// Auto-detect environment: use SameSite=None + Secure only in production
// (cross-domain Railway/Vercel setup). On Replit dev, use Lax + no Secure.
$_isReplitDev = !empty(getenv('REPL_ID')) || !empty(getenv('REPLIT_DEV_DOMAIN'));
$_isProduction = (getenv('APP_ENV') === 'production') && !$_isReplitDev;
session_set_cookie_params([
    'lifetime' => 7200,
    'path'     => '/',
    'domain'   => '',
    'secure'   => $_isProduction,
    'httponly' => true,
    'samesite' => $_isProduction ? 'None' : 'Lax',
]);

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/middleware/AuthMiddleware.php';
require_once __DIR__ . '/middleware/RoleMiddleware.php';
require_once __DIR__ . '/middleware/CsrfMiddleware.php';
require_once __DIR__ . '/helpers/Response.php';
require_once __DIR__ . '/helpers/Validator.php';

// Controllers
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/StudentController.php';
require_once __DIR__ . '/controllers/AdminController.php';
require_once __DIR__ . '/controllers/AdminGroupController.php';
require_once __DIR__ . '/controllers/SocialController.php';
require_once __DIR__ . '/controllers/GroupController.php';
require_once __DIR__ . '/controllers/GroupPostController.php';
require_once __DIR__ . '/controllers/GroupMessageController.php';
require_once __DIR__ . '/controllers/GroupManagerController.php';
require_once __DIR__ . '/controllers/MessageController.php';
require_once __DIR__ . '/controllers/NotificationController.php';

// Services
require_once __DIR__ . '/services/EmailService.php';

// Models
require_once __DIR__ . '/models/User.php';
require_once __DIR__ . '/models/Document.php';
require_once __DIR__ . '/models/Role.php';
require_once __DIR__ . '/models/Permission.php';
require_once __DIR__ . '/models/InterviewSlot.php';
require_once __DIR__ . '/models/ApplicationStage.php';
require_once __DIR__ . '/models/Notification.php';
require_once __DIR__ . '/models/Message.php';

// Simple Router
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = str_replace(['/api', '/Campus-Dive/api', '/Campus-Dive-main/api'], '', $path);
$path = '/' . trim($path, '/');

// --- DYNAMIC CORS HANDSHAKE ---
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowed_origins = [
    'https://campus-dive-v2.vercel.app',
    'https://campus-dive-v2-production.up.railway.app',
    'http://localhost:5173',
    'http://localhost:5000',
    'http://localhost:3000',
    'http://0.0.0.0:5000',
];

// Allow any *.replit.dev or *.repl.co origin for local development
$isReplitOrigin = preg_match('/^https?:\/\/[a-zA-Z0-9\-]+\.(replit\.dev|repl\.co|replit\.app)(:\d+)?$/', $origin);

if (in_array($origin, $allowed_origins) || $isReplitOrigin) {
    header("Access-Control-Allow-Origin: $origin");
} elseif (empty($origin)) {
    // Same-origin request (no Origin header) — no CORS header needed
} else {
    // Unknown origin — reflect it back only in non-production to avoid
    // leaking credentials to arbitrary third-party sites.
    // In production, block silently by not emitting an Allow-Origin header.
    if (getenv('APP_ENV') !== 'production') {
        header("Access-Control-Allow-Origin: $origin");
    }
}

header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-CSRF-Token");
header("Access-Control-Allow-Credentials: true");

if ($method === 'OPTIONS') {
    exit(0);
}

// Exact Routes Map
$routes = [
    // Auth
    'POST /auth/register'       => ['AuthController', 'register'],
    'POST /auth/login'          => ['AuthController', 'login'],
    'POST /auth/google'         => ['AuthController', 'googleLogin'],
    'GET  /auth/google-url'     => ['AuthController', 'googleLogin'],
    'GET  /auth/google-callback'=> ['AuthController', 'googleCallback'],
    'GET  /auth/me'             => ['AuthController', 'me'],
    'POST /auth/forgot-password'=> ['AuthController', 'forgotPassword'],
    'POST /auth/reset-password' => ['AuthController', 'resetPassword'],
    'GET  /auth/verify-email'   => ['AuthController', 'verifyEmail'],

    'POST /auth/logout'         => ['AuthController', 'logout'],

    // Student Dashboard
    'GET /student/dashboard'    => ['StudentController', 'dashboard'],
    'GET /student/documents'    => ['StudentController', 'getDocuments'],
    'POST /student/documents'   => ['StudentController', 'uploadDocument'],
    'POST /student/settings'    => ['StudentController', 'updateSettings'],
    'PUT  /student/profile'     => ['StudentController', 'updateProfile'],
    'PUT  /student/password'    => ['StudentController', 'changePassword'],
    'POST /student/avatar'      => ['StudentController', 'uploadAvatar'],

    // Admin / Management
    'GET /admin/dashboard'      => ['AdminController', 'dashboard'],
    'GET /admin/students'       => ['AdminController', 'students'],
    'POST /admin/students/bulk-action' => ['AdminController', 'bulkAction'],
    'GET /admin/roles'          => ['AdminController', 'roles'],
    'GET /admin/users'          => ['AdminController', 'users'],
    'GET /admin/analytics'      => ['AdminController', 'dashboard'],
    
    // Administrative System & Broadcast
    'POST /admin/system/test-email' => ['AdminController', 'testEmail'],
    'POST /admin/system/broadcast'  => ['AdminController', 'broadcast'],

    // Health & Diagnostics
    'GET /health'               => 'handle_health_check',
    'GET /system/debug-email'   => 'handle_email_debug',
    'GET /system/debug-db'      => 'handle_db_debug',
    'GET /system/google-check'  => function() {
        $output = [
            'vendor_exists' => file_exists(__DIR__ . '/../vendor/autoload.php'),
            'google_client_id' => substr(GOOGLE_CLIENT_ID, 0, 10) . '...',
            'google_redirect_uri' => GOOGLE_REDIRECT_URI,
        ];
        try {
            $googleConfigPath = file_exists(__DIR__ . '/../google_config.php') ? __DIR__ . '/../google_config.php' : __DIR__ . '/google_config.php';
            require_once $googleConfigPath;
            $output['login_url'] = getGoogleLoginUrl();
            $output['status'] = 'OK';
        } catch (Exception $e) {
            $output['status'] = 'ERROR';
            $output['error'] = $e->getMessage();
        }
        Response::success($output);
    },

    // Social (Shared)
    'GET /social/validate-url'  => ['SocialController', 'validateUrl'],
    'GET /social/profile'       => ['SocialController', 'getProfile'],
    'GET /social/groups/public' => ['SocialController', 'getPublicGroups'],

    // Groups & Feed
    'GET /social/feed'          => ['GroupPostController', 'globalFeed'],
    'GET /social/groups'        => ['GroupController', 'index'],
    'GET /social/groups/detail' => ['GroupController', 'show'],
    'GET /social/groups/members' => ['GroupController', 'getMembers'],
    'POST /social/groups/join'  => ['GroupController', 'join'],
    'POST /social/groups/leave' => ['GroupController', 'leave'],

    // Posts & Interaction
    'GET /social/posts'         => ['GroupPostController', 'index'],
    'GET /social/posts/show'    => ['GroupPostController', 'show'],
    'GET /social/posts/comments'=> ['GroupPostController', 'getComments'],
    'POST /social/posts'        => ['GroupPostController', 'store'],
    'POST /social/posts/like'   => ['GroupPostController', 'toggleLike'],
    'POST /social/posts/comment'=> ['GroupPostController', 'comment'],

    // Chat
    'GET /social/messages'      => ['GroupMessageController', 'index'],
    'POST /social/messages'     => ['GroupMessageController', 'store'],

    // Admin Group Management
    'GET /social/admin/groups'     => ['AdminGroupController', 'index'],
    'POST /social/admin/groups'    => ['AdminGroupController', 'store'],
    'DELETE /social/admin/groups'  => ['AdminGroupController', 'destroy'],
    'POST /social/admin/assign-manager' => ['AdminGroupController', 'assignManager'],

    // Manager Controls
    'POST /social/manager/settings'      => ['GroupManagerController', 'updateSettings'],
    'POST /social/manager/member-status'  => ['GroupManagerController', 'updateMemberStatus'],
    'GET /social/manager/pending'        => ['GroupManagerController', 'getPendingPosts'],
    'POST /social/manager/moderate-post' => ['GroupManagerController', 'moderatePost'],
    
    // Notifications & Messages (Direct)
    'GET /notifications'              => ['NotificationController', 'index'],
    'POST /notifications/clear'       => ['NotificationController', 'markAllRead'],
    'GET /notifications/unread-count' => ['NotificationController', 'unreadCount'],
    
    'GET /messages'                   => ['MessageController', 'index'],
    'POST /messages'                  => ['MessageController', 'send'],
    'GET /messages/conversations'     => ['MessageController', 'conversations'],
    'GET /messages/users'             => ['MessageController', 'getUsers'],
    'GET /messages/unread-count'      => ['MessageController', 'unreadCount'],
];

function handle_health_check() {
    $checks = [];

    // 1. Database connectivity
    try {
        $db = Database::getInstance();
        $db->query("SELECT 1");
        $checks['database'] = ['status' => 'ok', 'driver' => 'PDO/MySQL (TiDB compatible)'];
    } catch (Exception $e) {
        $checks['database'] = ['status' => 'error', 'message' => $e->getMessage()];
    }

    // 2. Session cookie configuration
    $cookieParams = session_get_cookie_params();
    $checks['session_cookie'] = [
        'samesite' => $cookieParams['samesite'] ?? 'not set',
        'secure'   => $cookieParams['secure'] ? 'true' : 'false',
        'httponly' => $cookieParams['httponly'] ? 'true' : 'false',
        'status'   => ($cookieParams['samesite'] === 'None' && $cookieParams['secure']) ? 'ok' : 'warning: cross-domain cookies may fail',
    ];

    // 3. Google OAuth env vars
    $googleId     = getenv('GOOGLE_CLIENT_ID');
    $googleSecret = getenv('GOOGLE_CLIENT_SECRET');
    $googleUri    = getenv('GOOGLE_REDIRECT_URI');
    $checks['google_oauth'] = [
        'GOOGLE_CLIENT_ID'     => $googleId     ? ('set (' . substr($googleId, 0, 8) . '...)') : 'MISSING',
        'GOOGLE_CLIENT_SECRET' => $googleSecret ? 'set'  : 'MISSING',
        'GOOGLE_REDIRECT_URI'  => $googleUri    ?: 'MISSING (will be auto-detected)',
    ];

    // 4. App env vars
    $checks['app'] = [
        'APP_URL'      => getenv('APP_URL')      ?: APP_URL,
        'FRONTEND_URL' => getenv('FRONTEND_URL') ?: 'not set',
        'APP_ENV'      => getenv('APP_ENV')      ?: 'not set',
        'APP_DEBUG'    => defined('APP_DEBUG') && APP_DEBUG ? 'true' : 'false',
    ];

    // 5. CORS origin (current request)
    $checks['cors'] = [
        'request_origin' => $_SERVER['HTTP_ORIGIN'] ?? '(no Origin header)',
    ];

    $allOk = $checks['database']['status'] === 'ok'
          && $checks['session_cookie']['status'] === 'ok'
          && $googleId && $googleSecret;

    http_response_code($allOk ? 200 : 500);
    header('Content-Type: application/json');
    echo json_encode(['ok' => $allOk, 'checks' => $checks], JSON_PRETTY_PRINT);
    exit;
}

function handle_email_debug() {
    $diag = EmailService::getDiagnostics();
    Response::success($diag, 'Email configuration diagnostics');
    exit;
}

function handle_db_debug() {
    try {
        $db = Database::getInstance();
        $userStatsResult = $db->query("SELECT role, role_id, status, COUNT(*) as count FROM users GROUP BY role, role_id, status")->fetchAll();
        Response::success(['user_stats' => $userStatsResult]);
    } catch (Exception $e) {
        Response::error("DB Debug Failed: " . $e->getMessage(), 500);
    }
}

// Parameterized routes (with :id)
$paramRoutes = [
    'DELETE /student/documents/:id'          => ['StudentController', 'deleteDocument'],
    'GET    /admin/students/:id'             => ['AdminController', 'studentDetail'],
    'PUT    /admin/students/:id/status'      => ['AdminController', 'updateStudentStatus'],
    'PUT    /admin/roles/:id'                => ['AdminController', 'updateRole'],
    'PUT    /admin/users/:id/role'           => ['AdminController', 'updateUserRole'],
    'GET    /messages/thread/:id'            => ['MessageController', 'thread'],
    'PUT    /messages/:id/read'              => ['MessageController', 'markRead'],
    'DELETE /messages/conversation/:id'      => ['MessageController', 'deleteConversation'],
    'PUT    /notifications/:id/read'         => ['NotificationController', 'markRead'],
];

// Route Matching
foreach ($routes as $pattern => $handler) {
    $parts = preg_split('/\s+/', trim($pattern), 2);
    $patternMethod = $parts[0];
    $patternPath = isset($parts[1]) ? $parts[1] : '';

    if ($patternMethod === $method && $patternPath === $path) {
        if (is_callable($handler)) {
            $handler();
        } else {
            [$class, $action] = $handler;
            $class::$action();
        }
        exit;
    }
}

foreach ($paramRoutes as $pattern => $handler) {
    $parts = preg_split('/\s+/', trim($pattern), 2);
    $patternMethod = $parts[0];
    $patternPath = $parts[1];
    if ($patternMethod !== $method) continue;
    $regex = preg_replace('#:(\w+)#', '(\d+)', $patternPath);
    $regex = '#^' . $regex . '$#';
    if (preg_match($regex, $path, $matches)) {
        array_shift($matches);
        [$class, $action] = $handler;
        $class::$action(...array_map('intval', $matches));
        exit;
    }
}

if ($path === '/' || $path === '') {
    Response::success(['app' => APP_NAME, 'version' => '2.0.0'], 'Campus Dive API is running.');
    exit;
}

Response::notFound('Route not found: ' . $method . ' ' . $path);
