<?php
require_once 'config.php';

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');

if (!isLoggedIn()) {
    echo "event: error\n";
    echo "data: Unauthorized\n\n";
    ob_flush();
    flush();
    exit;
}

$user_id = $_SESSION['user_id'];
$last_check = time();

// Keep connection open
while (true) {
    // 1. Check for unread messages count
    $msg_sql = "SELECT COUNT(*) as count FROM messages WHERE receiver_id = $user_id AND is_read = 0";
    $msg_res = $conn->query($msg_sql);
    $msg_count = $msg_res ? $msg_res->fetch_assoc()['count'] : 0;

    // 2. Check for new system notifications (e.g. status changes if student)
    // For now we just use message count as the primary notification
    
    $payload = json_encode([
        'unread_messages' => $msg_count,
        'timestamp' => time()
    ]);

    echo "data: {$payload}\n\n";

    ob_flush();
    flush();

    // Wait 3 seconds before next check to reduce load
    sleep(3);
    
    // Break loop if connection closed by client
    if (connection_aborted()) break;
}
?>
