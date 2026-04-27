<?php
/**
 * Campus Dive V2 - Production Entry Point
 * v2.1.0 - Stability Force Refresh
 */

$request = $_SERVER['REQUEST_URI'];
$api_prefix = '/api';

// Route to API if the URL starts with /api
if (strpos($request, $api_prefix) === 0 || strpos($request, '/Campus-Dive/api') === 0 || strpos($request, '/Campus-Dive-main/api') === 0) {
    require_once __DIR__ . '/api/index.php';
    exit;
}

// Otherwise, serve the Frontend
require_once __DIR__ . '/index.html';
