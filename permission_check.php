<?php
// Function to check if user has a specific permission
function checkPermission($permission_slug) {
    global $conn;
    
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role_id'])) {
        return false;
    }

    $role_id = $_SESSION['role_id'];
    
    // Cache perms in session to reduce DB hits? For now, DB query is safer for real-time changes.
    // Or optimized query:
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM role_permissions rp
        JOIN permissions p ON rp.permission_id = p.id
        WHERE rp.role_id = ? AND p.slug = ?
    ");
    $stmt->bind_param("is", $role_id, $permission_slug);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    return $result['count'] > 0;
}

// Middleware to enforce permission on a page
function requirePermission($permission_slug) {
    if (!checkPermission($permission_slug)) {
        // Log unauthorized access attempt?
        // redirect('unauthorized.php'); or show error
        die("
            <div style='font-family: sans-serif; text-align: center; padding: 50px;'>
                <h1>403 Forbidden</h1>
                <p>You do not have permission to access this page.</p>
                <a href='index.php'>Go Back</a>
            </div>
        ");
    }
}
?>
