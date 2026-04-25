<?php
require_once __DIR__ . '/api/config/app.php';
require_once __DIR__ . '/api/services/EmailService.php';

echo "Testing Email Sending...\n";
echo "Host: " . MAIL_HOST . "\n";
echo "Username: " . MAIL_USERNAME . "\n";

$to = 'test-recipient@example.com'; // User should change this or I'll use a dummy and check the boolean
$subject = 'Campus Dive SMTP Test';
$body = '<h1>Test Email</h1><p>If you receive this, SMTP is working correctly.</p>';

$result = EmailService::send($to, $subject, $body);

if ($result) {
    echo "SUCCESS: Email sent successfully!\n";
} else {
    echo "FAILURE: Email failed to send. Check error logs or ensure PHPMailer is correctly configured.\n";
}
