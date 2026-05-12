<?php
/**
 * Google OAuth Callback Gateway
 * Receives the OAuth code from Google and forwards it to the API handler.
 * Does NOT need a database connection — just a redirect.
 */

if (isset($_GET['code'])) {
    $code = urlencode($_GET['code']);
    $state = isset($_GET['state']) ? '&state=' . urlencode($_GET['state']) : '';
    header("Location: /api/auth/google-callback?code={$code}{$state}");
    exit;
}

if (isset($_GET['error'])) {
    $error = urlencode($_GET['error']);
    $frontendUrl = getenv('FRONTEND_URL') ?: 'https://campus-dive-v2.vercel.app';
    header("Location: {$frontendUrl}/login?error={$error}");
    exit;
}

// No code and no error — something unexpected
$frontendUrl = getenv('FRONTEND_URL') ?: 'https://campus-dive-v2.vercel.app';
header("Location: {$frontendUrl}/login?error=oauth_cancelled");
exit;
