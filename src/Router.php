<?php

class Router {
    private $routes = [];

    public function __construct() {
        $this->loadRoutes();
    }

    private function addRoute($method, $path, $callback) {
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $path,
            'callback' => $callback
        ];
    }

    private function loadRoutes() {
        // Core Drama Platforms
        $this->addRoute('GET', '/api/dramabox/foryou', ['DramaBoxScraper', 'getForYou']);
        $this->addRoute('GET', '/api/dramabox/vip', ['DramaBoxScraper', 'getForYou']);
        $this->addRoute('GET', '/api/dramabox/search', ['DramaBoxScraper', 'search']);
        $this->addRoute('GET', '/api/dramabox/detail', ['DramaBoxScraper', 'getDetail']);
        $this->addRoute('GET', '/api/dramabox/allepisode', ['DramaBoxScraper', 'getAllEpisodes']);

        $this->addRoute('GET', '/api/reelshort/foryou', ['ReelShortScraper', 'getForYou']);
        $this->addRoute('GET', '/api/reelshort/search', ['ReelShortScraper', 'search']);
        $this->addRoute('GET', '/api/reelshort/detail', ['ReelShortScraper', 'getDetail']);

        $this->addRoute('GET', '/api/shortmax/foryou', ['ShortMaxScraper', 'getForYou']);
        $this->addRoute('GET', '/api/shortmax/search', ['ShortMaxScraper', 'search']);
        $this->addRoute('GET', '/api/shortmax/hls', ['ShortMaxScraper', 'proxyHls']);

        // Other Media
        $this->addRoute('GET', '/api/anime/latest', ['AnimeScraper', 'getLatest']);
        $this->addRoute('GET', '/api/anime/search', ['AnimeScraper', 'search']);
        $this->addRoute('GET', '/api/anime/detail', ['AnimeScraper', 'getDetail']);

        $this->addRoute('GET', '/api/komik/recommended', ['KomikScraper', 'getRecommended']);
        $this->addRoute('GET', '/api/komik/search', ['KomikScraper', 'search']);

        $this->addRoute('GET', '/api/moviebox/homepage', ['MovieBoxScraper', 'getHomepage']);
        $this->addRoute('GET', '/api/moviebox/search', ['MovieBoxScraper', 'search']);

        // Melolo, FlickReels, FreeReels
        $this->addRoute('GET', '/api/melolo/foryou', ['MeloloScraper', 'getForYou']);
        $this->addRoute('GET', '/api/flickreels/foryou', ['FlickReelsScraper', 'getForYou']);
        $this->addRoute('GET', '/api/freereels/foryou', ['FreeReelsScraper', 'getForYou']);

        // AI & Uploader
        $this->addRoute('GET', '/api/ai/chatgpt', ['AIScraper', 'chatGpt']);
        $this->addRoute('POST', '/api/uploader', ['Uploader', 'upload']);
    }

    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';

        // Remove subdirectory if present
        $scriptName = dirname($_SERVER['SCRIPT_NAME']);
        if ($scriptName !== '/' && strpos($uri, $scriptName) === 0) {
            $uri = substr($uri, strlen($scriptName));
        }

        // Remove index.php from path
        $uri = str_replace('/index.php', '', $uri);

        $path = parse_url($uri, PHP_URL_PATH) ?: '/';

        foreach ($this->routes as $route) {
            if ($route['method'] === $method && $route['path'] === $path) {
                $className = $route['callback'][0];
                $methodName = $route['callback'][1];
                if (class_exists($className)) {
                    $instance = new $className();
                    $instance->$methodName();
                    return;
                }
            }
        }

        if ($path === '/' || $path === '/docs') {
            require 'docs.php';
        } else {
            header('Content-Type: application/json');
            http_response_code(404);
            echo json_encode(['error' => 'Not Found', 'path' => $path]);
        }
    }
}
