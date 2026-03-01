<?php

/**
 * Fetch HTML content from a URL using cURL
 */
function fetch_url($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}

/**
 * Scrape DramaBox website for drama list
 */
function scrape_dramabox() {
    $url = "https://www.dramaboxdb.com/";
    $html = fetch_url($url);
    if (!$html) return ['error' => 'Failed to fetch the website. Content might be blocked by the server or empty.'];

    $dramas = [];
    // More robust regex to handle potential variations in quotes or spaces
    preg_match_all('/\/movie\/(\d+)\/([^\s"\'>]+)/', $html, $matches);

    if (!empty($matches[1])) {
        $ids = $matches[1];
        $slugs = $matches[2];

        for ($i = 0; $i < count($ids); $i++) {
            $id = $ids[$i];
            $slug = $slugs[$i];

            if (!isset($dramas[$id])) {
                // Remove trailing punctuation from slug if any
                $slug = rtrim($slug, '/');
                $title = str_replace(['-', '_'], ' ', $slug);
                $title = ucwords($title);

                // Determine subdirectory for images (it's either 4x1 or 4x2 based on ID prefix usually)
                $subDir = (strpos($id, '41') === 0) ? '4x1' : '4x2';

                $dramas[$id] = [
                    'bookId' => $id,
                    'title' => $title,
                    'slug' => $slug,
                    'cover' => "https://thwztchapter.dramaboxdb.com/data/cppartner/$subDir/" . substr($id, 0, 2) . "x0/" . substr($id, 0, 3) . "x0/$id/$id.jpg@w=240&h=400"
                ];
            }
        }
    }

    if (empty($dramas)) {
        return ['error' => 'No dramas found on the page. Website structure might have changed.'];
    }

    return array_values($dramas);
}

/**
 * Fetch all episodes for a given bookId from the Sansekai API
 */
function fetch_episodes_from_api($bookId) {
    $apiUrl = "https://api.sansekai.my.id/api/dramabox/allepisode?bookId=" . $bookId;
    $json = fetch_url($apiUrl);
    if (!$json) return null;

    return json_decode($json, true);
}

/**
 * Check if admin is logged in
 */
function check_admin_login() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['admin_logged_in'])) {
        header("Location: login.php");
        exit;
    }
}
