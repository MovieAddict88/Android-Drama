<?php

$endpoints = [
    '/api/dramabox/foryou',
    '/api/reelshort/foryou',
    '/api/shortmax/foryou',
    '/api/anime/latest',
    '/api/komik/recommended?type=manhwa',
    '/api/moviebox/homepage',
    '/api/ai/chatgpt?prompt=hello'
];

foreach ($endpoints as $ep) {
    echo "Requesting $ep... ";
    $cmd = "REQUEST_METHOD=GET REQUEST_URI='$ep' php index.php 2>/dev/null";
    $res = shell_exec($cmd);
    $json = json_decode($res, true);
    if ($json !== null) {
        echo "VALID JSON (" . count($json) . " items)\n";
    } else {
        echo "FAILED: " . substr($res, 0, 50) . "\n";
    }
}
