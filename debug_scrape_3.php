<?php
function fetch_url($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
        'Accept-Language: en-US,en;q=0.9',
        'Cache-Control: max-age=0',
        'Connection: keep-alive',
        'Upgrade-Insecure-Requests: 1'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}

$html = fetch_url("https://www.reelshort.com/");
$startToken = '<script id="__NEXT_DATA__" type="application/json">';
$endToken = '</script>';
$startPos = strpos($html, $startToken);
if ($startPos !== false) {
    $startPos += strlen($startToken);
    $endPos = strpos($html, $endToken, $startPos);
    if ($endPos !== false) {
        $jsonStr = substr($html, $startPos, $endPos - $startPos);
        $jsonData = json_decode($jsonStr, true);
        if ($jsonData) {
            $hallInfo = $jsonData['props']['pageProps']['fallback']['/api/video/hall/info'] ?? [];
            $shelves = $hallInfo['shelves'] ?? [];
            foreach ($shelves as $shelf) {
                echo "Shelf: " . ($shelf['shelfName'] ?? 'Unknown') . "\n";
                if (isset($shelf['items'])) {
                    foreach ($shelf['items'] as $item) {
                        echo "  - " . ($item['bookName'] ?? 'Unknown') . " (" . ($item['bookId'] ?? 'N/A') . ")\n";
                    }
                }
            }
        }
    }
}
