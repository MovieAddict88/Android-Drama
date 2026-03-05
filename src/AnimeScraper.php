<?php

class AnimeScraper extends BaseScraper {
    private $baseUrl = "https://otakudesu.cloud";

    public function getLatest() {
        $html = $this->fetch($this->baseUrl . "/");
        $results = [];

        // Scrape latest release list
        preg_match_all('/<div class="venz">.*?<li>(.*?)<\/li>.*?<\/div>/s', $html, $matches);
        if (isset($matches[1])) {
            foreach ($matches[1] as $item) {
                if (preg_match('/<h2.*?>(.*?)<\/h2>.*?<a href="(.*?)">/s', $item, $m)) {
                    $urlParts = explode('/', rtrim($m[2], '/'));
                    $results[] = [
                        'title' => trim($m[1]),
                        'urlId' => end($urlParts),
                        'type' => 'Latest'
                    ];
                }
            }
        }
        $this->jsonResponse($results);
    }

    public function getRecommended() {
        $this->getLatest();
    }

    public function search() {
        $query = $this->getQueryParam('query');
        $html = $this->fetch($this->baseUrl . "/?s=" . urlencode($query) . "&post_type=anime");
        $results = [];

        preg_match_all('/<ul class="chivsrc">.*?<li>(.*?)<\/li>.*?<\/ul>/s', $html, $matches);
        if (isset($matches[1])) {
            foreach ($matches[1] as $item) {
                if (preg_match('/<h2><a href="(.*?)">(.*?)<\/a><\/h2>/s', $item, $m)) {
                    $urlParts = explode('/', rtrim($m[1], '/'));
                    $results[] = [
                        'title' => trim($m[2]),
                        'urlId' => end($urlParts)
                    ];
                }
            }
        }
        $this->jsonResponse($results);
    }

    public function getDetail() {
        $urlId = $this->getQueryParam('urlId');
        $html = $this->fetch($this->baseUrl . "/anime/" . $urlId);

        $detail = ['urlId' => $urlId];
        if (preg_match('/<div class="fotoanime">.*?<img src="(.*?)".*?<\/div>/s', $html, $m)) {
            $detail['cover'] = $m[1];
        }
        if (preg_match('/<div class="sinop">.*?<p>(.*?)<\/p>.*?<\/div>/s', $html, $m)) {
            $detail['description'] = trim(strip_tags($m[1]));
        }

        // Episodes
        $episodes = [];
        preg_match_all('/<span><a href="(.*?)">(.*?)<\/a><\/span>/s', $html, $matches);
        if (isset($matches[1])) {
            for ($i = 0; $i < count($matches[1]); $i++) {
                $urlParts = explode('/', rtrim($matches[1][$i], '/'));
                $episodes[] = [
                    'title' => trim($matches[2][$i]),
                    'chapterUrlId' => end($urlParts)
                ];
            }
        }
        $detail['episodes'] = $episodes;
        $this->jsonResponse($detail);
    }

    public function getMovie() {
        $this->jsonResponse([
            ['title' => 'Kimi no Na wa', 'urlId' => 'kimi-no-na-wa'],
            ['title' => 'Tenki no Ko', 'urlId' => 'tenki-no-ko']
        ]);
    }

    public function getVideo() {
        $chapterUrlId = $this->getQueryParam('chapterUrlId');
        // This usually requires bypassing intermediate pages (mirror sites)
        // Returning the source page link as a starting point
        $this->jsonResponse([
            'chapterUrlId' => $chapterUrlId,
            'sourceUrl' => $this->baseUrl . "/episode/" . $chapterUrlId,
            'note' => 'Video extraction requires specific resolution parameter (reso) and mirror handling.'
        ]);
    }
}
