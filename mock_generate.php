<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Mock some data
$bookId = "mock-reelshort-1";
$title = "Mock ReelShort Drama";
$cover = "https://via.placeholder.com/240x400?text=Mock+Cover";
$platform = "reelshort";

$episodesData = [
    [
        'chapterId' => 'mock-ep-1',
        'chapterIndex' => 0,
        'chapterName' => 'Mock Episode 1',
        'chapterImg' => $cover,
        'cdnList' => [
            [
                'videoPathList' => [
                    [
                        'quality' => 'Default',
                        'videoPath' => 'https://sample-videos.com/video123/mp4/720/big_buck_bunny_720p_1mb.mp4'
                    ]
                ]
            ]
        ]
    ]
];

try {
    $pdo->beginTransaction();

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
        $stmt = $pdo->prepare("UPDATE dramas SET title = ?, cover_img = ?, platform = ? WHERE id = ?");
        $stmt->execute([$title, $cover, $platform, $dramaId]);
    }

    $stmt = $pdo->prepare("INSERT INTO episodes (drama_id, chapter_id, chapter_index, chapter_name, video_url, chapter_img) VALUES (?, ?, ?, ?, ?, ?)");
    $sourceStmt = $pdo->prepare("INSERT INTO episode_sources (episode_id, quality, video_url) VALUES (?, ?, ?)");

    foreach ($episodesData as $ep) {
        $videoUrl = json_encode([['quality' => 'Default', 'videoPath' => $ep['cdnList'][0]['videoPathList'][0]['videoPath']]]);

        $pdo->prepare("DELETE FROM episodes WHERE drama_id = ? AND chapter_id = ?")->execute([$dramaId, $ep['chapterId']]);

        $stmt->execute([$dramaId, $ep['chapterId'], $ep['chapterIndex'], $ep['chapterName'], $videoUrl, $ep['chapterImg']]);
        $episodeId = $pdo->lastInsertId();

        $sourceStmt->execute([$episodeId, 'Default', $ep['cdnList'][0]['videoPathList'][0]['videoPath']]);
    }

    $pdo->commit();
    echo "Successfully generated mock ReelShort drama.\n";
} catch (Exception $e) {
    $pdo->rollBack();
    echo "Error: " . $e->getMessage() . "\n";
}
