<?php
require_once 'config.php';

// 1. Document Versions (History)
$sql = "CREATE TABLE IF NOT EXISTS document_versions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    document_id INT NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    file_size INT NOT NULL,
    version_num INT NOT NULL DEFAULT 1,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE
)";
if ($conn->query($sql) === TRUE) {
    echo "Table 'document_versions' created successfully.<br>";
} else {
    echo "Error creating table 'document_versions': " . $conn->error . "<br>";
}

// 2. Document Content (OCR Text for Search)
$sql = "CREATE TABLE IF NOT EXISTS document_content (
    id INT AUTO_INCREMENT PRIMARY KEY,
    document_id INT NOT NULL,
    content LONGTEXT, /* Extracted text can be large */
    extracted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE
)";
if ($conn->query($sql) === TRUE) {
    echo "Table 'document_content' created successfully.<br>";
} else {
    echo "Error creating table 'document_content': " . $conn->error . "<br>";
}

// 3. Interview Slots
$sql = "CREATE TABLE IF NOT EXISTS interview_slots (
    id INT AUTO_INCREMENT PRIMARY KEY,
    recruiter_id INT NOT NULL,
    start_time DATETIME NOT NULL,
    end_time DATETIME NOT NULL,
    status ENUM('open', 'booked', 'completed', 'cancelled') DEFAULT 'open',
    booked_by INT DEFAULT NULL, /* Student User ID */
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (recruiter_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (booked_by) REFERENCES users(id) ON DELETE SET NULL
)";
if ($conn->query($sql) === TRUE) {
    echo "Table 'interview_slots' created successfully.<br>";
} else {
    echo "Error creating table 'interview_slots': " . $conn->error . "<br>";
}

// 4. Update Users table for Profile Completion tracking (if not exists)
// We might not need a column, can calculate on fly, but let's add a 'profile_completed_percent' for easy querying if needed.
$sql = "SHOW COLUMNS FROM users LIKE 'profile_score'";
$result = $conn->query($sql);
if ($result->num_rows == 0) {
    $conn->query("ALTER TABLE users ADD COLUMN profile_score INT DEFAULT 0");
    echo "Column 'profile_score' added to users table.<br>";
}

echo "Advanced Schema Setup Completed.";
?>
