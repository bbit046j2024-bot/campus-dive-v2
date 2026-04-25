<?php
require_once 'config.php';
$result = $conn->query("SHOW COLUMNS FROM documents");
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}
?>
