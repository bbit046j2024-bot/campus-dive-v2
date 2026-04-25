<?php
/**
 * Campus Dive - Smart Router for Railway
 * Handles both the Backend UI and the API Routing
 */

// 1. Load CORS & Security Headers
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
// Allow localhost and any .vercel.app domain
if (str_ends_with($origin, '.vercel.app') || str_contains($origin, 'localhost')) {
    header("Access-Control-Allow-Origin: $origin");
} else {
    header('Access-Control-Allow-Origin: ' . (getenv('FRONTEND_URL') ?: '*'));
}

header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-CSRF-Token');

// Handle Preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 2. Route the Request
$requestUri = $_SERVER['REQUEST_URI'];
$path = parse_url($requestUri, PHP_URL_PATH);

// If it's an API call or a legacy file, hand it off to the API logic
if (str_starts_with($path, '/api') || $path === '/install.php') {
    // If it's exactly /install.php, we let the file exist
    if (file_exists(__DIR__ . $path) && is_file(__DIR__ . $path)) {
        require_once __DIR__ . $path;
        exit;
    }
    
    // Otherwise, everything else goes to the API controller
    require_once __DIR__ . '/api/index.php';
    exit;
}

// 3. Otherwise, show the Backend Dashboard UI
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campus Dive | Backend Mainframe</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-950 text-slate-200 min-h-screen flex items-center justify-center p-6">
    <div class="max-w-md w-full bg-slate-900 border border-slate-800 rounded-3xl p-8 shadow-2xl text-center">
        <div class="w-20 h-20 bg-indigo-600 rounded-2xl mx-auto mb-6 flex items-center justify-center shadow-lg shadow-indigo-500/20">
            <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
        </div>
        
        <h1 class="text-2xl font-black text-white mb-2 uppercase tracking-tight">Backend Mainframe</h1>
        <p class="text-slate-400 text-sm mb-8">The Campus Dive API engine is active and awaiting instructions.</p>
        
        <div class="space-y-3">
            <a href="/install.php" class="block w-full py-4 px-6 bg-indigo-600 hover:bg-indigo-500 text-white rounded-2xl font-bold text-sm transition-all shadow-lg shadow-indigo-500/20">
                INITIALIZE DATABASE
            </a>
            <p class="text-[10px] text-slate-500 font-bold uppercase tracking-widest pt-4">Status: Operational</p>
        </div>
    </div>
</body>
</html>
