<?php
if (!file_exists(__DIR__ . '/config.php')) {
    $script_path = $_SERVER['SCRIPT_NAME'];
    $base_dir = str_replace(basename($script_path), '', $script_path);
    // If we are in includes/, go up one level
    if (strpos($base_dir, '/includes/') !== false) {
        $base_dir = str_replace('/includes/', '/', $base_dir);
    }
    header("Location: " . $base_dir . "install.php");
    exit;
}

require_once __DIR__ . '/config.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
