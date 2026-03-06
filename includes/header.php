<!-- php_project/includes/header.php -->
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . " - " . PROJECT_NAME : PROJECT_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        night: {
                            50: '#f8fafc',
                            100: '#f1f5f9',
                            200: '#e2e8f0',
                            300: '#cbd5e1',
                            400: '#94a3b8',
                            500: '#64748b',
                            600: '#475569',
                            700: '#334155',
                            800: '#1e293b',
                            900: '#0f172a',
                            950: '#020617',
                        },
                        primary: '#4D65ED',
                    }
                }
            }
        }
    </script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .glass { background: rgba(255, 255, 255, 0.03); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.1); }
        .grad-text { background: linear-gradient(135deg, #fff 0%, rgba(255, 255, 255, 0.7) 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
    </style>
    <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-night-950 text-white selection:bg-primary/30">
    <header class="sticky top-0 z-40 w-full glass border-b border-white/5 bg-night-950/80">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex h-16 items-center justify-between gap-4">
                <a href="index.php" class="flex items-center gap-2 group">
                    <div class="bg-primary p-1.5 rounded-lg group-hover:scale-110 transition">
                        <i data-lucide="play" class="w-5 h-5 fill-current"></i>
                    </div>
                    <span class="font-bold text-xl tracking-tight"><?php echo PROJECT_NAME; ?></span>
                </a>

                <div class="flex-1 max-w-md hidden sm:block">
                    <form action="index.php" method="GET" class="relative">
                        <input type="text" name="q" value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>"
                               placeholder="Cari drama..."
                               class="w-full bg-white/5 border border-white/10 rounded-xl py-2 pl-10 pr-4 focus:outline-none focus:ring-2 focus:ring-primary/50 transition text-sm">
                        <i data-lucide="search" class="absolute left-3 top-2.5 w-4 h-4 text-white/40"></i>
                    </form>
                </div>

                <div class="flex items-center gap-3">
                    <a href="https://t.me/dramaboxgratis" target="_blank" class="p-2 hover:bg-white/5 rounded-lg transition text-white/70 hover:text-white">
                        <i data-lucide="send" class="w-5 h-5"></i>
                    </a>
                    <button class="sm:hidden p-2 hover:bg-white/5 rounded-lg transition text-white/70">
                        <i data-lucide="search" class="w-5 h-5"></i>
                    </button>
                </div>
            </div>
        </div>
    </header>
