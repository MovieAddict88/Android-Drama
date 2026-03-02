<?php
require_once 'includes/functions.php';

$html = fetch_url("https://www.reelshort.com/");
$start_marker = '<script id="__NEXT_DATA__" type="application/json">';
$end_marker = '</script>';
$start_pos = strpos($html, $start_marker);
if ($start_pos !== false) {
    $start_pos += strlen($start_marker);
    $end_pos = strpos($html, $end_marker, $start_pos);
    $json_str = substr($html, $start_pos, $end_pos - $start_pos);
    file_put_contents('debug_reelshort.json', $json_str);
    echo "Saved debug_reelshort.json\n";
} else {
    echo "Could not find __NEXT_DATA__\n";
    echo "HTML snippet: " . substr($html, 0, 500) . "\n";
}
