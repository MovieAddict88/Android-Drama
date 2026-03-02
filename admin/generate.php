<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
check_admin_login();

$bookId = $_GET['bookId'] ?? null;
$title = $_GET['title'] ?? 'Unknown';
$cover = $_GET['cover'] ?? '';
$platform = $_GET['platform'] ?? 'dramabox';
$category = $_GET['category'] ?? 'Trending';

if (!$bookId) {
    die("Missing bookId");
}

$episodesData = [];

if ($platform === 'reelshort') {
    // Try to fetch better metadata for ReelShort if title is unknown
    if ($title == 'Unknown' || empty($title) || empty($cover)) {
        $detailData = fetch_reelshort_detail($bookId);
        if ($detailData) {
            $title = $detailData['bookName'] ?? $title;
            $cover = $detailData['coverWap'] ?? $detailData['cover'] ?? $cover;
        }
    }

    // ReelShort requires sequential episode fetching
    $episodeNumber = 1;
    $maxFailures = 2; // Allow some missing indices just in case, though ReelShort is usually strictly sequential
    $failures = 0;

    while ($failures < $maxFailures) {
        $ep = fetch_reelshort_episode($bookId, $episodeNumber);
        if ($ep && !isset($ep['error']) && !empty($ep['cdnList'])) {
            $episodesData[] = $ep;
            $failures = 0; // Reset failures on success
        } else {
            $failures++;
            if ($episodeNumber > 1 && (!isset($ep['error']) || $ep['error'] !== 'Not Found')) {
                 // Stop if we hit a hard error after having found some episodes
                 if (isset($ep['error'])) break;
            }
        }
        $episodeNumber++;
        if ($episodeNumber > 200) break; // Safety limit
    }
} else {
    // DramaBox logic
    if ($title == 'Unknown' || empty($title) || empty($cover)) {
        $detailJson = fetch_url("https://api.sansekai.my.id/api/dramabox/detail?bookId=" . $bookId);
        if ($detailJson) {
            $detailData = json_decode($detailJson, true);
            if (isset($detailData['bookName'])) {
                $title = $detailData['bookName'];
            }
            if (isset($detailData['coverWap']) && empty($cover)) {
                $cover = $detailData['coverWap'];
            }
        }
    }
    $episodesData = fetch_episodes_from_api($bookId);
}

if ($episodesData && is_array($episodesData)) {
    try {
        $pdo->beginTransaction();

        // Fallback for title and cover
        if ($title == 'Unknown' || empty($title)) {
            $title = "Drama " . $bookId;
        }
        if (empty($cover) && isset($episodesData[0]['chapterImg'])) {
            $cover = $episodesData[0]['chapterImg'];
        }

        // Check if drama already exists
        $stmt = $pdo->prepare("SELECT id FROM dramas WHERE book_id = ?");
        $stmt->execute([$bookId]);
        $drama = $stmt->fetch();

        if (!$drama) {
            $stmt = $pdo->prepare("INSERT INTO dramas (book_id, title, cover_img, platform, category) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$bookId, $title, $cover, $platform, $category]);
            $dramaId = $pdo->lastInsertId();
        } else {
            $dramaId = $drama['id'];
            // Update title/cover/platform/category if they were previously unknown/empty or we want to refresh
            $stmt = $pdo->prepare("UPDATE dramas SET title = ?, cover_img = ?, platform = ?, category = ? WHERE id = ?");
            $stmt->execute([$title, $cover, $platform, $category, $dramaId]);
        }

        // Insert Episodes
        $stmt = $pdo->prepare("INSERT INTO episodes (drama_id, chapter_id, chapter_index, chapter_name, video_url, chapter_img) VALUES (?, ?, ?, ?, ?, ?)");
        $sourceStmt = $pdo->prepare("INSERT INTO episode_sources (episode_id, quality, video_url) VALUES (?, ?, ?)");

        foreach ($episodesData as $ep) {
            $chapterId = $ep['chapterId'] ?? ($platform === 'reelshort' ? $bookId . '-' . ($ep['chapterIndex'] ?? 0) : '');
            $chapterIndex = $ep['chapterIndex'] ?? 0;
            $chapterName = $ep['chapterName'] ?? "Episode $chapterIndex";
            $chapterImg = $ep['chapterImg'] ?? '';

            // Find video resolutions
            $resolutions = [];
            $cdnList = $ep['cdnList'] ?? [];
            if (isset($cdnList[0]['videoPathList'])) {
                foreach ($cdnList[0]['videoPathList'] as $video) {
                    $resolutions[] = [
                        'quality' => $video['quality'],
                        'videoPath' => $video['videoPath']
                    ];
                }
                // Sort by quality descending
                usort($resolutions, function($a, $b) {
                    return (int)$b['quality'] - (int)$a['quality'];
                });
            }
            $videoUrl = !empty($resolutions) ? json_encode($resolutions) : '';

            // Check if episode already exists
            $checkStmt = $pdo->prepare("SELECT id FROM episodes WHERE drama_id = ? AND chapter_id = ?");
            $checkStmt->execute([$dramaId, $chapterId]);
            $episode = $checkStmt->fetch();

            if (!$episode) {
                $stmt->execute([$dramaId, $chapterId, $chapterIndex, $chapterName, $videoUrl, $chapterImg]);
                $episodeId = $pdo->lastInsertId();
            } else {
                $episodeId = $episode['id'];
                // Update existing record
                $updateStmt = $pdo->prepare("UPDATE episodes SET video_url = ?, chapter_name = ?, chapter_img = ? WHERE id = ?");
                $updateStmt->execute([$videoUrl, $chapterName, $chapterImg, $episodeId]);
            }

            // Populate episode_sources table
            if (!empty($resolutions)) {
                // Clear old sources to avoid duplicates on regenerate
                $pdo->prepare("DELETE FROM episode_sources WHERE episode_id = ?")->execute([$episodeId]);
                foreach ($resolutions as $res) {
                    $sourceStmt->execute([$episodeId, $res['quality'], $res['videoPath']]);
                }
            }
        }

        $pdo->commit();
        $message = "Successfully generated " . count($episodesData) . " episodes for drama: " . htmlspecialchars($title);
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $error = "Error saving to database: " . $e->getMessage();
    }
} else {
    $error = "Failed to fetch episodes from Sansekai API. Platform: $platform, Book ID: $bookId";
    if (isset($ep['error'])) $error .= " API Error: " . $ep['message'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generating Content - Drama Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5 text-center">
        <?php if (isset($message)): ?>
            <div class="alert alert-success py-5">
                <i class="bi bi-check-circle h1"></i>
                <h3>Done!</h3>
                <p><?php echo $message; ?></p>
                <div class="mt-4">
                    <a href="<?php echo ($platform === 'reelshort' ? 'reelshort.php' : 'dramabox.php'); ?>" class="btn btn-outline-primary">Back to <?php echo ucfirst($platform); ?></a>
                    <a href="../index.php" class="btn btn-primary" target="_blank">View Site</a>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-danger py-5">
                <h3>Error</h3>
                <p><?php echo $error; ?></p>
                <div class="mt-4">
                    <a href="<?php echo ($platform === 'reelshort' ? 'reelshort.php' : 'dramabox.php'); ?>" class="btn btn-primary">Try Again</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
