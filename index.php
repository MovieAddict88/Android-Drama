<?php
// php_project/index.php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/DramaBox.php';

$query = $_GET['q'] ?? '';
$pageTitle = $query ? "Hasil Pencarian: " . htmlspecialchars($query) : "Drama Terbaru";

$initialRecords = [];
if ($query) {
    $res = DramaBox::fetchSuggest($query);
    $raw = $res['data']['data']['suggestList'] ?? [];
    foreach ($raw as $r) {
        $initialRecords[] = DramaBox::mapToItem($r);
    }
} else {
    $res = DramaBox::fetchLatest(1);
    $raw = $res['data']['data']['newTheaterList']['records'] ?? [];
    foreach ($raw as $r) {
        $initialRecords[] = DramaBox::mapToItem($r);
    }
}

// Get donors
$db = getDB();
$donors = $db->query("SELECT * FROM donors ORDER BY amount DESC LIMIT 10")->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<main class="py-6 sm:py-8 min-h-screen">
    <section class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

        <?php if (!$query): ?>
        <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-8 gap-6">
            <div class="flex gap-4 items-center">
                <div class="w-20 h-20 flex-shrink-0">
                    <lottie-player src="assets/dotlottie/Fire.lottie" background="transparent" speed="1" style="width: 100%; height: 100%;" loop autoplay></lottie-player>
                </div>
                <div>
                    <h2 class="text-3xl font-extrabold grad-text">Terbaru!</h2>
                    <p class="text-white/60 mt-1">Nikmati drama <span class="text-white font-semibold">terbaru</span> setiap harinya secara gratis!</p>
                </div>
            </div>

            <!-- Donatur Leaderboard -->
            <div>
                <button onclick="document.getElementById('donorModal').classList.remove('hidden')"
                        class="px-5 py-2.5 rounded-xl bg-primary hover:bg-primary/90 text-white text-sm font-bold shadow-lg shadow-primary/20 transition-all active:scale-95 flex items-center gap-2">
                    <i data-lucide="crown" class="w-4 h-4 text-yellow-300 fill-current"></i>
                    Lihat Leaderboard Donatur
                </button>
            </div>
        </div>
        <?php endif; ?>

        <div id="dramaGrid" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4 sm:gap-6">
            <?php foreach ($initialRecords as $item): ?>
                <a href="watch.php?id=<?php echo $item['bookId']; ?>" class="group block relative rounded-2xl overflow-hidden bg-white/5 border border-white/5 hover:border-white/20 transition-all duration-300">
                    <div class="aspect-[3/4] relative overflow-hidden">
                        <img src="<?php echo htmlspecialchars($item['coverWap']); ?>" alt="<?php echo htmlspecialchars($item['bookName']); ?>" class="absolute inset-0 w-full h-full object-cover transition duration-500 group-hover:scale-110">
                        <div class="absolute inset-0 bg-gradient-to-t from-night-950 via-night-950/20 to-transparent opacity-80"></div>

                        <?php if (isset($item['corner']['name'])): ?>
                            <div class="absolute top-2 right-2 px-2 py-1 rounded-md text-[10px] font-bold text-white shadow-lg" style="background: <?php echo $item['corner']['color']; ?>">
                                <?php echo htmlspecialchars($item['corner']['name']); ?>
                            </div>
                        <?php endif; ?>

                        <div class="absolute bottom-2 left-2 flex items-center gap-1.5 text-white/70 text-[10px] font-medium bg-night-950/60 backdrop-blur-md px-2 py-1 rounded-lg">
                            <i data-lucide="play" class="w-3 h-3 fill-current"></i>
                            <?php echo $item['playCount']; ?>
                        </div>
                    </div>
                    <div class="p-3">
                        <h3 class="text-sm font-semibold line-clamp-1 group-hover:text-primary transition"><?php echo htmlspecialchars($item['bookName']); ?></h3>
                        <?php if (isset($item['chapterCount'])): ?>
                            <p class="text-[10px] text-white/40 mt-1 uppercase tracking-wider"><?php echo $item['chapterCount']; ?> Episode</p>
                        <?php endif; ?>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>

        <?php if (!$query): ?>
        <div id="loadMoreContainer" class="mt-12 text-center">
            <button id="btnLoadMore" class="px-8 py-3 rounded-2xl bg-white/5 hover:bg-white/10 border border-white/10 font-bold transition-all active:scale-95">
                Muat lebih banyak
            </button>
        </div>
        <?php endif; ?>
    </section>
</main>

<!-- Donor Modal -->
<div id="donorModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-night-950/80 backdrop-blur-sm">
    <div class="bg-night-900 w-full max-w-md rounded-3xl overflow-hidden shadow-2xl border border-white/10 animate-in fade-in zoom-in duration-300">
        <div class="flex justify-between items-center p-6 border-b border-white/10">
            <div>
                <h3 class="text-xl font-bold">Leaderboard Donatur</h3>
                <p class="text-sm text-white/40 mt-1">Terima kasih atas dukungannya! ❤️</p>
            </div>
            <button onclick="document.getElementById('donorModal').classList.add('hidden')" class="p-2 hover:bg-white/5 rounded-full transition">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <div class="p-6 max-h-[400px] overflow-y-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="text-white/30 text-xs uppercase tracking-widest border-b border-white/5">
                        <th class="pb-4 font-medium">Rank</th>
                        <th class="pb-4 font-medium">Nama</th>
                        <th class="pb-4 font-medium text-right">Jumlah</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    <?php foreach ($donors as $index => $donor): ?>
                    <tr>
                        <td class="py-4">
                            <?php if ($index === 0): ?>
                                <span class="flex items-center gap-1.5 font-bold text-yellow-400">
                                    <i data-lucide="crown" class="w-4 h-4 fill-current"></i> #1
                                </span>
                            <?php else: ?>
                                <span class="text-white/40">#<?php echo $index + 1; ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="py-4 font-medium"><?php echo htmlspecialchars($donor['name']); ?></td>
                        <td class="py-4 text-right font-mono text-primary">Rp <?php echo number_format($donor['amount'], 0, ',', '.'); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="p-6 bg-night-950/50 border-t border-white/10 text-center">
            <button onclick="this.nextElementSibling.classList.remove('hidden'); this.remove();" class="w-full py-3 rounded-xl bg-green-600 hover:bg-green-700 font-bold transition">
                💳 Tampilkan QRIS Donasi
            </button>
            <div class="hidden">
                <img src="assets/qr-donasi.png" alt="QRIS" class="mx-auto w-48 h-48 rounded-2xl mb-3">
                <p class="text-xs text-white/40 italic">Scan QRIS di atas untuk berdonasi</p>
            </div>
        </div>
    </div>
</div>

<script>
    let currentPage = 1;
    let isLoading = false;
    const isSearch = <?php echo $query ? 'true' : 'false'; ?>;

    if (!isSearch) {
        document.getElementById('btnLoadMore').addEventListener('click', loadMore);

        async function loadMore() {
            if (isLoading) return;
            isLoading = true;
            const btn = document.getElementById('btnLoadMore');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i data-lucide="loader-2" class="w-5 h-5 animate-spin mx-auto"></i>';
            lucide.createIcons();

            try {
                currentPage++;
                const response = await fetch(`api/index.php?action=latest&page=${currentPage}`);
                const data = await response.json();

                if (data.records && data.records.length > 0) {
                    const grid = document.getElementById('dramaGrid');
                    data.records.forEach(item => {
                        const card = createCard(item);
                        grid.appendChild(card);
                    });
                    lucide.createIcons();
                } else {
                    document.getElementById('loadMoreContainer').innerHTML = '<p class="text-white/30 italic">Sudah mencapai akhir ✨</p>';
                }
            } catch (err) {
                console.error(err);
                alert('Gagal memuat drama. Silakan coba lagi.');
            } finally {
                isLoading = false;
                if (btn) btn.innerHTML = originalText;
            }
        }

        function createCard(item) {
            const div = document.createElement('div');
            div.innerHTML = `
                <a href="watch.php?id=${item.bookId}" class="group block relative rounded-2xl overflow-hidden bg-white/5 border border-white/5 hover:border-white/20 transition-all duration-300">
                    <div class="aspect-[3/4] relative overflow-hidden">
                        <img src="${item.coverWap}" alt="${item.bookName}" class="absolute inset-0 w-full h-full object-cover transition duration-500 group-hover:scale-110">
                        <div class="absolute inset-0 bg-gradient-to-t from-night-950 via-night-950/20 to-transparent opacity-80"></div>
                        ${item.corner ? `
                            <div class="absolute top-2 right-2 px-2 py-1 rounded-md text-[10px] font-bold text-white shadow-lg" style="background: ${item.corner.color}">
                                ${item.corner.name}
                            </div>
                        ` : ''}
                        <div class="absolute bottom-2 left-2 flex items-center gap-1.5 text-white/70 text-[10px] font-medium bg-night-950/60 backdrop-blur-md px-2 py-1 rounded-lg">
                            <i data-lucide="play" class="w-3 h-3 fill-current"></i>
                            ${item.playCount}
                        </div>
                    </div>
                    <div class="p-3">
                        <h3 class="text-sm font-semibold line-clamp-1 group-hover:text-primary transition">${item.bookName}</h3>
                        ${item.chapterCount ? `<p class="text-[10px] text-white/40 mt-1 uppercase tracking-wider">${item.chapterCount} Episode</p>` : ''}
                    </div>
                </a>
            `;
            return div.firstElementChild;
        }
    }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
