<!-- php_project/includes/footer.php -->
    <footer class="mt-auto border-t border-white/5 py-10 glass">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-center">
                <div>
                    <div class="flex items-center gap-2 mb-4">
                        <div class="bg-primary p-1.5 rounded-lg">
                            <i data-lucide="play" class="w-4 h-4 fill-current"></i>
                        </div>
                        <span class="font-bold text-lg"><?php echo PROJECT_NAME; ?></span>
                    </div>
                    <p class="text-white/50 text-sm max-w-xs leading-relaxed">
                        Nonton drama favoritmu secara gratis tanpa ribet. Update setiap hari dengan kualitas terbaik.
                    </p>
                </div>
                <div class="flex flex-col md:items-end gap-4">
                    <div class="flex items-center gap-6 text-sm text-white/60">
                        <a href="#" class="hover:text-white transition">Tentang Kami</a>
                        <a href="#" class="hover:text-white transition">Kebijakan Privasi</a>
                        <a href="https://t.me/dramaboxgratis" class="hover:text-white transition">Kontak</a>
                    </div>
                    <p class="text-white/30 text-xs">
                        &copy; <?php echo date('Y'); ?> <?php echo PROJECT_NAME; ?>. All rights reserved.
                    </p>
                </div>
            </div>
        </div>
    </footer>
    <script>
        lucide.createIcons();
    </script>
</body>
</html>
