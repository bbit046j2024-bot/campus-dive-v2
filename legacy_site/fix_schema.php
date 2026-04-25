<?php
require_once 'config.php';

echo "Altering Documents Table...\n";

$sql = "ALTER TABLE documents 
        ADD COLUMN IF NOT EXISTS document_name VARCHAR(100) AFTER user_id,
        ADD COLUMN IF NOT EXISTS status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        ADD COLUMN IF NOT EXISTS version INT DEFAULT 1";

if ($conn->query($sql) === TRUE) {
    echo "Documents table updated successfully.\n";
} else {
    echo "Error updating table: " . $conn->error . "\n";
}
?>
