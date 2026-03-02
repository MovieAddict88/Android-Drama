<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
check_admin_login();

// Increase time limit for sequential episode fetching
set_time_limit(0);

$bookId = $_GET['bookId'] ?? null;
$title = $_GET['title'] ?? 'Unknown';
$cover = $_GET['cover'] ?? '';
$platform = $_GET['platform'] ?? 'dramabox';

if (!$bookId) {
    die("Missing bookId");
}

if ($platform === 'reelshort') {
    // ReelShort Generation Logic
    $detail = fetch_reelshort_detail($bookId);
    if (!$detail || isset($detail['error'])) {
        $error = $detail['error'] ?? "Failed to fetch ReelShort drama details.";
    } else {
        $title = $detail['bookName'] ?? $title;
        $cover = $detail['cover'] ?? $cover;
        $description = $detail['introduction'] ?? '';
        $category = $detail['categoryName'] ?? 'ReelShort';

        try {
            $pdo->beginTransaction();
            // Insert or update drama
            $stmt = $pdo->prepare("INSERT INTO dramas (book_id, title, cover_img, description, category, platform)
                                   VALUES (?, ?, ?, ?, ?, ?)
                                   ON CONFLICT(book_id) DO UPDATE SET
                                   title=excluded.title, cover_img=excluded.cover_img, description=excluded.description, platform=excluded.platform");
            $stmt->execute([$bookId, $title, $cover, $description, $category, 'reelshort']);

            $dramaId = $pdo->lastInsertId();
            if (!$dramaId) {
                $idStmt = $pdo->prepare("SELECT id FROM dramas WHERE book_id = ?");
                $idStmt->execute([$bookId]);
                $dramaId = $idStmt->fetchColumn();
            }

            // Fetch episodes sequentially (ReelShort API requirement)
            if (isset($detail['chapters']) && is_array($detail['chapters'])) {
                // Clear old episodes
                $pdo->prepare("DELETE FROM episodes WHERE drama_id = ?")->execute([$dramaId]);

                $stmt = $pdo->prepare("INSERT INTO episodes (drama_id, chapter_index, chapter_name, video_url) VALUES (?, ?, ?, ?)");

                foreach ($detail['chapters'] as $index => $chapter) {
                    $episodeNum = $index + 1;
                    $epData = fetch_reelshort_episode($bookId, $episodeNum);

                    if ($epData && isset($epData['video_list'])) {
                        $resolutions = [];
                        foreach ($epData['video_list'] as $video) {
                            $resolutions[] = [
                                'quality' => $video['quality'],
                                'videoPath' => $video['videoPath']
                            ];
                        }
                        $videoUrl = json_encode($resolutions);
                        $epTitle = $epData['chapterName'] ?? "Episode $episodeNum";
                        $stmt->execute([$dramaId, $index, $epTitle, $videoUrl]);
                    }
                }
            }
            $pdo->commit();
            $message = "Successfully generated " . count($detail['chapters']) . " episodes for ReelShort drama: " . htmlspecialchars($title);
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Error saving ReelShort to database: " . $e->getMessage();
        }
    }
} else {
    // Original DramaBox Generation Logic (UNTOUCHED)
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

    if ($episodesData && is_array($episodesData)) {
        try {
            $pdo->beginTransaction();

            if ($title == 'Unknown' || empty($title)) {
                $title = "Drama " . $bookId;
            }
            if (empty($cover) && isset($episodesData[0]['chapterImg'])) {
                $cover = $episodesData[0]['chapterImg'];
            }

            $stmt = $pdo->prepare("SELECT id FROM dramas WHERE book_id = ?");
            $stmt->execute([$bookId]);
            $drama = $stmt->fetch();

            if (!$drama) {
                $stmt = $pdo->prepare("INSERT INTO dramas (book_id, title, cover_img) VALUES (?, ?, ?)");
                $stmt->execute([$bookId, $title, $cover]);
                $dramaId = $pdo->lastInsertId();
            } else {
                $dramaId = $drama['id'];
                $stmt = $pdo->prepare("UPDATE dramas SET title = ?, cover_img = ? WHERE id = ? AND (title LIKE 'Drama %' OR cover_img = '')");
                $stmt->execute([$title, $cover, $dramaId]);
            }

            $stmt = $pdo->prepare("INSERT INTO episodes (drama_id, chapter_id, chapter_index, chapter_name, video_url, chapter_img) VALUES (?, ?, ?, ?, ?, ?)");
            $sourceStmt = $pdo->prepare("INSERT INTO episode_sources (episode_id, quality, video_url) VALUES (?, ?, ?)");

            foreach ($episodesData as $ep) {
                $chapterId = $ep['chapterId'] ?? '';
                $chapterIndex = $ep['chapterIndex'] ?? 0;
                $chapterName = $ep['chapterName'] ?? '';
                $chapterImg = $ep['chapterImg'] ?? '';

                $resolutions = [];
                if (isset($ep['cdnList'][0]['videoPathList'])) {
                    foreach ($ep['cdnList'][0]['videoPathList'] as $video) {
                        $resolutions[] = [
                            'quality' => $video['quality'],
                            'videoPath' => $video['videoPath']
                        ];
                    }
                    usort($resolutions, function($a, $b) {
                        return $b['quality'] - $a['quality'];
                    });
                }
                $videoUrl = !empty($resolutions) ? json_encode($resolutions) : '';

                $checkStmt = $pdo->prepare("SELECT id FROM episodes WHERE drama_id = ? AND chapter_id = ?");
                $checkStmt->execute([$dramaId, $chapterId]);
                $episode = $checkStmt->fetch();

                if (!$episode) {
                    $stmt->execute([$dramaId, $chapterId, $chapterIndex, $chapterName, $videoUrl, $chapterImg]);
                    $episodeId = $pdo->lastInsertId();
                } else {
                    $episodeId = $episode['id'];
                    $updateStmt = $pdo->prepare("UPDATE episodes SET video_url = ? WHERE id = ?");
                    $updateStmt->execute([$videoUrl, $episodeId]);
                }

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
                    <a href="<?php echo $platform === 'reelshort' ? 'reelshort.php' : 'dramabox.php'; ?>" class="btn btn-outline-primary">Back</a>
                    <a href="../index.php" class="btn btn-primary" target="_blank">View Site</a>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-danger py-5">
                <h3>Error</h3>
                <p><?php echo $error; ?></p>
                <div class="mt-4">
                    <a href="<?php echo $platform === 'reelshort' ? 'reelshort.php' : 'dramabox.php'; ?>" class="btn btn-primary">Try Again</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
