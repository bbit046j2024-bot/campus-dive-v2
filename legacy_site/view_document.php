<?php
require_once 'config.php';

if (!isLoggedIn()) {
    die("Unauthorized");
}

$doc_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'] ?? 'user';

// Security Check: User can view own, Admin can view all
$sql = "SELECT * FROM documents WHERE id = $doc_id";
if ($role === 'user') {
    $sql .= " AND user_id = $user_id";
}

$result = $conn->query($sql);
if ($result->num_rows === 0) {
    die("Document not found or access denied.");
}

$doc = $result->fetch_assoc();
$filepath = 'uploads/' . $doc['filename'];
$ext = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));

if (!file_exists($filepath)) {
    die("File not found on server.");
}

// Content Type Map
$types = [
    'pdf' => 'application/pdf',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'txt' => 'text/plain'
];

if (isset($types[$ext])) {
    header("Content-Type: " . $types[$ext]);
    header("Content-Disposition: inline; filename=\"" . $doc['original_name'] . "\"");
    readfile($filepath);
} else {
    // Force Download for others
    header("Content-Type: application/octet-stream");
    header("Content-Disposition: attachment; filename=\"" . $doc['original_name'] . "\"");
    readfile($filepath);
}
?>
