<?php
/**
 * Social Controller for shared/general social features
 */
class SocialController {

    public static function validateUrl(): void {
        $user = AuthMiddleware::handle();
        $url = trim($_GET['url'] ?? '');
        
        if (empty($url)) {
            Response::error('URL is required.', 400);
        }

        $result = UrlMediaService::validate($url);
        
        if (!$result['valid']) {
            Response::error($result['error'], 422);
        }

        $response = [
            'valid' => true,
            'type'  => $result['type'],
            'url'   => $url,
        ];

        if ($result['type'] === 'video') {
            $response['embed_url'] = UrlMediaService::toEmbedUrl($url);
        }

        Response::success($response);
    }

    /**
     * Get user social profile
     */
    public static function getProfile(): void {
        AuthMiddleware::handle();
        $db = Database::getInstance();
        $userId = (int)($_GET['id'] ?? 0);

        if (!$userId) {
            Response::error('User ID is required.');
        }

        // 1. Get User Details
        $stmt = $db->prepare("
            SELECT id, firstname, lastname, avatar_image, role, bio, location, created_at
            FROM users 
            WHERE id = ?
        ");
        $stmt->execute([$userId]);
        $profile = $stmt->fetch();

        if (!$profile) {
            Response::notFound('User profile not found.');
        }

        // 2. Get Stats - Consolidated
        $statsStmt = $db->prepare("
            SELECT 
                (SELECT COUNT(*) FROM group_posts WHERE user_id = ? AND status = 'published') as post_count,
                (SELECT COUNT(*) FROM group_members WHERE user_id = ? AND status = 'active') as group_count
        ");
        $statsStmt->execute([$userId, $userId]);
        $stats = $statsStmt->fetch();
        $profile['post_count'] = $stats['post_count'];
        $profile['group_count'] = $stats['group_count'];

        // 3. Get Recent Posts
        $stmt = $db->prepare("
            SELECT p.*, u.firstname, u.lastname, u.avatar_image, g.name as group_name, g.slug as group_slug,
                   (SELECT COUNT(*) FROM post_likes WHERE post_id = p.id) as likes_count,
                   (SELECT COUNT(*) FROM post_comments WHERE post_id = p.id) as comments_count
            FROM group_posts p
            JOIN users u ON p.user_id = u.id
            JOIN social_groups g ON p.group_id = g.id
            WHERE p.user_id = ? AND p.status = 'published'
            ORDER BY p.created_at DESC
            LIMIT 10
        ");
        $stmt->execute([$userId]);
        $profile['posts'] = $stmt->fetchAll();

        Response::success($profile);
    }
}
