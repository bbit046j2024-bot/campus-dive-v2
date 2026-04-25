<?php
require_once 'config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('index.php');
}

$user_id = $_SESSION['user_id'];
$is_admin = isAdmin();

// Get current user details
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$success_message = '';
$error_message = '';

// Handle Profile Update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $firstname = sanitize($conn, $_POST['firstname']);
    $lastname = sanitize($conn, $_POST['lastname']);
    $phone = sanitize($conn, $_POST['phone']);
    $email = sanitize($conn, $_POST['email']);

    // Check if email is already taken by another user
    $check = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $check->bind_param("si", $email, $user_id);
    $check->execute();

    if ($check->get_result()->num_rows > 0) {
        $error_message = 'Email is already in use by another account!';
    } else {
        $update = $conn->prepare("UPDATE users SET firstname = ?, lastname = ?, phone = ?, email = ? WHERE id = ?");
        $update->bind_param("ssssi", $firstname, $lastname, $phone, $email, $user_id);

        if ($update->execute()) {
            // Update session
            $_SESSION['firstname'] = $firstname;
            $_SESSION['lastname'] = $lastname;
            $_SESSION['email'] = $email;

            $success_message = 'Profile updated successfully!';

            // Refresh session and user data
            refreshSession($conn, $user_id);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
        } else {
            $error_message = 'Failed to update profile. Please try again.';
        }
    }
}

// Handle Password Change
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Verify current password
    if (!password_verify($current_password, $user['password'])) {
        $error_message = 'Current password is incorrect!';
    } elseif ($new_password != $confirm_password) {
        $error_message = 'New passwords do not match!';
    } elseif (strlen($new_password) < 6) {
        $error_message = 'Password must be at least 6 characters!';
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $update = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $update->bind_param("si", $hashed_password, $user_id);

        if ($update->execute()) {
            $success_message = 'Password changed successfully!';
        } else {
            $error_message = 'Failed to change password. Please try again.';
        }
    }
}

// Handle Profile Picture Upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['profile_picture'])) {
    $file = $_FILES['profile_picture'];
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];

    if (in_array($file['type'], $allowed_types) && $file['size'] <= 2 * 1024 * 1024) { // 2MB max
        $filename = 'avatar_' . $user_id . '_' . time() . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
        $upload_path = 'uploads/avatars/' . $filename;

        if (!file_exists('uploads/avatars')) {
            mkdir('uploads/avatars', 0777, true);
        }

        if (move_uploaded_file($file['tmp_name'], $upload_path)) {
            // Delete old avatar if exists
            if (!empty($user['avatar_image']) && file_exists('uploads/avatars/' . $user['avatar_image'])) {
                unlink('uploads/avatars/' . $user['avatar_image']);
            }

            $update = $conn->prepare("UPDATE users SET avatar_image = ? WHERE id = ?");
            $update->bind_param("si", $filename, $user_id);
            $update->execute();

            $success_message = 'Profile picture updated!';

            // Update session with new avatar image
            $_SESSION['avatar_image'] = $filename;

            // Refresh user data
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
        }
    } else {
        $error_message = 'Invalid file type or size too large (max 2MB)';
    }
}

// Handle Notification Preferences (stored in session for demo, would use database in production)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_notifications'])) {
    $preferences = [
        'email_notifications' => isset($_POST['email_notifications']) ? 1 : 0,
        'message_notifications' => isset($_POST['message_notifications']) ? 1 : 0,
        'status_notifications' => isset($_POST['status_notifications']) ? 1 : 0,
        'marketing_emails' => isset($_POST['marketing_emails']) ? 1 : 0
    ];

    // Store in database (you'd need to create a user_preferences table for production)
    $_SESSION['notification_prefs'] = $preferences;
    $success_message = 'Notification preferences saved!';
}

// Handle Account Deletion Request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_account'])) {
    $password = $_POST['delete_password'];

    if (password_verify($password, $user['password'])) {
        // Delete user documents
        $doc_stmt = $conn->prepare("SELECT filename FROM documents WHERE user_id = ?");
        $doc_stmt->bind_param("i", $user_id);
        $doc_stmt->execute();
        $docs = $doc_stmt->get_result();

        while ($doc = $docs->fetch_assoc()) {
            if (file_exists('uploads/' . $doc['filename'])) {
                unlink('uploads/' . $doc['filename']);
            }
        }

        // Delete user from database
        $delete = $conn->prepare("DELETE FROM users WHERE id = ?");
        $delete->bind_param("i", $user_id);

        if ($delete->execute()) {
            session_destroy();
            redirect('index.php');
        }
    } else {
        $error_message = 'Incorrect password. Account deletion cancelled.';
    }
}

$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'profile';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - The Campus Dive</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .settings-container {
            max-width: 1000px;
            margin: 0 auto;
        }

        .settings-grid {
            display: grid;
            grid-template-columns: 250px 1fr;
            gap: 30px;
        }

        .settings-sidebar {
            background: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            height: fit-content;
        }

        .settings-nav {
            list-style: none;
        }

        .settings-nav li {
            margin-bottom: 5px;
        }

        .settings-nav a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 15px;
            color: var(--text-light);
            text-decoration: none;
            border-radius: 10px;
            transition: all 0.3s;
            font-weight: 500;
        }

        .settings-nav a:hover,
        .settings-nav a.active {
            background: rgba(30, 90, 168, 0.1);
            color: var(--primary-color);
        }

        .settings-nav i {
            width: 20px;
        }

        .settings-content {
            background: white;
            border-radius: 20px;
            padding: 35px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        }

        .settings-section {
            display: none;
        }

        .settings-section.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .settings-header {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid rgba(0, 0, 0, 0.05);
        }

        .settings-header h2 {
            color: var(--text-dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .profile-picture-section {
            display: flex;
            align-items: center;
            gap: 30px;
            margin-bottom: 30px;
            padding: 30px;
            background: rgba(30, 90, 168, 0.03);
            border-radius: 15px;
        }

        .profile-picture {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 3em;
            font-weight: 700;
            position: relative;
            overflow: hidden;
        }

        .profile-picture img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profile-picture-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(0, 0, 0, 0.6);
            padding: 10px;
            text-align: center;
            opacity: 0;
            transition: opacity 0.3s;
            cursor: pointer;
        }

        .profile-picture:hover .profile-picture-overlay {
            opacity: 1;
        }

        .profile-picture-info h3 {
            margin-bottom: 5px;
            color: var(--text-dark);
        }

        .profile-picture-info p {
            color: var(--text-light);
            font-size: 0.9em;
            margin-bottom: 15px;
        }

        .upload-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: var(--primary-color);
            color: white;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
        }

        .upload-btn:hover {
            background: var(--primary-dark);
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-dark);
            font-weight: 500;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid rgba(30, 90, 168, 0.1);
            border-radius: 10px;
            font-size: 1em;
            transition: all 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 4px rgba(30, 90, 168, 0.1);
        }

        .form-group input:disabled {
            background: rgba(0, 0, 0, 0.05);
            cursor: not-allowed;
        }

        .help-text {
            font-size: 0.85em;
            color: var(--text-light);
            margin-top: 5px;
        }

        .toggle-switch {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px;
            background: rgba(30, 90, 168, 0.03);
            border-radius: 12px;
            margin-bottom: 15px;
        }

        .toggle-info h4 {
            color: var(--text-dark);
            margin-bottom: 5px;
        }

        .toggle-info p {
            color: var(--text-light);
            font-size: 0.9em;
        }

        .switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked + .slider {
            background-color: var(--primary-color);
        }

        input:checked + .slider:before {
            transform: translateX(26px);
        }

        .danger-zone {
            border: 2px solid rgba(231, 76, 60, 0.2);
            border-radius: 15px;
            padding: 25px;
            margin-top: 30px;
        }

        .danger-zone h3 {
            color: var(--danger-color);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .danger-zone p {
            color: var(--text-light);
            margin-bottom: 20px;
        }

        .btn-danger-outline {
            padding: 12px 25px;
            background: transparent;
            border: 2px solid var(--danger-color);
            color: var(--danger-color);
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-danger-outline:hover {
            background: var(--danger-color);
            color: white;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 10000;
            justify-content: center;
            align-items: center;
        }

        .modal.show {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 20px;
            padding: 35px;
            max-width: 500px;
            width: 90%;
            animation: modalSlide 0.3s ease;
        }

        @keyframes modalSlide {
            from { opacity: 0; transform: translateY(-50px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .modal-header {
            margin-bottom: 20px;
        }

        .modal-header h3 {
            color: var(--danger-color);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .modal-footer {
            display: flex;
            gap: 15px;
            margin-top: 25px;
        }

        .btn-secondary {
            flex: 1;
            padding: 12px;
            background: rgba(30, 90, 168, 0.1);
            border: none;
            color: var(--primary-color);
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
        }

        .btn-danger {
            flex: 1;
            padding: 12px;
            background: var(--danger-color);
            border: none;
            color: white;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
        }

        .save-btn {
            margin-top: 25px;
            padding: 14px 35px;
        }

        @media (max-width: 768px) {
            .settings-grid {
                grid-template-columns: 1fr;
            }

            .settings-sidebar {
                order: 2;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .profile-picture-section {
                flex-direction: column;
                text-align: center;
            }
        }
    </style>
</head>
<body class="dashboard-body">
    <header class="dashboard-header">
        <div class="logo-container">
            <img src="campus.png" alt="The Campus Dive Logo" class="logo-img">
            <h2 class="logo-text"><?php echo $is_admin ? 'Admin Portal' : 'The Campus Dive'; ?></h2>
        </div>
        <nav class="dashboard-nav">
            <a href="<?php echo $is_admin ? 'admin_dashboard.php' : 'user_dashboard.php'; ?>" class="nav-item">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a href="settings.php" class="nav-item active">
                <i class="fas fa-cog"></i> Settings
            </a>
        </nav>
        <div class="user-profile">
            <div class="user-avatar <?php echo $is_admin ? 'admin-avatar' : ''; ?>" style="overflow: hidden;">
                <?php 
                $settings_avatar = isset($user['avatar_image']) ? $user['avatar_image'] : (isset($_SESSION['avatar_image']) ? $_SESSION['avatar_image'] : '');
                if (!empty($settings_avatar) && file_exists('uploads/avatars/' . $settings_avatar)): 
                ?>
                    <img src="uploads/avatars/<?php echo $settings_avatar; ?>?t=<?php echo time(); ?>" alt="Avatar" style="width: 100%; height: 100%; object-fit: cover;">
                <?php else: ?>
                    <?php echo isset($_SESSION['avatar']) ? $_SESSION['avatar'] : 'NA'; ?>
                <?php endif; ?>
            </div>
            <div class="user-info">
                <span class="user-name"><?php echo $_SESSION['firstname'] . ' ' . $_SESSION['lastname']; ?></span>
                <span class="user-role"><?php echo $is_admin ? 'Administrator' : 'Tech Student'; ?></span>
            </div>
            <div class="user-dropdown">
                <button class="dropdown-btn" onclick="toggleDropdown()">
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div class="dropdown-content" id="dropdownContent">
                    <a href="<?php echo $is_admin ? 'admin_dashboard.php' : 'user_dashboard.php'; ?>">
                        <i class="fas fa-home"></i> Dashboard
                    </a>
                    <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
                    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
        </div>
    </header>

    <main class="dashboard-main">
        <div class="settings-container">
            <?php if ($success_message): ?>
                <div class="alert alert-success" style="margin-bottom: 20px;">
                    <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="alert alert-error" style="margin-bottom: 20px;">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <div class="settings-grid">
                <!-- Sidebar -->
                <aside class="settings-sidebar">
                    <ul class="settings-nav">
                        <li>
                            <a href="?tab=profile" class="<?php echo $active_tab == 'profile' ? 'active' : ''; ?>">
                                <i class="fas fa-user"></i> Profile
                            </a>
                        </li>
                        <li>
                            <a href="?tab=password" class="<?php echo $active_tab == 'password' ? 'active' : ''; ?>">
                                <i class="fas fa-lock"></i> Password
                            </a>
                        </li>
                        <li>
                            <a href="?tab=notifications" class="<?php echo $active_tab == 'notifications' ? 'active' : ''; ?>">
                                <i class="fas fa-bell"></i> Notifications
                            </a>
                        </li>
                        <li>
                            <a href="?tab=privacy" class="<?php echo $active_tab == 'privacy' ? 'active' : ''; ?>">
                                <i class="fas fa-shield-alt"></i> Privacy & Security
                            </a>
                        </li>
                    </ul>
                </aside>

                <!-- Content -->
                <div class="settings-content">
                    <!-- Profile Settings -->
                    <div class="settings-section <?php echo $active_tab == 'profile' ? 'active' : ''; ?>" id="profile">
                        <div class="settings-header">
                            <h2><i class="fas fa-user"></i> Profile Settings</h2>
                        </div>

                        <!-- Profile Picture -->
                        <div class="profile-picture-section">
                            <div class="profile-picture" style="overflow: hidden;">
                                <?php 
                                $profile_avatar = isset($user['avatar_image']) ? $user['avatar_image'] : (isset($_SESSION['avatar_image']) ? $_SESSION['avatar_image'] : '');
                                if (!empty($profile_avatar) && file_exists('uploads/avatars/' . $profile_avatar)): 
                                ?>
                                    <img src="uploads/avatars/<?php echo $profile_avatar; ?>?t=<?php echo time(); ?>" alt="Profile Picture" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                                <?php else: ?>
                                    <?php echo isset($_SESSION['avatar']) ? $_SESSION['avatar'] : 'NA'; ?>
                                <?php endif; ?>
                            </div>
                            <div class="profile-picture-info">
                                <h3>Profile Picture</h3>
                                <p>Upload a new profile picture. JPG, PNG or GIF. Max 2MB.</p>
                                <form method="POST" enctype="multipart/form-data" id="pictureForm">
                                    <input type="file" name="profile_picture" id="profile_picture" accept="image/*" style="display: none;" onchange="document.getElementById('pictureForm').submit();">
                                    <label for="profile_picture" class="upload-btn">
                                        <i class="fas fa-camera"></i> Change Picture
                                    </label>
                                </form>
                            </div>
                        </div>

                        <!-- Profile Form -->
                        <form method="POST">
                            <input type="hidden" name="update_profile" value="1">

                            <div class="form-grid">
                                <div class="form-group">
                                    <label>First Name</label>
                                    <input type="text" name="firstname" value="<?php echo htmlspecialchars($user['firstname']); ?>" required>
                                </div>

                                <div class="form-group">
                                    <label>Last Name</label>
                                    <input type="text" name="lastname" value="<?php echo htmlspecialchars($user['lastname']); ?>" required>
                                </div>

                                <div class="form-group">
                                    <label>Email Address</label>
                                    <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                </div>

                                <div class="form-group">
                                    <label>Phone Number</label>
                                    <input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                                </div>

                                <?php if (!$is_admin): ?>
                                <div class="form-group">
                                    <label>Student ID</label>
                                    <input type="text" value="<?php echo htmlspecialchars($user['student_id']); ?>" disabled>
                                    <p class="help-text">Student ID cannot be changed</p>
                                </div>

                                <div class="form-group">
                                    <label>Account Status</label>
                                    <input type="text" value="<?php echo ucfirst($user['status']); ?>" disabled style="text-transform: capitalize; color: <?php echo $user['status'] == 'approved' ? '#27ae60' : ($user['status'] == 'rejected' ? '#e74c3c' : '#f39c12'); ?>; font-weight: 600;">
                                </div>
                                <?php endif; ?>

                                <div class="form-group full-width">
                                    <label>Member Since</label>
                                    <input type="text" value="<?php echo date('F d, Y', strtotime($user['created_at'])); ?>" disabled>
                                </div>
                            </div>

                            <button type="submit" class="btn-submit save-btn">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                        </form>
                    </div>

                    <!-- Password Settings -->
                    <div class="settings-section <?php echo $active_tab == 'password' ? 'active' : ''; ?>" id="password">
                        <div class="settings-header">
                            <h2><i class="fas fa-lock"></i> Change Password</h2>
                        </div>

                        <form method="POST">
                            <input type="hidden" name="change_password" value="1">

                            <div class="form-group">
                                <label>Current Password</label>
                                <input type="password" name="current_password" required>
                            </div>

                            <div class="form-group">
                                <label>New Password</label>
                                <input type="password" name="new_password" required minlength="6" id="newPassword">
                                <div class="password-strength" style="margin-top: 10px;">
                                    <div class="strength-bar">
                                        <div class="strength-fill" id="strengthFill"></div>
                                    </div>
                                    <span class="strength-text" id="strengthText">Password strength</span>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Confirm New Password</label>
                                <input type="password" name="confirm_password" required>
                            </div>

                            <button type="submit" class="btn-submit save-btn">
                                <i class="fas fa-key"></i> Update Password
                            </button>
                        </form>
                    </div>

                    <!-- Notification Settings -->
                    <div class="settings-section <?php echo $active_tab == 'notifications' ? 'active' : ''; ?>" id="notifications">
                        <div class="settings-header">
                            <h2><i class="fas fa-bell"></i> Notification Preferences</h2>
                        </div>

                        <form method="POST">
                            <input type="hidden" name="update_notifications" value="1">

                            <div class="toggle-switch">
                                <div class="toggle-info">
                                    <h4>Email Notifications</h4>
                                    <p>Receive email updates about your account activity</p>
                                </div>
                                <label class="switch">
                                    <input type="checkbox" name="email_notifications" checked>
                                    <span class="slider"></span>
                                </label>
                            </div>

                            <div class="toggle-switch">
                                <div class="toggle-info">
                                    <h4>Message Notifications</h4>
                                    <p>Get notified when you receive new messages</p>
                                </div>
                                <label class="switch">
                                    <input type="checkbox" name="message_notifications" checked>
                                    <span class="slider"></span>
                                </label>
                            </div>

                            <div class="toggle-switch">
                                <div class="toggle-info">
                                    <h4>Status Updates</h4>
                                    <p>Notifications when your application status changes</p>
                                </div>
                                <label class="switch">
                                    <input type="checkbox" name="status_notifications" checked>
                                    <span class="slider"></span>
                                </label>
                            </div>

                            <div class="toggle-switch">
                                <div class="toggle-info">
                                    <h4>Marketing Emails</h4>
                                    <p>Receive news, updates, and promotional offers</p>
                                </div>
                                <label class="switch">
                                    <input type="checkbox" name="marketing_emails">
                                    <span class="slider"></span>
                                </label>
                            </div>

                            <button type="submit" class="btn-submit save-btn">
                                <i class="fas fa-save"></i> Save Preferences
                            </button>
                        </form>
                    </div>

                    <!-- Privacy & Security -->
                    <div class="settings-section <?php echo $active_tab == 'privacy' ? 'active' : ''; ?>" id="privacy">
                        <div class="settings-header">
                            <h2><i class="fas fa-shield-alt"></i> Privacy & Security</h2>
                        </div>

                        <div class="form-group">
                            <label>Last Login</label>
                            <input type="text" value="<?php echo date('F d, Y H:i:s'); ?>" disabled>
                        </div>

                        <div class="form-group">
                            <label>Account Type</label>
                            <input type="text" value="<?php echo $is_admin ? 'Administrator' : 'Student'; ?>" disabled>
                        </div>

                        <div class="form-group">
                            <label>Two-Factor Authentication</label>
                            <div style="display: flex; align-items: center; gap: 15px; margin-top: 10px;">
                                <span style="color: var(--text-light);">Coming soon</span>
                                <button class="btn-secondary" disabled style="opacity: 0.5;">
                                    <i class="fas fa-lock"></i> Enable 2FA
                                </button>
                            </div>
                        </div>

                        <!-- Danger Zone -->
                        <div class="danger-zone">
                            <h3><i class="fas fa-exclamation-triangle"></i> Danger Zone</h3>
                            <p>Once you delete your account, there is no going back. Please be certain.</p>
                            <button type="button" class="btn-danger-outline" onclick="showDeleteModal()">
                                <i class="fas fa-trash-alt"></i> Delete Account
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Delete Account Modal -->
    <div class="modal" id="deleteModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-exclamation-triangle"></i> Delete Account</h3>
            </div>
            <p>Are you sure you want to delete your account? This action cannot be undone. All your data, including documents and messages, will be permanently removed.</p>

            <form method="POST" id="deleteForm">
                <div class="form-group">
                    <label>Enter your password to confirm:</label>
                    <input type="password" name="delete_password" required style="margin-top: 10px;">
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="hideDeleteModal()">Cancel</button>
                    <button type="submit" name="delete_account" class="btn-danger">
                        <i class="fas fa-trash-alt"></i> Delete Account
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleDropdown() {
            document.getElementById('dropdownContent').classList.toggle('show');
        }

        window.onclick = function(e) {
            if (!e.target.matches('.dropdown-btn') && !e.target.matches('.dropdown-btn *')) {
                const dropdown = document.getElementById('dropdownContent');
                if (dropdown.classList.contains('show')) {
                    dropdown.classList.remove('show');
                }
            }
        }

        // Password strength checker
        document.getElementById('newPassword').addEventListener('input', function() {
            const password = this.value;
            const fill = document.getElementById('strengthFill');
            const text = document.getElementById('strengthText');

            let strength = 0;
            if (password.length >= 6) strength++;
            if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
            if (password.match(/[0-9]/)) strength++;
            if (password.match(/[^a-zA-Z0-9]/)) strength++;

            const colors = ['#e74c3c', '#e67e22', '#f1c40f', '#27ae60'];
            const labels = ['Weak', 'Fair', 'Good', 'Strong'];

            fill.style.width = (strength / 4 * 100) + '%';
            fill.style.background = colors[strength - 1] || '#e74c3c';
            text.textContent = labels[strength - 1] || 'Too short';
            text.style.color = colors[strength - 1] || '#e74c3c';
        });

        // Modal functions
        function showDeleteModal() {
            document.getElementById('deleteModal').classList.add('show');
        }

        function hideDeleteModal() {
            document.getElementById('deleteModal').classList.remove('show');
        }

        // Close modal when clicking outside
        document.getElementById('deleteModal').addEventListener('click', function(e) {
            if (e.target === this) {
                hideDeleteModal();
            }
        });
    </script>
</body>
</html>