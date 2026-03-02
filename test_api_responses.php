<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

$bookId = "6994c7def370c522ef066652";
$detailData = fetch_reelshort_detail($bookId);
echo "Detail Data:\n";
print_r($detailData);

$ep = fetch_reelshort_episode($bookId, 1);
echo "\nEpisode 1 Data:\n";
print_r($ep);
