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

$episodesData = [];
foreach ($episodes as $ep) {
    $sourceStmt = $pdo->prepare("SELECT quality, video_url as videoPath FROM episode_sources WHERE episode_id = ? ORDER BY CAST(quality AS UNSIGNED) DESC");
    $sourceStmt->execute([$ep['id']]);
    $sources = $sourceStmt->fetchAll();

    if (empty($sources)) {
        $videoData = json_decode($ep['video_url'], true);
        if (is_array($videoData)) {
            $sources = $videoData;
        } else {
            $sources = [['quality' => 'Default', 'videoPath' => $ep['video_url']]];
        }
    }

    $episodesData[] = [
        'id' => (int)$ep['id'],
        'index' => (int)$ep['chapter_index'],
        'name' => $ep['chapter_name'],
        'img' => $ep['chapter_img'],
        'sources' => $sources
    ];
}
$episodesJson = json_encode($episodesData);

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
        .video-container { position: relative; padding-bottom: 177.77%; height: 0; overflow: hidden; background-color: #000; border-radius: 8px; max-width: 450px; margin: 0 auto; }
        .video-container video { position: absolute; top: 0; left: 0; width: 100%; height: 100%; }

        /* Fullscreen styles */
        .video-container:fullscreen { padding-bottom: 0; height: 100vh; max-width: none; border-radius: 0; }
        .video-container:fullscreen video { height: 100%; object-fit: contain; }
        .video-container:-webkit-full-screen { padding-bottom: 0; height: 100vh; max-width: none; border-radius: 0; }
        .video-container:-webkit-full-screen video { height: 100%; object-fit: contain; }
        .drama-header { background: linear-gradient(rgba(0,0,0,0.8), rgba(18,18,18,1)), url('<?php echo htmlspecialchars($drama['cover_img']); ?>'); background-size: cover; background-position: center; padding: 60px 0; margin-bottom: 30px; }
        .quality-selector { position: absolute; top: 10px; right: 10px; z-index: 10; }
        .quality-btn { background: rgba(0,0,0,0.5); border: 1px solid rgba(255,255,255,0.2); color: white; font-size: 0.8rem; padding: 2px 8px; border-radius: 4px; backdrop-filter: blur(4px); }
        .quality-btn:hover { background: rgba(255,255,255,0.1); color: white; }

        /* Player Navigation Overlay */
        .video-nav-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            pointer-events: none;
            z-index: 5;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .video-container.user-active .video-nav-overlay {
            opacity: 1;
        }
        .nav-overlay-btn {
            background: rgba(0, 0, 0, 0.5);
            color: white;
            border: none;
            padding: 20px 10px;
            cursor: pointer;
            pointer-events: auto;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            transition: background 0.2s;
        }
        .nav-overlay-btn:hover {
            background: rgba(0, 0, 0, 0.8);
            color: white;
        }
        .nav-overlay-prev {
            border-radius: 0 8px 8px 0;
        }
        .nav-overlay-next {
            border-radius: 8px 0 0 8px;
        }
        .fullscreen-btn {
            position: absolute;
            bottom: 60px;
            right: 10px;
            z-index: 10;
            background: rgba(0,0,0,0.5);
            border: 1px solid rgba(255,255,255,0.2);
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            backdrop-filter: blur(4px);
            cursor: pointer;
            pointer-events: auto;
            opacity: 0;
            transition: opacity 0.3s;
        }
        .video-container.user-active .fullscreen-btn {
            opacity: 1;
        }
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
                <div class="col-md-3 col-4 mb-3 mb-md-0">
                    <img src="<?php echo htmlspecialchars($drama['cover_img']); ?>" class="img-fluid rounded shadow" alt="<?php echo htmlspecialchars($drama['title']); ?>" onerror="this.src='https://via.placeholder.com/240x400?text=No+Image'">
                </div>
                <div class="col-md-9 col-8 d-flex flex-column justify-content-center">
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
                    <div class="video-container mb-3 shadow position-relative">
                        <div class="quality-selector dropdown" id="quality-selector-container" style="display: none;">
                            <button class="quality-btn dropdown-toggle" type="button" id="qualityDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-gear-fill me-1"></i> <span id="current-quality">Default</span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end" id="quality-list" aria-labelledby="qualityDropdown">
                                <!-- Qualities will be loaded by JS -->
                            </ul>
                        </div>
                        <video id="main-video" controls poster="" playsinline>
                            <source src="" type="video/mp4">
                            Your browser does not support the video tag.
                        </video>
                        <div class="video-nav-overlay">
                            <a href="#" id="overlay-prev" class="nav-overlay-btn nav-overlay-prev" onclick="loadEpisodeByIndex(currentIndex - 1); return false;">
                                <i class="bi bi-chevron-left"></i>
                            </a>
                            <div id="overlay-prev-placeholder"></div>

                            <a href="#" id="overlay-next" class="nav-overlay-btn nav-overlay-next" onclick="loadEpisodeByIndex(currentIndex + 1); return false;">
                                <i class="bi bi-chevron-right"></i>
                            </a>
                            <div id="overlay-next-placeholder"></div>
                        </div>
                        <button class="fullscreen-btn" onclick="toggleFullscreen()"><i class="bi bi-arrows-fullscreen"></i></button>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4 class="mb-0" id="current-ep-title">Loading...</h4>
                        <div>
                            <button id="btn-prev" onclick="loadEpisodeByIndex(currentIndex - 1)" class="btn btn-outline-light btn-sm"><i class="bi bi-chevron-left"></i> Previous</button>
                            <button id="btn-next" onclick="loadEpisodeByIndex(currentIndex + 1)" class="btn btn-outline-light btn-sm">Next <i class="bi bi-chevron-right"></i></button>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning">No video found for this drama.</div>
                <?php endif; ?>
            </div>
            <div class="col-lg-4">
                <h5 class="mb-3">Episode List</h5>
                <div class="ep-list shadow-sm" id="episode-list-container">
                    <?php foreach ($episodes as $ep): ?>
                        <a href="watch.php?id=<?php echo (int)$id; ?>&ep=<?php echo (int)$ep['chapter_index']; ?>"
                           id="ep-link-<?php echo (int)$ep['chapter_index']; ?>"
                           class="ep-item <?php echo ($currentEpisode && $currentEpisode['id'] == $ep['id']) ? 'active' : ''; ?>"
                           onclick="loadEpisodeByIndex(<?php echo (int)$ep['chapter_index']; ?>); return false;">
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
    <script>
        const episodes = <?php echo $episodesJson; ?>;
        const dramaId = <?php echo (int)$id; ?>;
        let currentIndex = <?php echo (int)$currentEpisode['chapter_index']; ?>;
        const video = document.getElementById('main-video');
        const videoContainer = document.querySelector('.video-container');
        let activityTimeout;

        function loadEpisodeByIndex(index) {
            const ep = episodes.find(e => e.index === index);
            if (!ep) return;

            currentIndex = index;

            // Update UI
            document.getElementById('current-ep-title').innerText = ep.name;
            video.poster = ep.img;

            // Update Active in List
            document.querySelectorAll('.ep-item').forEach(el => el.classList.remove('active'));
            const activeLink = document.getElementById('ep-link-' + index);
            if (activeLink) {
                activeLink.classList.add('active');
                activeLink.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }

            // Load Sources
            const qualityList = document.getElementById('quality-list');
            const qualityContainer = document.getElementById('quality-selector-container');
            qualityList.innerHTML = '';

            if (ep.sources && ep.sources.length > 0) {
                if (ep.sources.length > 1) {
                    qualityContainer.style.display = 'block';
                    ep.sources.forEach(source => {
                        const li = document.createElement('li');
                        const qualityDisplay = isNaN(source.quality) ? source.quality : source.quality + 'p';
                        li.innerHTML = `<a class="dropdown-item small" href="#" onclick="changeQuality('${source.videoPath}', '${source.quality}'); return false;">${qualityDisplay}</a>`;
                        qualityList.appendChild(li);
                    });
                } else {
                    qualityContainer.style.display = 'none';
                }

                // Set default source
                const defaultSource = ep.sources[0];
                video.src = defaultSource.videoPath;
                document.getElementById('current-quality').innerText = isNaN(defaultSource.quality) ? defaultSource.quality : defaultSource.quality + 'p';
            }

            video.load();
            video.play().catch(e => console.log("Auto-play prevented"));

            // Update Navigation Buttons
            updateNavButtons();

            // Update URL
            const newUrl = window.location.protocol + "//" + window.location.host + window.location.pathname + `?id=${dramaId}&ep=${index}`;
            window.history.pushState({path:newUrl},'',newUrl);
        }

        function updateNavButtons() {
            const hasPrev = episodes.some(e => e.index === currentIndex - 1);
            const hasNext = episodes.some(e => e.index === currentIndex + 1);

            // Bottom buttons
            document.getElementById('btn-prev').style.display = hasPrev ? 'inline-block' : 'none';
            document.getElementById('btn-next').style.display = hasNext ? 'inline-block' : 'none';

            // Overlay buttons
            document.getElementById('overlay-prev').style.display = hasPrev ? 'flex' : 'none';
            document.getElementById('overlay-prev-placeholder').style.display = hasPrev ? 'none' : 'block';
            document.getElementById('overlay-next').style.display = hasNext ? 'flex' : 'none';
            document.getElementById('overlay-next-placeholder').style.display = hasNext ? 'none' : 'block';
        }

        function changeQuality(url, quality) {
            const currentTime = video.currentTime;
            const isPaused = video.paused;

            video.src = url;
            video.load();

            video.onloadedmetadata = function() {
                video.currentTime = currentTime;
                if (!isPaused) {
                    video.play();
                }
                video.onloadedmetadata = null;
            };

            document.getElementById('current-quality').innerText = isNaN(quality) ? quality : quality + 'p';
        }

        function toggleFullscreen() {
            if (!document.fullscreenElement) {
                if (videoContainer.requestFullscreen) {
                    videoContainer.requestFullscreen();
                } else if (videoContainer.webkitRequestFullscreen) {
                    videoContainer.webkitRequestFullscreen();
                } else if (videoContainer.msRequestFullscreen) {
                    videoContainer.msRequestFullscreen();
                }
            } else {
                if (document.exitFullscreen) {
                    document.exitFullscreen();
                }
            }
        }

        // Auto-next functionality
        video.addEventListener('ended', function() {
            const nextIndex = currentIndex + 1;
            if (episodes.some(e => e.index === nextIndex)) {
                loadEpisodeByIndex(nextIndex);
            }
        });

        // Activity detection to show/hide navigation overlay
        function showControls() {
            videoContainer.classList.add('user-active');
            clearTimeout(activityTimeout);
            activityTimeout = setTimeout(() => {
                videoContainer.classList.remove('user-active');
            }, 3000);
        }

        videoContainer.addEventListener('mousemove', showControls);
        videoContainer.addEventListener('touchstart', showControls);
        videoContainer.addEventListener('click', showControls);

        // Initial load
        loadEpisodeByIndex(currentIndex);
    </script>
</body>
</html>
