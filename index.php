<?php
require_once 'includes/db.php';

// Fetch all dramas grouped by category
$stmt = $pdo->query("SELECT * FROM dramas ORDER BY created_at DESC");
$allDramas = $stmt->fetchAll();

$categorizedDramas = [];
foreach ($allDramas as $drama) {
    $cat = $drama['category'] ?? 'General';
    $categorizedDramas[$cat][] = $drama;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Drama Box - Watch Dramas Online</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body { background-color: #121212; color: white; font-family: 'Inter', sans-serif; }
        .navbar { background-color: #000; padding: 1rem 0; }
        .navbar-brand { font-size: 1.5rem; letter-spacing: 1px; }

        .hero {
            background: linear-gradient(rgba(0,0,0,0.7), rgba(18,18,18,1)), url('https://www.dramaboxdb.com/images/dramabox/subscription-bg.webp');
            background-size: cover;
            background-position: center;
            padding: 120px 0 60px;
            text-align: center;
        }

        .category-section { margin-bottom: 3rem; }
        .category-title { font-size: 1.5rem; font-weight: 700; margin-bottom: 1.5rem; padding-left: 10px; border-left: 4px solid #ff2d55; }

        .drama-slider {
            display: flex;
            overflow-x: auto;
            gap: 15px;
            padding-bottom: 15px;
            scrollbar-width: none; /* Firefox */
        }
        .drama-slider::-webkit-scrollbar { display: none; /* Chrome/Safari */ }

        .drama-card {
            flex: 0 0 180px;
            text-decoration: none;
            color: white;
            transition: transform 0.3s;
        }
        .drama-card:hover { transform: translateY(-5px); }

        .drama-img-wrapper {
            position: relative;
            aspect-ratio: 9/16;
            overflow: hidden;
            border-radius: 8px;
            margin-bottom: 8px;
        }
        .drama-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .drama-title {
            font-size: 0.9rem;
            font-weight: 500;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            line-height: 1.2;
        }

        @media (max-width: 576px) {
            .drama-card { flex: 0 0 130px; }
            .hero h1 { font-size: 2rem; }
        }

        footer { background-color: #000; border-top: 1px solid #333; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold text-danger" href="index.php">DRAMA<span class="text-white">BOX</span></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link active" href="index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="admin/login.php">Admin Panel</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="hero">
        <div class="container">
            <h1 class="display-4 fw-bold mb-3">Watch Trending Short Dramas</h1>
            <p class="lead opacity-75">Binge-worthy short dramas, anytime, anywhere.</p>
        </div>
    </div>

    <div class="container py-4">
        <?php if (empty($categorizedDramas)): ?>
            <div class="alert alert-secondary text-center py-5 bg-dark border-0">
                <i class="bi bi-film h1 d-block mb-3 opacity-50"></i>
                <p>No dramas available yet. Check back later or add from admin panel.</p>
            </div>
        <?php else: ?>
            <?php foreach ($categorizedDramas as $category => $dramas): ?>
                <div class="category-section">
                    <h2 class="category-title"><?php echo htmlspecialchars($category); ?></h2>
                    <div class="drama-slider">
                        <?php foreach ($dramas as $drama): ?>
                            <a href="watch.php?id=<?php echo (int)$drama['id']; ?>" class="drama-card">
                                <div class="drama-img-wrapper shadow">
                                    <img src="<?php echo htmlspecialchars($drama['cover_img']); ?>" class="drama-img" alt="<?php echo htmlspecialchars($drama['title']); ?>" onerror="this.src='https://via.placeholder.com/240x400?text=No+Image'">
                                </div>
                                <div class="drama-title"><?php echo htmlspecialchars($drama['title']); ?></div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <footer class="text-center py-5 mt-5">
        <div class="container">
            <div class="mb-4">
                <a class="navbar-brand fw-bold text-danger" href="index.php">DRAMA<span class="text-white">BOX</span></a>
            </div>
            <p class="mb-0 text-muted small">&copy; <?php echo date('Y'); ?> Drama Box Clone. For educational purposes.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
