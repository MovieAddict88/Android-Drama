<?php

class KomikScraper extends BaseScraper {
    private $apiBaseUrl = "https://api.mangadex.org";

    public function getRecommended() {
        $data = $this->fetch($this->apiBaseUrl . "/manga?limit=10");
        $json = json_decode($data, true);
        $this->jsonResponse($this->formatMangaList($json['data'] ?? []));
    }

    public function search() {
        $query = $this->getQueryParam('query');
        $data = $this->fetch($this->apiBaseUrl . "/manga?title=" . urlencode($query) . "&limit=10");
        $json = json_decode($data, true);
        $this->jsonResponse($this->formatMangaList($json['data'] ?? []));
    }

    private function formatMangaList($list) {
        $results = [];
        foreach ($list as $item) {
            $results[] = [
                'manga_id' => $item['id'] ?? "",
                'title' => $item['attributes']['title']['en'] ?? array_values($item['attributes']['title'] ?? [])[0] ?? ""
            ];
        }
        return $results;
    }
}
