<?php
/**
 * Service to validate and transform external media URLs.
 * Policy: accept any HTTPS URL that is not a private/local address.
 * No DNS lookups — static blocklist only to avoid Railway timeouts.
 */
class UrlMediaService {

    // File extensions treated as direct images
    private static array $imageExtensions = [
        'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp', 'tiff', 'avif'
    ];

    // File extensions treated as direct videos
    private static array $videoExtensions = [
        'mp4', 'webm', 'ogg', 'mov', 'avi', 'mkv'
    ];

    // Domains that serve embeddable video players
    private static array $videoDomains = [
        'youtube.com', 'youtu.be', 'youtube-nocookie.com',
        'vimeo.com',
        'loom.com', 'loom.io',
        'tiktok.com', 'vm.tiktok.com',
        'dailymotion.com',
        'twitch.tv', 'clips.twitch.tv',
        'streamable.com',
        'rumble.com',
        'odysee.com',
        'facebook.com', 'fb.watch',
        'instagram.com',
    ];

    // Domains known to serve images/media directly
    private static array $imageDomains = [
        // Image hosts
        'imgur.com', 'i.imgur.com',
        'postimg.cc', 'i.postimg.cc',
        'images.unsplash.com',
        'cdn.pixabay.com',
        'upload.wikimedia.org', 'commons.wikimedia.org',
        'pbs.twimg.com', 'abs.twimg.com',
        'media.giphy.com', 'i.giphy.com', 'media0.giphy.com',
        'media1.giphy.com', 'media2.giphy.com', 'media3.giphy.com',
        'res.cloudinary.com',
        'lh3.googleusercontent.com', 'lh4.googleusercontent.com',
        'lh5.googleusercontent.com', 'lh6.googleusercontent.com',
        'avatars.githubusercontent.com', 'raw.githubusercontent.com',
        'user-images.githubusercontent.com', 'camo.githubusercontent.com',
        'cdn.discordapp.com', 'media.discordapp.net',
        'i.redd.it', 'preview.redd.it',
        'flickr.com', 'staticflickr.com', 'live.staticflickr.com',
        'pinimg.com', 'i.pinimg.com',
        'cdninstagram.com',
        'scontent.cdninstagram.com',
        'images.pexels.com',
        'images.freeimages.com',
        'cdn.pixabay.com',
        'storage.googleapis.com',
        'firebasestorage.googleapis.com',
        'imagekit.io',
        'assets.imgix.net',
        'media.tenor.com', 'c.tenor.com',
        // Cloud/drive image previews
        'drive.google.com',
        'docs.google.com',
        'onedrive.live.com', '1drv.ms',
        'dropbox.com', 'dl.dropboxusercontent.com',
        'ibb.co', 'i.ibb.co',
        // CDNs
        'cdn.jsdelivr.net',
        'unpkg.com',
    ];

    // Blocked hostnames (static — no DNS lookup)
    private static array $blockedHosts = [
        'localhost', '127.0.0.1', '0.0.0.0', '::1',
        '10.0.0.1', '192.168.1.1', '172.16.0.1',
    ];

    // Blocked IP prefixes (CIDR-like static check)
    private static array $blockedPrefixes = [
        '127.', '10.', '192.168.', '172.16.', '172.17.', '172.18.',
        '172.19.', '172.20.', '172.21.', '172.22.', '172.23.', '172.24.',
        '172.25.', '172.26.', '172.27.', '172.28.', '172.29.', '172.30.',
        '172.31.', '0.', '169.254.',
    ];

    /**
     * Validate that a URL is safe and points to allowed media.
     * Returns ['valid'=>bool, 'type'=>string|null, 'error'=>string|null]
     */
    public static function validate(string $url): array {
        $url = trim($url);

        if (empty($url)) {
            return ['valid' => false, 'type' => null, 'error' => 'URL is required.'];
        }

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return ['valid' => false, 'type' => null, 'error' => 'Invalid URL format.'];
        }

        $scheme = strtolower(parse_url($url, PHP_URL_SCHEME) ?? '');
        if (!in_array($scheme, ['https', 'http'])) {
            return ['valid' => false, 'type' => null, 'error' => 'URL must use HTTP or HTTPS.'];
        }

        if (strlen($url) > 4096) {
            return ['valid' => false, 'type' => null, 'error' => 'URL is too long.'];
        }

        $host = strtolower(parse_url($url, PHP_URL_HOST) ?? '');

        if (self::isBlockedHost($host)) {
            return ['valid' => false, 'type' => null, 'error' => 'Local or private URLs are not allowed.'];
        }

        // --- Detect type ---

        // 1. Video embed domains
        foreach (self::$videoDomains as $domain) {
            if ($host === $domain || str_ends_with($host, '.' . $domain)) {
                return ['valid' => true, 'type' => 'video', 'error' => null];
            }
        }

        // 2. Direct video file extension
        $path = parse_url($url, PHP_URL_PATH) ?? '';
        $ext  = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (in_array($ext, self::$videoExtensions)) {
            return ['valid' => true, 'type' => 'video', 'error' => null];
        }

        // 3. Direct image file extension
        if (in_array($ext, self::$imageExtensions)) {
            return ['valid' => true, 'type' => 'image', 'error' => null];
        }

        // 4. Known image hosting domains
        foreach (self::$imageDomains as $domain) {
            if ($host === $domain || str_ends_with($host, '.' . $domain)) {
                return ['valid' => true, 'type' => 'image', 'error' => null];
            }
        }

        // 5. Accept all other public HTTPS URLs as generic links
        //    The browser will try to render them — if the image fails to load it won't crash the app.
        return ['valid' => true, 'type' => 'image', 'error' => null];
    }

    /**
     * Static-only host blocker. No DNS lookups.
     */
    private static function isBlockedHost(string $host): bool {
        if (in_array($host, self::$blockedHosts)) return true;
        foreach (self::$blockedPrefixes as $prefix) {
            if (str_starts_with($host, $prefix)) return true;
        }
        return false;
    }

    /**
     * Extract YouTube video ID from various URL formats.
     */
    public static function getYouTubeId(string $url): ?string {
        preg_match(
            '/(?:youtube\.com\/(?:watch\?v=|embed\/|shorts\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/',
            $url,
            $matches
        );
        return $matches[1] ?? null;
    }

    /**
     * Convert a video URL to its embeddable version.
     */
    public static function toEmbedUrl(string $url): string {
        $youtubeId = self::getYouTubeId($url);
        if ($youtubeId) {
            return "https://www.youtube.com/embed/{$youtubeId}";
        }
        if (preg_match('/vimeo\.com\/(\d+)/', $url, $m)) {
            return "https://player.vimeo.com/video/{$m[1]}";
        }
        if (str_contains($url, 'loom.com/share/')) {
            $id = basename(parse_url($url, PHP_URL_PATH));
            return "https://www.loom.com/embed/{$id}";
        }
        return $url;
    }
}
