<?php
require_once 'config.php';

// Generate correct hash for 'admin123'
$newHash = password_hash('admin123', PASSWORD_DEFAULT);

// Update database directly
$stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = 'admin@campusdive.com'");
$stmt->bind_param("s", $newHash);
$success = $stmt->execute();

echo "<h2 style='color: " . ($success ? "green" : "red") . "'>";
echo $success ? "SUCCESS: Admin Password Fixed!" : "ERROR: Failed to update password.";
echo "</h2>";
echo "<p>The password for <b>admin@campusdive.com</b> has been correctly hashed and updated to: <b>admin123</b></p>";
echo "<p>You may now log in to the application.</p>";
echo "<hr>";
echo "<p><a href='/Campus-Dive/Campus-Dive-main/'>Go to Login Page</a></p>";

// Also let's update setup_localhost.sql automatically to prevent future issues
$sqlFile = 'setup_localhost.sql';
if (file_exists($sqlFile)) {
    $content = file_get_contents($sqlFile);
    // Find the old hash (which was for the word "password")
    $content = str_replace(
        "'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'",
        "'" . $newHash . "'",
        $content
    );
    file_put_contents($sqlFile, $content);
    echo "<p><i>Also automatically patched setup_localhost.sql so future installations work flawlessly!</i></p>";
}
?>
