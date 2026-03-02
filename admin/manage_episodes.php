<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
check_admin_login();

$drama_id = $_GET['drama_id'] ?? null;
if (!$drama_id) {
    header("Location: manage.php");
    exit;
}

// Fetch drama details
$stmt = $pdo->prepare("SELECT * FROM dramas WHERE id = ?");
$stmt->execute([$drama_id]);
$drama = $stmt->fetch();

if (!$drama) {
    die("Drama not found");
}

// Fetch episodes
$stmt = $pdo->prepare("SELECT * FROM episodes WHERE drama_id = ? ORDER BY chapter_index ASC");
$stmt->execute([$drama_id]);
$episodes = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Episodes - <?php echo htmlspecialchars($drama['title']); ?></title>
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
        .content-container {
            background-color: var(--card-bg);
            border-radius: 15px;
            padding: 30px;
            margin-top: 30px;
        }
        .table {
            color: white;
            border-color: #333;
        }
        .table thead th {
            border-bottom: 2px solid #444;
            color: var(--text-muted);
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 1px;
        }
        .table tbody td {
            border-bottom: 1px solid #333;
            vertical-align: middle;
        }
        .episode-img {
            width: 60px;
            height: 90px;
            object-fit: cover;
            border-radius: 4px;
        }
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container-fluid px-lg-5">
            <a class="navbar-brand fw-bold" href="index.php"><span style="color: var(--primary-color);">DRAMA</span>ADMIN</a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="manage.php">Back to Manage</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid px-lg-5">
        <?php if (isset($_GET['message'])): ?>
            <div class="alert alert-success alert-dismissible fade show mt-4" role="alert">
                <i class="bi bi-check-circle me-2"></i>
                <?php echo htmlspecialchars($_GET['message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show mt-4" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <?php echo htmlspecialchars($_GET['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="content-container shadow">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3 class="mb-1">Episodes for: <?php echo htmlspecialchars($drama['title']); ?></h3>
                    <p class="text-muted small mb-0">Book ID: <?php echo htmlspecialchars($drama['book_id']); ?> | Total: <?php echo count($episodes); ?> Episodes</p>
                </div>
                <div>
                    <a href="generate.php?bookId=<?php echo $drama['book_id']; ?>" class="btn btn-outline-primary">
                        <i class="bi bi-arrow-repeat me-1"></i> Refresh/Regenerate
                    </a>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th width="80">Index</th>
                            <th width="100">Cover</th>
                            <th>Chapter Name</th>
                            <th>Chapter ID</th>
                            <th width="150">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($episodes as $ep): ?>
                            <tr>
                                <td class="fw-bold"><?php echo htmlspecialchars($ep['chapter_index']); ?></td>
                                <td>
                                    <img src="<?php echo htmlspecialchars($ep['chapter_img']); ?>" class="episode-img" onerror="this.src='https://via.placeholder.com/60x90?text=No+Img'">
                                </td>
                                <td><?php echo htmlspecialchars($ep['chapter_name']); ?></td>
                                <td class="text-muted small"><?php echo htmlspecialchars($ep['chapter_id']); ?></td>
                                <td>
                                    <div class="btn-group">
                                        <a href="edit_episode.php?id=<?php echo $ep['id']; ?>" class="btn btn-sm btn-outline-light">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="delete_episode.php" method="POST" style="display:inline;" onsubmit="return confirm('Delete this episode?')">
                                            <input type="hidden" name="id" value="<?php echo $ep['id']; ?>">
                                            <input type="hidden" name="drama_id" value="<?php echo $drama_id; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($episodes)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">No episodes found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
