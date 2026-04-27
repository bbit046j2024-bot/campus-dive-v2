<?php
/**
 * Campus Dive - Database Installer (TiDB Cloud Compatible)
 */
error_reporting(E_ALL);
ini_set('display_errors', '1');
set_time_limit(120);

echo "<h1>Campus Dive - Database Setup</h1>";
echo "<pre>";

// Step 1: Load database config
echo "[1/5] Loading database config...\n";
flush();
require_once __DIR__ . '/api/config/database.php';
echo "  OK\n";

// Step 2: Connect
echo "[2/5] Connecting to database...\n";
flush();
try {
    $db = Database::getInstance();
    echo "  CONNECTED!\n";
} catch (Exception $e) {
    echo "  FAILED: " . $e->getMessage() . "\n";
    echo "</pre>";
    die();
}
flush();

// Step 3: Load SQL
echo "[3/5] Loading setup_localhost.sql...\n";
flush();
$sqlFile = __DIR__ . '/setup_localhost.sql';
if (!file_exists($sqlFile)) {
    echo "  ERROR: File not found at $sqlFile\n";
    echo "</pre>";
    die();
}
$sql = file_get_contents($sqlFile);
echo "  Loaded " . strlen($sql) . " bytes\n";
flush();

// Step 4: Execute queries one by one
echo "[4/5] Executing queries...\n";
flush();

// Clean up SQL
$sql = preg_replace('/--.*?\n/', "\n", $sql);
$sql = preg_replace('/\/\*.*?\*\//s', '', $sql);

$queries = array_filter(array_map('trim', explode(';', $sql)));
$count = 0;
$errors = 0;

foreach ($queries as $i => $query) {
    if (empty($query)) continue;
    
    // Skip destructive and DB-level commands
    if (stripos($query, 'DROP TABLE') !== false 
        || stripos($query, 'DELETE FROM') !== false 
        || stripos($query, 'TRUNCATE') !== false
        || stripos($query, 'CREATE DATABASE') !== false 
        || stripos($query, 'USE ') === 0) {
        echo "  SKIP: " . substr($query, 0, 60) . "...\n";
        continue;
    }
    
    try {
        $db->exec($query);
        $count++;
        // Show first 60 chars of each query
        echo "  OK: " . substr(preg_replace('/\s+/', ' ', $query), 0, 60) . "...\n";
    } catch (PDOException $e) {
        $errors++;
        $msg = $e->getMessage();
        // Ignore "already exists" errors
        if (strpos($msg, 'already exists') !== false || strpos($msg, 'Duplicate') !== false) {
            echo "  SKIP (exists): " . substr($query, 0, 50) . "...\n";
        } else {
            echo "  ERROR: $msg\n";
            echo "  QUERY: " . substr($query, 0, 100) . "\n";
        }
    }
    flush();
}

// Step 5: Patches
echo "[5/5] Applying patches...\n";
flush();

// Patch: group_members
try {
    @$db->exec("ALTER TABLE group_members MODIFY COLUMN role ENUM('member', 'moderator', 'manager', 'admin') DEFAULT 'member'");
    @$db->exec("ALTER TABLE group_members ADD COLUMN IF NOT EXISTS status ENUM('active', 'pending', 'blocked') DEFAULT 'active' AFTER role");
    echo "  PATCH: group_members synced\n";
} catch (Exception $e) {
    echo "  PATCH NOTE: " . $e->getMessage() . "\n";
}

// Patch: users role
try {
    @$db->exec("ALTER TABLE users MODIFY COLUMN role ENUM('user', 'student', 'admin', 'manager', 'interviewer') DEFAULT 'student'");
    @$db->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS department VARCHAR(100) DEFAULT NULL AFTER student_id");
    echo "  PATCH: user roles synced\n";
} catch (Exception $e) {
    echo "  PATCH NOTE: " . $e->getMessage() . "\n";
}

// Patch: social_groups
try {
    @$db->exec("ALTER TABLE social_groups ADD COLUMN IF NOT EXISTS slug VARCHAR(100) AFTER name");
    @$db->exec("ALTER TABLE social_groups ADD COLUMN IF NOT EXISTS category VARCHAR(50) DEFAULT 'General' AFTER description");
    @$db->exec("ALTER TABLE social_groups ADD COLUMN IF NOT EXISTS avatar_url VARCHAR(255) DEFAULT NULL AFTER category");
    @$db->exec("ALTER TABLE social_groups ADD COLUMN IF NOT EXISTS is_private TINYINT(1) DEFAULT 0 AFTER is_public");
    @$db->exec("ALTER TABLE social_groups ADD COLUMN IF NOT EXISTS manager_id INT DEFAULT NULL AFTER is_private");
    @$db->exec("ALTER TABLE social_groups ADD COLUMN IF NOT EXISTS status ENUM('active', 'archived', 'pending') DEFAULT 'active' AFTER manager_id");
    @$db->exec("ALTER TABLE social_groups ADD COLUMN IF NOT EXISTS post_approval_required TINYINT(1) DEFAULT 0 AFTER status");
    echo "  PATCH: social_groups synced\n";
} catch (Exception $e) {
    echo "  PATCH NOTE: " . $e->getMessage() . "\n";
}

echo "\n========================================\n";
echo "DONE! $count queries executed, $errors errors.\n";
echo "========================================\n";
echo "\nDefault Login: admin@campusdive.com\n";
echo "Password: admin123\n";
echo "\n<a href='/'>Go to Campus Dive</a>\n";
echo "</pre>";
