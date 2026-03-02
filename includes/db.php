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
    if (defined('DB_TYPE') && DB_TYPE === 'sqlite') {
        $pdo = new PDO("sqlite:" . DB_PATH);
    } else {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    }
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Self-healing database: Ensure all tables exist
    try {
        $pdo->query("SELECT 1 FROM episode_sources LIMIT 1");
    } catch (Exception $e) {
        $schemaPath = dirname(__DIR__) . '/database/schema.sql';
        if (file_exists($schemaPath)) {
            $sql = file_get_contents($schemaPath);
            // Handle multiple statements (MySQL and SQLite have different requirements for exec())
            $isSqlite = (defined('DB_TYPE') && DB_TYPE === 'sqlite') || (defined('DB_HOST') && strpos(DB_HOST, 'sqlite') !== false);
            if ($isSqlite) {
                $pdo->exec($sql);
            } else {
                $pdo->exec($sql);
            }
        }
    }
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
