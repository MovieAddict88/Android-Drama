<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
check_admin_login();

$search = $_GET['search'] ?? '';

if ($search) {
    $stmt = $pdo->prepare("SELECT * FROM dramas WHERE title LIKE ? OR description LIKE ? OR category LIKE ? ORDER BY created_at DESC");
    $stmt->execute(["%$search%", "%$search%", "%$search%"]);
} else {
    $stmt = $pdo->query("SELECT * FROM dramas ORDER BY created_at DESC");
}
$dramas = $stmt->fetchAll();

$categorizedDramas = [];
foreach ($dramas as $drama) {
    $cat = $drama['category'] ?: 'Uncategorized';
    $categorizedDramas[$cat][] = $drama;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Dramas - Admin</title>
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
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
        .navbar {
            background-color: rgba(0,0,0,0.8) !important;
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 40px 0 15px;
            padding: 0 15px;
        }
        .section-title {
            font-size: 1.4rem;
            font-weight: 700;
            position: relative;
            padding-left: 15px;
        }
        .section-title::before {
            content: '';
            position: absolute;
            left: 0;
            top: 15%;
            height: 70%;
            width: 4px;
            background-color: var(--primary-color);
            border-radius: 2px;
        }
        .drama-card {
            transition: transform 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
            border: none;
            background: var(--card-bg);
            height: 100%;
            display: flex;
            flex-direction: column;
            border-radius: 12px;
            overflow: hidden;
        }
        .drama-card:hover {
            transform: scale(1.03);
            z-index: 5;
        }
        .card-img-container {
            position: relative;
            aspect-ratio: 2/3;
            overflow: hidden;
        }
        .card-img-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .card-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            opacity: 0;
            transition: opacity 0.3s;
            backdrop-filter: blur(2px);
            gap: 10px;
        }
        .drama-card:hover .card-overlay {
            opacity: 1;
        }
        .card-body {
            padding: 12px;
        }
        .card-title {
            font-size: 0.95rem;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            color: #fff;
            margin-bottom: 4px;
        }
        .card-subtitle {
            font-size: 0.8rem;
            color: var(--text-muted);
        }
        .action-btn {
            width: 80%;
            font-size: 0.8rem;
            font-weight: 600;
            border-radius: 20px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container-fluid px-lg-5">
            <a class="navbar-brand fw-bold" href="index.php"><span style="color: var(--primary-color);">DRAMA</span>ADMIN</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="index.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="dramabox.php">Browse DramaBox</a></li>
                    <li class="nav-item"><a class="nav-link active" href="manage.php">Manage</a></li>
                </ul>
                <div class="d-flex align-items-center">
                    <form class="d-flex me-3" action="manage.php" method="GET">
                        <input type="text" name="search" class="form-control form-control-sm bg-dark border-secondary text-white rounded-pill px-3" placeholder="Search local..." value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit" class="btn btn-sm btn-outline-light ms-2 rounded-pill">Search</button>
                    </form>
                    <a href="logout.php" class="text-white text-decoration-none"><i class="bi bi-box-arrow-right fs-5"></i></a>
                </div>
            </div>
        </div>
    </nav>

    <main class="py-4">
        <div class="container-fluid px-lg-5">
            <?php if (isset($_GET['message'])): ?>
                <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                    <i class="bi bi-check-circle me-2"></i>
                    <?php echo htmlspecialchars($_GET['message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <?php echo htmlspecialchars($_GET['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Manage Dramas</h2>
                <div class="text-muted">Total: <?php echo count($dramas); ?> Dramas</div>
            </div>

            <?php if (empty($categorizedDramas)): ?>
                <div class="alert alert-dark text-center py-5">
                    <i class="bi bi-film fs-1 mb-3 d-block"></i>
                    <h4>No dramas found in database</h4>
                    <p class="text-muted">You can add dramas from the <a href="dramabox.php" class="text-primary">Browse DramaBox</a> section.</p>
                </div>
            <?php else: ?>
                <?php foreach ($categorizedDramas as $category => $items): ?>
                    <div class="section-container mb-5">
                        <div class="section-header">
                            <h3 class="section-title"><?php echo htmlspecialchars($category); ?></h3>
                        </div>
                        <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-lg-5 row-cols-xl-6 g-3 g-lg-4">
                            <?php foreach ($items as $item): ?>
                                <div class="col">
                                    <div class="drama-card">
                                        <div class="card-img-container shadow">
                                            <img src="<?php echo htmlspecialchars($item['cover_img']); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>" loading="lazy" onerror="this.src='https://via.placeholder.com/240x400?text=No+Image'">
                                            <div class="card-overlay">
                                                <a href="manage_episodes.php?drama_id=<?php echo $item['id']; ?>" class="btn btn-primary btn-sm action-btn">
                                                    <i class="bi bi-collection-play me-1"></i> Episodes
                                                </a>
                                                <a href="edit_drama.php?id=<?php echo $item['id']; ?>" class="btn btn-light btn-sm action-btn">
                                                    <i class="bi bi-pencil-square me-1"></i> Edit
                                                </a>
                                                <form action="delete_drama.php" method="POST" class="w-100 d-flex justify-content-center" onsubmit="return confirm('Are you sure you want to delete this drama and all its episodes?')">
                                                    <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm action-btn">
                                                        <i class="bi bi-trash me-1"></i> Delete
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <div class="card-title"><?php echo htmlspecialchars($item['title']); ?></div>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div class="card-subtitle small"><?php echo htmlspecialchars($item['book_id']); ?></div>
                                                <span class="badge <?php echo $item['platform'] === 'reelshort' ? 'bg-info' : 'bg-danger'; ?> x-small">
                                                    <?php echo ucfirst($item['platform']); ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
