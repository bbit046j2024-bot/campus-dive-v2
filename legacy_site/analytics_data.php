<?php
require_once 'config.php';

header('Content-Type: application/json');

if (!isLoggedIn() || !checkPermission('view_analytics')) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// 1. Application Funnel (Stages)
$stages = ['submitted', 'documents_uploaded', 'under_review', 'interview_scheduled', 'approved', 'rejected'];
$funnel_data = [];
foreach ($stages as $stage) {
    // Count how many users are CURRENTLY in this stage
    $res = $conn->query("SELECT COUNT(*) as count FROM users WHERE status = '$stage'");
    $funnel_data[$stage] = $res->fetch_assoc()['count'];
}

// 2. Applications Over Time (Last 30 days)
$trend_data = [];
$labels = [];
for ($i = 29; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $labels[] = date('M d', strtotime($date));
    
    $res = $conn->query("SELECT COUNT(*) as count FROM users WHERE DATE(created_at) = '$date' AND role = 'user'");
    $trend_data[] = $res->fetch_assoc()['count'];
}

// 3. Document Statuses
$doc_stats = [
    'Uploaded' => 0,
    'Missing' => 0
];
// Count students with at least one doc vs no docs
// Simplified: Just count total docs for now vs expected (approximate)
$total_students = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'user'")->fetch_assoc()['count'];
$students_with_docs = $conn->query("SELECT COUNT(DISTINCT user_id) as count FROM documents JOIN users ON documents.user_id = users.id WHERE users.role = 'user'")->fetch_assoc()['count'];

$doc_stats['Uploaded'] = $students_with_docs;
$doc_stats['Missing'] = $total_students - $students_with_docs;


// 4. Average Time to Hire (Approved users)
// Calculate avg time from created_at to status='approved' update
// This requires stage history, but we can approximate with current approved users using application_stages table if populated, 
// or just diff created_at vs updated_at for approved users (if updated_at tracks status change).
// For now, let's use the 'application_stages' table we created in setup_schema.php
$avg_time_sql = "SELECT AVG(TIMESTAMPDIFF(DAY, u.created_at, s.entered_at)) as avg_days 
                 FROM users u 
                 JOIN application_stages s ON u.id = s.user_id 
                 WHERE s.stage_name = 'approved'";
$avg_time_res = $conn->query($avg_time_sql);
$avg_days = round($avg_time_res->fetch_assoc()['avg_days'] ?? 0, 1);


echo json_encode([
    'funnel' => [
        'labels' => array_map('ucfirst', array_keys($funnel_data)),
        'data' => array_values($funnel_data)
    ],
    'trend' => [
        'labels' => $labels,
        'data' => $trend_data
    ],
    'docs' => [
        'labels' => array_keys($doc_stats),
        'data' => array_values($doc_stats)
    ],
    'kpis' => [
        'avg_time_to_hire' => $avg_days,
        'total_applications' => $total_students,
        'conversion_rate' => $total_students > 0 ? round(($funnel_data['approved'] / $total_students) * 100, 1) : 0
    ]
]);
?>
