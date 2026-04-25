<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/api/config/app.php';
require_once dirname(__DIR__) . '/api/services/EmailService.php';

// Prevent timeout
set_time_limit(0); 

echo "Starting Queue Processor...\n";

// 1. Fetch Pending Items
// Limit to 50 to prevent memory issues, run frequently via Cron
$sql = "SELECT q.*, c.subject 
        FROM marketing_queue q 
        JOIN marketing_campaigns c ON q.campaign_id = c.id 
        WHERE q.status = 'pending' 
        LIMIT 50";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($item = $result->fetch_assoc()) {
        echo "Processing Queue ID: {$item['id']}... ";
        
        $success = sendEmail($item['recipient_contact'], $item['subject'], $item['message_content'], $item['id']);
        
        $status = $success ? 'sent' : 'failed';
        $sent_at = $success ? date('Y-m-d H:i:s') : null;
        
        // Error details are logged in api/logs/email_errors.log by EmailService
        $error = $success ? null : "Delivery Failed (Check api/logs/email_errors.log)";
        
        $upd = $conn->prepare("UPDATE marketing_queue SET status = ?, sent_at = ?, error_message = ? WHERE id = ?");
        $upd->bind_param("sssi", $status, $sent_at, $error, $item['id']);
        $upd->execute();
        
        echo "$status\n";
    }
} else {
    echo "No pending messages.\n";
}

function sendEmail($to, $subject, $body, $queueId) {
    // Add Tracking Pixel
    $trackingUrl = "http://localhost/campus%20recruitment/track.php?id=$queueId&type=open";
    $pixel = "<img src='$trackingUrl' width='1' height='1' style='display:none;' />";
    $fullBody = $body . $pixel;

    return EmailService::send($to, $subject, $fullBody);
}
?>
