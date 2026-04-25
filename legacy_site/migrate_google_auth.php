<?php
require_once 'config.php';

echo "<h2>Database Migration: Google Auth Support</h2>";

// Check if the column already exists
$result = $conn->query("SHOW COLUMNS FROM users LIKE 'google_id'");

if ($result->num_rows == 0) {
    echo "Adding 'google_id' column to 'users' table...<br>";
    
    $sql = "ALTER TABLE users ADD COLUMN google_id VARCHAR(255) UNIQUE AFTER email";
    
    if ($conn->query($sql)) {
        echo "✅ Success: 'google_id' column added successfully.<br>";
    } else {
        echo "❌ Error: Failed to add column. " . $conn->error . "<br>";
    }
} else {
    echo "ℹ️ Info: 'google_id' column already exists.<br>";
}

echo "<br><a href='index.php'>Return to Home</a>";
?>
