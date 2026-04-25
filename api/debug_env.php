<?php
header('Content-Type: application/json');

$vars = [
    'MYSQLHOST' => getenv('MYSQLHOST'),
    'MYSQLPORT' => getenv('MYSQLPORT'),
    'MYSQLUSER' => getenv('MYSQLUSER'),
    'MYSQLDATABASE' => getenv('MYSQLDATABASE'),
    'DB_HOST' => getenv('DB_HOST'),
    'APP_ENV' => getenv('APP_ENV'),
    'env_raw' => $_ENV,
    'server_raw' => array_intersect_key($_SERVER, array_flip(['MYSQLHOST', 'MYSQLPORT', 'MYSQLUSER', 'MYSQLDATABASE']))
];

// Redact everything except the first part of host for safety
if ($vars['MYSQLHOST']) {
    $vars['MYSQLHOST_LAST'] = substr($vars['MYSQLHOST'], -10);
}

echo json_encode($vars, JSON_PRETTY_PRINT);
