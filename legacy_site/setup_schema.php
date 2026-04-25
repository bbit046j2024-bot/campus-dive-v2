<?php
require_once 'config.php';

// disable foreign key checks to avoid issues during updates
$conn->query("SET FOREIGN_KEY_CHECKS = 0");

$updates = [
    // 1. Roles Table
    "CREATE TABLE IF NOT EXISTS roles (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50) NOT NULL UNIQUE,
        description TEXT
    )",

    // 2. Permissions Table
    "CREATE TABLE IF NOT EXISTS permissions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        slug VARCHAR(100) NOT NULL UNIQUE,
        category VARCHAR(50) DEFAULT 'general'
    )",

    // 3. Role-Permission Pivot Table
    "CREATE TABLE IF NOT EXISTS role_permissions (
        role_id INT NOT NULL,
        permission_id INT NOT NULL,
        PRIMARY KEY (role_id, permission_id),
        FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
        FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
    )",

    // 4. Analytics Logs Table
    "CREATE TABLE IF NOT EXISTS analytics_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        action VARCHAR(100) NOT NULL,
        details TEXT,
        ip_address VARCHAR(45),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",

    // 5. Update Users Table
    // Adding role_id and ensuring status column can hold new statuses
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS role_id INT DEFAULT NULL",
    "ALTER TABLE users MODIFY COLUMN status ENUM('submitted', 'pending', 'documents_uploaded', 'under_review', 'interview_scheduled', 'approved', 'rejected') DEFAULT 'submitted'",
    
    // 6. Update Messages Table for Chat Features
    "ALTER TABLE messages ADD COLUMN IF NOT EXISTS attachment_path VARCHAR(255) DEFAULT NULL",
    "ALTER TABLE messages ADD COLUMN IF NOT EXISTS read_at TIMESTAMP NULL DEFAULT NULL",
    "ALTER TABLE messages ADD COLUMN IF NOT EXISTS type ENUM('text', 'file', 'system') DEFAULT 'text'",

    // 7. Recruitment Stages Tracking (for Kanban/Analytics)
    "CREATE TABLE IF NOT EXISTS application_stages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        stage_name VARCHAR(50) NOT NULL,
        entered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        exited_at TIMESTAMP NULL DEFAULT NULL,
        duration_seconds INT DEFAULT 0,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )"
];

echo "Running Schema Updates...\n";

foreach ($updates as $sql) {
    if ($conn->query($sql) === TRUE) {
        echo "Success: " . substr($sql, 0, 50) . "...\n";
    } else {
        echo "Error: " . $conn->error . "\nSQL: " . $sql . "\n";
    }
}

// Seed Roles and Permissions
echo "\nSeeding Default Data...\n";

// Roles
$roles = [
    ['name' => 'Admin', 'description' => 'Full system access'],
    ['name' => 'Manager', 'description' => 'Can manage students and documents'],
    ['name' => 'Interviewer', 'description' => 'Can conduct interviews and update status'],
    ['name' => 'Student', 'description' => 'Regular user access']
];

foreach ($roles as $role) {
    $check = $conn->query("SELECT id FROM roles WHERE name = '{$role['name']}'");
    if ($check->num_rows == 0) {
        $conn->query("INSERT INTO roles (name, description) VALUES ('{$role['name']}', '{$role['description']}')");
        echo "Created Role: {$role['name']}\n";
    }
}

// Permissions
$permissions = [
    // Students
    ['name' => 'View Students', 'slug' => 'view_students', 'category' => 'students'],
    ['name' => 'Approve Applications', 'slug' => 'approve_applications', 'category' => 'students'],
    ['name' => 'Reject Applications', 'slug' => 'reject_applications', 'category' => 'students'],
    ['name' => 'Delete Students', 'slug' => 'delete_students', 'category' => 'students'],
    
    // Workflow
    ['name' => 'Move to Review', 'slug' => 'move_review', 'category' => 'workflow'],
    ['name' => 'Schedule Interview', 'slug' => 'schedule_interview', 'category' => 'workflow'],
    
    // Communication
    ['name' => 'Send Messages', 'slug' => 'send_messages', 'category' => 'communication'],
    ['name' => 'Send Recruitment Letter', 'slug' => 'send_letter', 'category' => 'communication'],
    
    // System
    ['name' => 'Manage Settings', 'slug' => 'manage_settings', 'category' => 'system'],
    ['name' => 'Manage Roles', 'slug' => 'manage_roles', 'category' => 'system'],
    ['name' => 'View Analytics', 'slug' => 'view_analytics', 'category' => 'system']
];

foreach ($permissions as $perm) {
    $check = $conn->query("SELECT id FROM permissions WHERE slug = '{$perm['slug']}'");
    if ($check->num_rows == 0) {
        $conn->query("INSERT INTO permissions (name, slug, category) VALUES ('{$perm['name']}', '{$perm['slug']}', '{$perm['category']}')");
        echo "Created Permission: {$perm['name']}\n";
    }
}

// Assign all permissions to Admin
$admin_role = $conn->query("SELECT id FROM roles WHERE name = 'Admin'")->fetch_assoc()['id'];
$all_perms = $conn->query("SELECT id FROM permissions");

while ($perm = $all_perms->fetch_assoc()) {
    $conn->query("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES ($admin_role, {$perm['id']})");
}
echo "Assigned all permissions to Admin.\n";

// Map existing users to roles (Migration)
// Admins -> Admin Role
$conn->query("UPDATE users SET role_id = (SELECT id FROM roles WHERE name = 'Admin') WHERE role = 'admin' AND role_id IS NULL");
// Users -> Student Role
$conn->query("UPDATE users SET role_id = (SELECT id FROM roles WHERE name = 'Student') WHERE role = 'user' AND role_id IS NULL");

echo "Migrated existing users to roles.\n";
echo "Database setup complete.";
?>
