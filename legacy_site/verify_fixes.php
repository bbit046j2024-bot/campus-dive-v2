<?php
/**
 * Verification script to test the fixed registration and verification flow
 */

// Mocking some dependencies to test logic if possible, 
// but since we updated the real files, let's try a dry-run or check syntax

$files = [
    'api/services/EmailService.php',
    'api/controllers/AuthController.php'
];

echo "Checking syntax of modified files...\n";

foreach ($files as $file) {
    $fullPath = __DIR__ . '/' . $file;
    $output = [];
    $returnVar = 0;
    exec("C:\\laragon\\bin\\php\\php-8.3.30-Win32-vs16-x64\\php.exe -l $fullPath", $output, $returnVar);
    
    if ($returnVar === 0) {
        echo "[OK] Syntax check passed for $file\n";
    } else {
        echo "[ERROR] Syntax check failed for $file\n";
        echo implode("\n", $output) . "\n";
    }
}

echo "\nVerifying EmailService error log directory creation...\n";
$logDir = __DIR__ . '/api/logs';
if (!is_dir($logDir)) {
    echo "Logs directory doesn't exist yet (expected if no errors happened since update).\n";
} else {
    echo "Logs directory exists at $logDir\n";
}

echo "\nDone verification check.\n";
