<?php
// Simple debug - no login required for testing
require_once 'config.php';

echo "<h1>🔧 Message System Debug</h1>";

// Check database connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<p style='color: green;'>✅ Database connected</p>";

// Check if messages table exists
$table_check = $conn->query("SHOW TABLES LIKE 'messages'");
if ($table_check->num_rows == 0) {
    echo "<p style='color: red;'>❌ Messages table does not exist!</p>";

    // Create messages table
    $create_sql = "CREATE TABLE IF NOT EXISTS messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sender_id INT NOT NULL,
        receiver_id INT NOT NULL,
        subject VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        is_read BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
    )";

    if ($conn->query($create_sql)) {
        echo "<p style='color: green;'>✅ Messages table created!</p>";
    } else {
        echo "<p style='color: red;'>❌ Error creating table: " . $conn->error . "</p>";
    }
} else {
    echo "<p style='color: green;'>✅ Messages table exists</p>";
}

// Show all messages
echo "<h2>All Messages in Database:</h2>";
$result = $conn->query("SELECT m.*, 
                               sender.email as sender_email, 
                               receiver.email as receiver_email 
                        FROM messages m 
                        LEFT JOIN users sender ON m.sender_id = sender.id 
                        LEFT JOIN users receiver ON m.receiver_id = receiver.id 
                        ORDER BY m.created_at DESC LIMIT 50");

if ($result && $result->num_rows > 0) {
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th>ID</th><th>From</th><th>To</th><th>Subject</th><th>Message</th><th>Read</th><th>Date</th>";
    echo "</tr>";

    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . ($row['sender_email'] ? $row['sender_email'] : 'User #' . $row['sender_id']) . "</td>";
        echo "<td>" . ($row['receiver_email'] ? $row['receiver_email'] : 'User #' . $row['receiver_id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['subject']) . "</td>";
        echo "<td>" . htmlspecialchars(substr($row['message'], 0, 50)) . "...</td>";
        echo "<td>" . ($row['is_read'] ? '✅' : '❌') . "</td>";
        echo "<td>" . $row['created_at'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No messages in database</p>";
}

// Show all users
echo "<h2>All Users:</h2>";
$users = $conn->query("SELECT id, firstname, lastname, email, role FROM users ORDER BY id");

if ($users && $users->num_rows > 0) {
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th>ID</th><th>Name</th><th>Email</th><th>Role</th>";
    echo "</tr>";

    while ($user = $users->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $user['id'] . "</td>";
        echo "<td>" . $user['firstname'] . " " . $user['lastname'] . "</td>";
        echo "<td>" . $user['email'] . "</td>";
        echo "<td>" . $user['role'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No users found</p>";
}

// Test message sending
echo "<h2>Test Message Sending:</h2>";

if (isset($_POST['test_send'])) {
    $from_id = intval($_POST['from_id']);
    $to_id = intval($_POST['to_id']);
    $subject = "Test Message " . date('H:i:s');
    $message = "This is a test message sent at " . date('Y-m-d H:i:s');

    $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, subject, message) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiss", $from_id, $to_id, $subject, $message);

    if ($stmt->execute()) {
        echo "<p style='color: green;'>✅ Test message sent! ID: " . $stmt->insert_id . "</p>";
    } else {
        echo "<p style='color: red;'>❌ Failed: " . $stmt->error . "</p>";
    }
}

echo "<form method='post'>";
echo "<p>From User ID: <input type='number' name='from_id' value='1' style='width: 60px;'> ";
echo "To User ID: <input type='number' name='to_id' value='2' style='width: 60px;'> ";
echo "<button type='submit' name='test_send' style='padding: 10px 20px; background: #1e5aa8; color: white; border: none; border-radius: 5px; cursor: pointer;'>Send Test Message</button></p>";
echo "</form>";

echo "<hr>";
echo "<p><a href='index.php' style='padding: 10px 20px; background: #27ae60; color: white; text-decoration: none; border-radius: 5px;'>Go to Login</a></p>";
?>