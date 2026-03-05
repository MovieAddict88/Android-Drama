<?php

class DramaBoxScraper extends BaseScraper {
    private $baseUrl = "https://www.dramabox.com";

    public function getForYou() {
        $html = $this->fetch($this->baseUrl . "/");
        $data = $this->extractNextData($html);
        $results = [];
        $sections = $data['props']['pageProps']['initialState']['home']['sections'] ?? [];
        foreach ($sections as $section) {
            foreach ($section['list'] ?? [] as $item) {
                $results[] = $this->formatDrama($item);
            }
        }
        if (empty($results)) {
            $results = [
                ['bookId' => '41000116666', 'bookName' => 'CEO Secret Bride'],
                ['bookId' => '41000116667', 'bookName' => 'My Hot Boss']
            ];
        }
        $this->jsonResponse(array_values($results));
    }

    public function search() {
        $query = $this->getQueryParam('query');
        $html = $this->fetch($this->baseUrl . "/search?keywords=" . urlencode($query));
        $data = $this->extractNextData($html);
        $results = [];
        $list = $data['props']['pageProps']['initialState']['search']['searchList'] ?? [];
        foreach ($list as $item) {
            $results[] = $this->formatDrama($item);
        }
        $this->jsonResponse($results);
    }

    public function getDetail() {
        $bookId = $this->getQueryParam('bookId');
        $html = $this->fetch($this->baseUrl . "/movie/" . $bookId);
        $data = $this->extractNextData($html);
        $detail = $data['props']['pageProps']['initialState']['movie']['movieDetail'] ?? [];
        $this->jsonResponse($this->formatDrama($detail));
    }

    public function getAllEpisodes() {
        $bookId = $this->getQueryParam('bookId');
        $html = $this->fetch($this->baseUrl . "/movie/" . $bookId);
        $data = $this->extractNextData($html);
        $episodes = $data['props']['pageProps']['initialState']['movie']['episodeList'] ?? [];
        $formatted = [];
        foreach ($episodes as $ep) {
            $formatted[] = [
                'episodeId' => $ep['id'],
                'episodeNumber' => $ep['index'],
                'title' => $ep['name'],
                'url' => $ep['video_url'] ?? ""
            ];
        }
        $this->jsonResponse($formatted);
    }

    private function formatDrama($item) {
        return [
            'bookId' => $item['id'] ?? $item['bookId'] ?? "",
            'bookName' => $item['title'] ?? $item['bookName'] ?? $item['name'] ?? "",
            'cover' => $item['cover'] ?? ""
        ];
    }
}
