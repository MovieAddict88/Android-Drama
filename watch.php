<?php
require_once 'includes/db.php';

$id = $_GET['id'] ?? null;
$epIndex = $_GET['ep'] ?? 0;

if (!$id) {
    header("Location: index.php");
    exit;
}

// Fetch Drama
$stmt = $pdo->prepare("SELECT * FROM dramas WHERE id = ?");
$stmt->execute([$id]);
$drama = $stmt->fetch();

if (!$drama) {
    header("Location: index.php");
    exit;
}

// Fetch Episodes
$stmt = $pdo->prepare("SELECT * FROM episodes WHERE drama_id = ? ORDER BY chapter_index ASC");
$stmt->execute([$id]);
$episodes = $stmt->fetchAll();

$currentEpisode = null;
foreach ($episodes as $ep) {
    if ($ep['chapter_index'] == $epIndex) {
        $currentEpisode = $ep;
        break;
    }
}

// If ep index not found, default to first episode
if (!$currentEpisode && !empty($episodes)) {
    $currentEpisode = $episodes[0];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Watching <?php echo htmlspecialchars($drama['title']); ?> - Drama Box</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body { background-color: #121212; color: white; }
        .navbar { background-color: #000; }
        .ep-list { height: 600px; overflow-y: auto; background-color: #1e1e1e; border-radius: 8px; }
        .ep-item { padding: 10px; border-bottom: 1px solid #333; cursor: pointer; text-decoration: none; color: white; display: block; }
        .ep-item:hover { background-color: #333; }
        .ep-item.active { background-color: #0d6efd; }
        .video-container { position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; background-color: #000; border-radius: 8px; }
        .video-container video { position: absolute; top: 0; left: 0; width: 100%; height: 100%; }
        .drama-header { background: linear-gradient(rgba(0,0,0,0.8), rgba(18,18,18,1)), url('<?php echo $drama['cover_img']; ?>'); background-size: cover; background-position: center; padding: 60px 0; margin-bottom: 30px; }
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
                    <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="admin/login.php">Admin Panel</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="drama-header">
        <div class="container">
            <div class="row">
                <div class="col-md-3 d-none d-md-block">
                    <img src="<?php echo htmlspecialchars($drama['cover_img']); ?>" class="img-fluid rounded shadow" alt="<?php echo htmlspecialchars($drama['title']); ?>" onerror="this.src='https://via.placeholder.com/240x400?text=No+Image'">
                </div>
                <div class="col-md-9 d-flex flex-column justify-content-center">
                    <h1 class="display-4 fw-bold"><?php echo htmlspecialchars($drama['title']); ?></h1>
                    <p class="lead">Book ID: <?php echo htmlspecialchars($drama['book_id']); ?></p>
                    <div class="mt-2">
                        <span class="badge bg-primary">DramaBox</span>
                        <span class="badge bg-secondary"><?php echo count($episodes); ?> Episodes</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container pb-5">
        <div class="row">
            <div class="col-lg-8">
                <?php if ($currentEpisode): ?>
                    <div class="video-container mb-3 shadow">
                        <video controls poster="<?php echo htmlspecialchars($currentEpisode['chapter_img']); ?>" playsinline>
                            <source src="<?php echo htmlspecialchars($currentEpisode['video_url']); ?>" type="video/mp4">
                            Your browser does not support the video tag.
                        </video>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4 class="mb-0"><?php echo htmlspecialchars($currentEpisode['chapter_name']); ?></h4>
                        <div>
                            <?php
                            $prevEp = null;
                            $nextEp = null;
                            foreach ($episodes as $idx => $ep) {
                                if ($ep['id'] == $currentEpisode['id']) {
                                    $prevEp = $episodes[$idx - 1] ?? null;
                                    $nextEp = $episodes[$idx + 1] ?? null;
                                    break;
                                }
                            }
                            ?>
                            <?php if ($prevEp): ?>
                                <a href="watch.php?id=<?php echo $id; ?>&ep=<?php echo $prevEp['chapter_index']; ?>" class="btn btn-outline-light btn-sm"><i class="bi bi-chevron-left"></i> Previous</a>
                            <?php endif; ?>
                            <?php if ($nextEp): ?>
                                <a href="watch.php?id=<?php echo $id; ?>&ep=<?php echo $nextEp['chapter_index']; ?>" class="btn btn-outline-light btn-sm">Next <i class="bi bi-chevron-right"></i></a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning">No video found for this drama.</div>
                <?php endif; ?>
            </div>
            <div class="col-lg-4">
                <h5 class="mb-3">Episode List</h5>
                <div class="ep-list shadow-sm">
                    <?php foreach ($episodes as $ep): ?>
                        <a href="watch.php?id=<?php echo (int)$id; ?>&ep=<?php echo (int)$ep['chapter_index']; ?>" class="ep-item <?php echo ($currentEpisode && $currentEpisode['id'] == $ep['id']) ? 'active' : ''; ?>">
                            <div class="d-flex align-items-center">
                                <span class="me-3 opacity-75"><?php echo (int)$ep['chapter_index'] + 1; ?></span>
                                <span><?php echo htmlspecialchars($ep['chapter_name']); ?></span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <footer class="bg-black text-center py-4 mt-5">
        <p class="mb-0 text-muted">&copy; <?php echo date('Y'); ?> Drama Box - All Rights Reserved</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
