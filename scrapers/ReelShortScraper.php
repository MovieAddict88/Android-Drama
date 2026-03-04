<?php

class ReelShortScraper {
    private $baseUrl = "https://www.reelshort.com";
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
        if (!$html) return null;

        if (preg_match('/<script id="__NEXT_DATA__" type="application\/json">(.*?)<\/script>/', $html, $matches)) {
            return json_decode($matches[1], true);
        }

        return null;
    }

    public function getForYou() {
        $data = $this->getNextData($this->baseUrl);
        if (!$data) return ["s" => 1, "m" => "Data not found", "data" => []];

        try {
            // ReelShort usually stores data in fallback or pageProps
            $sections = $data['props']['pageProps']['fallback']['/api/video/hall/info']['list'] ?? [];
            return ["s" => 0, "m" => "Success", "data" => $sections];
        } catch (Exception $e) {
            return ["s" => 1, "m" => $e->getMessage(), "data" => []];
        }
    }

    public function search($query) {
        $url = $this->baseUrl . "/search/" . urlencode($query);
        $data = $this->getNextData($url);
        if (!$data) return ["s" => 1, "m" => "Search failed", "data" => []];

        try {
            // Adjust path for search results
            $list = $data['props']['pageProps']['fallback']['/api/video/search/list'] ?? [];
            if (empty($list)) {
                $list = $data['props']['pageProps']['searchResult'] ?? [];
            }
            return ["s" => 0, "m" => "Success", "data" => $list];
        } catch (Exception $e) {
            return ["s" => 1, "m" => $e->getMessage(), "data" => []];
        }
    }

    public function getDetails($bookId) {
        $url = $this->baseUrl . "/book/" . $bookId;
        $data = $this->getNextData($url);
        if (!$data) return ["s" => 1, "m" => "Details not found", "data" => []];

        try {
            $book = $data['props']['pageProps']['fallback']["/api/video/book/info?book_id=$bookId"] ?? [];
            return ["s" => 0, "m" => "Success", "data" => $book];
        } catch (Exception $e) {
            return ["s" => 1, "m" => $e->getMessage(), "data" => []];
        }
    }

    public function getEpisodes($bookId) {
        $url = $this->baseUrl . "/book/" . $bookId;
        $data = $this->getNextData($url);
        if (!$data) return ["s" => 1, "m" => "Episodes not found", "data" => []];

        try {
            $episodes = $data['props']['pageProps']['fallback']["/api/video/book/chapters?book_id=$bookId"] ?? [];
            return ["s" => 0, "m" => "Success", "data" => $episodes];
        } catch (Exception $e) {
            return ["s" => 1, "m" => $e->getMessage(), "data" => []];
        }
    }
}
