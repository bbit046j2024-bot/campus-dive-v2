<?php
header('Content-Type: application/json');
echo json_encode([
    'pdo_mysql_loaded' => extension_loaded('pdo_mysql'),
    'pdo_loaded' => extension_loaded('pdo'),
    'extensions' => get_loaded_extensions(),
    'php_ini' => php_ini_loaded_file(),
    'php_version' => PHP_VERSION,
    'sapi' => PHP_SAPI
], JSON_PRETTY_PRINT);
