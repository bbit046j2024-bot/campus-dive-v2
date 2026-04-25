<?php
require_once 'config.php';
require_once 'email_config.php';
require_once 'google_config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    if (isAdmin()) {
        redirect('admin_dashboard.php');
    } else {
        redirect('user_dashboard.php');
    }
}

$error = '';
$success = '';

// Handle Login ONLY
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'login') {
    $email = sanitize($conn, $_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            // Check if email is verified (skip for admin)
            if ($user['role'] != 'admin' && isset($user['email_verified']) && $user['email_verified'] == 0) {
                $error = 'Please verify your email before logging in. <a href="resend_verification.php?email=' . urlencode($email) . '" style="color: #1e5aa8;">Resend verification</a>';
            } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['firstname'] = $user['firstname'];
                $_SESSION['lastname'] = $user['lastname'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['avatar'] = $user['avatar'];
                $_SESSION['avatar_image'] = isset($user['avatar_image']) ? $user['avatar_image'] : '';

                if ($user['role'] == 'admin') {
                    redirect('admin_dashboard.php');
                } else {
                    redirect('user_dashboard.php');
                }
            }
        } else {
            $error = 'Invalid password!';
        }
    } else {
        $error = 'User not found!';
    }
}

// Check for registration success message
if (isset($_GET['registered']) && $_GET['registered'] == 'success') {
    $success = 'Registration successful! Please check your email to verify your account.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - The Campus Dive</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Ensure only login form is visible */
        .form-box.login {
            display: block;
        }
    </style>
</head>
<body>
    <header>
        <div class="logo-container">
            <img src="campus.png" alt="The Campus Dive Logo" class="logo-img">
            <h2 class="logo-text">The Campus Dive</h2>
        </div>
        <nav class="navigation">
            <!-- Navigation Links Removed -->
            <button id="theme-toggle" class="theme-toggle" aria-label="Toggle Theme">
                <i class="fas fa-moon"></i>
            </button>
            <a href="register.php" class="btnLogin-popup">Register</a>
        </nav>
    </header>

    <div class="wrapper" id="authWrapper">
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <!-- Login Form ONLY -->
        <div class="form-box login" id="loginForm">
            <h2>Welcome Back</h2>
            <p class="subtitle">Sign in to access recruitment portal</p>

            <form method="POST" action="">
                <input type="hidden" name="action" value="login">

                <div class="input-box">
                    <span class="icon"><i class="fas fa-envelope"></i></span>
                    <input type="email" name="email" required>
                    <label>Email Address</label>
                </div>

                <div class="input-box">
                    <span class="icon"><i class="fas fa-lock"></i></span>
                    <input type="password" name="password" required>
                    <label>Password</label>
                    <span class="toggle-password" onclick="togglePassword(this)">
                        <i class="fas fa-eye"></i>
                    </span>
                </div>

                <div class="remember-forgot">
                    <label class="checkbox-container">
                        <input type="checkbox" name="remember">
                        <span class="checkmark"></span>
                        Remember me
                    </label>
                    <a href="password_reset.php" class="forgot-link">Forgot Password?</a>
                </div>

                <button type="submit" class="btn-submit">
                    <span>Sign In</span>
                    <i class="fas fa-arrow-right"></i>
                </button>

                <div class="login-register">
                    <p>Don't have an account? <a href="register.php" class="register-link">Register as Tech Student</a></p>
                </div>

                <div class="social-login" style="margin-top: 20px; text-align: center;">
                    <p style="color: var(--text-light); margin-bottom: 15px;">- OR -</p>
                    <a href="<?php echo getGoogleLoginUrl(); ?>" class="google-btn" style="display: inline-flex; align-items: center; gap: 10px; padding: 12px 25px; background: #fff; border: 2px solid #ddd; border-radius: 25px; text-decoration: none; color: #333; font-weight: 500; transition: all 0.3s;">
                        <img src="https://www.google.com/favicon.ico" alt="Google" style="width: 20px; height: 20px;">
                        Sign in with Google
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
        function togglePassword(btn) {
            const input = btn.parentElement.querySelector('input');
            const icon = btn.querySelector('i');

            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>
    <script src="theme.js"></script>
</body>
</html>