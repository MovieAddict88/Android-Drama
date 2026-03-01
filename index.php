<?php
require_once 'includes/db.php';

$stmt = $pdo->query("SELECT * FROM dramas ORDER BY created_at DESC");
$dramas = $stmt->fetchAll();
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
        body { background-color: #121212; color: white; }
        .navbar { background-color: #000; }
        .card { background-color: #1e1e1e; color: white; border: none; transition: 0.3s; }
        .card:hover { transform: scale(1.05); }
        .drama-img { height: 350px; object-fit: cover; }
        .hero { background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), url('https://www.dramaboxdb.com/images/dramabox/subscription-bg.webp'); background-size: cover; padding: 100px 0; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">DRAMA BOX</a>
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

    <div class="hero text-center">
        <div class="container">
            <h1 class="display-3 fw-bold">Watch Trending Short Dramas</h1>
            <p class="lead">Free and High Quality Episodes from DramaBox</p>
        </div>
    </div>

    <div class="container py-5">
        <h3 class="mb-4">Explore Dramas</h3>
        <?php if (empty($dramas)): ?>
            <div class="alert alert-secondary text-center py-5">
                <p>No dramas available yet. Check back later or add from admin panel.</p>
            </div>
        <?php else: ?>
            <div class="row row-cols-2 row-cols-sm-3 row-cols-md-3 row-cols-lg-4 row-cols-xl-6 g-4">
                <?php foreach ($dramas as $drama): ?>
                    <div class="col">
                        <a href="watch.php?id=<?php echo (int)$drama['id']; ?>" class="text-decoration-none">
                            <div class="card h-100 shadow">
                                <img src="<?php echo htmlspecialchars($drama['cover_img']); ?>" class="card-img-top drama-img" alt="<?php echo htmlspecialchars($drama['title']); ?>" onerror="this.src='https://via.placeholder.com/240x400?text=No+Image'">
                                <div class="card-body p-2">
                                    <h6 class="card-title text-truncate mb-0"><?php echo htmlspecialchars($drama['title']); ?></h6>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <footer class="bg-black text-center py-4 mt-5">
        <p class="mb-0 text-muted">&copy; <?php echo date('Y'); ?> Drama Box - All Rights Reserved</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
