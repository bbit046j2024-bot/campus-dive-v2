<?php
require_once 'config.php';
require_once 'google_config.php';

if (isset($_GET['code'])) {
    // Redirect to the API callback handler to centralize logic
    $callback_url = '/api/auth/google-callback?code=' . urlencode($_GET['code']);
    header("Location: " . $callback_url);
    exit;
} else {
    $_SESSION['alert'] = ['message' => 'Google login cancelled.', 'type' => 'error'];
    redirect('index.php');
}
?>