<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
check_admin_login();

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: manage.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM dramas WHERE id = ?");
$stmt->execute([$id]);
$drama = $stmt->fetch();

if (!$drama) {
    die("Drama not found");
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $cover_img = $_POST['cover_img'] ?? '';
    $category = $_POST['category'] ?? '';
    $platform = $_POST['platform'] ?? '';

    $updateStmt = $pdo->prepare("UPDATE dramas SET title = ?, description = ?, cover_img = ?, category = ?, platform = ? WHERE id = ?");
    try {
        $updateStmt->execute([$title, $description, $cover_img, $category, $platform, $id]);
        $message = "Drama updated successfully!";
        // Refresh drama data
        $stmt->execute([$id]);
        $drama = $stmt->fetch();
    } catch (Exception $e) {
        $message = "Error updating drama: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Drama - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #ff0055;
            --bg-color: #0b0b0b;
            --card-bg: #1a1a1a;
            --text-main: #ffffff;
            --text-muted: #aaaaaa;
        }
        body {
            background-color: var(--bg-color);
            color: var(--text-main);
            font-family: 'Inter', sans-serif;
        }
        .navbar {
            background-color: rgba(0,0,0,0.8) !important;
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .form-container {
            background-color: var(--card-bg);
            border-radius: 15px;
            padding: 30px;
            margin-top: 30px;
        }
        .form-control, .form-select {
            background-color: #2a2a2a;
            border: 1px solid #444;
            color: white;
        }
        .form-control:focus, .form-select:focus {
            background-color: #333;
            border-color: var(--primary-color);
            color: white;
            box-shadow: none;
        }
        .btn-primary {
            background-color: var(--primary-color);
            border: none;
        }
        .btn-primary:hover {
            background-color: #e6004d;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container-fluid px-lg-5">
            <a class="navbar-brand fw-bold" href="index.php"><span style="color: var(--primary-color);">DRAMA</span>ADMIN</a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="index.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="dramabox.php">DramaBox</a></li>
                    <li class="nav-item"><a class="nav-link" href="reelshort.php">ReelShort</a></li>
                    <li class="nav-item"><a class="nav-link active" href="manage.php">Manage</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="form-container shadow">
                    <h3 class="mb-4">Edit Drama: <?php echo htmlspecialchars($drama['title']); ?></h3>

                    <?php if ($message): ?>
                        <div class="alert <?php echo strpos($message, 'Error') === false ? 'alert-success' : 'alert-danger'; ?> alert-dismissible fade show" role="alert">
                            <?php echo $message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label text-muted">Title</label>
                            <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($drama['title']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted">Description</label>
                            <textarea name="description" class="form-control" rows="4"><?php echo htmlspecialchars($drama['description']); ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted">Cover Image URL</label>
                            <input type="text" name="cover_img" class="form-control" value="<?php echo htmlspecialchars($drama['cover_img']); ?>">
                            <div class="mt-2">
                                <img src="<?php echo htmlspecialchars($drama['cover_img']); ?>" alt="Cover Preview" style="height: 150px; border-radius: 8px;" onerror="this.src='https://via.placeholder.com/100x150?text=No+Preview'">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted">Category</label>
                                <input type="text" name="category" class="form-control" value="<?php echo htmlspecialchars($drama['category']); ?>" placeholder="e.g. Romance, Action">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted">Platform</label>
                                <input type="text" name="platform" class="form-control" value="<?php echo htmlspecialchars($drama['platform']); ?>">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted">Book ID (Read-only)</label>
                            <input type="text" class="form-control opacity-50" value="<?php echo htmlspecialchars($drama['book_id']); ?>" readonly>
                        </div>
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                            <a href="manage.php" class="btn btn-outline-secondary px-4">Cancel</a>
                            <button type="submit" class="btn btn-primary px-4">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
