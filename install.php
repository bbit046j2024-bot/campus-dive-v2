<?php
// Campus Dive - One Click Database Installer for Localhost
require_once 'config.php';

echo "<h1>Campus Dive - Database Setup</h1>";

// 1. Check connection (config.php handles this but let's be sure)
if ($conn->connect_error) {
    die("<p style='color:red'>Connection failed: " . $conn->connect_error . "</p>");
}

// 2. Read setup_localhost.sql
$sqlFile = 'setup_localhost.sql';
if (!file_exists($sqlFile)) {
    die("<p style='color:red'>Error: $sqlFile not found!</p>");
}

$sql = file_get_contents($sqlFile);

// 3. Split by semicolon but handle multi-line statements carefully
// A better way is to use multi_query
if ($conn->multi_query($sql)) {
    do {
        // Store first result set
        if ($result = $conn->store_result()) {
            $result->free();
        }
    } while ($conn->next_result());
    
    echo "<p style='color:green'>SUCCESS: Database initialized successfully!</p>";
    echo "<p><b>Default Login:</b> admin@campusdive.com | <b>Password:</b> admin123</p>";
    echo "<hr>";
    echo "<p><a href='index.php'>Go to Login Page</a></p>";
} else {
    echo "<p style='color:red'>Error executing SQL: " . $conn->error . "</p>";
}

// 4. Update index.php to remove potential legacy errors if redirect fails
// (Actually index.php is fine as is once the tables exist)
?>
