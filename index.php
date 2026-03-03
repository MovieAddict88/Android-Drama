<?php
require_once 'DramaBoxScraper.php';

$scraper = new DramaBoxScraper();
$command = $_GET['command'] ?? 'trending';
$query = $_GET['query'] ?? '';
$bookId = $_GET['bookId'] ?? '';

$data = [];
$error = null;

try {
    switch ($command) {
        case 'trending':
            $data = $scraper->getTrending();
            break;
        case 'search':
            if ($query) {
                $data = $scraper->search($query);
            } else {
                $error = "Search query is required.";
            }
            break;
        case 'details':
            if ($bookId) {
                $data = $scraper->getDetails($bookId);
            } else {
                $error = "Book ID is required.";
            }
            break;
        case 'episodes':
            if ($bookId) {
                $data = $scraper->getEpisodes($bookId);
            } else {
                $error = "Book ID is required.";
            }
            break;
        default:
            $error = "Unknown command.";
    }
} catch (Exception $e) {
    $error = "Error: " . $e->getMessage();
}

// Return JSON for easier integration, or a simple HTML view
if (isset($_GET['format']) && $_GET['format'] === 'json') {
    header('Content-Type: application/json');
    if ($error) {
        echo json_encode(['error' => $error]);
    } else {
        echo json_encode($data);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DramaBox Scraper</title>
    <style>
        body { font-family: sans-serif; max-width: 800px; margin: 20px auto; padding: 0 10px; }
        .drama-card { border: 1px solid #ddd; padding: 10px; margin-bottom: 10px; border-radius: 5px; }
        .drama-card img { max-width: 100px; float: left; margin-right: 15px; }
        .clearfix::after { content: ""; clear: both; display: table; }
        .nav { margin-bottom: 20px; }
        .episode-list { margin-top: 10px; }
    </style>
</head>
<body>
    <h1>DramaBox Scraper</h1>
    <div class="nav">
        <a href="?command=trending">Trending</a> |
        <form action="index.php" method="GET" style="display:inline;">
            <input type="hidden" name="command" value="search">
            <input type="text" name="query" placeholder="Search dramas..." value="<?php echo htmlspecialchars($query); ?>">
            <button type="submit">Search</button>
        </form>
    </div>

    <?php if ($error): ?>
        <p style="color:red;"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <div id="results">
        <?php if ($command === 'trending' || $command === 'search'): ?>
            <h2><?php echo ucfirst($command); ?> Results</h2>
            <?php foreach ($data as $drama): ?>
                <div class="drama-card clearfix">
                    <img src="<?php echo htmlspecialchars($drama['coverWap'] ?? $drama['cover'] ?? ''); ?>" alt="Cover">
                    <div>
                        <h3><a href="?command=details&bookId=<?php echo urlencode($drama['bookId']); ?>"><?php echo htmlspecialchars($drama['bookName']); ?></a></h3>
                        <p><?php echo htmlspecialchars(substr($drama['introduction'] ?? '', 0, 150)); ?>...</p>
                        <a href="?command=episodes&bookId=<?php echo urlencode($drama['bookId']); ?>">View Episodes</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php elseif ($command === 'details'): ?>
            <h2>Drama Details</h2>
            <div class="drama-card clearfix">
                <img src="<?php echo htmlspecialchars($data['coverWap'] ?? $data['cover'] ?? ''); ?>" alt="Cover">
                <div>
                    <h3><?php echo htmlspecialchars($data['bookName'] ?? ''); ?></h3>
                    <p><?php echo htmlspecialchars($data['introduction'] ?? ''); ?></p>
                    <p><strong>Tags:</strong> <?php echo htmlspecialchars(implode(', ', $data['tags'] ?? [])); ?></p>
                    <a href="?command=episodes&bookId=<?php echo urlencode($bookId); ?>">View Episodes</a>
                </div>
            </div>
        <?php elseif ($command === 'episodes'): ?>
            <h2>Episodes</h2>
            <div class="episode-list">
                <?php foreach ($data as $ep): ?>
                    <div class="drama-card">
                        <h4><?php echo htmlspecialchars($ep['chapterName']); ?></h4>
                        <?php
                        $videoUrl = '';
                        if (!empty($ep['cdnList'])) {
                            $videoUrl = $ep['cdnList'][0]['videoPathList'][0]['videoPath'] ?? '';
                        }
                        ?>
                        <?php if ($videoUrl): ?>
                            <a href="<?php echo htmlspecialchars($videoUrl); ?>" target="_blank">Watch Video</a>
                        <?php else: ?>
                            <p>No video URL found.</p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
