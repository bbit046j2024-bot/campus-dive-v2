<?php
// Load local .env file if it exists
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, '#') === 0 || strpos($line, '=') === false) continue;
        list($name, $value) = explode('=', $line, 2);
        putenv(trim($name) . "=" . trim($value));
        $_ENV[trim($name)] = trim($value);
    }
}

require_once __DIR__ . '/config/database.php';
putenv("APP_DEBUG=true"); // Enable debug for migration output

try {
    $db = Database::getInstance();
    
    // 1. Social Groups Table
    $db->exec("CREATE TABLE IF NOT EXISTS social_groups (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        slug VARCHAR(120) NOT NULL UNIQUE,
        description TEXT,
        category VARCHAR(60),
        cover_color VARCHAR(30) DEFAULT '#6366f1',
        cover_color_end VARCHAR(30) DEFAULT '#8b5cf6',
        icon_initials VARCHAR(5),
        icon_bg_color VARCHAR(30) DEFAULT '#6366f1',
        avatar_url VARCHAR(2048) DEFAULT NULL,
        banner_url VARCHAR(2048) DEFAULT NULL,
        is_private TINYINT(1) DEFAULT 0,
        allow_member_posts TINYINT(1) DEFAULT 1,
        allow_member_comments TINYINT(1) DEFAULT 1,
        allow_member_dm TINYINT(1) DEFAULT 1,
        post_approval_required TINYINT(1) DEFAULT 0,
        welcome_message TEXT DEFAULT NULL,
        rules TEXT DEFAULT NULL,
        created_by INT NOT NULL,
        manager_id INT DEFAULT NULL,
        status ENUM('active', 'archived') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (manager_id) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB;");

    // 2. Group Members Table
    $db->exec("CREATE TABLE IF NOT EXISTS group_members (
        id INT AUTO_INCREMENT PRIMARY KEY,
        group_id INT NOT NULL,
        user_id INT NOT NULL,
        role ENUM('member', 'moderator', 'manager') DEFAULT 'member',
        joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        status ENUM('active', 'banned', 'pending') DEFAULT 'active',
        UNIQUE KEY unique_membership (group_id, user_id),
        FOREIGN KEY (group_id) REFERENCES social_groups(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB;");

    // 3. Group Posts Table
    $db->exec("CREATE TABLE IF NOT EXISTS group_posts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        group_id INT NOT NULL,
        user_id INT NOT NULL,
        content TEXT NOT NULL,
        media_url VARCHAR(2048) DEFAULT NULL,
        media_type ENUM('image', 'video', 'unknown') DEFAULT NULL,
        status ENUM('published', 'pending', 'rejected') DEFAULT 'published',
        pinned TINYINT(1) DEFAULT 0,
        like_count INT DEFAULT 0,
        comment_count INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (group_id) REFERENCES social_groups(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB;");

    // 4. Post Likes Table
    $db->exec("CREATE TABLE IF NOT EXISTS post_likes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        post_id INT NOT NULL,
        user_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_like (post_id, user_id),
        FOREIGN KEY (post_id) REFERENCES group_posts(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB;");

    // 5. Post Comments Table
    $db->exec("CREATE TABLE IF NOT EXISTS post_comments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        post_id INT NOT NULL,
        user_id INT NOT NULL,
        content TEXT NOT NULL,
        parent_id INT DEFAULT NULL,
        like_count INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (post_id) REFERENCES group_posts(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (parent_id) REFERENCES post_comments(id) ON DELETE CASCADE
    ) ENGINE=InnoDB;");

    // 6. Group Messages Table
    $db->exec("CREATE TABLE IF NOT EXISTS group_messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        group_id INT NOT NULL,
        sender_id INT NOT NULL,
        content TEXT NOT NULL,
        media_url VARCHAR(2048) DEFAULT NULL,
        type ENUM('text', 'file', 'system', 'announcement') DEFAULT 'text',
        is_announcement TINYINT(1) DEFAULT 0,
        pinned TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (group_id) REFERENCES social_groups(id) ON DELETE CASCADE,
        FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB;");

    // 7. Group Message Reads (track who read what)
    $db->exec("CREATE TABLE IF NOT EXISTS group_message_reads (
        group_id INT NOT NULL,
        user_id INT NOT NULL,
        last_read_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (group_id, user_id),
        FOREIGN KEY (group_id) REFERENCES social_groups(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB;");

    // 8. Add group_context_id to messages
    try {
        $db->exec("ALTER TABLE messages ADD COLUMN group_context_id INT DEFAULT NULL AFTER is_read");
        $db->exec("ALTER TABLE messages ADD FOREIGN KEY (group_context_id) REFERENCES social_groups(id) ON DELETE SET NULL");
    } catch (Exception $e) {
        // Likely column already exists
    }

    // 9. Add avatar_url to social_groups if missing
    try {
        $db->exec("ALTER TABLE social_groups ADD COLUMN IF NOT EXISTS avatar_url VARCHAR(2048) DEFAULT NULL AFTER icon_bg_color");
    } catch (Exception $e) {
        // Fallback for older MySQL versions that don't support ADD COLUMN IF NOT EXISTS
        try {
            $db->exec("ALTER TABLE social_groups ADD COLUMN avatar_url VARCHAR(2048) DEFAULT NULL AFTER icon_bg_color");
        } catch (Exception $e2) {
            // Already exists or other error
        }
    }

    // 10. Insert Roles
    $db->exec("INSERT IGNORE INTO roles (name, description) VALUES ('GroupManager', 'Manages a specific student community group')");

    // 10. Insert Permissions
    $permissions = [
        ['View Group', 'group.view', 'groups'],
        ['Post to Group', 'group.post', 'groups'],
        ['Manage Group Settings', 'group.settings', 'groups'],
        ['Manage Group Members', 'group.members.manage', 'groups'],
        ['Pin Messages', 'group.messages.pin', 'groups'],
        ['Delete Member Posts', 'group.posts.delete', 'groups'],
        ['Approve Pending Posts', 'group.posts.approve', 'groups'],
        ['Send Announcements', 'group.announcements.send', 'groups'],
        ['Ban Members', 'group.members.ban', 'groups'],
        ['Customize Group Appearance', 'group.appearance', 'groups']
    ];

    $stmt = $db->prepare("INSERT IGNORE INTO permissions (name, slug, category) VALUES (?, ?, ?)");
    foreach ($permissions as $p) {
        $stmt->execute($p);
    }

    echo "Migration completed successfully!\n";

} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
