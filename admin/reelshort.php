<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
check_admin_login();

$search_query = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$search_results = [];
$categories = [];

if ($search_query) {
    $search_results = search_reelshort($search_query, $page);
} else {
    $categories = scrape_reelshort();
}

// Check which ones are already generated
$stmt = $pdo->query("SELECT book_id FROM dramas WHERE platform = 'reelshort'");
$existing_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ReelShort Content - Admin</title>
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
            cursor: pointer;
            border: none;
            background: transparent;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        .drama-card:hover {
            transform: scale(1.06);
            z-index: 5;
        }
        .card-img-container {
            position: relative;
            border-radius: 12px;
            overflow: hidden;
            aspect-ratio: 2/3;
            box-shadow: 0 10px 20px rgba(0,0,0,0.4);
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
            background: rgba(0,0,0,0.6);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            opacity: 0;
            transition: opacity 0.3s;
            backdrop-filter: blur(2px);
        }
        .drama-card:hover .card-overlay {
            opacity: 1;
        }
        .card-title {
            margin-top: 12px;
            font-size: 0.95rem;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            color: #fff;
            padding: 0 2px;
        }
        .generate-btn {
            background-color: var(--primary-color);
            color: #fff;
            border: none;
            padding: 7px 18px;
            border-radius: 25px;
            font-size: 0.8rem;
            font-weight: 700;
            text-decoration: none;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 10px rgba(255, 0, 85, 0.3);
        }
        .generate-btn:hover {
            background-color: #e6004d;
            color: #fff;
            transform: translateY(-2px);
        }
        .btn-generated {
            background-color: #2ecc71;
            box-shadow: 0 4px 10px rgba(46, 204, 113, 0.3);
            cursor: default;
        }
        .btn-generated:hover {
            transform: none;
        }
        .manual-form-container {
            padding: 20px;
            background: var(--card-bg);
            border-radius: 15px;
            margin-bottom: 30px;
        }
        .alert-custom {
            background-color: rgba(255, 0, 85, 0.1);
            border: 1px solid var(--primary-color);
            color: #fff;
            border-radius: 12px;
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
                    <li class="nav-item"><a class="nav-link" href="dramabox.php">DramaBox</a></li>
                    <li class="nav-item"><a class="nav-link active" href="reelshort.php">ReelShort</a></li>
                    <li class="nav-item"><a class="nav-link" href="manage.php">Manage</a></li>
                </ul>
                <div class="d-flex align-items-center flex-wrap gap-2 py-2 py-lg-0">
                    <form class="d-flex" action="reelshort.php" method="GET">
                        <input type="text" name="search" class="form-control form-control-sm bg-dark border-secondary text-white rounded-pill px-3" placeholder="Search ReelShort..." value="<?php echo htmlspecialchars($search_query); ?>" style="max-width: 150px;">
                        <button type="submit" class="btn btn-sm btn-outline-light ms-2 rounded-pill">Search</button>
                    </form>
                    <form class="d-flex me-3" action="generate.php" method="GET">
                        <input type="hidden" name="platform" value="reelshort">
                        <input type="text" name="bookId" class="form-control form-control-sm bg-dark border-secondary text-white rounded-pill px-3" placeholder="Book ID..." required style="max-width: 100px;">
                        <button type="submit" class="btn btn-sm btn-primary ms-2 rounded-pill" style="background-color: var(--primary-color); border: none;">Add</button>
                    </form>
                    <a href="logout.php" class="text-white text-decoration-none small opacity-75 hover-opacity-100"><i class="bi bi-box-arrow-right fs-5"></i></a>
                </div>
            </div>
        </div>
    </nav>

    <?php if ($search_query): ?>
        <main class="py-4">
            <div class="section-container mb-5">
                <div class="section-header px-lg-5">
                    <h2 class="section-title">Search Results for "<?php echo htmlspecialchars($search_query); ?>"</h2>
                    <span class="text-muted small"><?php echo (isset($search_results) && is_array($search_results) && !isset($search_results['error'])) ? count($search_results) : 0; ?> Results</span>
                </div>
                <div class="container-fluid px-lg-5">
                    <?php if (isset($search_results['error'])): ?>
                        <div class="alert alert-custom p-4"><?php echo $search_results['error']; ?></div>
                    <?php elseif (empty($search_results)): ?>
                        <div class="alert alert-custom p-4">No results found for "<?php echo htmlspecialchars($search_query); ?>"</div>
                    <?php else: ?>
                        <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-lg-5 row-cols-xl-6 g-3 g-lg-4">
                            <?php foreach ($search_results as $item): ?>
                                <div class="col">
                                    <div class="drama-card">
                                        <div class="card-img-container shadow">
                                            <img src="<?php echo $item['cover']; ?>" alt="<?php echo $item['title']; ?>" loading="lazy" onerror="this.src='https://via.placeholder.com/240x400?text=No+Image'">
                                            <div class="card-overlay">
                                                <?php if (in_array($item['bookId'], $existing_ids)): ?>
                                                    <span class="generate-btn btn-generated">DONE</span>
                                                <?php else: ?>
                                                    <a href="generate.php?platform=reelshort&bookId=<?php echo $item['bookId']; ?>&title=<?php echo urlencode($item['title']); ?>&cover=<?php echo urlencode($item['cover']); ?>" class="generate-btn">GENERATE</a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="card-title mt-2"><?php echo $item['title']; ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Pagination -->
                        <div class="d-flex justify-content-center mt-5">
                            <nav aria-label="Search results pagination">
                                <ul class="pagination pagination-lg">
                                    <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                                        <a class="page-link bg-dark border-secondary text-white" href="?search=<?php echo urlencode($search_query); ?>&page=<?php echo $page - 1; ?>" aria-label="Previous">
                                            <span aria-hidden="true">&laquo; Previous</span>
                                        </a>
                                    </li>
                                    <li class="page-item active">
                                        <span class="page-link bg-primary border-primary"><?php echo $page; ?></span>
                                    </li>
                                    <li class="page-item">
                                        <a class="page-link bg-dark border-secondary text-white" href="?search=<?php echo urlencode($search_query); ?>&page=<?php echo $page + 1; ?>" aria-label="Next">
                                            <span aria-hidden="true">Next &raquo;</span>
                                        </a>
                                    </li>
                                </ul>
                            </nav>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    <?php elseif (isset($categories['error'])): ?>
        <div class="container mt-5">
            <div class="alert alert-custom p-4 shadow">
                <div class="d-flex align-items-center">
                    <i class="bi bi-exclamation-triangle-fill me-3 fs-2" style="color: var(--primary-color);"></i>
                    <div>
                        <h5 class="mb-1">Scraping limit reached or structure changed</h5>
                        <p class="mb-0 opacity-75"><?php echo htmlspecialchars($categories['error']); ?></p>
                    </div>
                </div>
            </div>

            <div class="manual-form-container mt-4">
                <h4>Manual Content Generation</h4>
                <p class="text-muted">Enter the ReelShort Book ID to fetch data directly via API.</p>
                <form action="generate.php" method="GET" class="row g-3">
                    <input type="hidden" name="platform" value="reelshort">
                    <div class="col-md-6">
                        <input type="text" name="bookId" class="form-control bg-dark border-secondary text-white" placeholder="Example: 6994c7def370c522ef066652" required>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary px-4" style="background-color: var(--primary-color); border: none;">Generate Content</button>
                    </div>
                </form>
            </div>
        </div>
    <?php else: ?>
        <main class="py-4">
            <?php foreach ($categories as $category): ?>
                <div class="section-container mb-5">
                    <div class="section-header px-lg-5">
                        <h2 class="section-title"><?php echo $category['name']; ?></h2>
                        <span class="text-muted small"><?php echo count($category['items']); ?> Items</span>
                    </div>
                    <div class="container-fluid px-lg-5">
                        <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-lg-5 row-cols-xl-6 g-3 g-lg-4">
                            <?php foreach ($category['items'] as $item): ?>
                                <div class="col">
                                    <div class="drama-card">
                                        <div class="card-img-container shadow">
                                            <img src="<?php echo $item['cover']; ?>" alt="<?php echo $item['title']; ?>" loading="lazy" onerror="this.src='https://via.placeholder.com/240x400?text=No+Image'">
                                            <div class="card-overlay">
                                                <?php if (in_array($item['bookId'], $existing_ids)): ?>
                                                    <span class="generate-btn btn-generated">DONE</span>
                                                <?php else: ?>
                                                    <a href="generate.php?platform=reelshort&bookId=<?php echo $item['bookId']; ?>&title=<?php echo urlencode($item['title']); ?>&cover=<?php echo urlencode($item['cover']); ?>" class="generate-btn">GENERATE</a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="card-title mt-2"><?php echo $item['title']; ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <div class="container-fluid px-lg-5 mb-5 mt-4">
                <div class="manual-form-container">
                    <h4>Manual Content Generation</h4>
                    <p class="text-muted">Enter the ReelShort Book ID to fetch data directly via API.</p>
                    <form action="generate.php" method="GET" class="row g-3">
                        <input type="hidden" name="platform" value="reelshort">
                        <div class="col-md-6">
                            <input type="text" name="bookId" class="form-control bg-dark border-secondary text-white" placeholder="Example: 6994c7def370c522ef066652" required>
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-primary px-4" style="background-color: var(--primary-color); border: none;">Generate Content</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
