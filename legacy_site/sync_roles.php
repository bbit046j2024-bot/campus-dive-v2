<?php
require_once __DIR__ . '/api/config/app.php';
require_once __DIR__ . '/api/config/database.php';

try {
    $db = Database::getInstance();
    echo "Syncing roles based on 'role' column...\n";

    // Admin Role (1)
    $stmt1 = $db->prepare("UPDATE users SET role_id = ? WHERE role = 'admin'");
    $stmt1->execute([ROLE_ADMIN]);
    $count1 = $stmt1->rowCount();
    echo "Updated $count1 Admin users to role_id " . ROLE_ADMIN . ".\n";

    // Student Role (4)
    $stmt2 = $db->prepare("UPDATE users SET role_id = ? WHERE role = 'user' OR role IS NULL");
    $stmt2->execute([ROLE_STUDENT]);
    $count2 = $stmt2->rowCount();
    echo "Updated $count2 Student users to role_id " . ROLE_STUDENT . ".\n";

    echo "\nSync complete. You should now have correct access in the dashboard.\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
