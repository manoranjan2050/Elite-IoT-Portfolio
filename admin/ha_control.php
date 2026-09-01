<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
redirectToLogin();

$pageTitle = 'HA Control Panel';

// Fetch entities marked for control panel
$entities = [];
try {
    $entities = $pdo->query("SELECT * FROM ha_entities WHERE show_in_control = 1 ORDER BY site, display_order, id")->fetchAll();
} catch(Exception $e) {}

// Group by type
$switches    = array_filter($entities, fn($e) => $e['entity_type'] === 'switch');
$lights      = array_filter($entities, fn($e) => $e['entity_type'] === 'light');
$automations = array_filter($entities, fn($e) => in_array($e['entity_type'], ['automation', 'scene']));
$sensors     = array_filter($entities, fn($e) => in_array($e['entity_type'], ['sensor', 'binary_sensor', 'other']));
?>
<?php include 'includes/admin_head.php'; ?>
<style>
    .control-card { transition: all 0.3s; }
    .control-card:hover { transform: translateY(-2px); }
    @keyframes pulse-glow { 0%,100% { box-shadow: 0 0 5px rgba(34,197,94,0.3); } 50% { box-shadow: 0 0 15px rgba(34,197,94,0.6); } }
    .state-on { animation: pulse-glow 2s infinite; border-color: rgba(34,197,94,0.4) !important; }
    .btn-toggle { transition: all 0.2s; }
    .btn-toggle:active { transform: scale(0.96); }
    @keyframes spin-once { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
    .loading-spin { animation: spin-once 0.5s linear; }
</style>
<?php include 'includes/sidebar.php'; ?>

    <!-- Live clock + status bar -->
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6 p-4 glass-card rounded-2xl border border-gray-800">
        <div class="flex items-center gap-4">
            <div class="relative">
                <div class="w-3 h-3 bg-green-500 rounded-full animate-ping absolute"></div>
                <div class="w-3 h-3 bg-green-500 rounded-full relative"></div>
            </div>
            <div>
                <p class="text-xs text-gray-500 uppercase font-bold tracking-widest">Control Panel Live</p>
                <p class="text-sm font-bold text-white" id="live-clock">--:--:--</p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <span class="text-xs text-gray-500">Auto-refresh:</span>
            <div class="flex gap-1">
                <button onclick="setRefresh(5)"  class="refresh-btn px-2.5 py-1 rounded-lg text-xs font-bold bg-gray-700 text-gray-300 hover:bg-blue-600 hover:text-white transition" data-secs="5">5s</button>
                <button onclick="setRefresh(15)" class="refresh-btn px-2.5 py-1 rounded-lg text-xs font-bold bg-blue-600 text-white" data-secs="15">15s</button>
                <button onclick="setRefresh(30)" class="refresh-btn px-2.5 py-1 rounded-lg text-xs font-bold bg-gray-700 text-gray-300 hover:bg-blue-600 hover:text-white transition" data-secs="30">30s</button>
                <button onclick="setRefresh(0)"  class="refresh-btn px-2.5 py-1 rounded-lg text-xs font-bold bg-gray-700 text-gray-300 hover:bg-blue-600 hover:text-white transition" data-secs="0">Off</button>
            </div>
            <button onclick="refreshAll()" class="px-3 py-1.5 bg-gray-700 hover:bg-gray-600 text-gray-300 rounded-lg text-xs font-medium transition flex items-center gap-1.5">
                <i class="fa-solid fa-rotate-right text-[10px]" id="refresh-icon"></i> Refresh
            </button>
        </div>
    </div>

    <?php if (empty($entities)): ?>
    <!-- No entities configured -->
    <div class="glass-card rounded-2xl border border-gray-800 p-12 text-center">
        <i class="fa-solid fa-gamepad text-gray-700 text-5xl mb-4 block"></i>
        <h3 class="text-lg font-bold text-gray-400 mb-2">No control entities configured</h3>
        <p class="text-sm text-gray-600 mb-6">Go to Power Entities settings and enable "Control" for entities you want to control here.</p>
        <a href="power_settings.php" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-500 text-white font-bold rounded-xl transition text-sm inline-flex items-center gap-2">
            <i class="fa-solid fa-bolt"></i> Configure Entities
        </a>
    </div>

    <?php else: ?>

    <!-- Switches Section -->
    <?php if (!empty($switches)): ?>
    <div class="mb-8">
        <h2 class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-4 flex items-center gap-2">
            <i class="fa-solid fa-toggle-on text-green-400"></i> Switches
        </h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
            <?php foreach ($switches as $e): ?>
            <div class="control-card glass-card rounded-2xl border border-gray-800 p-5 text-center" id="card-<?php echo htmlspecialchars($e['entity_key']); ?>">
                <div class="w-12 h-12 rounded-2xl bg-gray-800 flex items-center justify-center mx-auto mb-3 transition" id="icon-<?php echo htmlspecialchars($e['entity_key']); ?>">
                    <i class="fa-solid fa-power-off text-xl text-gray-500"></i>
                </div>
                <p class="text-xs font-bold text-white mb-0.5"><?php echo htmlspecialchars($e['friendly_name'] ?? $e['entity_key']); ?></p>
                <p class="text-[9px] text-gray-600 font-mono mb-3 truncate"><?php echo htmlspecialchars($e['entity_id']); ?></p>
                <p class="text-sm font-black mb-3" id="state-<?php echo htmlspecialchars($e['entity_key']); ?>">
                    <span class="text-gray-500">Loading...</span>
                </p>
                <button onclick="toggleSwitch('<?php echo htmlspecialchars($e['entity_id']); ?>', '<?php echo htmlspecialchars($e['entity_key']); ?>')"
                    id="btn-<?php echo htmlspecialchars($e['entity_key']); ?>"
                    class="btn-toggle w-full py-2 rounded-xl text-xs font-bold bg-gray-700 hover:bg-gray-600 text-gray-300 transition">
                    Toggle
                </button>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Lights Section -->
    <?php if (!empty($lights)): ?>
    <div class="mb-8">
        <h2 class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-4 flex items-center gap-2">
            <i class="fa-solid fa-lightbulb text-yellow-400"></i> Lights
        </h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
            <?php foreach ($lights as $e): ?>
            <div class="control-card glass-card rounded-2xl border border-gray-800 p-5 text-center" id="card-<?php echo htmlspecialchars($e['entity_key']); ?>">
                <div class="w-12 h-12 rounded-2xl bg-gray-800 flex items-center justify-center mx-auto mb-3 transition" id="icon-<?php echo htmlspecialchars($e['entity_key']); ?>">
                    <i class="fa-solid fa-lightbulb text-xl text-gray-500"></i>
                </div>
                <p class="text-xs font-bold text-white mb-0.5"><?php echo htmlspecialchars($e['friendly_name'] ?? $e['entity_key']); ?></p>
                <p class="text-[9px] text-gray-600 font-mono mb-3 truncate"><?php echo htmlspecialchars($e['entity_id']); ?></p>
                <p class="text-sm font-black mb-3" id="state-<?php echo htmlspecialchars($e['entity_key']); ?>">
                    <span class="text-gray-500">Loading...</span>
                </p>
                <button onclick="toggleSwitch('<?php echo htmlspecialchars($e['entity_id']); ?>', '<?php echo htmlspecialchars($e['entity_key']); ?>')"
                    id="btn-<?php echo htmlspecialchars($e['entity_key']); ?>"
                    class="btn-toggle w-full py-2 rounded-xl text-xs font-bold bg-gray-700 hover:bg-gray-600 text-gray-300 transition">
                    Toggle
                </button>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Automations / Scenes -->
    <?php if (!empty($automations)): ?>
    <div class="mb-8">
        <h2 class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-4 flex items-center gap-2">
            <i class="fa-solid fa-wand-magic-sparkles text-orange-400"></i> Automations & Scenes
        </h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
            <?php foreach ($automations as $e): ?>
            <div class="control-card glass-card rounded-2xl border border-orange-500/20 p-5 text-center">
                <div class="w-12 h-12 rounded-2xl bg-orange-500/10 flex items-center justify-center mx-auto mb-3">
                    <i class="fa-solid <?php echo $e['entity_type'] === 'scene' ? 'fa-palette' : 'fa-robot'; ?> text-xl text-orange-400"></i>
                </div>
                <p class="text-xs font-bold text-white mb-0.5"><?php echo htmlspecialchars($e['friendly_name'] ?? $e['entity_key']); ?></p>
                <p class="text-[9px] text-gray-600 font-mono mb-4 truncate"><?php echo htmlspecialchars($e['entity_id']); ?></p>
                <button onclick="triggerAutomation('<?php echo htmlspecialchars($e['entity_id']); ?>', '<?php echo $e['entity_type']; ?>', this)"
                    class="btn-toggle w-full py-2 rounded-xl text-xs font-bold bg-orange-600/20 hover:bg-orange-600/40 text-orange-400 border border-orange-500/30 transition">
                    <i class="fa-solid fa-play mr-1"></i> Trigger
                </button>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Sensor Display -->
    <?php if (!empty($sensors)): ?>
    <div class="mb-8">
        <h2 class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-4 flex items-center gap-2">
            <i class="fa-solid fa-gauge text-blue-400"></i> Live Sensor Values
        </h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-3">
            <?php foreach ($sensors as $e): ?>
            <div class="glass-card rounded-2xl border border-gray-800 p-4 text-center">
                <p class="text-[9px] text-gray-500 uppercase font-bold tracking-widest mb-2"><?php echo htmlspecialchars($e['friendly_name'] ?? $e['entity_key']); ?></p>
                <p class="text-xl font-black text-white" id="state-<?php echo htmlspecialchars($e['entity_key']); ?>">
                    <span class="text-gray-600">—</span>
                </p>
                <p class="text-[9px] text-gray-600 mt-0.5"><?php echo htmlspecialchars($e['display_unit'] ?? ''); ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php endif; // end if entities ?>

<script>
    // Live clock
    function updateClock() {
        document.getElementById('live-clock').textContent = new Date().toLocaleTimeString('en-IN', {hour12: false});
    }
    setInterval(updateClock, 1000);
    updateClock();

    // Fetch entity state
    async function fetchState(entityId, key) {
        try {
            const res = await fetch('../api/ha_proxy.php?entity=' + encodeURIComponent(entityId) + '&_t=' + Date.now());
            const data = await res.json();
            if (data.state !== undefined) {
                updateStateUI(key, data.state, data.attributes || {});
            }
        } catch(e) {}
    }

    function updateStateUI(key, state, attrs) {
        const stateEl = document.getElementById('state-' + key);
        const cardEl  = document.getElementById('card-' + key);
        const iconEl  = document.getElementById('icon-' + key);
        const btnEl   = document.getElementById('btn-' + key);

        if (stateEl) {
            if (state === 'on') {
                stateEl.innerHTML = '<span class="text-green-400">ON</span>';
                if (cardEl)  cardEl.classList.add('state-on');
                if (iconEl)  iconEl.className = 'w-12 h-12 rounded-2xl bg-green-500/15 flex items-center justify-center mx-auto mb-3 transition';
                if (btnEl)   { btnEl.className = 'btn-toggle w-full py-2 rounded-xl text-xs font-bold bg-green-600 hover:bg-green-500 text-white transition'; btnEl.textContent = '⬡ Turn OFF'; }
            } else if (state === 'off') {
                stateEl.innerHTML = '<span class="text-red-400">OFF</span>';
                if (cardEl)  cardEl.classList.remove('state-on');
                if (iconEl)  iconEl.className = 'w-12 h-12 rounded-2xl bg-gray-800 flex items-center justify-center mx-auto mb-3 transition';
                if (btnEl)   { btnEl.className = 'btn-toggle w-full py-2 rounded-xl text-xs font-bold bg-gray-700 hover:bg-gray-600 text-gray-300 transition'; btnEl.textContent = '⬡ Turn ON'; }
            } else {
                stateEl.innerHTML = '<span class="text-gray-300">' + state + '</span>';
                if (stateEl && !stateEl.closest('[id^="card-"]')) {
                    stateEl.textContent = state;
                }
            }
        }
    }

    // Toggle switch/light
    async function toggleSwitch(entityId, key) {
        const btn = document.getElementById('btn-' + key);
        if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>'; }

        try {
            const res = await fetch('../api/ha_control.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ entity_id: entityId, action: 'toggle' })
            });
            const data = await res.json();
            if (data.success) {
                setTimeout(() => fetchState(entityId, key), 600);
            } else {
                alert('Error: ' + (data.error || 'Unknown error'));
                if (btn) { btn.disabled = false; btn.textContent = 'Toggle'; }
            }
        } catch(e) {
            alert('Connection error');
            if (btn) { btn.disabled = false; btn.textContent = 'Toggle'; }
        }
    }

    // Trigger automation/scene
    async function triggerAutomation(entityId, type, btn) {
        btn.disabled = true;
        const orig = btn.innerHTML;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1"></i> Running...';

        try {
            const action = type === 'scene' ? 'turn_on' : 'trigger';
            const res = await fetch('../api/ha_control.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ entity_id: entityId, action: action, entity_type: type })
            });
            const data = await res.json();
            if (data.success) {
                btn.innerHTML = '<i class="fa-solid fa-check mr-1"></i> Done!';
                btn.className = btn.className.replace('bg-orange-600/20', 'bg-green-600/20').replace('text-orange-400', 'text-green-400');
                setTimeout(() => { btn.innerHTML = orig; btn.disabled = false; }, 2000);
            } else {
                alert('Error: ' + (data.error || 'Unknown'));
                btn.innerHTML = orig; btn.disabled = false;
            }
        } catch(e) {
            alert('Connection error');
            btn.innerHTML = orig; btn.disabled = false;
        }
    }

    // Refresh all entity states
    const entityMap = <?php
        $map = [];
        foreach ($entities as $e) { $map[$e['entity_key']] = $e['entity_id']; }
        echo json_encode($map);
    ?>;

    function refreshAll() {
        const icon = document.getElementById('refresh-icon');
        icon.classList.add('loading-spin');
        setTimeout(() => icon.classList.remove('loading-spin'), 500);
        for (const [key, entityId] of Object.entries(entityMap)) {
            fetchState(entityId, key);
        }
    }

    // Auto-refresh
    let refreshTimer = null;
    function setRefresh(secs) {
        if (refreshTimer) clearInterval(refreshTimer);
        document.querySelectorAll('.refresh-btn').forEach(b => {
            b.className = b.dataset.secs == secs
                ? b.className.replace('bg-gray-700 text-gray-300', 'bg-blue-600 text-white')
                : b.className.replace('bg-blue-600 text-white', 'bg-gray-700 text-gray-300');
        });
        if (secs > 0) refreshTimer = setInterval(refreshAll, secs * 1000);
    }

    // Initial load
    refreshAll();
    setRefresh(15);
</script>

<?php include 'includes/admin_footer.php'; ?>
