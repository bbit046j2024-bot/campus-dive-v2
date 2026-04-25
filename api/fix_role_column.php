<?php
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, '#') === 0 || strpos($line, '=') === false) continue;
        list($name, $value) = explode('=', $line, 2);
        putenv("$name=$value");
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }
}
require_once __DIR__ . '/config/database.php';

try {
    $db = Database::getInstance();
    echo "Updating users table 'role' column...\n";
    
    // Change ENUM to VARCHAR to support more roles and legacy compatibility
    $db->exec("ALTER TABLE users MODIFY COLUMN role VARCHAR(50) DEFAULT 'user'");
    
    echo "Successfully updated 'role' column to VARCHAR(50).\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
