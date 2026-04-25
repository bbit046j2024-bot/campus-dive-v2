<?php
require_once 'config.php';
require_once 'email_config.php';

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

// Handle Registration ONLY
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'register') {
    $firstname = sanitize($conn, $_POST['firstname']);
    $lastname = sanitize($conn, $_POST['lastname']);
    $email = sanitize($conn, $_POST['email']);
    $phone = sanitize($conn, $_POST['phone']);
    $student_id = sanitize($conn, $_POST['student_id']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $avatar = strtoupper(substr($firstname, 0, 1) . substr($lastname, 0, 1));

    // Check if email exists
    $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        $error = 'Email already registered!';
    } else {
        // Generate verification token
        $verification_token = bin2hex(random_bytes(32));
        
        $stmt = $conn->prepare("INSERT INTO users (firstname, lastname, email, phone, student_id, password, avatar, verification_token, email_verified) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0)");
        $stmt->bind_param("ssssssss", $firstname, $lastname, $email, $phone, $student_id, $password, $avatar, $verification_token);

        if ($stmt->execute()) {
            // Send verification email
            $full_name = $firstname . ' ' . $lastname;
            $email_sent = sendVerificationEmail($email, $full_name, $verification_token);
            
            if ($email_sent) {
                // Redirect to login with success message
                redirect('index.php?registered=success');
            } else {
                // Email failed - show verification link
                $verify_link = getVerificationLink($verification_token);
                $success = "Registration successful! <br><a href='{$verify_link}' style='color: #1e5aa8;'>Click here to verify your email</a>";
            }
        } else {
            $error = 'Registration failed. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - The Campus Dive</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Ensure only register form is visible */
        .form-box.register {
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
            <a href="index.php" class="btnLogin-popup">Login</a>
        </nav>
    </header>

    <div class="wrapper" id="authWrapper">
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <!-- Registration Form ONLY -->
        <div class="form-box register" id="registerForm">
            <h2>Tech Student Registration</h2>
            <p class="subtitle">Join our recruitment platform</p>

            <form method="POST" action="">
                <input type="hidden" name="action" value="register">

                <div class="input-row">
                    <div class="input-box half">
                        <span class="icon"><i class="fas fa-user"></i></span>
                        <input type="text" name="firstname" required>
                        <label>First Name</label>
                    </div>
                    <div class="input-box half">
                        <span class="icon"><i class="fas fa-user"></i></span>
                        <input type="text" name="lastname" required>
                        <label>Last Name</label>
                    </div>
                </div>

                <div class="input-box">
                    <span class="icon"><i class="fas fa-envelope"></i></span>
                    <input type="email" name="email" required>
                    <label>Email Address</label>
                </div>

                <div class="input-box">
                    <span class="icon"><i class="fas fa-phone"></i></span>
                    <input type="tel" name="phone" required>
                    <label>Phone Number</label>
                </div>

                <div class="input-box">
                    <span class="icon"><i class="fas fa-id-card"></i></span>
                    <input type="text" name="student_id" required>
                    <label>Student ID</label>
                </div>

                <div class="input-box">
                    <span class="icon"><i class="fas fa-lock"></i></span>
                    <input type="password" name="password" required minlength="6" id="regPassword">
                    <label>Create Password</label>
                    <span class="toggle-password" onclick="togglePassword(this)">
                        <i class="fas fa-eye"></i>
                    </span>
                </div>

                <div class="password-strength">
                    <div class="strength-bar">
                        <div class="strength-fill" id="strengthFill"></div>
                    </div>
                    <span class="strength-text" id="strengthText">Password strength</span>
                </div>

                <div class="terms">
                    <label class="checkbox-container">
                        <input type="checkbox" name="terms" required>
                        <span class="checkmark"></span>
                        I agree to the <a href="#">Terms of Service</a>
                    </label>
                </div>

                <button type="submit" class="btn-submit">
                    <span>Register</span>
                    <i class="fas fa-arrow-right"></i>
                </button>

                <div class="login-register">
                    <p>Already have an account? <a href="index.php" class="login-link">Sign In</a></p>
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

        // Password strength checker
        document.getElementById('regPassword').addEventListener('input', function() {
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
    </script>
</body>
</html>