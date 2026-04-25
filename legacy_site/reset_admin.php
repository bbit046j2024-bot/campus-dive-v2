<?php
require_once 'api/config/app.php';
require_once 'api/config/database.php';

$email = 'admin@campusdive.com';
$password = 'admin123';
$hash = password_hash($password, PASSWORD_DEFAULT);

try {
    $db = Database::getInstance();
    $stmt = $db->prepare("UPDATE users SET password = ?, role_id = 1, status = 'approved' WHERE email = ?");
    $stmt->execute([$hash, $email]);
    
    if ($stmt->rowCount() > 0) {
        echo "✅ Password for $email has been reset to: $password\n";
        echo "Role has been set to Admin (1) and status to Approved.\n";
    } else {
        echo "❌ User $email not found. Check your users table.\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
