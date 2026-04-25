<?php
require_once 'config.php';
require_once 'google_config.php';

echo "<h2>Google Auth Verification</h2>";

// 1. Check database column
$check_column = $conn->query("SHOW COLUMNS FROM users LIKE 'google_id'");
if ($check_column->num_rows > 0) {
    echo "✅ Database: 'google_id' column exists.<br>";
} else {
    echo "❌ Database: 'google_id' column is MISSING in the live database. Run: <code>ALTER TABLE users ADD COLUMN google_id VARCHAR(255) UNIQUE AFTER email;</code><br>";
}

// 2. Check composer dependency
if (file_exists('vendor/google/apiclient')) {
    echo "✅ Vendor: Google API Client library found.<br>";
} else {
    echo "❌ Vendor: Google API Client library NOT found. Run <code>composer install</code> from a terminal with PHP installed.<br>";
}

// 3. Check configuration
if (GOOGLE_CLIENT_ID === 'your-google-client-id.apps.googleusercontent.com' || GOOGLE_CLIENT_SECRET === 'your-google-client-secret') {
    echo "⚠️ Config: You are still using placeholder credentials in <code>google_config.php</code>.<br>";
} else {
    echo "✅ Config: Custom credentials detected.<br>";
}

// 4. Check Redirect URI
echo "ℹ️ Redirect URI is set to: <code>" . GOOGLE_REDIRECT_URI . "</code><br>";
echo "Ensure this matches your Google Cloud Console settings.<br>";
?>
