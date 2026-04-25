-- ============================================================
-- Campus Dive - Complete Localhost Setup Script
-- Run this ONCE in phpMyAdmin or MySQL CLI to set up the DB.
-- ============================================================

-- 1. Create & Select Database
CREATE DATABASE IF NOT EXISTS campus_recruitment CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE campus_recruitment;

-- 2. Disable FK checks during setup
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- CORE TABLES
-- ============================================================

-- Roles
DROP TABLE IF EXISTS roles;
CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT
);

-- Permissions
DROP TABLE IF EXISTS permissions;
CREATE TABLE permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    category VARCHAR(50) DEFAULT 'general'
);

-- Role <-> Permission mapping
CREATE TABLE IF NOT EXISTS role_permissions (
    role_id INT NOT NULL,
    permission_id INT NOT NULL,
    PRIMARY KEY (role_id, permission_id),
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
);

-- Users (Students, Admins, Managers, Interviewers)
DROP TABLE IF EXISTS users;
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    firstname VARCHAR(100) NOT NULL,
    lastname VARCHAR(100) NOT NULL,
    email VARCHAR(191) UNIQUE NOT NULL,
    google_id VARCHAR(191) UNIQUE DEFAULT NULL,
    phone VARCHAR(20) NOT NULL,
    student_id VARCHAR(50) DEFAULT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    role_id INT DEFAULT NULL,
    avatar VARCHAR(10) DEFAULT NULL,
    avatar_image VARCHAR(255) DEFAULT NULL,
    status ENUM('submitted', 'pending', 'documents_uploaded', 'under_review', 'interview_scheduled', 'approved', 'rejected') DEFAULT 'submitted',
    recruitment_letter TEXT DEFAULT NULL,
    verification_token VARCHAR(255) DEFAULT NULL,
    email_verified TINYINT(1) DEFAULT 0,
    reset_token VARCHAR(255) DEFAULT NULL,
    reset_token_expires DATETIME DEFAULT NULL,
    profile_score INT DEFAULT 0,
    bio TEXT DEFAULT NULL,
    location VARCHAR(100) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Documents
DROP TABLE IF EXISTS documents;
CREATE TABLE documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    document_name VARCHAR(100) DEFAULT NULL,
    filename VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    file_type VARCHAR(50) NOT NULL,
    file_size INT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Messages
DROP TABLE IF EXISTS messages;
CREATE TABLE messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    attachment_path VARCHAR(255) DEFAULT NULL,
    type ENUM('text', 'file', 'system') DEFAULT 'text',
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Notifications
DROP TABLE IF EXISTS notifications;
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type VARCHAR(50) DEFAULT 'info',
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Recruitment Letters
DROP TABLE IF EXISTS recruitment_letters;
CREATE TABLE recruitment_letters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    letter_content TEXT NOT NULL,
    sent_by INT NOT NULL,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (sent_by) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================================
-- ANALYTICS & AUDIT
-- ============================================================

-- ANALYTICS & AUDIT
DROP TABLE IF EXISTS analytics_logs;
CREATE TABLE analytics_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    action VARCHAR(100) NOT NULL,
    details TEXT DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

DROP TABLE IF EXISTS application_stages;
CREATE TABLE application_stages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    stage_name VARCHAR(50) NOT NULL,
    entered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    exited_at TIMESTAMP NULL DEFAULT NULL,
    duration_seconds INT DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================================
-- MARKETING / CAMPAIGNS
-- ============================================================

CREATE TABLE IF NOT EXISTS marketing_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    subject VARCHAR(255) DEFAULT NULL,
    body_content TEXT NOT NULL,
    type ENUM('email', 'sms') NOT NULL DEFAULT 'email',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

DROP TABLE IF EXISTS marketing_campaigns;
CREATE TABLE marketing_campaigns (
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

DROP TABLE IF EXISTS marketing_queue;
CREATE TABLE marketing_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT NOT NULL,
    user_id INT NOT NULL,
    recipient_contact VARCHAR(255) NOT NULL,
    message_content TEXT NOT NULL,
    subject VARCHAR(255) DEFAULT NULL,
    status ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
    sent_at TIMESTAMP NULL DEFAULT NULL,
    error_message TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (campaign_id) REFERENCES marketing_campaigns(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Marketing Logs
DROP TABLE IF EXISTS marketing_logs;
CREATE TABLE marketing_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    queue_id INT NOT NULL,
    event_type ENUM('open', 'click') NOT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- DOCUMENTS (ADVANCED)
-- ============================================================

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
    content LONGTEXT DEFAULT NULL,
    extracted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE
);

-- ============================================================
-- INTERVIEWS
-- ============================================================

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

-- ============================================================
-- SOCIAL HUB (Groups & Posts)
-- ============================================================

CREATE TABLE IF NOT EXISTS social_groups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT DEFAULT NULL,
    category VARCHAR(50) DEFAULT 'General',
    avatar_url VARCHAR(255) DEFAULT NULL,
    cover_image VARCHAR(255) DEFAULT NULL,
    is_public TINYINT(1) DEFAULT 1,
    is_private TINYINT(1) DEFAULT 0,
    manager_id INT DEFAULT NULL,
    status ENUM('active', 'archived', 'pending') DEFAULT 'active',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (manager_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS group_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    group_id INT NOT NULL,
    user_id INT NOT NULL,
    role ENUM('member', 'moderator', 'admin') DEFAULT 'member',
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_membership (group_id, user_id),
    FOREIGN KEY (group_id) REFERENCES social_groups(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    group_id INT DEFAULT NULL,
    content TEXT NOT NULL,
    image_path VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (group_id) REFERENCES social_groups(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS post_likes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_like (post_id, user_id),
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS post_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================================
-- SEED DATA
-- ============================================================

-- Roles
INSERT IGNORE INTO roles (id, name, description) VALUES
(1, 'Admin', 'Full system access'),
(2, 'Manager', 'Can manage students and documents'),
(3, 'Interviewer', 'Can conduct interviews and update status'),
(4, 'Student', 'Regular user access');

-- Permissions
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

-- Default Admin Account
-- Login: admin@campusdive.com / Password: admin123
INSERT INTO users (firstname, lastname, email, phone, password, role, role_id, avatar, status, email_verified)
VALUES ('Admin', 'User', 'admin@campusdive.com', '+254700000000',
        '$2y$10$AAJa1cJ0Pln7N3kXIMPz0Ov0/f9/VFv4vM21jkX6uAob5taiWJVKW',
        'admin', 1, 'AU', 'approved', 1)
ON DUPLICATE KEY UPDATE role_id = 1, email_verified = 1, status = 'approved';

-- Re-enable FK checks
SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- DONE. Open http://localhost/Campus-Dive/Campus-Dive-main/
-- Login: admin@campusdive.com | Password: admin123
-- ============================================================
