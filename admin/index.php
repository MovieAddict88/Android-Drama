<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
check_admin_login();

$platforms = [
    ['name' => 'DramaBox', 'url' => 'dramabox.php', 'active' => true, 'logo' => 'https://www.dramaboxdb.com/images/logo.png'],
    ['name' => 'ReelShort', 'url' => '#', 'active' => false, 'logo' => 'https://www.reelshort.com/favicon.ico'],
    ['name' => 'ShortMax', 'url' => '#', 'active' => false, 'logo' => 'https://shortmax.app/favicon.ico'],
    ['name' => 'FlickReels', 'url' => '#', 'active' => false, 'logo' => 'https://www.flickreels.net/favicon.ico'],
    ['name' => 'NetShort', 'url' => '#', 'active' => false, 'logo' => 'https://netshort.com/favicon.ico']
];

$stmt = $pdo->query("SELECT COUNT(*) FROM dramas");
$drama_count = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM episodes");
$episode_count = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Drama Website</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .platform-card:hover {
            transform: scale(1.05);
            transition: 0.3s;
        }
        .platform-disabled {
            filter: grayscale(100%);
            opacity: 0.6;
            cursor: not-allowed;
        }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">Drama Admin</a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link active" href="index.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="dramabox.php">DramaBox</a></li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><span class="nav-link text-white">Hello, <?php echo $_SESSION['admin_user']; ?></span></li>
                    <li class="nav-item"><a class="nav-link" href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <div class="row mb-4">
            <div class="col-md-6 col-lg-3">
                <div class="card bg-white shadow-sm border-0 h-100">
                    <div class="card-body">
                        <h6 class="text-muted text-uppercase mb-2">Total Dramas</h6>
                        <h2 class="mb-0"><?php echo $drama_count; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card bg-white shadow-sm border-0 h-100">
                    <div class="card-body">
                        <h6 class="text-muted text-uppercase mb-2">Total Episodes</h6>
                        <h2 class="mb-0"><?php echo $episode_count; ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <h3 class="mb-4">Content Platforms</h3>
        <div class="row row-cols-1 row-cols-md-3 row-cols-lg-5 g-4">
            <?php foreach ($platforms as $platform): ?>
                <div class="col">
                    <div class="card h-100 shadow-sm platform-card <?php echo $platform['active'] ? '' : 'platform-disabled'; ?>">
                        <div class="card-body text-center d-flex flex-column align-items-center justify-content-center p-4">
                            <img src="<?php echo $platform['logo']; ?>" alt="<?php echo $platform['name']; ?>" class="mb-3" style="height: 50px;">
                            <h5 class="card-title"><?php echo $platform['name']; ?></h5>
                            <?php if ($platform['active']): ?>
                                <a href="<?php echo $platform['url']; ?>" class="btn btn-sm btn-outline-primary mt-2">Manage</a>
                            <?php else: ?>
                                <span class="badge bg-secondary mt-2">Coming Soon</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>
