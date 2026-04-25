<?php
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

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';

header('Content-Type: application/json');

try {
    $db = Database::getInstance();
    
    // Check Roles
    $roles = $db->query("SELECT * FROM roles")->fetchAll();
    
    // Check Users counts by role and status
    $userStats = $db->query("
        SELECT role, role_id, status, COUNT(*) as count 
        FROM users 
        GROUP BY role, role_id, status
    ")->fetchAll();
    
    // Sample users (last 5)
    $samples = $db->query("SELECT id, firstname, lastname, email, role, role_id, status FROM users ORDER BY id DESC LIMIT 5")->fetchAll();
    
    echo json_encode([
        'success' => true,
        'constants' => [
            'ROLE_STUDENT' => ROLE_STUDENT,
            'STATUS_SUBMITTED' => STATUS_SUBMITTED
        ],
        'roles_table' => $roles,
        'user_stats' => $userStats,
        'recent_users' => $samples
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
