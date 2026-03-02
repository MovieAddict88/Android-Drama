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

$description = '';
$episodesData = [];

if ($platform === 'reelshort') {
    $detailData = fetch_reelshort_detail($bookId);
    if ($detailData && isset($detailData['success']) && $detailData['success']) {
        $title = !empty($detailData['title']) ? $detailData['title'] : $title;
        $cover = !empty($detailData['cover']) ? $detailData['cover'] : $cover;
        $description = $detailData['description'] ?? '';

        if (isset($detailData['chapters']) && is_array($detailData['chapters'])) {
            foreach ($detailData['chapters'] as $chapter) {
                // For ReelShort we need to fetch each episode's video URL
                $epInfo = fetch_reelshort_episode($bookId, $chapter['index']);
                if ($epInfo && isset($epInfo['success']) && $epInfo['success']) {
                    $resolutions = [];
                    if (isset($epInfo['videoList'])) {
                        foreach ($epInfo['videoList'] as $video) {
                            $resolutions[] = [
                                'quality' => $video['quality'],
                                'videoPath' => $video['url']
                            ];
                        }
                    }

                    $episodesData[] = [
                        'chapterId' => $chapter['chapterId'],
                        'chapterIndex' => $chapter['index'] - 1, // Store as 0-indexed
                        'chapterName' => $chapter['title'],
                        'chapterImg' => $cover, // ReelShort detail doesn't seem to have per-episode images in the list
                        'resolutions' => $resolutions
                    ];
                }
            }
        }
    }
} else {
    // DramaBox logic
    $detailJson = fetch_url("https://api.sansekai.my.id/api/dramabox/detail?bookId=" . $bookId);
    if ($detailJson) {
        $detailData = json_decode($detailJson, true);
        if (isset($detailData['bookName']) && !empty($detailData['bookName'])) {
            $title = $detailData['bookName'];
        }
        if (isset($detailData['coverWap']) && !empty($detailData['coverWap'])) {
            $cover = $detailData['coverWap'];
        }
        $description = $detailData['introduction'] ?? '';
    }

    $rawEpisodes = fetch_episodes_from_api($bookId);
    if ($rawEpisodes && is_array($rawEpisodes)) {
        foreach ($rawEpisodes as $ep) {
            $resolutions = [];
            if (isset($ep['cdnList'][0]['videoPathList'])) {
                foreach ($ep['cdnList'][0]['videoPathList'] as $video) {
                    $resolutions[] = [
                        'quality' => $video['quality'],
                        'videoPath' => $video['videoPath']
                    ];
                }
            }
            $episodesData[] = [
                'chapterId' => $ep['chapterId'] ?? '',
                'chapterIndex' => $ep['chapterIndex'] ?? 0,
                'chapterName' => $ep['chapterName'] ?? '',
                'chapterImg' => $ep['chapterImg'] ?? '',
                'resolutions' => $resolutions
            ];
        }
    }
}

if (!empty($episodesData)) {
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
            $stmt = $pdo->prepare("INSERT INTO dramas (book_id, title, cover_img, description, platform) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$bookId, $title, $cover, $description, $platform]);
            $dramaId = $pdo->lastInsertId();
        } else {
            $dramaId = $drama['id'];
            // Update metadata
            $stmt = $pdo->prepare("UPDATE dramas SET title = ?, cover_img = ?, description = ?, platform = ? WHERE id = ?");
            $stmt->execute([$title, $cover, $description, $platform, $dramaId]);
        }

        // Insert Episodes
        $stmt = $pdo->prepare("INSERT INTO episodes (drama_id, chapter_id, chapter_index, chapter_name, video_url, chapter_img) VALUES (?, ?, ?, ?, ?, ?)");
        $sourceStmt = $pdo->prepare("INSERT INTO episode_sources (episode_id, quality, video_url) VALUES (?, ?, ?)");

        foreach ($episodesData as $ep) {
            $chapterId = $ep['chapterId'];
            $chapterIndex = $ep['chapterIndex'];
            $chapterName = $ep['chapterName'];
            $chapterImg = $ep['chapterImg'];
            $resolutions = $ep['resolutions'];

            // Sort by quality descending
            usort($resolutions, function($a, $b) {
                return (int)$b['quality'] - (int)$a['quality'];
            });
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
                $updateStmt = $pdo->prepare("UPDATE episodes SET video_url = ?, chapter_index = ?, chapter_name = ?, chapter_img = ? WHERE id = ?");
                $updateStmt->execute([$videoUrl, $chapterIndex, $chapterName, $chapterImg, $episodeId]);
            }

            // Populate episode_sources table
            if (!empty($resolutions)) {
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
                    <a href="dramabox.php" class="btn btn-outline-primary">Back to DramaBox</a>
                    <a href="../index.php" class="btn btn-primary" target="_blank">View Site</a>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-danger py-5">
                <h3>Error</h3>
                <p><?php echo $error; ?></p>
                <div class="mt-4">
                    <a href="dramabox.php" class="btn btn-primary">Try Again</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
