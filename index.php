<?php

require_once 'scrapers/DramaBoxScraper.php';
require_once 'scrapers/ReelShortScraper.php';
require_once 'scrapers/ShortMaxScraper.php';
require_once 'scrapers/NetShortScraper.php';
require_once 'scrapers/MeloloScraper.php';
require_once 'scrapers/AIScraper.php';
require_once 'scrapers/Uploader.php';
require_once 'scrapers/ShortMaxHLSProxy.php';

$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
$basePath = dirname($scriptName);
if ($basePath === DIRECTORY_SEPARATOR) $basePath = '';
$path = str_replace($basePath, '', $requestUri);
$path = explode('?', $path)[0];
$path = trim($path, '/');

// If accessing the root, serve the HTML documentation
if ($path === '' || $path === 'index.php') {
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Drama Scraper API Hub</title>
    <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5/swagger-ui.css">
    <style>
        html { box-sizing: border-box; overflow: -moz-scrollbars-vertical; overflow-y: scroll; }
        *, *:before, *:after { box-sizing: inherit; }
        body { margin: 0; background: #fafafa; }
    </style>
</head>
<body>
    <div id="swagger-ui"></div>
    <script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-bundle.js"></script>
    <script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-standalone-preset.js"></script>
    <script>
        window.onload = function() {
            const ui = SwaggerUIBundle({
                url: "openapi.json",
                dom_id: '#swagger-ui',
                presets: [SwaggerUIBundle.presets.apis, SwaggerUIStandalonePreset],
                layout: "BaseLayout"
            });
        };
    </script>
</body>
</html>
<?php
} else {
    header('Content-Type: application/json');
    $parts = explode('/', $path);
    $platform = $parts[0] ?? '';
    $action = $parts[1] ?? '';

    switch ($platform) {
        case 'dramabox':
            $scraper = new DramaBoxScraper();
            if ($action === 'foryou' || $action === 'trending') {
                echo json_encode($scraper->getTrending());
            } elseif ($action === 'search') {
                echo json_encode($scraper->search($_GET['query'] ?? ''));
            } elseif ($action === 'detail') {
                echo json_encode($scraper->getDetails($_GET['bookId'] ?? ''));
            } elseif ($action === 'allepisode') {
                echo json_encode($scraper->getEpisodes($_GET['bookId'] ?? ''));
            } else {
                echo json_encode(["s" => 1, "m" => "Unknown action for dramabox"]);
            }
            break;

        case 'reelshort':
            $scraper = new ReelShortScraper();
            if ($action === 'foryou') {
                echo json_encode($scraper->getForYou());
            } elseif ($action === 'search') {
                echo json_encode($scraper->search($_GET['query'] ?? ''));
            } elseif ($action === 'detail') {
                echo json_encode($scraper->getDetails($_GET['bookId'] ?? ''));
            } elseif ($action === 'allepisode') {
                echo json_encode($scraper->getEpisodes($_GET['bookId'] ?? ''));
            } else {
                echo json_encode(["s" => 1, "m" => "Unknown action for reelshort"]);
            }
            break;

        case 'shortmax':
            $scraper = new ShortMaxScraper();
            if ($action === 'latest') {
                echo json_encode($scraper->getLatest());
            } elseif ($action === 'search') {
                echo json_encode($scraper->search($_GET['query'] ?? ''));
            } elseif ($action === 'detail') {
                echo json_encode($scraper->getDetails($_GET['shortPlayId'] ?? ''));
            } elseif ($action === 'allepisode') {
                echo json_encode($scraper->getAllEpisodes($_GET['shortPlayId'] ?? ''));
            } elseif ($action === 'hls') {
                $proxy = new ShortMaxHLSProxy();
                $proxy->handle($_GET['url'] ?? '');
            } else {
                echo json_encode(["s" => 1, "m" => "Unknown action for shortmax"]);
            }
            break;

        case 'netshort':
            $scraper = new NetShortScraper();
            if ($action === 'foryou') {
                echo json_encode($scraper->getForYou());
            } elseif ($action === 'search') {
                echo json_encode($scraper->search($_GET['query'] ?? ''));
            } else {
                echo json_encode(["s" => 1, "m" => "Unknown action for netshort"]);
            }
            break;

        case 'melolo':
            $scraper = new MeloloScraper();
            if ($action === 'foryou') {
                echo json_encode($scraper->getForYou());
            } else {
                echo json_encode(["s" => 1, "m" => "Unknown action for melolo"]);
            }
            break;

        case 'ai':
            if ($action === 'chatgpt') {
                $scraper = new AIScraper();
                echo json_encode($scraper->getChatGPTResponse($_GET['prompt'] ?? ''));
            } else {
                echo json_encode(["s" => 1, "m" => "Unknown AI action"]);
            }
            break;

        case 'uploader':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $uploader = new Uploader();
                echo json_encode($uploader->handleUpload($_FILES['file'] ?? []));
            } else {
                echo json_encode(["s" => 1, "m" => "POST method required for uploader"]);
            }
            break;

        default:
            http_response_code(404);
            echo json_encode(["s" => 1, "m" => "Endpoint not found"]);
            break;
    }
    exit;
}
