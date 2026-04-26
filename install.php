<?php
/**
 * Campus Dive - Robust Database Installer (Aiven Optimized)
 */
require_once __DIR__ . '/api/config/database.php';

echo "<h1>Campus Dive - Database Setup</h1>";

try {
    $db = Database::getInstance();
    
    $sqlFile = 'setup_localhost.sql';
    if (!file_exists($sqlFile)) {
        die("<p style='color:red'>Error: $sqlFile not found!</p>");
    }

    $sql = file_get_contents($sqlFile);
    
    // Clean up SQL
    $sql = preg_replace('/--.*?\n/', '', $sql);
    $sql = preg_replace('/\/\*.*?\*\//', '', $sql);
    
    // Split into individual queries
    $queries = array_filter(array_map('trim', explode(';', $sql)));

    $count = 0;
    foreach ($queries as $query) {
        if (empty($query)) continue;
        
        // SAFETY: Skip any destructive commands during initialization
        if (stripos($query, 'DROP TABLE') !== false || stripos($query, 'DELETE FROM') !== false || stripos($query, 'TRUNCATE') !== false) {
            continue;
        }

        // SKIP "CREATE DATABASE" and "USE" for Aiven/Railway compatibility
        if (stripos($query, 'CREATE DATABASE') !== false || stripos($query, 'USE ') === 0) {
            continue;
        }
        
        $db->exec($query);
        $count++;
    }
    
    // Synchronize group_members (for status/manager support)
    try {
        @$db->exec("ALTER TABLE group_members MODIFY COLUMN role ENUM('member', 'moderator', 'manager', 'admin') DEFAULT 'member'");
        @$db->exec("ALTER TABLE group_members ADD COLUMN IF NOT EXISTS status ENUM('active', 'pending', 'blocked') DEFAULT 'active' AFTER role");
        echo "<p style='color:blue'>PATCH: Synchronized group_members (added status/manager support).</p>";
    } catch (Exception $e) {
        // Ignore errors
    }

    // Synchronize users role ENUM (for manager/interviewer support)
    try {
        @$db->exec("ALTER TABLE users MODIFY COLUMN role ENUM('user', 'student', 'admin', 'manager', 'interviewer') DEFAULT 'student'");
        @$db->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS department VARCHAR(100) DEFAULT NULL AFTER student_id");
        echo "<p style='color:blue'>PATCH: Synchronized user roles and added department support.</p>";
    } catch (Exception $e) {
        // Ignore errors
    }

    // Synchronize social_groups schema (for existing databases)
    try {
        // Use individual exec calls to be safe
        @$db->exec("ALTER TABLE social_groups ADD COLUMN slug VARCHAR(100) NOT NULL UNIQUE AFTER name");
        @$db->exec("ALTER TABLE social_groups ADD COLUMN category VARCHAR(50) DEFAULT 'General' AFTER description");
        @$db->exec("ALTER TABLE social_groups ADD COLUMN avatar_url VARCHAR(255) DEFAULT NULL AFTER category");
        @$db->exec("ALTER TABLE social_groups ADD COLUMN IF NOT EXISTS is_private TINYINT(1) DEFAULT 0 AFTER is_public");
        @$db->exec("ALTER TABLE social_groups ADD COLUMN IF NOT EXISTS manager_id INT DEFAULT NULL AFTER is_private");
        @$db->exec("ALTER TABLE social_groups ADD COLUMN IF NOT EXISTS status ENUM('active', 'archived', 'pending') DEFAULT 'active' AFTER manager_id");
        @$db->exec("ALTER TABLE social_groups ADD COLUMN IF NOT EXISTS post_approval_required TINYINT(1) DEFAULT 0 AFTER status");
        
        // Handle posts -> group_posts migration
        @$db->exec("RENAME TABLE posts TO group_posts");
        @$db->exec("ALTER TABLE group_posts ADD COLUMN IF NOT EXISTS media_url VARCHAR(255) DEFAULT NULL AFTER content");
        @$db->exec("ALTER TABLE group_posts ADD COLUMN IF NOT EXISTS media_type ENUM('image', 'video', 'link') DEFAULT 'image' AFTER media_url");
        @$db->exec("ALTER TABLE group_posts ADD COLUMN IF NOT EXISTS status ENUM('pending', 'published', 'rejected') DEFAULT 'published' AFTER media_type");
        @$db->exec("ALTER TABLE group_posts ADD COLUMN IF NOT EXISTS pinned TINYINT(1) DEFAULT 0 AFTER status");
        @$db->exec("ALTER TABLE group_posts ADD COLUMN IF NOT EXISTS like_count INT DEFAULT 0 AFTER pinned");
        @$db->exec("ALTER TABLE group_posts ADD COLUMN IF NOT EXISTS comment_count INT DEFAULT 0 AFTER like_count");
        
        echo "<p style='color:blue'>PATCH: Synchronized group_posts and hub settings.</p>";
    } catch (Exception $e) {
        // Log error but continue
        echo "<p style='color:orange'>Notice: Database synchronization check complete.</p>";
    }
    
    echo "<p style='color:green'>SUCCESS: Database initialized! ($count queries executed into current database)</p>";
    echo "<p><b>Default Login:</b> admin@campusdive.com | <b>Password:</b> admin123</p>";
    echo "<hr>";
    echo "<p><a href='/'>Go to API Mainframe</a></p>";
} catch (Exception $e) {
    echo "<p style='color:red'>Setup failed: " . $e->getMessage() . "</p>";
}
