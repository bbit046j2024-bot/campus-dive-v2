-- Comprehensive Database Schema Fix for Campus Dive
-- This script ensures all missing columns required for both the old dashboard and the new MVC system are present.

SET FOREIGN_KEY_CHECKS = 0;

DELIMITER //

CREATE PROCEDURE IF NOT EXISTS UpdateDatabaseSchema()
BEGIN
    -- 1. FIX USERS TABLE
    -- Add role_id
    IF NOT EXISTS (SELECT * FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'users' AND column_name = 'role_id') THEN
        ALTER TABLE users ADD COLUMN role_id INT DEFAULT NULL;
    END IF;

    -- Add avatar_image (Path to uploaded avatar)
    IF NOT EXISTS (SELECT * FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'users' AND column_name = 'avatar_image') THEN
        ALTER TABLE users ADD COLUMN avatar_image VARCHAR(255) DEFAULT NULL;
    END IF;

    -- Add verification_token
    IF NOT EXISTS (SELECT * FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'users' AND column_name = 'verification_token') THEN
        ALTER TABLE users ADD COLUMN verification_token VARCHAR(255) DEFAULT NULL;
    END IF;

    -- Add email_verified
    IF NOT EXISTS (SELECT * FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'users' AND column_name = 'email_verified') THEN
        ALTER TABLE users ADD COLUMN email_verified TINYINT(1) DEFAULT 0;
    END IF;

    -- Add reset_token
    IF NOT EXISTS (SELECT * FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'users' AND column_name = 'reset_token') THEN
        ALTER TABLE users ADD COLUMN reset_token VARCHAR(255) DEFAULT NULL;
    END IF;

    -- Add reset_token_expires
    IF NOT EXISTS (SELECT * FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'users' AND column_name = 'reset_token_expires') THEN
        ALTER TABLE users ADD COLUMN reset_token_expires DATETIME DEFAULT NULL;
    END IF;

    -- Add profile_score
    IF NOT EXISTS (SELECT * FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'users' AND column_name = 'profile_score') THEN
        ALTER TABLE users ADD COLUMN profile_score INT DEFAULT 0;
    END IF;

    -- 2. FIX MESSAGES TABLE
    -- Add attachment_path
    IF NOT EXISTS (SELECT * FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'messages' AND column_name = 'attachment_path') THEN
        ALTER TABLE messages ADD COLUMN attachment_path VARCHAR(255) DEFAULT NULL;
    END IF;

    -- Add type
    IF NOT EXISTS (SELECT * FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'messages' AND column_name = 'type') THEN
        ALTER TABLE messages ADD COLUMN type ENUM('text', 'file', 'system') DEFAULT 'text';
    END IF;

    -- 3. FIX DOCUMENTS TABLE
    -- Add document_name
    IF NOT EXISTS (SELECT * FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'documents' AND column_name = 'document_name') THEN
        ALTER TABLE documents ADD COLUMN document_name VARCHAR(100) AFTER user_id;
    END IF;

    -- Add status
    IF NOT EXISTS (SELECT * FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'documents' AND column_name = 'status') THEN
        ALTER TABLE documents ADD COLUMN status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending';
    END IF;
END //

DELIMITER ;

-- Execute the procedure
CALL UpdateDatabaseSchema();
DROP PROCEDURE UpdateDatabaseSchema;

-- 4. FINAL ADJUSTMENTS
-- Update status column in users to match new system ENUMs
ALTER TABLE users MODIFY COLUMN status ENUM('submitted', 'pending', 'documents_uploaded', 'under_review', 'interview_scheduled', 'approved', 'rejected') DEFAULT 'submitted';

-- 5. ENSURE ROLES TABLE & DATA
CREATE TABLE IF NOT EXISTS roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT
);

INSERT IGNORE INTO roles (id, name, description) VALUES 
(1, 'Admin', 'Full system access'),
(2, 'Manager', 'Can manage students and documents'),
(3, 'Interviewer', 'Can conduct interviews and update status'),
(4, 'Student', 'Regular user access');

-- 6. SYNC ROLES
UPDATE users SET role_id = 1 WHERE role = 'admin' AND role_id IS NULL;
UPDATE users SET role_id = 4 WHERE (role = 'user' OR role IS NULL) AND role_id IS NULL;

SET FOREIGN_KEY_CHECKS = 1;
