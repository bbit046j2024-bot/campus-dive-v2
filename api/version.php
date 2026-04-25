<?php
// Simple script to verify which code is running
echo json_encode([
    'status' => 'online',
    'timestamp' => '2026-03-06 18:50 (Debug Fix Applied)',
    'php_version' => PHP_VERSION,
    'app_debug' => getenv('APP_DEBUG'),
    'last_commit_included' => true
]);
