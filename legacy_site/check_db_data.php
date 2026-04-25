<?php
require_once __DIR__ . '/api/config/app.php';
require_once __DIR__ . '/api/config/database.php';

try {
    $db = Database::getInstance();
    echo "--- ROLES TABLE ---\n";
    $roles = $db->query("SELECT * FROM roles")->fetchAll();
    foreach ($roles as $r) echo "{$r['id']} | {$r['name']}\n";
    
    echo "\n--- ADMIN USERS ---\n";
    $admins = $db->query("SELECT id, firstname, lastname, email, role, role_id FROM users WHERE role = 'admin'")->fetchAll();
    foreach ($admins as $a) {
        $roleName = $db->prepare("SELECT name FROM roles WHERE id = ?");
        $roleName->execute([$a['role_id']]);
        $rn = $roleName->fetch();
        echo "ID: {$a['id']} | Email: {$a['email']} | role_id: " . ($a['role_id'] ?? 'NULL') . " | role_name: " . ($rn['name'] ?? 'NOT FOUND') . "\n";
    }
    
    echo "\n--- STUDENT COUNT ---\n";
    $students = $db->query("SELECT COUNT(*) as c FROM users WHERE role = 'user' OR role_id = " . ROLE_STUDENT)->fetch();
    echo "Count: " . $students['c'] . "\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
