<?php
require_once 'config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('index.php');
}

if (isAdmin()) {
    redirect('admin_dashboard.php');
}

$user_id = $_SESSION['user_id'];

// Get user details
$stmt = $conn->prepare("SELECT id, firstname, lastname, email, role, avatar, avatar_image, status, created_at FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Session Sync
if ($user && !empty($user['avatar_image'])) {
    $_SESSION['avatar_image'] = $user['avatar_image'];
}

// Page Router
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';

// --- DATA FETCHING ---

// Unread Messages
$msg_stmt = $conn->prepare("SELECT COUNT(*) as count FROM messages WHERE receiver_id = ? AND is_read = FALSE");
$msg_stmt->bind_param("i", $user_id);
$msg_stmt->execute();
$unread_messages = $msg_stmt->get_result()->fetch_assoc()['count'];

// Notifications
$notif_stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$notif_stmt->bind_param("i", $user_id);
$notif_stmt->execute();
$notifications = $notif_stmt->get_result();

// Documents
$doc_stmt = $conn->prepare("SELECT * FROM documents WHERE user_id = ? ORDER BY uploaded_at DESC");
$doc_stmt->bind_param("i", $user_id);
$doc_stmt->execute();
$documents = $doc_stmt->get_result();

// Recruitment Letter
$letter_stmt = $conn->prepare("SELECT * FROM recruitment_letters WHERE user_id = ? ORDER BY sent_at DESC LIMIT 1");
$letter_stmt->bind_param("i", $user_id);
$letter_stmt->execute();
$recruitment_letter = $letter_stmt->get_result()->fetch_assoc();

// --- ACTION HANDLING ---

// Upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['document'])) {
    // ... (Keep existing upload logic) ...
    $file = $_FILES['document'];
    $allowed_types = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'image/jpeg', 'image/png'];

    if (in_array($file['type'], $allowed_types) && $file['size'] <= 5 * 1024 * 1024) {
        $filename = time() . '_' . basename($file['name']);
        $upload_path = 'uploads/' . $filename;
        if (!file_exists('uploads')) mkdir('uploads', 0777, true);

        if (move_uploaded_file($file['tmp_name'], $upload_path)) {
            $stmt = $conn->prepare("INSERT INTO documents (user_id, filename, original_name, file_type, file_size) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("isssi", $user_id, $filename, $file['name'], $file['type'], $file['size']);
            $stmt->execute();

            // Notify Admin
            $admin_stmt = $conn->prepare("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
            $admin_stmt->execute();
            $admin_res = $admin_stmt->get_result();
            if ($admin_res->num_rows > 0) {
                 $admin = $admin_res->fetch_assoc();
                 $notif_msg = $user['firstname'] . ' uploaded: ' . $file['name'];
                 $conn->query("INSERT INTO notifications (user_id, title, message, type) VALUES ({$admin['id']}, 'New Document', '$notif_msg', 'info')");
            }
            $_SESSION['alert'] = ['type' => 'success', 'message' => 'Document uploaded successfully!'];
            header("Location: user_dashboard.php?page=documents");
            exit;
        }
    } else {
        $_SESSION['alert'] = ['type' => 'error', 'message' => 'Invalid file. Max 5MB.'];
    }
}

// Send Message
if (isset($_POST['action']) && $_POST['action'] === 'send_message') {
    // ... (Keep existing message logic) ...
    $subject = sanitize($conn, $_POST['subject']);
    $message = sanitize($conn, $_POST['message']);
    
    $admin_stmt = $conn->prepare("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
    $admin_stmt->execute();
    $admin_res = $admin_stmt->get_result();
    
    if ($admin_res->num_rows > 0) {
        $admin_id = $admin_res->fetch_assoc()['id'];
        $conn->prepare("INSERT INTO messages (sender_id, receiver_id, subject, message) VALUES (?, ?, ?, ?)")->execute([$user_id, $admin_id, $subject, $message]);
        // Notify admin...
        $_SESSION['alert'] = ['type' => 'success', 'message' => 'Message sent!'];
    }
    header("Location: user_dashboard.php?page=messages");
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - The Campus Dive</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="dashboard-body">
    
    <div class="dashboard-container">
        
        <!-- SIDEBAR -->
        <?php include 'user_sidebar.php'; ?>
        
        <!-- MAIN CONTENT -->
        <div class="main-content">
            
            <!-- TOP HEADER -->
            <header class="top-header">
                <div class="header-left">
                    <button id="menuToggle" class="menu-toggle"><i class="fas fa-bars"></i></button>
                    <h2><?php echo ucfirst($page); ?></h2>
                </div>
                
                <div class="header-right">
                    <button class="theme-toggle" id="theme-toggle"><i class="fas fa-moon"></i></button>
                    
                    <a href="?page=messages" class="notification-bell" id="notificationBell" style="margin-right: 15px; position: relative; color: var(--text-muted);">
                        <i class="fas fa-bell" style="font-size: 1.2em;"></i>
                        <span id="bellBadge" class="badge" style="display: none; position: absolute; top: -5px; right: -5px; background: var(--danger-color); color: white; border-radius: 50%; padding: 2px 5px; font-size: 0.7em;"></span>
                    </a>

                    <div class="user-profile">
                        <div class="user-avatar">
                            <?php if (!empty($user['avatar_image'])): ?>
                                <img src="uploads/avatars/<?php echo $user['avatar_image']; ?>" alt="Avatar">
                            <?php else: ?>
                                <?php echo substr($user['firstname'], 0, 1); ?>
                            <?php endif; ?>
                        </div>
                        <div class="user-info">
                            <span class="user-name"><?php echo $user['firstname']; ?></span>
                        </div>
                    </div>
                </div>
            </header>

            <div class="dashboard-main">
                
                <?php if (isset($_SESSION['alert'])): ?>
                    <div class="alert alert-<?php echo $_SESSION['alert']['type']; ?>">
                        <?php echo $_SESSION['alert']['message']; unset($_SESSION['alert']); ?>
                    </div>
                <?php endif; ?>

                <!-- DASHBOARD VIEW -->
                <?php if ($page == 'dashboard'): ?>
                    <div class="welcome-banner">
                        <div class="welcome-content">
                            <h1>Welcome back, <?php echo $user['firstname']; ?>!</h1>
                            <p>Here's what's happening with your application.</p>
                        </div>
                        <div class="status-card">
                             <span class="status-value"><?php echo strtoupper($user['status']); ?></span>
                        </div>
                    </div>

                    <div class="dashboard-grid">
                        <div class="dashboard-card">
                            <h3><i class="fas fa-bell"></i> Recent Notifications</h3>
                            <ul class="notification-list">
                                <?php while ($n = $notifications->fetch_assoc()): ?>
                                <li>
                                    <i class="fas fa-info-circle"></i>
                                    <div>
                                        <strong><?php echo $n['title']; ?></strong>
                                        <p><?php echo $n['message']; ?></p>
                                        <small><?php echo date('M d', strtotime($n['created_at'])); ?></small>
                                    </div>
                                </li>
                                <?php endwhile; ?>
                            </ul>
                        </div>

                        <div class="dashboard-card">
                            <h3><i class="fas fa-chart-pie"></i> Quick Stats</h3>
                            <div class="stats-grid" style="grid-template-columns: 1fr; gap: 15px;">
                                <div class="stat-item">
                                    <span class="stat-value"><?php echo $unread_messages; ?></span>
                                    <span class="stat-label">Unread Msgs</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-value"><?php echo $documents->num_rows; ?></span>
                                    <span class="stat-label">Documents</span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- MESSAGES VIEW -->
                <?php if ($page == 'messages'): include 'user_messages_partial.php'; endif; ?>
                
                <!-- DOCUMENTS VIEW -->
                <?php if ($page == 'documents'): ?>
                    <div class="upload-section">
                        <h3>Upload Document</h3>
                        <form method="POST" enctype="multipart/form-data">
                            <div class="input-box">
                                <input type="file" name="document" required>
                                <button type="submit" class="btn-submit">Upload</button>
                            </div>
                        </form>
                    </div>
                    <div class="documents-grid">
                        <?php 
                        $documents->data_seek(0);
                        while($doc = $documents->fetch_assoc()): 
                        ?>
                        <div class="doc-card">
                            <i class="fas fa-file-pdf fa-3x"></i>
                            <h4><?php echo $doc['original_name']; ?></h4>
                            <a href="uploads/<?php echo $doc['filename']; ?>" target="_blank" class="btn-small">View</a>
                        </div>
                        <?php endwhile; ?>
                    </div>
                <?php endif; ?>

                 <!-- STATUS VIEW -->
                 <?php if ($page == 'status'): ?>
                    <div class="dashboard-card">
                        <h3>Application Status Pipeline</h3>
                        <div class="status-timeline">
                            <!-- Helper to render timeline -->
                            <div class="timeline-item <?php echo $user['status'] != 'pending' ? 'completed' : 'active'; ?>">
                                <div class="timeline-icon">1</div>
                                <div class="timeline-content"><h4>Submitted</h4></div>
                            </div>
                            <div class="timeline-item <?php echo ($user['status'] == 'approved' || $user['status'] == 'rejected') ? 'active' : ''; ?>">
                                <div class="timeline-icon">2</div>
                                <div class="timeline-content"><h4>Review</h4></div>
                            </div>
                            <div class="timeline-item <?php echo $user['status'] == 'approved' ? 'completed' : ''; ?>">
                                <div class="timeline-icon">3</div>
                                <div class="timeline-content"><h4>Decision</h4></div>
                            </div>
                        </div>
                    </div>
                 <?php endif; ?>

            </div>
        </div>
    </div>

    <script src="theme.js"></script>
    <script src="notifications.js"></script>
    <script>
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
        if(menuToggle && sidebar) {
            menuToggle.addEventListener('click', () => {
                sidebar.classList.toggle('collapsed');
            });
        }
    </script>
</body>
</html>