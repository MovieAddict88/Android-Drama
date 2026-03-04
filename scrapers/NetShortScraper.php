<?php

class NetShortScraper {
    private $baseUrl = "https://www.netshort.com";
    private $userAgent = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36";

    private function fetch($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, $this->userAgent);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        curl_close($ch);
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
        return ["s" => 0, "m" => "Success", "data" => $data['props']['pageProps']['recommendList'] ?? []];
    }

    public function search($query) {
        $url = $this->baseUrl . "/search?q=" . urlencode($query);
        $data = $this->getNextData($url);
        return ["s" => 0, "m" => "Success", "data" => $data['props']['pageProps']['searchResult'] ?? []];
    }
}
