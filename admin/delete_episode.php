<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
check_admin_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: manage.php");
    exit;
}

$id = $_POST['id'] ?? null;
$drama_id = $_POST['drama_id'] ?? null;

if ($id && $drama_id) {
    try {
        $stmt = $pdo->prepare("DELETE FROM episodes WHERE id = ?");
        $stmt->execute([$id]);

        header("Location: manage_episodes.php?drama_id=$drama_id&message=Episode deleted successfully");
        exit;
    } catch (Exception $e) {
        die("Error deleting episode: " . $e->getMessage());
    }
} else {
    header("Location: manage.php");
    exit;
}
