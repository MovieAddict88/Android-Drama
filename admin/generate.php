<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
check_admin_login();

$bookId = $_GET['bookId'] ?? null;
$title = $_GET['title'] ?? 'Unknown';
$cover = $_GET['cover'] ?? '';
$platform = $_GET['platform'] ?? 'dramabox';

if (!$bookId) {
    die("Missing bookId");
}

// Try to fetch better metadata from Sansekai detail API if title is unknown
if ($title == 'Unknown' || empty($title) || empty($cover)) {
    if ($platform === 'reelshort') {
        $detailData = fetch_reelshort_detail($bookId);
        if ($detailData) {
            if (isset($detailData['bookName'])) {
                $title = $detailData['bookName'];
            }
            if (isset($detailData['cover']) && empty($cover)) {
                $cover = $detailData['cover'];
            }
        }
    } else {
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
}

if ($platform === 'reelshort') {
    $episodesData = fetch_reelshort_episodes($bookId);
} else {
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
            $stmt = $pdo->prepare("INSERT INTO dramas (book_id, title, cover_img, platform) VALUES (?, ?, ?, ?)");
            $stmt->execute([$bookId, $title, $cover, $platform]);
            $dramaId = $pdo->lastInsertId();
        } else {
            $dramaId = $drama['id'];
            // Update title/cover/platform if they were previously unknown/empty
            $stmt = $pdo->prepare("UPDATE dramas SET title = ?, cover_img = ?, platform = ? WHERE id = ? AND (title LIKE 'Drama %' OR cover_img = '')");
            $stmt->execute([$title, $cover, $platform, $dramaId]);
        }

        // Insert Episodes
        $stmt = $pdo->prepare("INSERT INTO episodes (drama_id, chapter_id, chapter_index, chapter_name, video_url, chapter_img) VALUES (?, ?, ?, ?, ?, ?)");
        $sourceStmt = $pdo->prepare("INSERT INTO episode_sources (episode_id, quality, video_url) VALUES (?, ?, ?)");

        foreach ($episodesData as $ep) {
            if ($platform === 'reelshort') {
                $chapterId = $ep['chapterId'] ?? '';
                $chapterIndex = $ep['chapterIndex'] ?? 0;
                $chapterName = $ep['chapterName'] ?? '';
                $chapterImg = $ep['chapterImg'] ?? '';

                $resolutions = [];
                if (isset($ep['videoList']) && is_array($ep['videoList'])) {
                    foreach ($ep['videoList'] as $video) {
                        $resolutions[] = [
                            'quality' => $video['quality'] ?: 'Default',
                            'videoPath' => $video['url']
                        ];
                    }
                }
            } else {
                $chapterId = $ep['chapterId'] ?? '';
                $chapterIndex = $ep['chapterIndex'] ?? 0;
                $chapterName = $ep['chapterName'] ?? '';
                $chapterImg = $ep['chapterImg'] ?? '';

                // Find video resolutions
                $resolutions = [];
                if (isset($ep['cdnList'][0]['videoPathList'])) {
                    foreach ($ep['cdnList'][0]['videoPathList'] as $video) {
                        $resolutions[] = [
                            'quality' => $video['quality'],
                            'videoPath' => $video['videoPath']
                        ];
                    }
                    // Sort by quality descending
                    usort($resolutions, function($a, $b) {
                        return $b['quality'] - $a['quality'];
                    });
                }
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
                // Update existing video_url JSON just in case it was a legacy record
                $updateStmt = $pdo->prepare("UPDATE episodes SET video_url = ? WHERE id = ?");
                $updateStmt->execute([$videoUrl, $episodeId]);
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
        $pdo->rollBack();
        $error = "Error saving to database: " . $e->getMessage();
    }
} else {
    $error = "Failed to fetch episodes from Sansekai API.";
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
                    <a href="<?php echo ($platform === 'reelshort' ? 'reelshort.php' : 'dramabox.php'); ?>" class="btn btn-outline-primary">Back to <?php echo ($platform === 'reelshort' ? 'ReelShort' : 'DramaBox'); ?></a>
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
