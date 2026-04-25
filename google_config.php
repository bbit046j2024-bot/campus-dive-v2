<?php
// Google OAuth Configuration
// Get credentials from: https://console.cloud.google.com/apis/credentials

define('GOOGLE_CLIENT_ID', getenv('GOOGLE_CLIENT_ID') ?: '');
define('GOOGLE_CLIENT_SECRET', getenv('GOOGLE_CLIENT_SECRET') ?: '');

// Dynamic Redirect URI based on environment/host
// Support for Railway/Vercel reverse proxies
$protocol = 'http';
if ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || 
    (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')) {
    $protocol = 'https';
}
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$defaultRedirect = $protocol . '://' . $host . '/google_callback.php';
define('GOOGLE_REDIRECT_URI', getenv('GOOGLE_REDIRECT_URI') ?: $defaultRedirect);

// Google API Client Library
// Download from: https://github.com/googleapis/google-api-php-client
// Or install via Composer: composer require google/apiclient:^2.0

require_once __DIR__ . '/vendor/autoload.php';

use Google\Client;
use Google\Service\Oauth2;

function getGoogleClient() {
    $client = new Client();
    $client->setClientId(GOOGLE_CLIENT_ID);
    $client->setClientSecret(GOOGLE_CLIENT_SECRET);
    $client->setRedirectUri(GOOGLE_REDIRECT_URI);
    $client->addScope("email");
    $client->addScope("profile");

    return $client;
}

function getGoogleLoginUrl() {
    $client = getGoogleClient();
    return $client->createAuthUrl();
}

function handleGoogleCallback($code) {
    global $conn;

    $client = getGoogleClient();
    $token = $client->fetchAccessTokenWithAuthCode($code);

    if (isset($token['error'])) {
        return ['success' => false, 'error' => $token['error']];
    }

    $client->setAccessToken($token);

    // Get user info
    $oauth2 = new Oauth2($client);
    $googleUser = $oauth2->userinfo->get();

    $email = $googleUser->getEmail();
    $firstname = $googleUser->getGivenName();
    $lastname = $googleUser->getFamilyName();
    $googleId = $googleUser->getId();

    // Prefer API User model if available
    if (class_exists('User')) {
        $user = User::findByEmail($email);
        if (!$user) {
            // Check by Google ID too
            $db = Database::getInstance();
            $stmt = $db->prepare("SELECT * FROM users WHERE google_id = ?");
            $stmt->execute([$googleId]);
            $user = $stmt->fetch();
        }
    } else {
        // Fallback to mysqli
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? OR google_id = ?");
        $stmt->bind_param("ss", $email, $googleId);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
    }

    if ($user) {
        // User exists - update Google ID if missing
        if (empty($user['google_id'])) {
            if (class_exists('User')) {
                User::update($user['id'], ['google_id' => $googleId]);
            } else {
                $update = $conn->prepare("UPDATE users SET google_id = ? WHERE id = ?");
                $update->bind_param("si", $googleId, $user['id']);
                $update->execute();
            }
        }

        // Establish session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['firstname'] = $user['firstname'];
        $_SESSION['lastname'] = $user['lastname'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'] ?? 'user';
        $_SESSION['role_id'] = $user['role_id'] ?? 4; // Fallback to student
        
        return ['success' => true, 'user' => $user];
    } else {
        // New user creation
        $avatar = strtoupper(substr($firstname, 0, 1) . substr($lastname, 0, 1));
        $role = 'user';
        $role_id = defined('ROLE_STUDENT') ? ROLE_STUDENT : 4;
        $status = 'pending';
        $randomPassword = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);

        if (class_exists('User')) {
            // Using API model
            $db = Database::getInstance();
            $stmt = $db->prepare("INSERT INTO users (firstname, lastname, email, google_id, password, role, role_id, avatar, status, email_verified) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");
            $stmt->execute([$firstname, $lastname, $email, $googleId, $randomPassword, $role, $role_id, $avatar, $status]);
            $userId = $db->lastInsertId();
        } else {
            // Fallback to mysqli
            $stmt = $conn->prepare("INSERT INTO users (firstname, lastname, email, google_id, password, role, avatar, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
            $stmt->bind_param("sssssss", $firstname, $lastname, $email, $googleId, $randomPassword, $role, $avatar);
            $stmt->execute();
            $userId = $stmt->insert_id;
        }

        if ($userId) {
            $_SESSION['user_id'] = $userId;
            $_SESSION['firstname'] = $firstname;
            $_SESSION['lastname'] = $lastname;
            $_SESSION['email'] = $email;
            $_SESSION['role'] = $role;
            $_SESSION['role_id'] = $role_id;

            // Notifications/Emails
            if (class_exists('EmailService')) {
                try {
                    EmailService::sendNotification($email, "Welcome to Campus Dive", "Your account has been created via Google Sign-in.");
                } catch (\Exception $e) { /* ignore email failures */ }
            }

            return ['success' => true, 'new_user' => true];
        }
    }

    return ['success' => false, 'error' => 'Failed to process Google login'];
}