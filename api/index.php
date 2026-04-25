<?php
ob_start(); // ← OUTPUT BUFFERING - MUST BE FIRST LINE

// Load local .env file if it exists (for local development)
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, '#') === 0 || strpos($line, '=') === false) continue;
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        putenv("$name=$value");
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }
}

// 1. Error handling & Shutdown (Register as early as possible)
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Proxy Fix for HTTPS
if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
    $_SERVER['HTTPS'] = 'on';
}

register_shutdown_function(function () {
    $error = error_get_last();
    if ($error !== NULL && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        if (ob_get_length()) ob_clean();
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Fatal Error (Shutdown): ' . $error['message'],
            'file'    => defined('APP_DEBUG') && APP_DEBUG ? $error['file'] : null,
            'line'    => defined('APP_DEBUG') && APP_DEBUG ? $error['line'] : null,
        ]);
        exit;
    }
});

set_exception_handler(function (Throwable $e) {
    if (ob_get_length()) ob_clean(); 
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Uncaught Exception: ' . $e->getMessage(),
        'file'    => defined('APP_DEBUG') && APP_DEBUG ? $e->getFile() : null,
        'line'    => defined('APP_DEBUG') && APP_DEBUG ? $e->getLine() : null,
    ]);
    exit;
});

/**
 * Campus Dive API — Front Controller / Router
 */

// 2. [CORS & Preflight handled by root index.php]


// Session Configuration (Required for cross-site Vercel <-> Railway)
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '', // Use current host
        'secure'   => true,   // Required for SameSite=None
        'httponly' => true,
        'samesite' => 'None', // Required for cross-site
    ]);
    session_start();
}

// Load framework
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/helpers/Response.php';
require_once __DIR__ . '/helpers/Validator.php';

// Middleware
require_once __DIR__ . '/middleware/AuthMiddleware.php';
require_once __DIR__ . '/middleware/RoleMiddleware.php';
require_once __DIR__ . '/middleware/CsrfMiddleware.php';

// Models
require_once __DIR__ . '/models/User.php';
require_once __DIR__ . '/models/Document.php';
require_once __DIR__ . '/models/Message.php';
require_once __DIR__ . '/models/Notification.php';
require_once __DIR__ . '/models/Role.php';
require_once __DIR__ . '/models/ApplicationStage.php';
require_once __DIR__ . '/models/InterviewSlot.php';
require_once __DIR__ . '/models/Permission.php';
require_once __DIR__ . '/models/MarketingTemplate.php';
require_once __DIR__ . '/models/MarketingCampaign.php';
require_once __DIR__ . '/models/MarketingQueue.php';
require_once __DIR__ . '/models/DocumentVersion.php';
require_once __DIR__ . '/models/DocumentContent.php';
require_once __DIR__ . '/models/RecruitmentLetter.php';
require_once __DIR__ . '/models/Analytics.php';

// Services
require_once __DIR__ . '/services/EmailService.php';
require_once __DIR__ . '/services/FileService.php';

// Controllers
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/StudentController.php';
require_once __DIR__ . '/controllers/AdminController.php';
require_once __DIR__ . '/controllers/MessageController.php';
require_once __DIR__ . '/controllers/NotificationController.php';
require_once __DIR__ . '/controllers/SocialController.php';
require_once __DIR__ . '/controllers/GroupController.php';
require_once __DIR__ . '/controllers/GroupPostController.php';
require_once __DIR__ . '/controllers/GroupMessageController.php';
require_once __DIR__ . '/controllers/AdminGroupController.php';
require_once __DIR__ . '/controllers/GroupManagerController.php';
require_once __DIR__ . '/services/UrlMediaService.php';

// Parse route
$requestUri = $_SERVER['REQUEST_URI'];
$scriptName = $_SERVER['SCRIPT_NAME'];
$basePath = str_replace('/index.php', '', $scriptName);
$path = parse_url($requestUri, PHP_URL_PATH);

// Strip the base path if we are running in a subdirectory (like WAMP/XAMPP)
if (str_starts_with($path, $basePath)) {
    $path = substr($path, strlen($basePath));
}

$path = rtrim($path, '/') ?: '/';

// Specialized logic for Railway/Vercel: Strip /api prefix if present
if (str_starts_with($path, '/api/')) {
    $path = substr($path, 4);
}
if ($path === '/api') {
    $path = '/';
}
$method = $_SERVER['REQUEST_METHOD'];

// Debug: Log all requests
if (defined('APP_DEBUG') && APP_DEBUG) {
    error_log("REQUEST: {$method} {$path} (Original: {$_SERVER['REQUEST_URI']}, Script: {$_SERVER['SCRIPT_NAME']}, Base: {$basePath})");
}

// ────────────────────────────────────────────
// Route Definitions
// ────────────────────────────────────────────

$routes = [
    // Auth (public)
    'POST /auth/login'           => ['AuthController', 'login'],
    'POST /auth/register'        => ['AuthController', 'register'],
    'GET  /auth/verify-email'    => ['AuthController', 'verifyEmail'],
    'POST /auth/forgot-password' => ['AuthController', 'forgotPassword'],
    'POST /auth/reset-password'  => ['AuthController', 'resetPassword'],
    'GET  /auth/me'              => ['AuthController', 'me'],
    'POST /auth/logout'          => ['AuthController', 'logout'],
    'GET  /auth/csrf'            => function() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        Response::success(['csrf_token' => CsrfMiddleware::getToken()]);
    },
    'GET /ping'   => function() { 
        $rootPath = dirname(__DIR__);
        Response::success([
            'pong' => true, 
            'current_dir' => __DIR__,
            'root_path' => $rootPath,
            'root_exists' => is_dir($rootPath),
            'google_config_in_root' => file_exists($rootPath . '/google_config.php'),
            'google_config_in_api' => file_exists(__DIR__ . '/google_config.php'),
            'google_id_set' => !empty(getenv('GOOGLE_CLIENT_ID')),
            'script_filename' => $_SERVER['SCRIPT_FILENAME'] ?? 'N/A'
        ]); 
    },

    // Student
    'GET  /student/dashboard'    => ['StudentController', 'dashboard'],
    'GET  /student/documents'    => ['StudentController', 'documents'],
    'POST /student/documents'    => ['StudentController', 'uploadDocument'],
    'PUT  /student/profile'      => ['StudentController', 'updateProfile'],
    'PUT  /student/password'     => ['StudentController', 'changePassword'],
    'POST /student/avatar'       => ['StudentController', 'uploadAvatar'],

    // Admin
    'GET  /admin/dashboard'      => ['AdminController', 'dashboard'],
    'GET  /admin/students'       => ['AdminController', 'students'],
    'GET  /admin/users'          => ['AdminController', 'users'],
    'POST /admin/students/bulk-action' => ['AdminController', 'bulkAction'],
    'GET  /admin/roles'          => ['AdminController', 'roles'],

    // Messages
    'GET  /messages/conversations'  => ['MessageController', 'conversations'],
    'POST /messages'                => ['MessageController', 'send'],
    'GET  /messages/unread-count'   => ['MessageController', 'unreadCount'],
    'GET  /messages/users'          => ['MessageController', 'getUsers'],

    // Notifications
    'GET  /notifications'           => ['NotificationController', 'index'],
    'PUT  /notifications/read-all'  => ['NotificationController', 'markAllRead'],
    'GET  /notifications/unread-count' => ['NotificationController', 'unreadCount'],

    // Logs (Debug Only)
    'GET  /logs/emails' => function() {
        if (!defined('APP_DEBUG') || !APP_DEBUG) {
            Response::error('Logs only accessible in debug mode.', 403);
        }
        $logFile = __DIR__ . '/logs/email_errors.log';
        if (!file_exists($logFile)) {
            Response::success(['logs' => 'No logs found yet.'], 'No email error logs available.');
        }
        $content = file_get_contents($logFile);
        echo "<pre>$content</pre>";
        exit;
    },

    // Debug DB Inspector
    'GET /debug/db' => function() {
        return handle_db_debug();
    },
    'GET /api/debug/db' => function() {
        return handle_db_debug();
    },
    'GET /api/debug/email' => function() {
        return handle_email_debug();
    },

    // Migrations (Internal/Temporary)
    'GET /auth/migrate-google-auth' => function() {
        try {
            $db = Database::getInstance();
            // Check if column exists
            $check = $db->query("SHOW COLUMNS FROM users LIKE 'google_id'")->fetch();
            
            if (!$check) {
                $db->exec("ALTER TABLE users ADD COLUMN google_id VARCHAR(255) UNIQUE AFTER email");
                Response::success(null, "Successfully added google_id column to users table.");
            } else {
                Response::success(null, "google_id column already exists.");
            }
        } catch (Exception $e) {
            Response::error("Migration failed: " . $e->getMessage(), 500);
        }
    },
    'GET /auth/migrate-social-profile' => function() {
        try {
            $db = Database::getInstance();
            $messages = [];
            
            // Add bio
            $check = $db->query("SHOW COLUMNS FROM users LIKE 'bio'")->fetch();
            if (!$check) {
                $db->exec("ALTER TABLE users ADD COLUMN bio TEXT DEFAULT NULL");
                $messages[] = "Added 'bio' column.";
            }
            
            // Add location
            $check = $db->query("SHOW COLUMNS FROM users LIKE 'location'")->fetch();
            if (!$check) {
                $db->exec("ALTER TABLE users ADD COLUMN location VARCHAR(100) DEFAULT NULL");
                $messages[] = "Added 'location' column.";
            }

            if (empty($messages)) {
                Response::success(null, "Social profile columns already exist.");
            } else {
                Response::success(null, implode(" ", $messages));
            }
        } catch (Exception $e) {
            Response::error("Migration failed: " . $e->getMessage(), 500);
        }
    },
    'GET /inspect-db' => function() {
        require_once __DIR__ . '/inspect_db.php';
    },

    'GET /auth/google-url' => function() {
        try {
            $rootPath = dirname(__DIR__);
            $possiblePaths = [
                $rootPath . DIRECTORY_SEPARATOR . 'google_config.php',
                __DIR__ . DIRECTORY_SEPARATOR . 'google_config.php'
            ];
            
            $googleConfigPath = null;
            foreach ($possiblePaths as $p) {
                if (file_exists($p)) {
                    $googleConfigPath = $p;
                    break;
                }
            }
            
            if (!$googleConfigPath) {
                $checked = implode(', ', $possiblePaths);
                error_log("Google Config Error: File not found at checked paths: $checked");
                Response::error("Google Auth configuration file is missing. Checked: $checked", 500);
            }

            require_once $googleConfigPath;
            
            // Guard against placeholder secret
            if (!defined('GOOGLE_CLIENT_SECRET') || GOOGLE_CLIENT_SECRET === 'your-google-client-secret' || empty(GOOGLE_CLIENT_SECRET)) {
                Response::error('Google OAuth is not configured on this server.', 503);
            }
            $url = getGoogleLoginUrl();
            Response::success(['url' => $url]);
        } catch (\Throwable $e) {
            error_log('Google URL Error: ' . $e->getMessage());
            Response::error('Google Login is currently unavailable: ' . $e->getMessage(), 503);
        }
    },

    'GET /auth/google-callback' => ['AuthController', 'googleCallback'],

    'GET /debug/google' => function() {
        if (!defined('APP_DEBUG') || !APP_DEBUG) {
            // Guard normally, but allow for this specific production debug
        }
        
        $output = [
            'vendor_exists' => file_exists(__DIR__ . '/../vendor/autoload.php'),
            'google_client_id' => substr(GOOGLE_CLIENT_ID, 0, 10) . '...',
            'google_redirect_uri' => GOOGLE_REDIRECT_URI,
            'env_client_id' => getenv('GOOGLE_CLIENT_ID') ? 'SET' : 'NOT SET',
            'env_client_secret' => getenv('GOOGLE_CLIENT_SECRET') ? 'SET' : 'NOT SET',
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
];

function handle_email_debug() {
    if (!defined('APP_DEBUG') || !APP_DEBUG) {
        // Allow it for now since we are debugging a critical production issue, 
        // but we should normally guard it.
    }
    
    $output = "--- Campus Dive Email Config Check ---\n";
    $output .= "MAIL_HOST: " . MAIL_HOST . "\n";
    $output .= "MAIL_PORT: " . MAIL_PORT . "\n";
    $output .= "MAIL_ENCRYPTION: " . (defined('MAIL_ENCRYPTION') ? MAIL_ENCRYPTION : 'not defined') . "\n";
    $output .= "MAIL_USERNAME: " . MAIL_USERNAME . "\n";
    $output .= "MAIL_FROM_ADDRESS: " . MAIL_FROM_ADDRESS . "\n";
    $output .= "MAIL_PASSWORD length: " . strlen(MAIL_PASSWORD) . "\n";
    $output .= "MAIL_PASSWORD starts with re_: " . (str_starts_with(MAIL_PASSWORD, 're_') ? 'YES' : 'NO') . "\n";

    $output .= "\n--- Environment Check ---\n";
    $output .= "getenv('MAIL_PASSWORD'): " . (getenv('MAIL_PASSWORD') ? 'SET (length ' . strlen(getenv('MAIL_PASSWORD')) . ')' : 'NOT SET') . "\n";
    $output .= "getenv('MAIL_HOST'): " . (getenv('MAIL_HOST') ? 'SET' : 'NOT SET') . "\n";

    $output .= "\n--- Connectivity Check (to SMTP) ---\n";
    $connection = @fsockopen(MAIL_HOST, MAIL_PORT, $errno, $errstr, 5);
    if ($connection) {
        $output .= "SUCCESS: Can connect to " . MAIL_HOST . " on port " . MAIL_PORT . "\n";
        fclose($connection);
    } else {
        $output .= "FAILURE: Cannot connect to " . MAIL_HOST . " on port " . MAIL_PORT . "\n";
        $output .= "Error: $errstr ($errno)\n";
    }

    header('Content-Type: text/plain');
    echo $output;
    exit;
}

function handle_db_debug() {
    if (!defined('APP_DEBUG') || !APP_DEBUG) {
        Response::error('Debug info only accessible in debug mode.', 403);
    }
    
    try {
        $db = Database::getInstance();
        $rolesResult = $db->query("SELECT * FROM roles")->fetchAll();
        $userStatsResult = $db->query("SELECT role, role_id, status, COUNT(*) as count FROM users GROUP BY role, role_id, status")->fetchAll();
        $samplesResult = $db->query("SELECT id, firstname, lastname, email, role, role_id, status FROM users ORDER BY id DESC LIMIT 5")->fetchAll();
        
        Response::success([
            'constants' => [
                'ROLE_STUDENT' => ROLE_STUDENT,
                'STATUS_SUBMITTED' => STATUS_SUBMITTED
            ],
            'roles_table' => $rolesResult,
            'user_stats' => $userStatsResult,
            'recent_users' => $samplesResult
        ]);
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

// ────────────────────────────────────────────
// Route Matching
// ────────────────────────────────────────────

// 1. Check exact routes
foreach ($routes as $pattern => $handler) {
    $patternMethod = trim(explode(' ', trim($pattern))[0]);
    $patternPath = trim(explode(' ', trim($pattern), 2)[1]);

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

// 2. Check parameterized routes
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

// 3. Root info
if ($path === '/' || $path === '') {
    Response::success([
        'app'     => APP_NAME,
        'version' => '2.0.0',
        'status'  => 'running',
    ], 'Campus Dive API is running.');
    exit;
}

// 4. Not found
Response::notFound('Route not found: ' . $method . ' ' . $path);