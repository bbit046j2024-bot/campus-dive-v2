<?php
require_once 'config.php';

echo "<h1>🔧 Fixing Messages Table</h1>";

// Check if messages table exists
$table_exists = $conn->query("SHOW TABLES LIKE 'messages'");

if ($table_exists->num_rows == 0) {
    echo "<p>Messages table doesn't exist. Creating...</p>";

    $create_sql = "CREATE TABLE messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sender_id INT NOT NULL,
        receiver_id INT NOT NULL,
        subject VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_sender (sender_id),
        INDEX idx_receiver (receiver_id),
        FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    if ($conn->query($create_sql)) {
        echo "<p style='color: green;'>✅ Messages table created successfully!</p>";
    } else {
        echo "<p style='color: red;'>❌ Error: " . $conn->error . "</p>";
    }
} else {
    echo "<p style='color: green;'>✅ Messages table exists</p>";

    // Check table structure
    $structure = $conn->query("DESCRIBE messages");
    echo "<h3>Current Table Structure:</h3>";
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    while ($row = $structure->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "<p><a href='messages_test.php'>Test Messaging</a> | <a href='index.php'>Back to Login</a></p>";
?>