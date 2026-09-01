    </main><!-- End Page Content -->

    <footer class="border-t border-gray-800 px-6 py-4 text-center">
        <p class="text-xs text-gray-600">© <?php echo date('Y'); ?> manoranjan.dev — Admin Panel</p>
    </footer>
</div><!-- End Main Wrapper -->

<script>
    // Sidebar mobile toggle
    const sidebarToggle = document.getElementById('sidebar-toggle');
    const sidebar = document.getElementById('sidebar');
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('-translate-x-full');
        });
        // Close on outside click
        document.addEventListener('click', (e) => {
            if (!sidebar.contains(e.target) && !sidebarToggle.contains(e.target)) {
                sidebar.classList.add('-translate-x-full');
            }
        });
    }

    // HA status check in top bar
    async function checkHAStatus() {
        const badge = document.getElementById('ha-status-badge');
        const dot = document.getElementById('ha-dot');
        const text = document.getElementById('ha-status-text');
        if (!badge) return;
        badge.classList.remove('hidden');
        badge.classList.add('flex');
        try {
            const res = await fetch('../api/ha_proxy.php?entity=sensor.time&_t=' + Date.now());
            if (res.ok) {
                const data = await res.json();
                if (!data.error) {
                    dot.className = 'w-2 h-2 rounded-full bg-green-400';
                    text.textContent = 'HA Online';
                    badge.className = 'flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-bold bg-green-500/10 text-green-400 border border-green-500/20';
                    return;
                }
            }
        } catch(e) {}
        dot.className = 'w-2 h-2 rounded-full bg-red-400';
        text.textContent = 'HA Offline';
        badge.className = 'flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-bold bg-red-500/10 text-red-400 border border-red-500/20';
    }
    checkHAStatus();
</script>
</body>
</html>
