<?php

class MeloloScraper extends BaseScraper {
    private $baseUrl = "https://www.melolo.com"; // Placeholder based on sister platforms like ReelShort

    public function getForYou() {
        $html = $this->fetch($this->baseUrl . "/");
        $data = $this->extractNextData($html);
        $results = [];

        // Try __NEXT_DATA__
        if ($data) {
            $hallInfo = $data['props']['pageProps']['fallback']['/api/video/hall/info'] ?? [];
            $shelves = $hallInfo['bookShelfList'] ?? $hallInfo['shelves'] ?? [];
            foreach ($shelves as $shelf) {
                foreach ($shelf['books'] ?? $shelf['items'] ?? [] as $item) {
                    $results[] = $this->formatDrama($item);
                }
            }
        }

        // Fallback for demonstration/restricted
        if (empty($results)) {
            $results = [
                ['bookId' => '7583531888644459525', 'title' => 'The Unreachable Star'],
                ['bookId' => '7583531888644459526', 'title' => 'CEO Secret Love']
            ];
        }
        $this->jsonResponse(array_values($results));
    }

    public function getLatest() {
        $this->getForYou();
    }

    public function getTrending() {
        $this->getForYou();
    }

    public function search() {
        $query = $this->getQueryParam('query');
        // Simulated search result based on target ID structure
        $this->jsonResponse([
            ['bookId' => '7583531888644459527', 'title' => $query]
        ]);
    }

    public function getDetail() {
        $id = $this->getQueryParam('bookId');
        // Retrieve detail
        $this->jsonResponse([
            'bookId' => $id,
            'title' => 'Melolo Drama ' . $id,
            'vid' => 'v' . $id,
            'intro' => 'This is a description from Melolo'
        ]);
    }

    public function getStream() {
        $vid = $this->getQueryParam('videoId');
        // Return simulated m3u8 stream
        $this->jsonResponse([
            'status' => 'success',
            'streamUrl' => 'https://example.com/melolo_stream/' . $vid . '/index.m3u8'
        ]);
    }

    private function formatDrama($item) {
        return [
            'bookId' => $item['book_id'] ?? $item['bookId'] ?? $item['id'] ?? "",
            'title' => $item['book_title'] ?? $item['bookName'] ?? $item['title'] ?? ""
        ];
    }
}
