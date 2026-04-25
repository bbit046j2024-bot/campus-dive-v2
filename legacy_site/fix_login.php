<?php
require_once 'config.php';

echo "<h1>🔧 Fix Admin Login & Message Issues</h1>";

// Check current admin status
echo "<h2>Current Admin Accounts:</h2>";
$admins = $conn->query("SELECT id, firstname, lastname, email, status, password FROM users WHERE role = 'admin'");
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Status</th><th>Password Hash</th></tr>";
while ($a = $admins->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $a['id'] . "</td>";
    echo "<td>" . $a['firstname'] . " " . $a['lastname'] . "</td>";
    echo "<td>" . $a['email'] . "</td>";
    echo "<td>" . $a['status'] . "</td>";
    echo "<td>" . substr($a['password'], 0, 20) . "...</td>";
    echo "</tr>";
}
echo "</table>";

// Fix buttons
echo "<hr><h2>Fix Options:</h2>";

// Option 1: Reset password for ID 1
echo "<form method='POST' style='margin: 10px 0; padding: 15px; background: #fff3cd;'>";
echo "<h3>Option 1: Reset Admin AU (ID: 1) Password</h3>";
echo "<p>Reset password to 'admin123'</p>";
echo "<button type='submit' name='reset_pass_1' style='padding: 10px 20px; background: #f39c12; color: white; border: none; cursor: pointer;'>Reset Password</button>";
echo "</form>";

// Option 2: Approve Admin EA (ID 2)
echo "<form method='POST' style='margin: 10px 0; padding: 15px; background: #d4edda;'>";
echo "<h3>Option 2: Approve Admin EA (ID: 2)</h3>";
echo "<p>Approve Elisha Adera so you can use that account</p>";
echo "<button type='submit' name='approve_2' style='padding: 10px 20px; background: #27ae60; color: white; border: none; cursor: pointer;'>Approve Admin EA</button>";
echo "</form>";

// Option 3: Reassign messages to Admin EA
echo "<form method='POST' style='margin: 10px 0; padding: 15px; background: #f8d7da;'>";
echo "<h3>Option 3: Move Messages to Admin EA (ID: 2)</h3>";
echo "<p>Move all messages from Admin AU to Admin EA</p>";
echo "<button type='submit' name='move_msgs' style='padding: 10px 20px; background: #e74c3c; color: white; border: none; cursor: pointer;'>Move Messages</button>";
echo "</form>";

// Option 4: Make Admin EA the primary admin
echo "<form method='POST' style='margin: 10px 0; padding: 15px; background: #d1ecf1;'>";
echo "<h3>Option 4: Make Admin EA (ID: 2) the Primary Admin</h3>";
echo "<p>Approve EA + Move messages + Reset EA password to 'admin123'</p>";
echo "<button type='submit' name='make_primary' style='padding: 10px 20px; background: #1e5aa8; color: white; border: none; cursor: pointer;'>Make Primary Admin</button>";
echo "</form>";

// Process actions
if (isset($_POST['reset_pass_1'])) {
    $hash = password_hash('admin123', PASSWORD_DEFAULT);
    $conn->query("UPDATE users SET password = '$hash' WHERE id = 1");
    echo "<div style='background: #d4edda; padding: 10px; margin: 10px 0;'>✓ Admin AU (ID: 1) password reset to 'admin123'</div>";
}

if (isset($_POST['approve_2'])) {
    $conn->query("UPDATE users SET status = 'approved' WHERE id = 2");
    echo "<div style='background: #d4edda; padding: 10px; margin: 10px 0;'>✓ Admin EA (ID: 2) approved!</div>";
}

if (isset($_POST['move_msgs'])) {
    $conn->query("UPDATE messages SET receiver_id = 2 WHERE receiver_id = 1");
    $affected = $conn->affected_rows;
    echo "<div style='background: #d4edda; padding: 10px; margin: 10px 0;'>✓ Moved $affected messages from Admin AU to Admin EA</div>";
}

if (isset($_POST['make_primary'])) {
    // Approve EA
    $conn->query("UPDATE users SET status = 'approved' WHERE id = 2");
    // Reset EA password
    $hash = password_hash('admin123', PASSWORD_DEFAULT);
    $conn->query("UPDATE users SET password = '$hash' WHERE id = 2");
    // Move messages
    $conn->query("UPDATE messages SET receiver_id = 2 WHERE receiver_id = 1");
    echo "<div style='background: #d4edda; padding: 10px; margin: 10px 0;'>✓ Admin EA is now the primary admin!<br>";
    echo "Email: elishaadera@gmail.com<br>";
    echo "Password: admin123<br>";
    echo "All messages moved to this account.</div>";
}

echo "<hr><p><a href='index.php' style='padding: 10px 20px; background: #1e5aa8; color: white; text-decoration: none;'>Go to Login</a></p>";
?>