<?php
// php_project/watch.php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/DramaBox.php';

$bookId = $_GET['id'] ?? die("Book ID required");
$index = (int)($_GET['ep'] ?? 1);

$res = DramaBox::fetchStream($bookId, $index);
if ($res['status'] !== 200 || !isset($res['data']['data'])) {
    die("Gagal mengambil data drama. Status: " . $res['status']);
}

$d = $res['data']['data'];
$chapters = $d['chapterList'] ?? [];
$wantedIdx = max(0, $index - 1);

// Find current chapter
$currentChapter = null;
foreach ($chapters as $ch) {
    if ((int)($ch['chapterIndex'] ?? 0) === $wantedIdx) {
        $currentChapter = $ch;
        break;
    }
}
if (!$currentChapter && !empty($chapters)) {
    $currentChapter = $chapters[0];
}

if (!$currentChapter) {
    die("Episode tidak ditemukan.");
}

$sources = DramaBox::mapVideoSources($currentChapter['cdnList'] ?? []);
$meta = [
    'bookId' => $d['bookId'],
    'bookName' => $d['bookName'],
    'bookCover' => $d['bookCover'],
    'playCount' => $d['playCount'],
    'description' => $d['introduction'] ?? '',
    'tags' => $d['tags'] ?? [],
    'chapterCount' => $d['chapterCount'] ?? count($chapters),
    'orientation' => 'portrait' // DramaBox is usually portrait
];

$pageTitle = "Tonton " . $meta['bookName'] . " - Episode " . ($wantedIdx + 1);

require_once __DIR__ . '/includes/header.php';
?>

<link rel="stylesheet" href="https://cdn.plyr.io/3.7.8/plyr.css" />

<main class="py-6 sm:py-8 min-h-screen">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-6">
            <nav class="flex text-white/40 text-xs mb-3 gap-2 uppercase tracking-widest">
                <a href="index.php" class="hover:text-white transition">Home</a>
                <span>/</span>
                <span class="text-white/60">Tonton Drama</span>
            </nav>
            <h1 class="text-3xl font-extrabold grad-text"><?php echo htmlspecialchars($meta['bookName']); ?></h1>
            <div class="flex items-center gap-3 mt-3">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/5 border border-white/10 text-xs font-medium text-white/70">
                    <i data-lucide="play" class="w-3 h-3 fill-current"></i>
                    <?php echo $meta['playCount']; ?> Play
                </span>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/5 border border-white/10 text-xs font-medium text-white/70">
                    <i data-lucide="layers" class="w-3 h-3"></i>
                    <?php echo $meta['chapterCount']; ?> Episode
                </span>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Player Area -->
            <div class="lg:col-span-2 space-y-6">
                <div class="relative rounded-3xl overflow-hidden border border-white/10 bg-black shadow-2xl">
                    <div class="aspect-[9/16] max-h-[80vh] mx-auto bg-night-900 relative">
                         <video id="player" playsinline controls data-poster="<?php echo htmlspecialchars($currentChapter['chapterImg'] ?? $meta['bookCover']); ?>">
                             <?php foreach ($sources as $s): ?>
                                <source src="<?php echo htmlspecialchars($s['url']); ?>" type="video/mp4" size="<?php echo $s['quality']; ?>">
                             <?php endforeach; ?>
                         </video>

                         <!-- Source Overlay -->
                         <div class="absolute top-4 left-4 z-10 hidden sm:flex gap-2">
                             <div class="glass px-3 py-1.5 rounded-xl text-[10px] font-bold uppercase tracking-wider flex items-center gap-2">
                                 <div class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></div>
                                 Episode <?php echo $wantedIdx + 1; ?>: <?php echo htmlspecialchars($currentChapter['chapterName'] ?? "EP ".($wantedIdx+1)); ?>
                             </div>
                         </div>
                    </div>
                </div>

                <div class="glass p-6 rounded-3xl">
                    <h3 class="text-lg font-bold mb-3">Tentang Drama</h3>
                    <p class="text-white/60 text-sm leading-relaxed mb-4">
                        <?php echo nl2br(htmlspecialchars($meta['description'])); ?>
                    </p>
                    <div class="flex flex-wrap gap-2">
                        <?php foreach ($meta['tags'] as $tag): ?>
                            <span class="px-3 py-1 rounded-lg bg-white/5 border border-white/10 text-[10px] font-medium text-white/50">#<?php echo htmlspecialchars($tag); ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Sidebar: Episode List -->
            <aside class="space-y-6">
                <div class="glass rounded-3xl p-6 flex flex-col max-h-[90vh]">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-bold text-lg">Daftar Episode</h3>
                        <span class="text-[10px] px-2 py-1 rounded bg-white/5 border border-white/10 text-white/40 uppercase"><?php echo count($chapters); ?> Tersedia</span>
                    </div>

                    <div class="grid grid-cols-2 gap-3 overflow-y-auto pr-2 custom-scrollbar">
                        <?php foreach ($chapters as $i => $ch): ?>
                            <?php
                                $isCurrent = ($i === $wantedIdx);
                                $epNum = $i + 1;
                            ?>
                            <a href="watch.php?id=<?php echo $bookId; ?>&ep=<?php echo $epNum; ?>"
                               class="group relative rounded-xl overflow-hidden border <?php echo $isCurrent ? 'border-primary bg-primary/10' : 'border-white/10 bg-white/5'; ?> transition-all duration-300">
                                <div class="aspect-video relative">
                                    <img src="<?php echo htmlspecialchars($ch['chapterImg'] ?? $meta['bookCover']); ?>" alt="EP <?php echo $epNum; ?>" class="absolute inset-0 w-full h-full object-cover">
                                    <div class="absolute inset-0 bg-black/40 group-hover:bg-black/20 transition"></div>
                                    <?php if ($isCurrent): ?>
                                        <div class="absolute inset-0 flex items-center justify-center">
                                            <div class="bg-primary/80 p-1.5 rounded-full">
                                                <i data-lucide="play" class="w-4 h-4 fill-current"></i>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="p-2">
                                    <p class="text-[10px] font-bold <?php echo $isCurrent ? 'text-primary' : 'text-white/70'; ?>">Episode <?php echo $epNum; ?></p>
                                    <p class="text-[8px] text-white/30 line-clamp-1 mt-0.5"><?php echo htmlspecialchars($ch['chapterName'] ?? ""); ?></p>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>

                    <?php if ($meta['chapterCount'] > count($chapters)): ?>
                        <div class="mt-4 text-center">
                             <p class="text-[10px] text-white/30 italic">Muat lebih banyak episode tersedia di aplikasi DramaBox</p>
                        </div>
                    <?php endif; ?>
                </div>
            </aside>
        </div>
    </div>
</main>

<script src="https://cdn.plyr.io/3.7.8/plyr.polyfilled.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const player = new Plyr('#player', {
            ratio: '9:16',
            controls: ['play-large', 'play', 'progress', 'current-time', 'mute', 'volume', 'settings', 'pip', 'airplay', 'fullscreen'],
            settings: ['quality', 'speed'],
            quality: { default: <?php echo !empty($sources) ? $sources[0]['quality'] : 720; ?>, options: <?php echo json_encode(array_column($sources, 'quality')); ?> }
        });

        // Handle quality switch
        player.on('qualitychange', event => {
            console.log('Quality changed to: ' + event.detail.quality);
        });
    });
</script>

<style>
    .custom-scrollbar::-webkit-scrollbar { width: 4px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.1); border-radius: 10px; }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: rgba(255, 255, 255, 0.2); }
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
