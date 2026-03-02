<?php
require_once 'includes/functions.php';

echo "Testing ReelShort Search...\n";
$results = search_reelshort("billionaire", 1);
if (isset($results['error'])) {
    echo "Search Error: " . $results['error'] . "\n";
} else {
    echo "Found " . count($results) . " results.\n";
    foreach ($results as $item) {
        echo "- " . $item['title'] . " (ID: " . $item['bookId'] . ")\n";
    }
}

echo "\nTesting ReelShort Scrape...\n";
$categories = scrape_reelshort();
if (isset($categories['error'])) {
    echo "Scrape Error: " . $categories['error'] . "\n";
} else {
    echo "Found " . count($categories) . " categories.\n";
    foreach ($categories as $cat) {
        echo "- " . $cat['name'] . " (" . count($cat['items']) . " items)\n";
    }
}
