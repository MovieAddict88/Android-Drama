<?php

/**
 * Fetch HTML content from a URL using cURL
 */
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
    if (curl_errno($ch)) {
        $error_msg = curl_error($ch);
        curl_close($ch);
        return "Error: $error_msg";
    }
    curl_close($ch);
    return $result;
}

/**
 * Scrape DramaBox website for drama list with categories
 */
function scrape_dramabox() {
    $url = "https://www.dramaboxdb.com/";
    $html = fetch_url($url);
    if (!$html || strpos($html, 'Error:') === 0) return ['error' => 'Failed to fetch the website. ' . $html];

    $categories = [];

    // Attempt to parse JSON from __NEXT_DATA__ for more accurate info
    if (preg_match('/<script id="__NEXT_DATA__" type="application\/json">(.*?)<\/script>/', $html, $scriptMatches)) {
        $jsonData = json_decode($scriptMatches[1], true);
        $pageProps = $jsonData['props']['pageProps'] ?? [];

        // Check for bigList (Featured/Hero)
        if (isset($pageProps['bigList']) && is_array($pageProps['bigList'])) {
            $featuredItems = [];
            foreach ($pageProps['bigList'] as $item) {
                if (isset($item['bookId'])) {
                    $featuredItems[] = [
                        'bookId' => $item['bookId'],
                        'title' => $item['bookName'] ?? $item['bookNameEn'] ?? 'Unknown',
                        'slug' => $item['replacedBookName'] ?? '',
                        'cover' => $item['cover'] ?? '',
                        'description' => $item['introduction'] ?? ''
                    ];
                }
            }
            if (!empty($featuredItems)) {
                $categories[] = [
                    'name' => 'Featured',
                    'items' => $featuredItems
                ];
            }
        }

        // Check for smallData (Categories)
        if (isset($pageProps['smallData']) && is_array($pageProps['smallData'])) {
            foreach ($pageProps['smallData'] as $section) {
                if (isset($section['list']) && is_array($section['list'])) {
                    $sectionItems = [];
                    foreach ($section['list'] as $item) {
                        if (isset($item['bookId'])) {
                            $sectionItems[] = [
                                'bookId' => $item['bookId'],
                                'title' => $item['bookName'] ?? $item['bookNameEn'] ?? 'Unknown',
                                'slug' => $item['replacedBookName'] ?? '',
                                'cover' => $item['cover'] ?? ''
                            ];
                        }
                    }
                    if (!empty($sectionItems)) {
                        $categories[] = [
                            'name' => $section['moduleName'] ?? 'Recommended',
                            'items' => $sectionItems
                        ];
                    }
                }
            }
        }

        // Check for initialState fallback (legacy or different structure)
        if (empty($categories) && isset($pageProps['initialState']['home']['homeData'])) {
            $homeData = $pageProps['initialState']['home']['homeData'];
            foreach ($homeData as $section) {
                if (isset($section['list']) && is_array($section['list'])) {
                    $sectionItems = [];
                    foreach ($section['list'] as $item) {
                        if (isset($item['bookId'])) {
                            $sectionItems[] = [
                                'bookId' => $item['bookId'],
                                'title' => $item['bookName'] ?? $item['bookNameEn'] ?? 'Unknown',
                                'slug' => $item['replacedBookName'] ?? '',
                                'cover' => $item['cover'] ?? ''
                            ];
                        }
                    }
                    if (!empty($sectionItems)) {
                        $categories[] = [
                            'name' => $section['moduleName'] ?? 'Recommended',
                            'items' => $sectionItems
                        ];
                    }
                }
            }
        }
    }

    // Fallback to regex if JSON parsing failed or found nothing
    if (empty($categories)) {
        $dramas = [];
        preg_match_all('/\/movie\/(\d+)\/([^\s"\'>]+)/', $html, $matches);
        if (!empty($matches[1])) {
            $ids = $matches[1];
            $slugs = $matches[2];
            for ($i = 0; $i < count($ids); $i++) {
                $id = $ids[$i];
                $slug = rtrim($slugs[$i], '/');
                if (!isset($dramas[$id])) {
                    $title = ucwords(str_replace(['-', '_'], ' ', $slug));
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
        if (!empty($dramas)) {
            $categories[] = [
                'name' => 'All Content',
                'items' => array_values($dramas)
            ];
        }
    }

    if (empty($categories)) {
        return ['error' => 'No dramas found on the page. Website structure might have changed.'];
    }

    return $categories;
}

/**
 * Search DramaBox via Sansekai API
 */
function search_dramabox($keyword, $page = 1) {
    $apiUrl = "https://api.sansekai.my.id/api/dramabox/search?query=" . urlencode($keyword) . "&page=" . (int)$page;
    $json = fetch_url($apiUrl);
    if (!$json) return ['error' => 'Failed to fetch search results.'];

    $data = json_decode($json, true);
    if (!is_array($data)) return ['error' => 'Invalid response from search API.'];

    $items = [];
    $searchResults = $data['results'] ?? [];
    foreach ($searchResults as $item) {
        if (isset($item['bookId'])) {
            $items[] = [
                'bookId' => $item['bookId'],
                'title' => $item['title'] ?? 'Unknown',
                'cover' => $item['cover'] ?? ''
            ];
        }
    }

    return $items;
}

/**
 * Scrape ReelShort website for drama list with categories
 */
function scrape_reelshort() {
    $url = "https://www.reelshort.com/";
    $html = fetch_url($url);
    if (!$html || strpos($html, 'Error:') === 0) return ['error' => 'Failed to fetch the website. ' . $html];

    $categories = [];

    $startTag = '<script id="__NEXT_DATA__" type="application/json">';
    $endTag = '</script>';
    $start = strpos($html, $startTag);
    if ($start !== false) {
        $start += strlen($startTag);
        $end = strpos($html, $endTag, $start);
        if ($end !== false) {
            $json = substr($html, $start, $end - $start);
            $jsonData = json_decode($json, true);
        $hallInfo = $jsonData['props']['pageProps']['fallback']['/api/video/hall/info'] ?? [];

        if (isset($hallInfo['bookShelfList']) && is_array($hallInfo['bookShelfList'])) {
            foreach ($hallInfo['bookShelfList'] as $shelf) {
                // For 'Continue Watching', books might be empty but we want to skip it anyway
                if (!empty($shelf['books']) && is_array($shelf['books'])) {
                    $shelfItems = [];
                    foreach ($shelf['books'] as $book) {
                        if (isset($book['book_id'])) {
                            $shelfItems[] = [
                                'bookId' => $book['book_id'],
                                'title' => $book['book_title'] ?? 'Unknown',
                                'cover' => $book['book_pic'] ?? '',
                                'description' => $book['special_desc'] ?? ''
                            ];
                        }
                    }
                    if (!empty($shelfItems)) {
                        $categories[] = [
                            'name' => $shelf['bookshelf_name'] ?? 'Recommended',
                            'items' => $shelfItems
                        ];
                    }
                }
            }
        }
    } }

    if (empty($categories)) {
        return ['error' => 'No ReelShort dramas found on the page. Website structure might have changed.'];
    }

    return $categories;
}

/**
 * Search ReelShort via Sansekai API
 */
function search_reelshort($keyword, $page = 1) {
    $apiUrl = "https://api.sansekai.my.id/api/reelshort/search?query=" . urlencode($keyword) . "&page=" . (int)$page;
    $json = fetch_url($apiUrl);
    if (!$json) return ['error' => 'Failed to fetch ReelShort search results.'];

    $data = json_decode($json, true);
    if (!is_array($data)) return ['error' => 'Invalid response from ReelShort search API.'];

    // Handle error messages from API (e.g. Forbidden/Blacklisted)
    if (isset($data['error'])) {
        return ['error' => $data['message'] ?? $data['error']];
    }

    $items = [];
    foreach ($data as $item) {
        if (isset($item['bookId'])) {
            $items[] = [
                'bookId' => $item['bookId'],
                'title' => $item['bookName'] ?? 'Unknown',
                'cover' => $item['cover'] ?? ''
            ];
        }
    }

    return $items;
}

/**
 * Fetch ReelShort drama detail via Sansekai API
 */
function fetch_reelshort_detail($bookId) {
    $apiUrl = "https://api.sansekai.my.id/api/reelshort/detail?bookId=" . $bookId;
    $json = fetch_url($apiUrl);
    if (!$json) return null;

    $data = json_decode($json, true);
    if (isset($data['error'])) return null;
    return $data;
}

/**
 * Fetch ReelShort episode via Sansekai API
 */
function fetch_reelshort_episode($bookId, $episodeNumber) {
    $apiUrl = "https://api.sansekai.my.id/api/reelshort/episode?bookId=$bookId&episodeNumber=$episodeNumber";
    $json = fetch_url($apiUrl);
    if (!$json) return null;

    $data = json_decode($json, true);
    if (isset($data['error'])) return $data; // Return error data so generate.php can handle it
    return $data;
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
