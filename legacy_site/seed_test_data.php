<?php
require_once 'config.php';

// disable foreign key checks
$conn->query("SET FOREIGN_KEY_CHECKS = 0");

echo "Seeding Test Data...\n";

// 1. Create Dummy Students
$students = [
    ['fname' => 'John', 'lname' => 'Doe', 'email' => 'john@test.com', 'status' => 'submitted'],
    ['fname' => 'Jane', 'lname' => 'Smith', 'email' => 'jane@test.com', 'status' => 'documents_uploaded'],
    ['fname' => 'Mike', 'lname' => 'Ross', 'email' => 'mike@test.com', 'status' => 'under_review'],
    ['fname' => 'Rachel', 'lname' => 'Zane', 'email' => 'rachel@test.com', 'status' => 'interview_scheduled'],
    ['fname' => 'Harvey', 'lname' => 'Specter', 'email' => 'harvey@test.com', 'status' => 'approved'],
    ['fname' => 'Louis', 'lname' => 'Litt', 'email' => 'louis@test.com', 'status' => 'rejected']
];

$role_student = $conn->query("SELECT id FROM roles WHERE name = 'Student'")->fetch_assoc()['id'];
$password = password_hash('password123', PASSWORD_DEFAULT);

foreach ($students as $s) {
    $check = $conn->query("SELECT id FROM users WHERE email = '{$s['email']}'");
    if ($check->num_rows == 0) {
        $sql = "INSERT INTO users (firstname, lastname, email, password, role, role_id, status) 
                VALUES ('{$s['fname']}', '{$s['lname']}', '{$s['email']}', '$password', 'user', $role_student, '{$s['status']}')";
        if ($conn->query($sql)) {
            $user_id = $conn->insert_id;
            echo "Created Student: {$s['fname']} ({$s['status']})\n";
            
            // 2. Add Dummy Document for some
            if (in_array($s['status'], ['documents_uploaded', 'under_review', 'interview_scheduled', 'approved'])) {
                $conn->query("INSERT INTO documents (user_id, document_name, filename, original_name, file_type, file_size, status) 
                              VALUES ($user_id, 'Resume', 'dummy.pdf', 'resume.pdf', 'pdf', 1024, 'approved')");
                echo " - Added Resume\n";
            }
            
            // 3. Add Application Stage Log
            $conn->query("INSERT INTO application_stages (user_id, stage_name) VALUES ($user_id, 'submitted')");
            if ($s['status'] != 'submitted') {
                $conn->query("INSERT INTO application_stages (user_id, stage_name) VALUES ($user_id, '{$s['status']}')");
            }

            // 4. Add Dummy Message
            $conn->query("INSERT INTO messages (sender_id, receiver_id, message, type, created_at) 
                          VALUES ($user_id, 1, 'Hi, I have a question about my application.', 'text', NOW())");
        }
    } else {
        echo "Student {$s['fname']} already exists.\n";
    }
}

echo "\nSeeding Completed.\n";
?>
