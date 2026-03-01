<?php
require_once 'includes/functions.php';
$dramas = scrape_dramabox();
if (isset($dramas['error'])) {
    echo "Error: " . $dramas['error'] . "\n";
} else {
    foreach ($dramas as $d) {
        echo "ID: {$d['bookId']} | Title: {$d['title']} | Cover: {$d['cover']}\n";
    }
}
