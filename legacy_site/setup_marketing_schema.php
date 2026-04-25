<?php
require_once 'config.php';

// 1. Marketing Templates
$sql = "CREATE TABLE IF NOT EXISTS marketing_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    subject VARCHAR(255) DEFAULT NULL,
    body_content TEXT NOT NULL, /* Stores HTML for email or Text for SMS */
    type ENUM('email', 'sms') NOT NULL DEFAULT 'email',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";
if ($conn->query($sql) === TRUE) {
    echo "Table 'marketing_templates' created successfully.<br>";
} else {
    echo "Error creating table 'marketing_templates': " . $conn->error . "<br>";
}

// 2. Marketing Campaigns
$sql = "CREATE TABLE IF NOT EXISTS marketing_campaigns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    template_id INT NOT NULL,
    type ENUM('email', 'sms') NOT NULL DEFAULT 'email',
    segment_criteria JSON DEFAULT NULL, /* Stores filters like {'status': 'approved', 'role': 'student'} */
    scheduled_at TIMESTAMP NULL DEFAULT NULL,
    status ENUM('draft', 'scheduled', 'processing', 'completed', 'cancelled') DEFAULT 'draft',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (template_id) REFERENCES marketing_templates(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
)";
if ($conn->query($sql) === TRUE) {
    echo "Table 'marketing_campaigns' created successfully.<br>";
} else {
    echo "Error creating table 'marketing_campaigns': " . $conn->error . "<br>";
}

// 3. Marketing Queue
$sql = "CREATE TABLE IF NOT EXISTS marketing_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT NOT NULL,
    user_id INT NOT NULL,
    recipient_contact VARCHAR(255) NOT NULL, /* Email or Phone */
    message_content TEXT NOT NULL, /* Personalized content */
    status ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
    sent_at TIMESTAMP NULL DEFAULT NULL,
    error_message TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (campaign_id) REFERENCES marketing_campaigns(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";
if ($conn->query($sql) === TRUE) {
    echo "Table 'marketing_queue' created successfully.<br>";
} else {
    echo "Error creating table 'marketing_queue': " . $conn->error . "<br>";
}

// 4. Marketing Logs (Tracking)
$sql = "CREATE TABLE IF NOT EXISTS marketing_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    queue_id INT NOT NULL,
    event_type ENUM('open', 'click') NOT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (queue_id) REFERENCES marketing_queue(id) ON DELETE CASCADE
)";
if ($conn->query($sql) === TRUE) {
    echo "Table 'marketing_logs' created successfully.<br>";
} else {
    echo "Error creating table 'marketing_logs': " . $conn->error . "<br>";
}

// Seed a Default Welcome Template
$seed_sql = "INSERT INTO marketing_templates (name, subject, body_content, type) 
             SELECT 'Welcome Email', 'Welcome to Campus Dive!', '<p>Hi {{firstname}}, thanks for joining us!</p>', 'email'
             WHERE NOT EXISTS (SELECT 1 FROM marketing_templates WHERE name = 'Welcome Email')";
$conn->query($seed_sql);

echo "Marketing Schema Setup Completed.";
?>
