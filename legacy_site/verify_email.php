<?php
require_once 'config.php';

if (isset($_GET['token'])) {
    $token = sanitize($conn, $_GET['token']);

    $stmt = $conn->prepare("SELECT id FROM users WHERE verification_token = ? AND email_verified = 0");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();

        // Mark email as verified
        $update = $conn->prepare("UPDATE users SET email_verified = 1, verification_token = NULL WHERE id = ?");
        $update->bind_param("i", $user['id']);

        if ($update->execute()) {
            $success = 'Email verified successfully! You can now log in.';
        } else {
            $error = 'Verification failed. Please try again.';
        }
    } else {
        $error = 'Invalid or expired verification token.';
    }
} else {
    $error = 'No verification token provided.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification - The Campus Dive</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="wrapper" style="margin-top: 100px;">
        <div class="form-box" style="text-align: center;">
            <h2>Email Verification</h2>

            <?php if (isset($success)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                </div>
                <a href="index.php" class="btn-submit" style="margin-top: 20px; display: inline-block;">Go to Login</a>
            <?php elseif (isset($error)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
                <a href="index.php" class="btn-submit" style="margin-top: 20px; display: inline-block;">Back to Home</a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>