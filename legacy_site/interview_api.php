<?php
require_once 'config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';
$user_id = $_SESSION['user_id'];

if ($action === 'get_slots') {
    // Fetch OPEN slots or slots booked by THIS user
    $sql = "SELECT id, start_time, end_time, status 
            FROM interview_slots 
            WHERE (status = 'open' AND start_time > NOW()) 
               OR (booked_by = $user_id) 
            ORDER BY start_time ASC";
    
    $result = $conn->query($sql);
    $slots = [];
    while ($row = $result->fetch_assoc()) {
        $slots[] = [
            'id' => $row['id'],
            'start' => date('D, M d h:i A', strtotime($row['start_time'])),
            'status' => $row['status'],
            'is_mine' => ($row['status'] == 'booked' || $row['status'] == 'completed') // Simplified check since we filtered by user_id for booked ones
        ];
    }
    echo json_encode($slots);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'book') {
    $input = json_decode(file_get_contents('php://input'), true);
    $slot_id = intval($input['slot_id']);

    // Check if slot is open
    $check = $conn->query("SELECT id FROM interview_slots WHERE id = $slot_id AND status = 'open'");
    if ($check->num_rows === 0) {
        echo json_encode(['error' => 'Slot not available']);
        exit;
    }

    // Book it
    $stmt = $conn->prepare("UPDATE interview_slots SET status = 'booked', booked_by = ? WHERE id = ?");
    $stmt->bind_param("ii", $user_id, $slot_id);
    
    if ($stmt->execute()) {
        // Update User Status
        $conn->query("UPDATE users SET status = 'interview_scheduled' WHERE id = $user_id");
        
        // Log Application Stage
        $conn->query("INSERT INTO application_stages (user_id, stage_name) VALUES ($user_id, 'interview_scheduled')");

        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Booking failed']);
    }
    exit;
}
?>
