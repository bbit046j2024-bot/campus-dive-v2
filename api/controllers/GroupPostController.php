<?php
/**
 * Controller for managing Posts within Groups
 */
class GroupPostController {

    /**
     * Get feed for a specific group
     */
    public static function index(): void {
        $user = AuthMiddleware::handle();
        $groupId = (int)($_GET['group_id'] ?? 0);
        $db = Database::getInstance();

        if (!$groupId) {
            Response::error('Group ID is required.');
        }

        $stmt = $db->prepare("
            SELECT p.*, u.firstname, u.lastname, u.avatar_image, g.name as group_name, g.slug as group_slug,
                   (SELECT COUNT(*) FROM post_likes WHERE post_id = p.id) as likes_count,
                   (SELECT COUNT(*) FROM post_comments WHERE post_id = p.id) as comments_count,
                   (SELECT 1 FROM post_likes WHERE post_id = p.id AND user_id = ?) as is_liked
            FROM group_posts p
            JOIN users u ON p.user_id = u.id
            JOIN social_groups g ON p.group_id = g.id
            WHERE p.group_id = ? AND p.status = 'published'
            ORDER BY p.pinned DESC, p.created_at DESC
        ");
        $stmt->execute([$user['id'], $groupId]);
        Response::success($stmt->fetchAll());
    }

    /**
     * Get global feed (posts from all groups the user joined)
     */
    public static function globalFeed(): void {
        $user = AuthMiddleware::handle();
        $db = Database::getInstance();

        $stmt = $db->prepare("
            SELECT p.*, u.firstname, u.lastname, u.avatar_image, g.name as group_name, g.slug as group_slug,
                   (SELECT COUNT(*) FROM post_likes WHERE post_id = p.id) as likes_count,
                   (SELECT COUNT(*) FROM post_comments WHERE post_id = p.id) as comments_count,
                   (SELECT 1 FROM post_likes WHERE post_id = p.id AND user_id = ?) as is_liked
            FROM group_posts p
            JOIN users u ON p.user_id = u.id
            JOIN social_groups g ON p.group_id = g.id
            JOIN group_members gm ON p.group_id = gm.group_id
            WHERE gm.user_id = ? AND gm.status = 'active' AND p.status = 'published'
            ORDER BY p.created_at DESC
            LIMIT 50
        ");
        $stmt->execute([$user['id'], $user['id']]);
        Response::success($stmt->fetchAll());
    }

    /**
     * Get a single post detail
     */
    public static function show(): void {
        $id = (int)($_GET['id'] ?? 0);
        $user = AuthMiddleware::handle();
        $db = Database::getInstance();

        $stmt = $db->prepare("
            SELECT p.*, u.firstname, u.lastname, u.avatar_image, g.name as group_name, g.slug as group_slug,
                   (SELECT COUNT(*) FROM post_likes WHERE post_id = p.id) as likes_count,
                   (SELECT COUNT(*) FROM post_comments WHERE post_id = p.id) as comments_count,
                   (SELECT 1 FROM post_likes WHERE post_id = p.id AND user_id = ?) as is_liked
            FROM group_posts p
            JOIN users u ON p.user_id = u.id
            JOIN social_groups g ON p.group_id = g.id
            WHERE p.id = ?
        ");
        $stmt->execute([$user['id'], $id]);
        $post = $stmt->fetch();

        if (!$post) {
            Response::notFound('Post not found.');
        }

        Response::success($post);
    }

    /**
     * Create a new post
     */
    public static function store(): void {
        $user = AuthMiddleware::handle();
        $input = json_decode(file_get_contents('php://input'), true);
        $db = Database::getInstance();

        $groupId = (int)($input['group_id'] ?? 0);
        $content = trim($input['content'] ?? '');
        $mediaUrl = $input['media_url'] ?? null;

        if (empty($content)) {
            Response::error('Content is required.');
        }

        // Check if user is a member
        $stmt = $db->prepare("SELECT role FROM group_members WHERE group_id = ? AND user_id = ? AND status = 'active'");
        $stmt->execute([$groupId, $user['id']]);
        $membership = $stmt->fetch();

        if (!$membership) {
            Response::forbidden('Only members can post.');
        }

        // Check group settings for post approval
        $stmt = $db->prepare("SELECT post_approval_required FROM social_groups WHERE id = ?");
        $stmt->execute([$groupId]);
        $group = $stmt->fetch();

        $status = ($group['post_approval_required'] && $membership['role'] === 'member') ? 'pending' : 'published';

        $mediaType = null;
        if ($mediaUrl) {
            $validation = UrlMediaService::validate($mediaUrl);
            if (!$validation['valid']) {
                Response::error($validation['error'], 422);
            }
            $mediaType = $validation['type'];
        }

        $stmt = $db->prepare("INSERT INTO group_posts (group_id, user_id, content, media_url, media_type, status) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$groupId, $user['id'], $content, $mediaUrl, $mediaType, $status]);

        Response::success(['status' => $status], $status === 'published' ? 'Post created!' : 'Post submitted for approval.');
    }

    /**
     * Like/Unlike a post
     */
    public static function toggleLike(): void {
        $user = AuthMiddleware::handle();
        $input = json_decode(file_get_contents('php://input'), true);
        $postId = (int)($input['post_id'] ?? 0);
        
        $db = Database::getInstance();

        if (!$postId) {
            Response::error('Post ID is required.');
        }

        $stmt = $db->prepare("SELECT id FROM post_likes WHERE post_id = ? AND user_id = ?");
        $stmt->execute([$postId, $user['id']]);
        $like = $stmt->fetch();

        if ($like) {
            $stmt = $db->prepare("DELETE FROM post_likes WHERE id = ?");
            $stmt->execute([$like['id']]);
            $liked = false;
        } else {
            $stmt = $db->prepare("INSERT INTO post_likes (post_id, user_id) VALUES (?, ?)");
            $stmt->execute([$postId, $user['id']]);
            $liked = true;
        }

        // Update post like count denormalized
        $db->prepare("UPDATE group_posts SET like_count = (SELECT COUNT(*) FROM post_likes WHERE post_id = ?) WHERE id = ?")
           ->execute([$postId, $postId]);

        Response::success(['is_liked' => $liked]);
    }

    /**
     * Add a comment
     */
    public static function comment(): void {
        $user = AuthMiddleware::handle();
        $input = json_decode(file_get_contents('php://input'), true);
        $db = Database::getInstance();

        $postId = (int)($input['post_id'] ?? 0);
        $content = trim($input['content'] ?? '');
        $parentId = $input['parent_id'] ?? null;

        if (empty($content)) {
            Response::error('Comment cannot be empty.');
        }

        $stmt = $db->prepare("INSERT INTO post_comments (post_id, user_id, content, parent_id) VALUES (?, ?, ?, ?)");
        $stmt->execute([$postId, $user['id'], $content, $parentId]);

        // Update comment count denormalized
        $db->prepare("UPDATE group_posts SET comment_count = (SELECT COUNT(*) FROM post_comments WHERE post_id = ?) WHERE id = ?")
           ->execute([$postId, $postId]);

        Response::success(null, 'Comment added.');
    }

    /**
     * Get comments for a post
     */
    public static function getComments(): void {
        AuthMiddleware::handle();
        $postId = (int)($_GET['post_id'] ?? 0);
        $db = Database::getInstance();

        if (!$postId) {
            Response::error('Post ID is required.');
        }

        $stmt = $db->prepare("
            SELECT c.*, u.firstname, u.lastname, u.avatar_image
            FROM post_comments c
            JOIN users u ON c.user_id = u.id
            WHERE c.post_id = ?
            ORDER BY c.created_at ASC
        ");
        $stmt->execute([$postId]);
        Response::success($stmt->fetchAll());
    }
}
