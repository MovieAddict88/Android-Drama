<?php

class DramaBoxScraper {
    private $baseUrl = "https://www.dramabox.com";
    private $apiBaseUrl = "https://dramabox.sansekai.my.id/api/dramabox";
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

    public function getTrending() {
        $response = $this->fetch($this->apiBaseUrl . "/trending");
        return $response ? json_decode($response, true) : [];
    }

    public function search($query) {
        $response = $this->fetch($this->apiBaseUrl . "/search", ["query" => $query]);
        return $response ? json_decode($response, true) : [];
    }

    public function getPopularSearch() {
        $response = $this->fetch($this->apiBaseUrl . "/populersearch");
        return $response ? json_decode($response, true) : [];
    }

    public function getDetails($bookId) {
        $response = $this->fetch($this->apiBaseUrl . "/detail", ["bookId" => $bookId]);
        return $response ? json_decode($response, true) : [];
    }

    public function getEpisodes($bookId) {
        $response = $this->fetch($this->apiBaseUrl . "/allepisode", ["bookId" => $bookId]);
        return $response ? json_decode($response, true) : [];
    }

    // Official site scraping (more fragile but available)
    public function getTrendingOfficial() {
        $data = $this->getNextData($this->baseUrl);
        if (!$data) return [];

        try {
            $sections = $data['props']['pageProps']['initialState']['home']['sections'] ?? [];
            foreach ($sections as $section) {
                if (($section['name'] ?? '') === 'Trending') {
                    return $section['list'] ?? [];
                }
            }
        } catch (Exception $e) {
            return [];
        }

        return [];
    }
}
