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
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
try {
    $db = Database::getInstance();
    $columns = $db->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_ASSOC);
    header('Content-Type: application/json');
    echo json_encode($columns, JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo $e->getMessage();
}
