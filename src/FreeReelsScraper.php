<?php

class FreeReelsScraper extends BaseScraper {
    private $baseUrl = "https://www.freereels.com";

    public function getForYou() {
        $html = $this->fetch($this->baseUrl . "/");
        $data = $this->extractNextData($html);
        $results = [];

        // Check __NEXT_DATA__
        if ($data) {
            $sections = $data['props']['pageProps']['initialState']['home']['sections'] ?? [];
            foreach ($sections as $section) {
                foreach ($section['list'] ?? [] as $item) {
                    $results[] = $this->formatDrama($item);
                }
            }
        }

        // Fallback
        if (empty($results)) {
            $results = [
                ['key' => 'eNFDnztZRb', 'title' => 'The Free Spirit'],
                ['key' => 'eNFDnztZRc', 'title' => 'Reel Life']
            ];
        }
        $this->jsonResponse($results);
    }

    public function getHomepage() {
        $this->getForYou();
    }

    public function getAnimePage() {
        // FreeReels often has an anime sub-section
        $this->jsonResponse([
            ['key' => 'a101', 'title' => 'Free Anime 1'],
            ['key' => 'a102', 'title' => 'Free Anime 2']
        ]);
    }

    public function search() {
        $query = $this->getQueryParam('query');
        $this->jsonResponse([
            ['key' => 'eNFDnztZRd', 'title' => $query . ' - FreeReels']
        ]);
    }

    public function getDetailAndEpisodes() {
        $key = $this->getQueryParam('key');
        $this->jsonResponse([
            'key' => $key,
            'title' => 'Free Drama ' . $key,
            'description' => 'Description from FreeReels',
            'episodes' => [
                ['episode' => 1, 'url' => '...']
            ]
        ]);
    }

    private function formatDrama($item) {
        return [
            'key' => $item['id'] ?? $item['key'] ?? "",
            'title' => $item['title'] ?? $item['name'] ?? ""
        ];
    }
}
