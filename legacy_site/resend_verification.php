<?php
require_once 'config.php';
require_once 'email_config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['resend'])) {
    $email = sanitize($conn, $_POST['email']);
    
    $stmt = $conn->prepare("SELECT id, firstname, lastname, email_verified, verification_token FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        
        if ($user['email_verified'] == 1) {
            $error = 'This email is already verified. Please <a href="index.php">login</a>.';
        } else {
            $token = $user['verification_token'];
            if (empty($token)) {
                $token = bin2hex(random_bytes(32));
                $update = $conn->prepare("UPDATE users SET verification_token = ? WHERE id = ?");
                $update->bind_param("si", $token, $user['id']);
                $update->execute();
            }
            
            $full_name = $user['firstname'] . ' ' . $user['lastname'];
            $email_sent = sendVerificationEmail($email, $full_name, $token);
            
            if ($email_sent) {
                $success = 'Verification email sent! Check your inbox.<br><br><a href="index.php">← Back to Login</a>';
            } else {
                $verify_link = getVerificationLink($token);
                $success = "Email failed. <a href='{$verify_link}' style='color: #1e5aa8;'>Click here to verify</a>";
            }
        }
    } else {
        $error = 'Email not found.';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Resend Verification</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="wrapper" style="margin-top: 100px;">
        <div class="form-box" style="text-align: center;">
            <h2>Resend Verification</h2>
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php else: ?>
                <form method="POST">
                    <div class="input-box">
                        <input type="email" name="email" required placeholder="Email">
                    </div>
                    <button type="submit" name="resend" class="btn-submit">Send Verification</button>
                </form>
            <?php endif; ?>
            <br><a href="index.php">← Back to Login</a>
        </div>
    </div>
</body>
</html>