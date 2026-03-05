<?php

class DramaBoxScraper {
    private $baseUrl = "https://www.dramabox.com";
    private $userAgent = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36";

    private function fetch($url, $params = []) {
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, $this->userAgent);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            return null;
        }

        return $response;
    }

    private function getNextData($url) {
        $html = $this->fetch($url);
        if (!$html) {
            error_log("DramaBoxScraper: Failed to fetch $url");
            return null;
        }

        if (preg_match('/<script id="__NEXT_DATA__" type="application\/json"[^>]*>(.*?)<\/script>/s', $html, $matches)) {
            $data = json_decode($matches[1], true);
            if (!$data) {
                error_log("DramaBoxScraper: Failed to decode JSON from $url");
            }
            return $data;
        }

        error_log("DramaBoxScraper: __NEXT_DATA__ not found in $url");
        return null;
    }

    public function getTrending() {
        $data = $this->getNextData($this->baseUrl);
        if (!$data) return ["s" => 1, "m" => "Data not found or fetch failed", "data" => []];

        try {
            // New path based on observed structure
            $list = $data['props']['pageProps']['bigList'] ?? [];
            if (!empty($list)) {
                 return ["s" => 0, "m" => "Success", "data" => $list];
            }

            $sections = $data['props']['pageProps']['initialState']['home']['sections'] ?? [];
            if (empty($sections)) {
                // Try alternate path for mobile or updated site
                $sections = $data['props']['pageProps']['fallback']['/api/video/hall/info']['list'] ?? [];
                if (!empty($sections)) {
                     return ["s" => 0, "m" => "Success", "data" => $sections];
                }
            }
            foreach ($sections as $section) {
                if (in_array($section['name'] ?? '', ['Trending', 'Recommend', 'Popular', '必看好剧', '精彩剧集', '当前热播'])) {
                    return ["s" => 0, "m" => "Success", "data" => $section['list'] ?? []];
                }
            }
            if (!empty($sections)) {
                return ["s" => 0, "m" => "Success", "data" => $sections[0]['list'] ?? []];
            }
        } catch (Exception $e) {
            return ["s" => 1, "m" => $e->getMessage(), "data" => []];
        }

        return ["s" => 1, "m" => "Section Trending not found", "data" => []];
    }

    public function search($query) {
        $url = $this->baseUrl . "/search";
        $data = $this->getNextData($url . "?q=" . urlencode($query));
        if (!$data) return ["s" => 1, "m" => "Search failed", "data" => []];

        try {
            $list = $data['props']['pageProps']['initialState']['search']['searchResult']['list'] ?? [];
            return ["s" => 0, "m" => "Success", "data" => $list];
        } catch (Exception $e) {
            return ["s" => 1, "m" => $e->getMessage(), "data" => []];
        }
    }

    public function getDetails($bookId) {
        $data = $this->getNextData($this->baseUrl);
        if (!$data) return ["s" => 1, "m" => "Details not found", "data" => []];

        try {
            $list = array_merge($data['props']['pageProps']['bigList'] ?? [], $data['props']['pageProps']['smallData']['list'] ?? []);
            foreach ($list as $book) {
                if (($book['bookId'] ?? '') == $bookId) {
                    return ["s" => 0, "m" => "Success", "data" => $book];
                }
            }

            // If not in home list, try fetching the specific page (though currently it returns 404/empty for us)
            $url = $this->baseUrl . "/book/" . $bookId;
            $bookData = $this->getNextData($url);
            if ($bookData) {
                 $book = $bookData['props']['pageProps']['initialState']['book']['bookDetail'] ?? [];
                 if (!empty($book)) return ["s" => 0, "m" => "Success", "data" => $book];
            }

            return ["s" => 1, "m" => "Book not found in home lists", "data" => []];
        } catch (Exception $e) {
            return ["s" => 1, "m" => $e->getMessage(), "data" => []];
        }
    }

    public function getEpisodes($bookId) {
        $url = $this->baseUrl . "/book/" . $bookId;
        $data = $this->getNextData($url);

        // If /book/id fails, try /play/id/first_episode_id if we can find it
        if (!$data) {
             $homeData = $this->getNextData($this->baseUrl);
             $list = array_merge($homeData['props']['pageProps']['bigList'] ?? [], $homeData['props']['pageProps']['smallData']['list'] ?? []);
             foreach ($list as $book) {
                 if (($book['bookId'] ?? '') == $bookId) {
                     // We don't have the first episode ID easily, but some sites use 0 or 1
                     // For now, if /book/id fails, we're stuck without a better discovery mechanism
                     break;
                 }
             }
        }

        if (!$data) return ["s" => 1, "m" => "Episodes not found (404 or Parsing failed)", "data" => []];

        try {
            $episodes = $data['props']['pageProps']['initialState']['book']['chapterList'] ?? [];
            if (empty($episodes)) {
                 $episodes = $data['props']['pageProps']['fallback']["/api/video/book/chapters?book_id=$bookId"] ?? [];
            }

            // Map to include a play URL if missing
            $mapped = array_map(function($ep) use ($bookId) {
                if (!isset($ep['play_url']) && isset($ep['chapterId'])) {
                    $ep['play_url'] = $this->baseUrl . "/play/" . $bookId . "/" . $ep['chapterId'];
                }
                return $ep;
            }, $episodes);

            return ["s" => 0, "m" => "Success", "data" => $mapped];
        } catch (Exception $e) {
            return ["s" => 1, "m" => $e->getMessage(), "data" => []];
        }
    }
}
