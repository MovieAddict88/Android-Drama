<?php
header('Content-Type: application/json');

$checks = [
    'php_version' => PHP_VERSION,
    'curl_enabled' => function_exists('curl_init'),
    'openssl_enabled' => extension_loaded('openssl'),
    'finfo_enabled' => class_exists('finfo'),
    'uploads_writable' => is_writable('uploads/'),
    'htaccess_exists' => file_exists('.htaccess'),
    'router_exists' => file_exists('src/Router.php')
];

$all_ok = !in_array(false, $checks, true);

echo json_encode([
    'status' => $all_ok ? 'OK' : 'FAIL',
    'checks' => $checks,
    'server' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown'
]);
