<?php
/**
 * Application Configuration
 */

// App
define('APP_NAME', 'Campus Dive');
define('APP_URL', getenv('APP_URL') ?: 'https://campus-dive-production.up.railway.app');
define('APP_DEBUG', (getenv('APP_DEBUG') === 'true' || getenv('APP_DEBUG') === '1') ? true : false);

// Session
define('SESSION_LIFETIME', 7200); // 2 hours
define('SESSION_NAME', 'campus_dive_session');

// CSRF
define('CSRF_TOKEN_NAME', 'csrf_token');

// Uploads
// Uploads (Fallback to local api/uploads if root uploads doesn't exist)
// Try local vendor first (for Railway), then fallback to root (for Local Development)
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
} else {
    require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
}
$rootUploads = dirname(__DIR__, 2) . '/uploads/';
$localUploads = dirname(__DIR__, 1) . '/uploads/';
define('UPLOAD_DIR', is_dir($rootUploads) ? $rootUploads : $localUploads);
define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_DOC_TYPES', ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'image/jpeg', 'image/png']);
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('AVATAR_MAX_SIZE', 2 * 1024 * 1024); // 2MB

// Email (Uses getenv for Railway/Production, fallbacks for Local)
define('MAIL_HOST',          getenv('MAIL_HOST')          ?: 'smtp.gmail.com');
define('MAIL_PORT',          (int)(getenv('MAIL_PORT')    ?: 465));
define('MAIL_ENCRYPTION',    getenv('MAIL_ENCRYPTION')    ?: 'ssl');
define('MAIL_USERNAME',      getenv('MAIL_USERNAME')      ?: ''); 
define('MAIL_PASSWORD',      getenv('MAIL_PASSWORD') ?: getenv('RESEND_API_KEY') ?: ''); 

// Resend.com compatibility: 
// If using Resend (password starts with re_), we must use a verified domain.
// Fallback to onboarding@resend.dev if the from address looks like a personal email (gmail/outlook etc)
$rawFrom = getenv('MAIL_FROM_ADDRESS') ?: 'campusdive.org@gmail.com';
if (str_starts_with(MAIL_PASSWORD, 're_') && (str_contains($rawFrom, 'gmail.com') || str_contains($rawFrom, 'outlook.com'))) {
    $rawFrom = 'onboarding@resend.dev';
}
define('MAIL_FROM_ADDRESS',  $rawFrom);
define('MAIL_FROM_NAME',     getenv('MAIL_FROM_NAME')     ?: 'Campus Dive');

// CORS (Split by comma for multiple origins)
$defaultOrigins = 'http://localhost:5173,https://campus-dive.vercel.app';
define('CORS_ORIGIN', getenv('CORS_ORIGIN') ?: $defaultOrigins);

// Roles
define('ROLE_ADMIN', 1);
define('ROLE_MANAGER', 2);
define('ROLE_INTERVIEWER', 3);
define('ROLE_STUDENT', 4);

// Application Statuses
define('STATUS_SUBMITTED', 'submitted');
define('STATUS_PENDING', 'pending');
define('STATUS_DOCS_UPLOADED', 'documents_uploaded');
define('STATUS_UNDER_REVIEW', 'under_review');
define('STATUS_INTERVIEW_SCHEDULED', 'interview_scheduled');
define('STATUS_APPROVED', 'approved');
define('STATUS_REJECTED', 'rejected');
