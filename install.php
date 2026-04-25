<?php
// Campus Dive - One Click Database Installer for Localhost
require_once __DIR__ . '/api/config/database.php';

echo "<h1>Campus Dive - Database Setup</h1>";

try {
    $db = Database::getInstance();
    
    // 2. Read setup_localhost.sql
    $sqlFile = 'setup_localhost.sql';
    if (!file_exists($sqlFile)) {
        die("<p style='color:red'>Error: $sqlFile not found!</p>");
    }

    $sql = file_get_contents($sqlFile);

    // 3. Execute SQL
    $db->exec($sql);
    
    echo "<p style='color:green'>SUCCESS: Database initialized successfully!</p>";
    echo "<p><b>Default Login:</b> admin@campusdive.com | <b>Password:</b> admin123</p>";
    echo "<hr>";
    echo "<p><a href='/'>Go to API Mainframe</a></p>";
} catch (Exception $e) {
    echo "<p style='color:red'>Setup failed: " . $e->getMessage() . "</p>";
}


// 4. Update index.php to remove potential legacy errors if redirect fails
// (Actually index.php is fine as is once the tables exist)
?>
