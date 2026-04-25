<?php
require_once __DIR__ . '/api/config/app.php';
require_once __DIR__ . '/api/config/database.php';
require_once __DIR__ . '/api/models/User.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    echo "Testing User::getAllStudents()...\n";
    $filters = ['page' => 1, 'limit' => 10];
    $result = User::getAllStudents($filters);
    
    echo "Total Students: " . $result['pagination']['total'] . "\n";
    echo "Count in Data: " . count($result['data']) . "\n";
    
    if (count($result['data']) > 0) {
        echo "First student: " . $result['data'][0]['firstname'] . " " . $result['data'][0]['lastname'] . " (" . $result['data'][0]['role_name'] . ")\n";
    } else {
        echo "No students found.\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
?>
