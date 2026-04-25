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
        
        // SKIP "CREATE DATABASE" and "USE" for Aiven/Railway compatibility
        if (stripos($query, 'CREATE DATABASE') !== false || stripos($query, 'USE ') === 0) {
            continue;
        }
        
        $db->exec($query);
        $count++;
    }
    
    echo "<p style='color:green'>SUCCESS: Database initialized! ($count queries executed into current database)</p>";
    echo "<p><b>Default Login:</b> admin@campusdive.com | <b>Password:</b> admin123</p>";
    echo "<hr>";
    echo "<p><a href='/'>Go to API Mainframe</a></p>";
} catch (Exception $e) {
    echo "<p style='color:red'>Setup failed: " . $e->getMessage() . "</p>";
}
