<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

echo "Testing ReelShort Generation logic...\n";

$bookId = "6994c7def370c522ef066652"; // The Billionaire and the Baby Trap
$platform = "reelshort";

$detailData = fetch_reelshort_detail($bookId);
if (!$detailData) {
    echo "Failed to fetch detail for $bookId. (Likely API blacklist)\n";
} else {
    echo "Found drama: " . $detailData['bookName'] . "\n";
    $ep = fetch_reelshort_episode($bookId, 1);
    if ($ep) {
        echo "Successfully fetched Episode 1. Video Path: " . $ep['videoPath'] . "\n";
    } else {
        echo "Failed to fetch Episode 1.\n";
    }
}
