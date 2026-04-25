<?php
require_once 'config.php';
require_once __DIR__ . '/src/MarketingEngine.php';

if (!isLoggedIn() || !checkPermission('approve_applications')) {
    redirect('admin_dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['launch_campaign'])) {
    $campaignId = intval($_POST['campaign_id']);
    
    $engine = new MarketingEngine($conn);
    $count = $engine->processCampaign($campaignId);
    
    $_SESSION['alert'] = ['type' => 'success', 'message' => "Campaign launched! $count messages queued."];
    header("Location: campaigns.php");
    exit;
}
?>
