<?php
require_once 'config.php';

if (!isLoggedIn()) {
    http_response_code(403);
    exit(json_encode(['error' => 'Unauthorized']));
}

$query = isset($_GET['q']) ? sanitize($conn, $_GET['q']) : '';

if (strlen($query) < 2) {
    exit(json_encode([]));
}

$results = [];

// 1. Search Students
if (checkPermission('view_students')) {
    $sql = "SELECT id, firstname, lastname, email, student_id FROM users 
            WHERE role = 'user' AND (firstname LIKE '%$query%' OR lastname LIKE '%$query%' OR email LIKE '%$query%' OR student_id LIKE '%$query%') 
            LIMIT 5";
    $res = $conn->query($sql);
    while ($row = $res->fetch_assoc()) {
        $results[] = [
            'type' => 'student',
            'title' => $row['firstname'] . ' ' . $row['lastname'],
            'subtitle' => $row['student_id'] . ' - ' . $row['email'],
            'url' => 'admin_dashboard.php?page=student_detail&id=' . $row['id'],
            'icon' => 'fas fa-user-graduate'
        ];
    }
}

// 2. Search Documents
$sql = "SELECT d.id, d.document_name, d.document_type, u.firstname, u.lastname 
        FROM documents d 
        JOIN users u ON d.user_id = u.id 
        WHERE d.document_name LIKE '%$query%' 
        LIMIT 5";
$res = $conn->query($sql);
while ($row = $res->fetch_assoc()) {
    $results[] = [
        'type' => 'document',
        'title' => $row['document_name'],
        'subtitle' => 'Uploaded by ' . $row['firstname'] . ' ' . $row['lastname'],
        'url' => 'uploads/' . $row['document_name'], // Direct link or viewer
        'icon' => 'fas fa-file-alt'
    ];
}

// 3. Search Messages
if (checkPermission('send_messages')) {
    $sql = "SELECT m.id, m.subject, m.message, u.firstname, u.lastname 
            FROM messages m 
            JOIN users u ON m.sender_id = u.id 
            WHERE m.subject LIKE '%$query%' OR m.message LIKE '%$query%' 
            LIMIT 5";
    $res = $conn->query($sql);
    while ($row = $res->fetch_assoc()) {
        $results[] = [
            'type' => 'message',
            'title' => $row['subject'],
            'subtitle' => 'From: ' . $row['firstname'] . ' ' . $row['lastname'],
            'url' => 'admin_dashboard.php?page=messages&view=' . $row['id'],
            'icon' => 'fas fa-envelope'
        ];
    }
}

header('Content-Type: application/json');
echo json_encode($results);
?>
