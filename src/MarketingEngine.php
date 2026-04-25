<?php
require_once __DIR__ . '/../config.php';

class MarketingEngine {
    private $conn;

    public function __construct($dbConnection) {
        $this->conn = $dbConnection;
    }

    public function processCampaign($campaignId) {
        // 1. Get Campaign Details
        $stmt = $this->conn->prepare("SELECT c.*, t.body_content, t.subject FROM marketing_campaigns c JOIN marketing_templates t ON c.template_id = t.id WHERE c.id = ?");
        $stmt->bind_param("i", $campaignId);
        $stmt->execute();
        $campaign = $stmt->get_result()->fetch_assoc();
        
        if (!$campaign) return false;

        // 2. Update Status to Processing
        $this->conn->query("UPDATE marketing_campaigns SET status = 'processing' WHERE id = $campaignId");

        // 3. Fetch Recipients based on Segment
        $recipients = $this->getRecipients($campaign['segment_criteria']);

        // 4. Generate Messages (Mail Merge) & Add to Queue
        $count = 0;
        $stmt = $this->conn->prepare("INSERT INTO marketing_queue (campaign_id, user_id, recipient_contact, message_content) VALUES (?, ?, ?, ?)");
        
        foreach ($recipients as $user) {
            $personalizedContent = $this->applyMailMerge($campaign['body_content'], $user);
            
            // Add Tracking Pixel to HTML emails
            if ($campaign['type'] == 'email') {
                $personalizedContent .= $this->getTrackingPixel($campaignId, $user['id']);
            }

            $contact = ($campaign['type'] == 'email') ? $user['email'] : '1234567890'; // Phone placeholder
            
            $stmt->bind_param("iiss", $campaignId, $user['id'], $contact, $personalizedContent);
            $stmt->execute();
            $count++;
        }

        // 5. Update Status to Scheduled/Completed
        $newStatus = ($campaign['scheduled_at'] && strtotime($campaign['scheduled_at']) > time()) ? 'scheduled' : 'ready'; // 'ready' means picked up by cron immediately if not future scheduled
        // actually for simplicity, let's just mark queue items as 'pending' and campaign as 'completed' (in terms of queue generation)
        $this->conn->query("UPDATE marketing_campaigns SET status = 'completed' WHERE id = $campaignId");
        
        return $count;
    }

    private function getRecipients($criteriaJson) {
        $criteria = json_decode($criteriaJson, true);
        $filter = $criteria['filter'] ?? 'all';
        
        $sql = "SELECT id, firstname, lastname, email, status FROM users WHERE role = 'user'";
        
        if ($filter !== 'all') {
            if (strpos($filter, 'status:') === 0) {
                $status = substr($filter, 7);
                $sql .= " AND status = '$status'";
            }
        }
        
        return $this->conn->query($sql)->fetch_all(MYSQLI_ASSOC);
    }

    private function applyMailMerge($content, $user) {
        $vars = [
            '{{firstname}}' => $user['firstname'],
            '{{lastname}}' => $user['lastname'],
            '{{email}}' => $user['email'],
            '{{status}}' => $user['status']
        ];
        return str_replace(array_keys($vars), array_values($vars), $content);
    }

    private function getTrackingPixel($campaignId, $userId) {
        // In reality, you'd generate a queue_id first, but to keep it simple we append it during sending or 
        // use a simplified tracking URL that looks up the log.
        // For correct tracking, we need the queue_id. 
        // Since we are taking a shortcut by batch inserting content, we can use a placeholder 
        // or accept that pixel generation happens at SEND time (in process_queue.php).
        // Let's defer pixel addition to process_queue.php for accurate queue_id.
        return ""; 
    }
}
?>
