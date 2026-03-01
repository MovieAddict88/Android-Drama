<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
check_admin_login();

$dramas = scrape_dramabox();

// Check which ones are already generated
$stmt = $pdo->query("SELECT book_id FROM dramas");
$existing_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DramaBox Content - Drama Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .drama-card {
            transition: 0.3s;
            border: none;
            overflow: hidden;
        }
        .drama-card:hover {
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .card-img-top {
            height: 300px;
            object-fit: cover;
        }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">Drama Admin</a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="index.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link active" href="dramabox.php">DramaBox</a></li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3>DramaBox Content</h3>
            <div class="text-muted">Scraped from dramaboxdb.com</div>
        </div>

        <?php if (isset($dramas['error'])): ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle-fill"></i> <?php echo htmlspecialchars($dramas['error']); ?>
            </div>
        <?php elseif (empty($dramas)): ?>
            <div class="alert alert-warning">No dramas found. Website structure might have changed.</div>
        <?php else: ?>
            <div class="row row-cols-1 row-cols-md-3 row-cols-lg-4 g-4">
                <?php foreach ($dramas as $drama): ?>
                    <?php if (!is_array($drama)) continue; ?>
                    <div class="col">
                        <div class="card h-100 drama-card shadow-sm">
                            <img src="<?php echo $drama['cover']; ?>" class="card-img-top" alt="<?php echo $drama['title']; ?>" onerror="this.src='https://via.placeholder.com/240x400?text=No+Image'">
                            <div class="card-body">
                                <h6 class="card-title text-truncate"><?php echo $drama['title']; ?></h6>
                                <p class="card-text small text-muted">ID: <?php echo $drama['bookId']; ?></p>

                                <div class="d-grid mt-3">
                                    <?php if (in_array($drama['bookId'], $existing_ids)): ?>
                                        <button class="btn btn-sm btn-success disabled">Generated</button>
                                    <?php else: ?>
                                        <a href="generate.php?bookId=<?php echo $drama['bookId']; ?>&title=<?php echo urlencode($drama['title']); ?>&cover=<?php echo urlencode($drama['cover']); ?>" class="btn btn-sm btn-primary">Generate Content</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
