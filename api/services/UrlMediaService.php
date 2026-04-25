<?php
/**
 * Service to validate and transform external media URLs
 * Enforces URL-only policy for social content
 */
class UrlMediaService {

    // Allowed image extensions
    private static array $imageExtensions = [
        'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'
    ];

    // Allowed video/embed domains
    private static array $videoDomains = [
        'youtube.com', 'youtu.be',
        'vimeo.com',
        'loom.com',
    ];

    // Allowed image hosting domains (trusted sources)
    private static array $imageDomains = [
        'imgur.com', 'i.imgur.com',
        'images.unsplash.com',
        'cdn.pixabay.com',
        'upload.wikimedia.org',
        'pbs.twimg.com',
        'media.giphy.com',
        'i.giphy.com',
        'res.cloudinary.com',
        'lh3.googleusercontent.com',
        'avatars.githubusercontent.com',
        'raw.githubusercontent.com',
        'cdn.discordapp.com',
        'media.discordapp.net',
        'i.redd.it',
    ];

    /**
     * Validate that a URL is safe and points to allowed media
     */
    public static function validate(string $url): array {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return ['valid' => false, 'type' => null, 'error' => 'Invalid URL format.'];
        }

        if (!str_starts_with($url, 'https://')) {
            return ['valid' => false, 'type' => null, 'error' => 'URL must use HTTPS.'];
        }

        if (strlen($url) > 2048) {
            return ['valid' => false, 'type' => null, 'error' => 'URL is too long.'];
        }

        $host = parse_url($url, PHP_URL_HOST);
        if (self::isPrivateHost($host)) {
            return ['valid' => false, 'type' => null, 'error' => 'Private or local URLs are not allowed.'];
        }

        foreach (self::$videoDomains as $domain) {
            if (str_contains($host, $domain)) {
                return ['valid' => true, 'type' => 'video', 'error' => null];
            }
        }

        $path = parse_url($url, PHP_URL_PATH) ?? '';
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (in_array($ext, self::$imageExtensions)) {
            return ['valid' => true, 'type' => 'image', 'error' => null];
        }

        foreach (self::$imageDomains as $domain) {
            if (str_contains($host, $domain)) {
                return ['valid' => true, 'type' => 'image', 'error' => null];
            }
        }

        return ['valid' => true, 'type' => 'unknown', 'error' => null];
    }

    /**
     * Extract YouTube video ID
     */
    public static function getYouTubeId(string $url): ?string {
        preg_match(
            '/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([a-zA-Z0-9_-]{11})/',
            $url,
            $matches
        );
        return $matches[1] ?? null;
    }

    /**
     * Convert to embed URL
     */
    public static function toEmbedUrl(string $url): string {
        $youtubeId = self::getYouTubeId($url);
        if ($youtubeId) {
            return "https://www.youtube.com/embed/{$youtubeId}";
        }
        if (str_contains($url, 'vimeo.com')) {
            $id = basename(parse_url($url, PHP_URL_PATH));
            return "https://player.vimeo.com/video/{$id}";
        }
        return $url;
    }

    private static function isPrivateHost(string $host): bool {
        $blocked = ['localhost', '127.0.0.1', '0.0.0.0', '::1'];
        if (in_array($host, $blocked)) return true;
        
        $ip = gethostbyname($host);
        return filter_var($ip, FILTER_VALIDATE_IP, 
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
    }
}
