<?php
/**
 * Campus Dive - Robust Database Installer
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

    // PDO::exec doesn't handle multiple queries. We must split them.
    // However, splitting by ; is dangerous with triggers/functions.
    // For this script, we'll use a simple split or better: use the PDO connection to run them.
    
    // Clean up SQL (remove comments)
    $sql = preg_replace('/--.*?\n/', '', $sql);
    $sql = preg_replace('/\/\*.*?\*\//', '', $sql);
    
    // Split into individual queries
    $queries = array_filter(array_map('trim', explode(';', $sql)));

    $count = 0;
    foreach ($queries as $query) {
        if (!empty($query)) {
            $db->exec($query);
            $count++;
        }
    }
    
    echo "<p style='color:green'>SUCCESS: Database initialized! ($count queries executed)</p>";
    echo "<p><b>Default Login:</b> admin@campusdive.com | <b>Password:</b> admin123</p>";
    echo "<hr>";
    echo "<p><a href='/'>Go to API Mainframe</a></p>";
} catch (Exception $e) {
    echo "<p style='color:red'>Setup failed: " . $e->getMessage() . "</p>";
}
