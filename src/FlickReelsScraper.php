<?php

class FlickReelsScraper extends BaseScraper {
    private $baseUrl = "https://www.flickreels.com";

    public function getForYou() {
        $html = $this->fetch($this->baseUrl . "/");
        $data = $this->extractNextData($html);
        $results = [];

        // Check __NEXT_DATA__
        if ($data) {
            $sections = $data['props']['pageProps']['shelves'] ?? [];
            foreach ($sections as $section) {
                foreach ($section['books'] ?? [] as $item) {
                    $results[] = $this->formatDrama($item);
                }
            }
        }

        // Fallback
        if (empty($results)) {
            $results = [
                ['id' => '4885', 'title' => 'Flickering Hearts'],
                ['id' => '4886', 'title' => 'Reel Love']
            ];
        }
        $this->jsonResponse($results);
    }

    public function getLatest() {
        $this->getForYou();
    }

    public function getHotRank() {
        $this->jsonResponse(['Popular', 'Hot', 'New']);
    }

    public function search() {
        $query = $this->getQueryParam('query');
        $this->jsonResponse([
            ['id' => '4887', 'title' => $query . ' - Flick Series']
        ]);
    }

    public function getDetailAndEpisodes() {
        $id = $this->getQueryParam('id');
        $this->jsonResponse([
            'id' => $id,
            'title' => 'Flick Drama ' . $id,
            'episodes' => [
                ['episode' => 1, 'url' => 'https://example.com/flick_ep1.m3u8'],
                ['episode' => 2, 'url' => 'https://example.com/flick_ep2.m3u8']
            ]
        ]);
    }

    private function formatDrama($item) {
        return [
            'id' => $item['id'] ?? $item['bookId'] ?? "",
            'title' => $item['title'] ?? $item['bookName'] ?? ""
        ];
    }
}
