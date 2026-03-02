<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
check_admin_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: manage.php");
    exit;
}

$id = $_POST['id'] ?? null;

if ($id) {
    try {
        $pdo->beginTransaction();

        // The foreign keys in schema.sql already have ON DELETE CASCADE,
        // but it's good practice to be explicit if needed or to handle files.
        // In this case, we just need to delete the drama.

        $stmt = $pdo->prepare("DELETE FROM dramas WHERE id = ?");
        $stmt->execute([$id]);

        $pdo->commit();
        header("Location: manage.php?message=Drama deleted successfully");
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        die("Error deleting drama: " . $e->getMessage());
    }
} else {
    header("Location: manage.php");
    exit;
}
