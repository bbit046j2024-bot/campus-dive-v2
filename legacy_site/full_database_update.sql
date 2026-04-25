-- 1. Disable Foreign Key Checks to prevent errors during creation
SET FOREIGN_KEY_CHECKS = 0;

-- 2. CREATE ROLES AND PERMISSIONS
CREATE TABLE IF NOT EXISTS roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT
);

CREATE TABLE IF NOT EXISTS permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    category VARCHAR(50) DEFAULT 'general'
);

CREATE TABLE IF NOT EXISTS role_permissions (
    role_id INT NOT NULL,
    permission_id INT NOT NULL,
    PRIMARY KEY (role_id, permission_id),
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
);

-- 3. UPDATE USERS TABLE (Safe addition of columns)
SET @exist := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'users' AND column_name = 'role_id');
SET @sql := IF(@exist = 0, 'ALTER TABLE users ADD COLUMN role_id INT DEFAULT NULL', 'SELECT "Column role_id already exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'users' AND column_name = 'profile_score');
SET @sql := IF(@exist = 0, 'ALTER TABLE users ADD COLUMN profile_score INT DEFAULT 0', 'SELECT "Column profile_score already exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

ALTER TABLE users MODIFY COLUMN status ENUM('submitted', 'pending', 'documents_uploaded', 'under_review', 'interview_scheduled', 'approved', 'rejected') DEFAULT 'submitted';

-- 4. UPDATE MESSAGES TABLE
SET @exist := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'messages' AND column_name = 'attachment_path');
SET @sql := IF(@exist = 0, 'ALTER TABLE messages ADD COLUMN attachment_path VARCHAR(255) DEFAULT NULL', 'SELECT "Column attachment_path already exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'messages' AND column_name = 'type');
SET @sql := IF(@exist = 0, 'ALTER TABLE messages ADD COLUMN type ENUM("text", "file", "system") DEFAULT "text"', 'SELECT "Column type already exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Ensure receiver_id exists (Fixing legacy recipient_id references if any)
SET @exist := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'messages' AND column_name = 'receiver_id');
SET @sql := IF(@exist = 0, 'ALTER TABLE messages ADD COLUMN receiver_id INT DEFAULT NULL AFTER sender_id', 'SELECT "Column receiver_id already exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 5. ANALYTICS & LOGS
CREATE TABLE IF NOT EXISTS analytics_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS application_stages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    stage_name VARCHAR(50) NOT NULL,
    entered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    exited_at TIMESTAMP NULL DEFAULT NULL,
    duration_seconds INT DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 6. MARKETING TABLES
CREATE TABLE IF NOT EXISTS marketing_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    subject VARCHAR(255) DEFAULT NULL,
    body_content TEXT NOT NULL,
    type ENUM('email', 'sms') NOT NULL DEFAULT 'email',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS marketing_campaigns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    template_id INT NOT NULL,
    type ENUM('email', 'sms') NOT NULL DEFAULT 'email',
    segment_criteria JSON DEFAULT NULL,
    scheduled_at TIMESTAMP NULL DEFAULT NULL,
    status ENUM('draft', 'scheduled', 'processing', 'completed', 'cancelled') DEFAULT 'draft',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (template_id) REFERENCES marketing_templates(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS marketing_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT NOT NULL,
    user_id INT NOT NULL,
    recipient_contact VARCHAR(255) NOT NULL,
    message_content TEXT NOT NULL,
    status ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
    sent_at TIMESTAMP NULL DEFAULT NULL,
    error_message TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (campaign_id) REFERENCES marketing_campaigns(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS marketing_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    queue_id INT NOT NULL,
    event_type ENUM('open', 'click') NOT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 7. ADVANCED DOCUMENTS
-- Update Documents table
SET @exist := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'documents' AND column_name = 'document_name');
SET @sql := IF(@exist = 0, 'ALTER TABLE documents ADD COLUMN document_name VARCHAR(100) AFTER user_id', 'SELECT "Column document_name already exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'documents' AND column_name = 'status');
SET @sql := IF(@exist = 0, 'ALTER TABLE documents ADD COLUMN status ENUM("pending", "approved", "rejected") DEFAULT "pending"', 'SELECT "Column status already exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

CREATE TABLE IF NOT EXISTS document_versions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    document_id INT NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    file_size INT NOT NULL,
    version_num INT NOT NULL DEFAULT 1,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS document_content (
    id INT AUTO_INCREMENT PRIMARY KEY,
    document_id INT NOT NULL,
    content LONGTEXT,
    extracted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE
);

-- 8. INTERVIEW SLOTS
CREATE TABLE IF NOT EXISTS interview_slots (
    id INT AUTO_INCREMENT PRIMARY KEY,
    recruiter_id INT NOT NULL,
    start_time DATETIME NOT NULL,
    end_time DATETIME NOT NULL,
    status ENUM('open', 'booked', 'completed', 'cancelled') DEFAULT 'open',
    booked_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (recruiter_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (booked_by) REFERENCES users(id) ON DELETE SET NULL
);

-- 9. SEED ROLES (Idempotent)
INSERT IGNORE INTO roles (name, description) VALUES 
('Admin', 'Full system access'),
('Manager', 'Can manage students and documents'),
('Interviewer', 'Can conduct interviews and update status'),
('Student', 'Regular user access');

-- 10. SEED PERMISSIONS (Idempotent)
INSERT IGNORE INTO permissions (name, slug, category) VALUES
('View Students', 'view_students', 'students'),
('Approve Applications', 'approve_applications', 'students'),
('Reject Applications', 'reject_applications', 'students'),
('Delete Students', 'delete_students', 'students'),
('Move to Review', 'move_review', 'workflow'),
('Schedule Interview', 'schedule_interview', 'workflow'),
('Send Messages', 'send_messages', 'communication'),
('Manage Settings', 'manage_settings', 'system'),
('Manage Roles', 'manage_roles', 'system'),
('View Analytics', 'view_analytics', 'system');

-- Enable Checks
SET FOREIGN_KEY_CHECKS = 1;
