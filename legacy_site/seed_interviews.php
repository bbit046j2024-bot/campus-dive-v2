<?php
require_once 'config.php';

// Create 5 slots for tomorrow
$start_time = strtotime('tomorrow 09:00');
$recruiter_id = 1; // Admin

echo "Seeding Interview Slots...<br>";

for ($i = 0; $i < 5; $i++) {
    $slot_start = date('Y-m-d H:i:s', $start_time + ($i * 3600)); // 1 hour intervals
    $slot_end = date('Y-m-d H:i:s', $start_time + ($i * 3600) + 1800); // 30 mins duration
    
    $check = $conn->query("SELECT id FROM interview_slots WHERE start_time = '$slot_start'");
    if ($check->num_rows == 0) {
        $sql = "INSERT INTO interview_slots (recruiter_id, start_time, end_time, status) VALUES ($recruiter_id, '$slot_start', '$slot_end', 'open')";
        if ($conn->query($sql)) {
            echo "Created slot: $slot_start <br>";
        } else {
            echo "Error: " . $conn->error . "<br>";
        }
    } else {
        echo "Slot already exists: $slot_start <br>";
    }
}
echo "Done.";
?>
