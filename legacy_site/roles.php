<?php
require_once 'config.php';

if (!isLoggedIn() || !checkPermission('manage_roles')) {
    redirect('index.php');
}

$success = '';
$error = '';

// Handle Role Creation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'create_role') {
    $role_name = sanitize($conn, $_POST['role_name']);
    $role_desc = sanitize($conn, $_POST['role_desc']);
    
    $stmt = $conn->prepare("INSERT INTO roles (name, description) VALUES (?, ?)");
    $stmt->bind_param("ss", $role_name, $role_desc);
    
    if ($stmt->execute()) {
        $success = "Role '$role_name' created successfully.";
    } else {
        $error = "Error creating role: " . $conn->error;
    }
}

// Handle Permission Assignment
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update_permissions') {
    $role_id = intval($_POST['role_id']);
    $permissions = isset($_POST['permissions']) ? $_POST['permissions'] : [];
    
    // Clear existing permissions
    $conn->query("DELETE FROM role_permissions WHERE role_id = $role_id");
    
    // Add new permissions
    if (!empty($permissions)) {
        $stmt = $conn->prepare("INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)");
        foreach ($permissions as $perm_id) {
            $stmt->bind_param("ii", $role_id, $perm_id);
            $stmt->execute();
        }
    }
    $success = "Permissions updated successfully.";
}

// Format page variable
$page = 'settings';
$tab = 'roles';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Roles - Admin Dashboard</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="dashboard-body admin-body">
    <!-- Include Sidebar (Placeholder until Phase 2) -->
    <header class="dashboard-header">
        <div class="logo-container">
            <h2 class="logo-text">Role Manager</h2>
        </div>
        <nav class="dashboard-nav">
            <a href="admin_dashboard.php" class="nav-item"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </nav>
    </header>

    <main class="dashboard-main">
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="dashboard-grid admin-grid">
            <!-- Create Role Card -->
            <div class="dashboard-card">
                <h3><i class="fas fa-plus-circle"></i> Create New Role</h3>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="create_role">
                    <div class="input-box">
                        <label>Role Name</label>
                        <input type="text" name="role_name" required>
                    </div>
                    <div class="input-box">
                        <label>Description</label>
                        <textarea name="role_desc" rows="3"></textarea>
                    </div>
                    <button type="submit" class="btn-submit">Create Role</button>
                </form>
            </div>

            <!-- Manage Features -->
            <div class="dashboard-card wide">
                <h3><i class="fas fa-user-shield"></i> Manage Permissions</h3>
                
                <?php 
                $roles = $conn->query("SELECT * FROM roles ORDER BY id");
                $all_perms = $conn->query("SELECT * FROM permissions ORDER BY category, name");
                $permissions_list = [];
                while ($p = $all_perms->fetch_assoc()) {
                    $permissions_list[$p['category']][] = $p;
                }
                
                while ($role = $roles->fetch_assoc()): 
                    // Get current permissions for this role
                    $current_perms = [];
                    $rp = $conn->query("SELECT permission_id FROM role_permissions WHERE role_id = {$role['id']}");
                    while ($row = $rp->fetch_assoc()) $current_perms[] = $row['permission_id'];
                ?>
                <div class="role-permission-block" style="margin-bottom: 30px; border-bottom: 1px solid var(--border-color); padding-bottom: 20px;">
                    <div class="role-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                        <div>
                            <h4 style="color: var(--text-main); font-size: 1.2em;"><?php echo $role['name']; ?></h4>
                            <p style="color: var(--text-muted); font-size: 0.9em;"><?php echo $role['description']; ?></p>
                        </div>
                        <button class="btn-secondary" onclick="document.getElementById('perm-editor-<?php echo $role['id']; ?>').style.display = 'block'">
                            <i class="fas fa-edit"></i> Edit Permissions
                        </button>
                    </div>

                    <!-- Permission Editor (Hidden by default) -->
                    <div id="perm-editor-<?php echo $role['id']; ?>" style="display: none; background: var(--bg-body); padding: 20px; border-radius: 10px;">
                        <form method="POST">
                            <input type="hidden" name="action" value="update_permissions">
                            <input type="hidden" name="role_id" value="<?php echo $role['id']; ?>">
                            
                            <div class="permissions-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px;">
                                <?php foreach ($permissions_list as $category => $perms): ?>
                                    <div class="perm-category">
                                        <h5 style="text-transform: uppercase; color: var(--primary-color); margin-bottom: 10px;"><?php echo $category; ?></h5>
                                        <?php foreach ($perms as $p): ?>
                                            <label class="checkbox-container" style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                                                <input type="checkbox" name="permissions[]" value="<?php echo $p['id']; ?>" 
                                                    <?php echo in_array($p['id'], $current_perms) ? 'checked' : ''; ?>>
                                                <span class="text"><?php echo $p['name']; ?></span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div style="margin-top: 20px; text-align: right;">
                                <button type="button" class="btn-secondary" onclick="this.closest('div[id^=perm-editor]').style.display = 'none'">Cancel</button>
                                <button type="submit" class="btn-success">Save Changes</button>
                            </div>
                        </form>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </main>
    <script src="theme.js"></script>
</body>
</html>
