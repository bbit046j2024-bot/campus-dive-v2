-- Performance Optimization Indexes
-- This script adds indexes to frequently queried columns to speed up dashboard and listing pages.

SET @database = DATABASE();

DELIMITER //

CREATE PROCEDURE IF NOT EXISTS AddIndex(
    IN tableName VARCHAR(64),
    IN indexName VARCHAR(64),
    IN columnList VARCHAR(255)
)
BEGIN
    IF NOT EXISTS (
        SELECT * FROM information_schema.statistics 
        WHERE table_schema = @database 
        AND table_name = tableName 
        AND index_name = indexName
    ) THEN
        SET @sql = CONCAT('CREATE INDEX ', indexName, ' ON ', tableName, '(', columnList, ')');
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END //

DELIMITER ;

-- Users table indexes
CALL AddIndex('users', 'idx_users_role_id', 'role_id');
CALL AddIndex('users', 'idx_users_role', 'role');
CALL AddIndex('users', 'idx_users_status', 'status');
CALL AddIndex('users', 'idx_users_created_at', 'created_at');

-- Messages table indexes
CALL AddIndex('messages', 'idx_messages_receiver_read', 'receiver_id, is_read');
CALL AddIndex('messages', 'idx_messages_sender_id', 'sender_id');
CALL AddIndex('messages', 'idx_messages_created_at', 'created_at');

-- Notifications table indexes
CALL AddIndex('notifications', 'idx_notifications_user_read', 'user_id, is_read');
CALL AddIndex('notifications', 'idx_notifications_created_at', 'created_at');

DROP PROCEDURE IF EXISTS AddIndex;
