<?php
// PHPMailer Email Configuration

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

// ═══════════════════════════════════════════════════════
// UPDATE THESE 4 VALUES
// DEPRECATED: Please use api/services/EmailService.php instead.
// Removing hardcoded credentials for security.
require_once __DIR__ . '/api/services/EmailService.php';

function sendEmail($to, $toName, $subject, $body, $isHTML = true) {
    return EmailService::send($to, $subject, $body);
}

function sendVerificationEmail($to, $name, $token) {
    $verify_link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/verify_email.php?token=" . $token;
    $subject = "Verify Your Email - The Campus Dive";
    $body = "<h2>Welcome!</h2><p>Click to verify: <a href='{$verify_link}'>{$verify_link}</a></p>";
    return sendEmail($to, $name, $subject, $body);
}

function getVerificationLink($token) {
    return "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/verify_email.php?token=" . $token;
}
?>