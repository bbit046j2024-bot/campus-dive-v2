<?php
// Load local .env file if it exists
if (file_exists(__DIR__ . '/.env')) {
    $lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
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

// Database Configuration
define('DB_HOST', getenv('MYSQLHOST') ?: getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('MYSQLUSER') ?: getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('MYSQLPASSWORD') ?: getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('MYSQLDATABASE') ?: getenv('DB_NAME') ?: 'campus_recruitment');

// Create database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

require_once 'permission_check.php';
// Ensure session role_id is set if not already (for migration compaibility)
if (isset($_SESSION['user_id']) && !isset($_SESSION['role_id'])) {
    $uid = $_SESSION['user_id'];
    $r_query = $conn->query("SELECT role_id FROM users WHERE id = $uid");
    if ($r_query && $r_query->num_rows > 0) {
        $_SESSION['role_id'] = $r_query->fetch_assoc()['role_id'];
    }
}

// Set charset to handle special characters
$conn->set_charset("utf8mb4");

// Start session - auto-detect localhost vs production
if (session_status() === PHP_SESSION_NONE) {
    $isLocalhost = in_array($_SERVER['SERVER_NAME'] ?? 'localhost', ['localhost', '127.0.0.1', '::1']);
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => !$isLocalhost,   // Only require HTTPS on production
        'httponly' => true,
        'samesite' => $isLocalhost ? 'Lax' : 'None', // Lax works on localhost HTTP
    ]);
    session_start();
}

// Helper function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Helper function to check if user is admin
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Helper function to redirect
function redirect($url) {
    header("Location: " . $url);
    exit();
}

// Helper function to sanitize input
function sanitize($conn, $data) {
    return htmlspecialchars(strip_tags(trim($conn->real_escape_string($data))));
}

// Helper function to show alerts
function showAlert($message, $type = 'success') {
    $_SESSION['alert'] = ['message' => $message, 'type' => $type];
}

// Update session with latest user data
function refreshSession($conn, $user_id) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['firstname'] = $user['firstname'];
        $_SESSION['lastname'] = $user['lastname'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['avatar'] = $user['avatar'];
        $_SESSION['avatar_image'] = $user['avatar_image'];
    }
}

// Helper function to ensure messages table exists
function ensureMessagesTable($conn) {
    $check = $conn->query("SHOW TABLES LIKE 'messages'");
    if ($check->num_rows == 0) {
        $sql = "CREATE TABLE IF NOT EXISTS messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            sender_id INT NOT NULL,
            receiver_id INT NOT NULL,
            subject VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            is_read BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $conn->query($sql);
    }
}

?>