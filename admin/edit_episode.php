<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
check_admin_login();

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: manage.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM episodes WHERE id = ?");
$stmt->execute([$id]);
$episode = $stmt->fetch();

if (!$episode) {
    die("Episode not found");
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $chapter_name = $_POST['chapter_name'] ?? '';
    $chapter_index = $_POST['chapter_index'] ?? 0;
    $video_url = $_POST['video_url'] ?? '';
    $chapter_img = $_POST['chapter_img'] ?? '';

    $updateStmt = $pdo->prepare("UPDATE episodes SET chapter_name = ?, chapter_index = ?, video_url = ?, chapter_img = ? WHERE id = ?");
    try {
        $updateStmt->execute([$chapter_name, $chapter_index, $video_url, $chapter_img, $id]);
        $message = "Episode updated successfully!";
        // Refresh episode data
        $stmt->execute([$id]);
        $episode = $stmt->fetch();
    } catch (Exception $e) {
        $message = "Error updating episode: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Episode - Admin</title>
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
        .form-control {
            background-color: #2a2a2a;
            border: 1px solid #444;
            color: white;
        }
        .form-control:focus {
            background-color: #333;
            border-color: var(--primary-color);
            color: white;
            box-shadow: none;
        }
        .btn-primary {
            background-color: var(--primary-color);
            border: none;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container-fluid px-lg-5">
            <a class="navbar-brand fw-bold" href="index.php"><span style="color: var(--primary-color);">DRAMA</span>ADMIN</a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="manage_episodes.php?drama_id=<?php echo $episode['drama_id']; ?>">Back to Episodes</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="form-container shadow">
                    <h3 class="mb-4">Edit Episode</h3>

                    <?php if ($message): ?>
                        <div class="alert <?php echo strpos($message, 'Error') === false ? 'alert-success' : 'alert-danger'; ?> alert-dismissible fade show" role="alert">
                            <?php echo $message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label text-muted">Chapter Name</label>
                            <input type="text" name="chapter_name" class="form-control" value="<?php echo htmlspecialchars($episode['chapter_name']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted">Chapter Index (Order)</label>
                            <input type="number" name="chapter_index" class="form-control" value="<?php echo htmlspecialchars($episode['chapter_index']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted">Video URL (JSON or Single Link)</label>
                            <textarea name="video_url" class="form-control" rows="3"><?php echo htmlspecialchars($episode['video_url']); ?></textarea>
                            <div class="form-text text-muted">Usually a JSON string of resolutions.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted">Thumbnail URL</label>
                            <input type="text" name="chapter_img" class="form-control" value="<?php echo htmlspecialchars($episode['chapter_img']); ?>">
                            <div class="mt-2 text-center">
                                <img src="<?php echo htmlspecialchars($episode['chapter_img']); ?>" alt="Thumbnail Preview" style="height: 120px; border-radius: 8px;" onerror="this.src='https://via.placeholder.com/150x100?text=No+Preview'">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted">Chapter ID (Read-only)</label>
                            <input type="text" class="form-control opacity-50" value="<?php echo htmlspecialchars($episode['chapter_id']); ?>" readonly>
                        </div>
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                            <a href="manage_episodes.php?drama_id=<?php echo $episode['drama_id']; ?>" class="btn btn-outline-secondary px-4">Cancel</a>
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
