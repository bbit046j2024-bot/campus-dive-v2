-- Social Hub Database Migration
-- This script aligns the database with the controllers' expectations.

USE campus_recruitment;

-- 1. Rename posts to group_posts
RENAME TABLE posts TO group_posts;

-- 2. Update group_posts table
ALTER TABLE group_posts 
ADD COLUMN IF NOT EXISTS media_url VARCHAR(255) DEFAULT NULL AFTER content,
ADD COLUMN IF NOT EXISTS media_type ENUM('image', 'video', 'link') DEFAULT NULL AFTER media_url,
ADD COLUMN IF NOT EXISTS status ENUM('pending', 'published', 'rejected') DEFAULT 'published' AFTER media_type,
ADD COLUMN IF NOT EXISTS pinned TINYINT(1) DEFAULT 0 AFTER status,
ADD COLUMN IF NOT EXISTS like_count INT DEFAULT 0 AFTER pinned,
ADD COLUMN IF NOT EXISTS comment_count INT DEFAULT 0 AFTER like_count;

-- 3. Update social_groups table
ALTER TABLE social_groups
ADD COLUMN IF NOT EXISTS slug VARCHAR(100) UNIQUE AFTER name,
ADD COLUMN IF NOT EXISTS status ENUM('active', 'archived') DEFAULT 'active' AFTER description,
ADD COLUMN IF NOT EXISTS is_private TINYINT(1) DEFAULT 0 AFTER cover_image,
ADD COLUMN IF NOT EXISTS post_approval_required TINYINT(1) DEFAULT 0 AFTER is_private;

-- 4. Update group_members table
ALTER TABLE group_members
ADD COLUMN IF NOT EXISTS status ENUM('active', 'pending', 'blocked') DEFAULT 'active' AFTER role;

-- 5. Update post_comments table
ALTER TABLE post_comments
ADD COLUMN IF NOT EXISTS parent_id INT DEFAULT NULL AFTER user_id;

ALTER TABLE post_comments
ADD CONSTRAINT fk_comment_parent FOREIGN KEY (parent_id) REFERENCES post_comments(id) ON DELETE CASCADE;

-- 6. Initialize slugs for existing groups
UPDATE social_groups SET slug = LOWER(REPLACE(name, ' ', '-')) WHERE slug IS NULL;
