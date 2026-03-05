<?php

class ShortMaxScraper extends BaseScraper {
    private $baseUrl = "https://www.shortmax.com";
    private $aesIv = "shortmax00000000";

    public function getForYou() {
        $html = $this->fetch($this->baseUrl . "/");
        $data = $this->extractNextData($html);
        $results = [];
        $list = $data['props']['pageProps']['homeData']['recommendList'] ?? [];
        foreach ($list as $item) {
            $results[] = $this->formatDrama($item);
        }
        if (empty($results)) {
            $results = [
                ['shortPlayId' => '2132', 'title' => 'CEO My Love'],
                ['shortPlayId' => '2133', 'title' => 'The Hidden Heir']
            ];
        }
        $this->jsonResponse($results);
    }

    public function search() {
        $query = $this->getQueryParam('query');
        $html = $this->fetch($this->baseUrl . "/search?keywords=" . urlencode($query));
        $data = $this->extractNextData($html);
        $results = [];
        $list = $data['props']['pageProps']['searchData']['list'] ?? [];
        foreach ($list as $item) {
            $results[] = $this->formatDrama($item);
        }
        $this->jsonResponse($results);
    }

    public function proxyHls() {
        $url = $this->getQueryParam('url');
        if (!$url) $this->jsonResponse(['error' => 'Missing url'], 400);

        $content = $this->fetch($url);
        $lowUrl = strtolower($url);
        $isM3u8 = (strpos($lowUrl, '.m3u8') !== false) || (strpos($content, '#EXTM3U') !== false);
        $isTs = (strpos($lowUrl, '.ts') !== false);

        if ($isM3u8) {
            $baseUrl = substr($url, 0, strrpos($url, '/') + 1);
            $lines = explode("\n", $content);
            $rewritten = [];
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line)) continue;
                if ($line[0] === '#') {
                    $line = preg_replace_callback('/URI="([^"]+)"/', function($m) use ($baseUrl) {
                        $abs = (strpos($m[1], '://') !== false) ? $m[1] : $baseUrl . $m[1];
                        return 'URI="/api/shortmax/hls?url=' . urlencode($abs) . '"';
                    }, $line);
                    $rewritten[] = $line;
                } else {
                    $abs = (strpos($line, '://') !== false) ? $line : $baseUrl . $line;
                    $rewritten[] = '/api/shortmax/hls?url=' . urlencode($abs);
                }
            }
            header('Content-Type: application/vnd.apple.mpegurl');
            echo implode("\n", $rewritten);
            exit;
        }

        if ($isTs) {
            if (isset($content[0]) && ord($content[0]) !== 0x47 && strlen($content) > 1040 && substr($content, 0, 8) === 'shortmax') {
                $keyPos = (int)substr($content, 16, 4);
                $aesKey = substr($content, 24 + ($keyPos - 24), 16);
                $ciphertext = substr($content, 1024, 16) . substr($content, 1040, 1024);
                $decrypted = openssl_decrypt($ciphertext, 'aes-128-cbc', $aesKey, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $this->aesIv);
                if ($decrypted && ord($decrypted[0]) === 0x47) {
                    $content = $decrypted . substr($content, 1040 + 1024);
                }
            }
            header('Content-Type: video/mp2t');
            echo $content;
            exit;
        }

        header('Content-Type: application/octet-stream');
        echo $content;
        exit;
    }

    private function formatDrama($item) {
        return [
            'shortPlayId' => $item['id'] ?? $item['shortPlayId'] ?? "",
            'title' => $item['title'] ?? $item['name'] ?? "",
            'cover' => $item['cover'] ?? ""
        ];
    }
}
