<?php
/**
 * Campus Dive V2 - Production Router
 * All API requests funnel through here.
 */

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/middleware/AuthMiddleware.php';
require_once __DIR__ . '/middleware/RoleMiddleware.php';
require_once __DIR__ . '/middleware/CsrfMiddleware.php';
require_once __DIR__ . '/utils/Response.php';
require_once __DIR__ . '/utils/Validator.php';

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

// Security Headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-CSRF-Token");

if ($method === 'OPTIONS') {
    exit(0);
}

// Exact Routes Map
$routes = [
    // Auth
    'POST /auth/register'       => ['AuthController', 'register'],
    'POST /auth/login'          => ['AuthController', 'login'],
    'POST /auth/google'         => ['AuthController', 'googleLogin'],
    'GET  /auth/me'             => ['AuthController', 'me'],
    'POST /auth/forgot-password'=> ['AuthController', 'forgotPassword'],
    'POST /auth/reset-password' => ['AuthController', 'resetPassword'],
    'GET  /auth/verify-email'   => ['AuthController', 'verifyEmail'],

    // Student Dashboard
    'GET /student/dashboard'    => ['StudentController', 'dashboard'],
    'GET /student/documents'    => ['StudentController', 'getDocuments'],
    'POST /student/documents'   => ['StudentController', 'uploadDocument'],
    'POST /student/settings'    => ['StudentController', 'updateSettings'],

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

    // Debugging Uplinks
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
    'GET /notifications'         => ['NotificationController', 'index'],
    'POST /notifications/clear'  => ['NotificationController', 'clearAll'],
    'GET /messages'              => ['MessageController', 'index'],
    'POST /messages'             => ['MessageController', 'store'],
];

function handle_email_debug() {
    $output = "--- Campus Dive Email Config Check ---\n";
    $output .= "MAIL_HOST: " . MAIL_HOST . "\n";
    $output .= "MAIL_PORT: " . MAIL_PORT . "\n";
    $output .= "MAIL_USERNAME: " . MAIL_USERNAME . "\n";
    header('Content-Type: text/plain');
    echo $output; exit;
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
    $parts = explode(' ', trim($pattern), 2);
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
