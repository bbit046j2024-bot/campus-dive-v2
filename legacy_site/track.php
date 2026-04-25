<?php
require_once 'config.php';

if (isset($_GET['id']) && isset($_GET['type'])) {
    $queueId = intval($_GET['id']);
    $type = $_GET['type']; // 'open' or 'click'
    
    // Log Event
    $stmt = $conn->prepare("INSERT INTO marketing_logs (queue_id, event_type, ip_address, user_agent) VALUES (?, ?, ?, ?)");
    $ip = $_SERVER['REMOTE_ADDR'];
    $ua = $_SERVER['HTTP_USER_AGENT'];
    $stmt->bind_param("isss", $queueId, $type, $ip, $ua);
    $stmt->execute();
    
    if ($type === 'open') {
        // Return 1x1 transparent GIF
        header('Content-Type: image/gif');
        echo base64_decode('R0lGODlhAQABAJAAAP8AAAAAACH5BAUQAAAALAAAAAABAAEAAAICBAEAOw==');
        exit;
    } elseif ($type === 'click') {
        // Redirect to target URL
        $target = isset($_GET['url']) ? $_GET['url'] : 'index.php'; // Default fallback
        header("Location: $target");
        exit;
    }
}
?>
