<?php
require_once 'includes/db.php';
$pageTitle = "IoT Control Center | manoranjan.dev";
include 'includes/header.php';

// Fetch dynamic control entities from DB
$controlEntities = [];
try {
    $controlEntities = $pdo->query(
        "SELECT * FROM ha_entities WHERE show_in_control = 1 ORDER BY site, display_order, id"
    )->fetchAll();
} catch(Exception $e) {}

// Whether to show the Shop Pump gauges/stats (admin -> HA Settings). Off by default.
$pumpMetersEnabled = false;
try {
    $st = $pdo->prepare("SELECT setting_value FROM ha_settings WHERE setting_key = 'pump_meters_enabled'");
    $st->execute();
    $pumpMetersEnabled = $st->fetchColumn() === '1';
} catch(Exception $e) {}

// Water pump entity IDs (from user's HA config)
$pump = [
    'voltage' => 'sensor.shop_waterpump_pump_voltage',
    'amps'    => 'sensor.shop_waterpump_pump_amps',
    'watts'   => 'sensor.shop_waterpump_pump_watts',
    'kwh'     => 'sensor.shop_waterpump_pump_total_energy',
    'freq'    => 'sensor.shop_waterpump_pump_ac_frequency',
    'status'  => 'binary_sensor.shop_waterpump_pump_running_status',
    'start'   => 'switch.shop_waterpump_start_pump',
    'stop'    => 'switch.shop_waterpump_stop_pump',
];

// Grid Control Center - inverter grid-connection switches
$gridSwitches = [
    ['entity_key' => 'inverter_one_grid', 'entity_id' => 'switch.inverter_one_grid_connection', 'friendly_name' => 'Inverter ONE'],
    ['entity_key' => 'inverter_two_grid', 'entity_id' => 'switch.inverter_two_inverter_two_grid', 'friendly_name' => 'Inverter TWO'],
];

// Group dynamic switch/light entities into named card sections.
// Pumps are detected by "pump" in the name; everything else splits by type + site.
$isPumpEntity = fn($e) => $e['entity_type'] === 'switch' && stripos($e['friendly_name'] ?? $e['entity_key'], 'pump') !== false;
$homeWaterPump = array_filter($controlEntities, fn($e) => $isPumpEntity($e) && $e['site'] === 'home');
$shopLights    = array_filter($controlEntities, fn($e) => $e['entity_type'] === 'light' && $e['site'] === 'shop');

// Shop front lighting - hardcoded (not yet in ha_entities admin table)
$shopLightsExtra = [
    ['entity_key' => 'shop_ceiling_light', 'entity_id' => 'switch.shop_control_1_front_ceiling_light', 'friendly_name' => 'Ceiling Light', 'icon' => 'fa-solid fa-lightbulb', 'color' => 'orange'],
    ['entity_key' => 'shop_front_light',   'entity_id' => 'switch.shop_control_1_front_light',          'friendly_name' => 'Front Light',   'icon' => 'fa-solid fa-sun',      'color' => 'yellow'],
    ['entity_key' => 'shop_rope_blue',     'entity_id' => 'switch.shop_control_1_front_rope_light_blue', 'friendly_name' => 'Blue Rope',     'icon' => 'fa-solid fa-grip-lines', 'color' => 'blue'],
    ['entity_key' => 'shop_rope_green',    'entity_id' => 'switch.shop_control_1_front_rope_light_green','friendly_name' => 'Green Rope',    'icon' => 'fa-solid fa-grip-lines', 'color' => 'green'],
];
$shopLights = array_merge($shopLights, $shopLightsExtra);
$homeLights    = array_filter($controlEntities, fn($e) => $e['entity_type'] === 'light' && $e['site'] === 'home');
$otherSwitches = array_filter($controlEntities, fn($e) => $e['entity_type'] === 'switch' && !$isPumpEntity($e));
?>

<style>
    /* ── Page Base ── */
    .control-page { background: #030712; }

    /* Grid background overlay */
    .grid-overlay {
        position: fixed; inset: 0; z-index: 0; pointer-events: none;
        background-image:
            linear-gradient(rgba(59,130,246,0.03) 1px, transparent 1px),
            linear-gradient(90deg, rgba(59,130,246,0.03) 1px, transparent 1px);
        background-size: 40px 40px;
    }

    /* ── Section Cards ── */
    .ctrl-section {
        background: rgba(15,20,35,0.85);
        backdrop-filter: blur(16px);
        border: 1px solid rgba(255,255,255,0.07);
        border-radius: 1.5rem;
    }
    @media (min-width: 640px) { .ctrl-section { border-radius: 2rem; } }
    .ctrl-section-header {
        padding: 1rem 1.25rem;
        border-bottom: 1px solid rgba(255,255,255,0.06);
        display: flex; align-items: center; justify-content: space-between;
        gap: 0.75rem; flex-wrap: wrap;
    }
    @media (min-width: 640px) { .ctrl-section-header { padding: 1.25rem 1.75rem; } }

    /* ── Gauge Canvas Wrapper ── */
    .gauge-wrap { position: relative; width: 100%; padding-top: 75%; }
    .gauge-wrap canvas { position: absolute; inset: 0; width: 100% !important; height: 100% !important; }

    /* ── Entity Cards ── */
    .entity-card {
        background: rgba(255,255,255,0.03);
        border: 1px solid rgba(255,255,255,0.06);
        border-radius: 1rem; padding: 0.75rem 0.875rem; text-align: center;
        transition: all 0.3s;
    }
    @media (min-width: 640px) { .entity-card { padding: 0.875rem 1rem; } }
    .entity-card:hover { background: rgba(255,255,255,0.06); border-color: rgba(59,130,246,0.3); }

    /* ── Status Indicator ── */
    @keyframes pulse-green { 0%,100%{box-shadow:0 0 0 0 rgba(34,197,94,0.5);} 50%{box-shadow:0 0 0 10px rgba(34,197,94,0);} }
    @keyframes pulse-red   { 0%,100%{box-shadow:0 0 0 0 rgba(239,68,68,0.5);}  50%{box-shadow:0 0 0 10px rgba(239,68,68,0);} }
    .led-green { background:#22c55e; animation: pulse-green 2s infinite; border-radius:50%; width:12px; height:12px; }
    .led-red   { background:#ef4444; animation: pulse-red   2s infinite; border-radius:50%; width:12px; height:12px; }
    .led-gray  { background:#6b7280; border-radius:50%; width:12px; height:12px; }

    /* ── Control Buttons ── */
    .btn-start {
        background: linear-gradient(135deg, #16a34a, #15803d);
        border: 1px solid rgba(34,197,94,0.4);
        border-radius: 1.25rem; padding: 1rem 1.5rem;
        min-height: 56px;
        color: #fff; font-weight: 900; font-size: 0.9rem; letter-spacing: 0.08em;
        transition: all 0.25s; cursor: pointer; width: 100%;
        display: flex; align-items: center; justify-content: center; gap: 0.75rem;
        -webkit-tap-highlight-color: transparent;
    }
    @media (min-width: 640px) { .btn-start { padding: 1.25rem 2rem; font-size: 1rem; } }
    .btn-start:hover { background: linear-gradient(135deg, #22c55e, #16a34a); box-shadow: 0 0 30px rgba(34,197,94,0.4); transform: translateY(-2px); }
    .btn-start:active { transform: scale(0.98); box-shadow: none; }
    .btn-start:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }

    .btn-stop {
        background: linear-gradient(135deg, #dc2626, #b91c1c);
        border: 1px solid rgba(239,68,68,0.4);
        border-radius: 1.25rem; padding: 1rem 1.5rem;
        min-height: 56px;
        color: #fff; font-weight: 900; font-size: 0.9rem; letter-spacing: 0.08em;
        transition: all 0.25s; cursor: pointer; width: 100%;
        display: flex; align-items: center; justify-content: center; gap: 0.75rem;
        -webkit-tap-highlight-color: transparent;
    }
    @media (min-width: 640px) { .btn-stop { padding: 1.25rem 2rem; font-size: 1rem; } }
    .btn-stop:hover { background: linear-gradient(135deg, #ef4444, #dc2626); box-shadow: 0 0 30px rgba(239,68,68,0.4); transform: translateY(-2px); }
    .btn-stop:active { transform: scale(0.98); box-shadow: none; }
    .btn-stop:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }

    /* ── Pump Running Banner ── */
    @keyframes running-shimmer {
        0%   { background-position: -200% center; }
        100% { background-position: 200% center; }
    }
    .pump-running-banner {
        background: linear-gradient(90deg, rgba(34,197,94,0.1), rgba(34,197,94,0.25), rgba(34,197,94,0.1));
        background-size: 200% auto;
        animation: running-shimmer 3s linear infinite;
        border: 1px solid rgba(34,197,94,0.3);
        border-radius: 1rem;
        padding: 0.875rem 1.25rem;
    }
    .pump-stopped-banner {
        background: rgba(239,68,68,0.08);
        border: 1px solid rgba(239,68,68,0.2);
        border-radius: 1rem;
        padding: 0.875rem 1.25rem;
    }

    /* ── Pattern Lock Modal ── */
    #pattern-modal {
        position: fixed; inset: 0; z-index: 100;
        background: rgba(0,0,0,0.85); backdrop-filter: blur(8px);
        display: none; align-items: center; justify-content: center; padding: 1rem;
    }
    #pattern-modal.open { display: flex; animation: fadeIn 0.2s ease; }
    @keyframes fadeIn { from{opacity:0;transform:scale(0.96)} to{opacity:1;transform:scale(1)} }

    .pattern-card {
        background: rgba(13,17,28,0.98);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 1.5rem;
        padding: 1.5rem;
        width: 100%; max-width: 340px;
        text-align: center;
    }
    @media (min-width: 400px) { .pattern-card { border-radius: 2rem; padding: 2rem; } }
    #pattern-canvas {
        width: min(240px, calc(100vw - 80px));
        height: min(240px, calc(100vw - 80px));
        cursor: crosshair;
        touch-action: none;
        border-radius: 1rem;
        background: rgba(255,255,255,0.02);
        border: 1px solid rgba(255,255,255,0.05);
        display: block; margin: 0 auto;
    }
    #pattern-msg {
        min-height: 24px;
        font-size: 0.8rem; font-weight: 600;
        margin-top: 0.75rem;
        transition: all 0.3s;
    }

    /* ── Value counter animation ── */
    @keyframes countUp { from{opacity:0;transform:translateY(6px)} to{opacity:1;transform:translateY(0)} }
    .val-updated { animation: countUp 0.4s ease; }

    /* ── Lock icon bounce on hover ── */
    @keyframes lock-bounce { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-3px)} }
    .lock-bounce:hover i { animation: lock-bounce 0.6s ease infinite; }

    /* ── Old-model manual rocker switch ── */
    .old-switch-wrap { width: 56px; height: 84px; margin: 0 auto; }
    .old-switch {
        position: relative;
        width: 56px; height: 84px;
        background: linear-gradient(145deg, #3a3a3a, #161616);
        border-radius: 9px;
        border: 2px solid #000;
        box-shadow: inset 0 2px 3px rgba(255,255,255,0.08), inset 0 -3px 8px rgba(0,0,0,0.7), 0 4px 10px rgba(0,0,0,0.5);
        cursor: pointer;
        -webkit-tap-highlight-color: transparent;
        user-select: none;
        transition: box-shadow 0.2s;
    }
    .old-switch:hover { box-shadow: inset 0 2px 3px rgba(255,255,255,0.08), inset 0 -3px 8px rgba(0,0,0,0.7), 0 4px 14px rgba(59,130,246,0.35); }
    .old-switch:active .old-switch-rocker { transform: scale(0.97); }
    .old-switch .sw-label {
        position: absolute; left: 0; right: 0; text-align: center;
        font-size: 8px; font-weight: 900; letter-spacing: 0.1em;
        color: #4b5563; pointer-events: none;
    }
    .old-switch .sw-label-on  { top: 6px; }
    .old-switch .sw-label-off { bottom: 6px; }
    .old-switch.on .sw-label-on   { color: #22c55e; }
    .old-switch:not(.on) .sw-label-off { color: #ef4444; }
    .old-switch-rocker {
        position: absolute; left: 7px; right: 7px; top: 7px;
        height: 34px;
        background: linear-gradient(180deg, #e8e8e8, #a3a3a3);
        border-radius: 5px;
        border: 1px solid #7a7a7a;
        box-shadow: 0 3px 6px rgba(0,0,0,0.55), inset 0 1px 1px rgba(255,255,255,0.7), inset 0 -2px 2px rgba(0,0,0,0.15);
        transition: top 0.22s cubic-bezier(.34,1.3,.64,1), background 0.22s;
    }
    .old-switch.on .old-switch-rocker {
        top: 43px;
        background: linear-gradient(180deg, #4ade80, #15803d);
        border-color: #166534;
    }
    .old-switch.locked { opacity: 0.55; cursor: not-allowed; }
    .old-switch-screw {
        position: absolute; width: 5px; height: 5px; border-radius: 50%;
        background: radial-gradient(circle at 35% 35%, #8a8a8a, #2a2a2a);
        box-shadow: 0 0.5px 1px rgba(255,255,255,0.3);
    }
    .old-switch-screw.tl { top: 4px; left: 4px; }
    .old-switch-screw.br { bottom: 4px; right: 4px; }
</style>

<div class="control-page min-h-screen pt-24 md:pt-32 pb-24 px-4 relative overflow-hidden">
<div class="grid-overlay"></div>

<!-- Blobs -->
<div style="position:absolute;width:600px;height:600px;background:rgba(59,130,246,0.06);filter:blur(100px);border-radius:50%;top:-100px;left:-200px;z-index:0;pointer-events:none;"></div>
<div style="position:absolute;width:500px;height:500px;background:rgba(34,197,94,0.05);filter:blur(100px);border-radius:50%;bottom:0;right:-100px;z-index:0;pointer-events:none;"></div>

<div class="container mx-auto max-w-5xl relative z-10">

    <!-- ── PAGE HEADER ── -->
    <div class="text-center mb-8 md:mb-12" data-aos="fade-up">
        <div class="inline-flex items-center gap-2 px-4 py-1.5 bg-green-500/10 border border-green-500/20 rounded-full text-green-400 text-xs font-bold uppercase tracking-widest mb-4">
            <div class="w-2 h-2 rounded-full bg-green-400 animate-ping absolute"></div>
            <div class="w-2 h-2 rounded-full bg-green-400 relative mr-1"></div>
            Live Control Panel
        </div>
        <h1 class="text-3xl sm:text-4xl md:text-5xl font-extrabold mb-3">
            <span class="text-gradient">IoT Control Center</span>
        </h1>
        <p class="text-gray-500 text-xs sm:text-sm uppercase tracking-widest px-4">Real-time monitoring · Pattern-protected controls</p>
        <div class="flex items-center justify-center gap-4 sm:gap-6 mt-4 text-xs text-gray-600">
            <span><i class="fa-solid fa-clock mr-1"></i><span id="page-clock">--:--:--</span></span>
            <span><i class="fa-solid fa-rotate-right mr-1"></i>Auto-refresh <span id="refresh-countdown">15s</span></span>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════
         SHOP: PUMP CONTROL
    ══════════════════════════════════════════════════ -->
    <div class="ctrl-section mb-8" data-aos="fade-up">
        <div class="ctrl-section-header">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-blue-500/15 flex items-center justify-center">
                    <i class="fa-solid fa-water text-blue-400 text-lg"></i>
                </div>
                <div>
                    <h2 class="font-black text-white tracking-tight">⚡ SHOP: PUMP CONTROL</h2>
                    <p class="text-[10px] text-gray-600 uppercase tracking-widest font-bold">Site A · Water Pump System</p>
                </div>
            </div>
            <!-- Pump running status badge -->
            <div id="pump-status-badge" class="flex items-center gap-2 px-3 py-1.5 bg-gray-800 rounded-full border border-gray-700 text-xs font-bold text-gray-500 transition-all">
                <div class="led-gray" id="pump-led"></div>
                <span id="pump-status-text">LOADING...</span>
            </div>
        </div>

        <div class="p-4 sm:p-5 md:p-7 space-y-5 md:space-y-6">

            <?php if ($pumpMetersEnabled): ?>
            <!-- Gauges Row -->
            <div class="grid grid-cols-2 gap-3 sm:gap-4 md:gap-6">
                <div>
                    <p class="text-[9px] font-bold text-gray-500 uppercase tracking-widest text-center mb-2">Line Voltage</p>
                    <div class="gauge-wrap">
                        <canvas id="gauge-voltage"></canvas>
                    </div>
                </div>
                <div>
                    <p class="text-[9px] font-bold text-gray-500 uppercase tracking-widest text-center mb-2">Motor Load</p>
                    <div class="gauge-wrap">
                        <canvas id="gauge-amps"></canvas>
                    </div>
                </div>
            </div>

            <!-- Entity Cards Row -->
            <div class="grid grid-cols-3 gap-3">
                <div class="entity-card">
                    <p class="text-[9px] text-gray-600 uppercase font-bold tracking-widest mb-1.5">Power</p>
                    <p class="text-xl font-black text-white"><span id="pump-watts">--</span></p>
                    <p class="text-[10px] text-gray-600 mt-0.5">Watts</p>
                </div>
                <div class="entity-card">
                    <p class="text-[9px] text-gray-600 uppercase font-bold tracking-widest mb-1.5">Total kWh</p>
                    <p class="text-xl font-black text-white"><span id="pump-kwh">--</span></p>
                    <p class="text-[10px] text-gray-600 mt-0.5">kWh</p>
                </div>
                <div class="entity-card">
                    <p class="text-[9px] text-gray-600 uppercase font-bold tracking-widest mb-1.5">Frequency</p>
                    <p class="text-xl font-black text-white"><span id="pump-freq">--</span></p>
                    <p class="text-[10px] text-gray-600 mt-0.5">Hz</p>
                </div>
            </div>
            <?php endif; ?>

            <!-- Pump Status Banner -->
            <div id="pump-status-banner" class="pump-stopped-banner flex items-center gap-3">
                <div class="led-gray" id="banner-led"></div>
                <div class="flex-1">
                    <p class="text-sm font-bold" id="banner-title">Motor Status: Loading...</p>
                    <p class="text-[10px] text-gray-500" id="banner-sub">Fetching data from Home Assistant</p>
                </div>
                <i class="fa-solid fa-water-ladder text-2xl opacity-20" id="banner-icon"></i>
            </div>

            <!-- CONTROL BUTTONS — Pattern Protected -->
            <div>
                <div class="flex items-center gap-2 mb-3">
                    <i class="fa-solid fa-lock text-gray-600 text-xs"></i>
                    <p class="text-[10px] text-gray-600 uppercase font-bold tracking-widest">Pattern-protected controls</p>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <button class="btn-start lock-bounce"
                        onclick="askPattern('<?php echo $pump['start']; ?>', 'turn_on', 'START PUMP', 'start')">
                        <i class="fa-solid fa-play text-lg"></i>
                        <span>START</span>
                    </button>
                    <button class="btn-stop lock-bounce"
                        onclick="askPattern('<?php echo $pump['stop']; ?>', 'turn_on', 'STOP PUMP', 'stop')">
                        <i class="fa-solid fa-stop text-lg"></i>
                        <span>STOP</span>
                    </button>
                </div>
                <p class="text-[10px] text-gray-700 text-center mt-2">
                    <i class="fa-solid fa-shield-halved mr-1"></i>
                    Controls require pattern authentication · Monitoring is public
                </p>
            </div>
        </div>
    </div>

    <?php
    // Full literal Tailwind classes per color name (kept literal in source so the
    // Tailwind content scanner picks them up - interpolated "text-{$color}-400"
    // strings would NOT be detected by the build).
    $switchIconColors = [
        'orange' => 'bg-orange-500/15 text-orange-400',
        'yellow' => 'bg-yellow-500/15 text-yellow-400',
        'blue'   => 'bg-blue-500/15 text-blue-400',
        'green'  => 'bg-green-500/15 text-green-400',
        'purple' => 'bg-purple-500/15 text-purple-400',
        'red'    => 'bg-red-500/15 text-red-400',
    ];

    // Reusable renderer for a group of old-style rocker switch cards
    function renderSwitchGroup($title, $icon, $iconBg, $subtitle, $entities) {
        global $switchIconColors;
        if (empty($entities)) return;
        ?>
        <div class="ctrl-section mb-8" data-aos="fade-up">
            <div class="ctrl-section-header">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl <?php echo $iconBg; ?> flex items-center justify-center">
                        <i class="<?php echo $icon; ?> text-lg"></i>
                    </div>
                    <div>
                        <h2 class="font-black text-white"><?php echo htmlspecialchars($title); ?></h2>
                        <p class="text-[10px] text-gray-600 uppercase tracking-widest font-bold"><?php echo htmlspecialchars($subtitle); ?></p>
                    </div>
                </div>
            </div>
            <div class="p-5 md:p-7">
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
                    <?php foreach ($entities as $e): $key = htmlspecialchars($e['entity_key']);
                        $entIconClass = $switchIconColors[$e['color'] ?? ''] ?? $iconBg;
                        $entIcon = $e['icon'] ?? $icon;
                    ?>
                    <div class="entity-card" id="dyn-card-<?php echo $key; ?>">
                        <div class="w-9 h-9 rounded-xl <?php echo $entIconClass; ?> flex items-center justify-center mx-auto mb-2.5">
                            <i class="<?php echo $entIcon; ?> text-sm"></i>
                        </div>
                        <p class="text-xs font-bold text-white mb-3 text-center"><?php echo htmlspecialchars($e['friendly_name'] ?? $e['entity_key']); ?></p>
                        <div class="old-switch-wrap">
                            <div class="old-switch lock-bounce" id="oldsw-<?php echo $key; ?>"
                                onclick="askPattern('<?php echo htmlspecialchars($e['entity_id']); ?>', 'toggle', '<?php echo htmlspecialchars($e['friendly_name'] ?? $e['entity_key']); ?>', 'generic')">
                                <div class="old-switch-screw tl"></div>
                                <div class="sw-label sw-label-on">ON</div>
                                <div class="old-switch-rocker"></div>
                                <div class="sw-label sw-label-off">OFF</div>
                                <div class="old-switch-screw br"></div>
                            </div>
                        </div>
                        <p class="text-[10px] font-black mt-2" id="dyn-state-<?php echo $key; ?>"><span class="text-gray-500">--</span></p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php
    }

    renderSwitchGroup('Grid Control Center', 'fa-solid fa-plug-circle-bolt', 'bg-green-500/15 text-green-400', 'Inverter Grid Connections', $gridSwitches);
    renderSwitchGroup('Home Water Pump', 'fa-solid fa-water', 'bg-blue-500/15 text-blue-400', 'Site B · Water Pump', $homeWaterPump);
    renderSwitchGroup('Shop Light', 'fa-solid fa-lightbulb', 'bg-yellow-500/15 text-yellow-400', 'Site A · Lighting', $shopLights);
    renderSwitchGroup('Home Light', 'fa-solid fa-lightbulb', 'bg-yellow-500/15 text-yellow-400', 'Site B · Lighting', $homeLights);
    renderSwitchGroup('Other Switch', 'fa-solid fa-toggle-on', 'bg-purple-500/15 text-purple-400', 'Everything else', $otherSwitches);

    // Remaining sensors / binary sensors from HA config, unchanged
    $sensors = array_filter($controlEntities, fn($e) => in_array($e['entity_type'], ['sensor','binary_sensor','other']));
    if (!empty($sensors)):
    ?>
    <div class="ctrl-section mb-8" data-aos="fade-up">
        <div class="ctrl-section-header">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gray-500/15 flex items-center justify-center">
                    <i class="fa-solid fa-gauge-high text-gray-400 text-lg"></i>
                </div>
                <div>
                    <h2 class="font-black text-white">Sensors</h2>
                    <p class="text-[10px] text-gray-600 uppercase tracking-widest font-bold">Read-only monitoring</p>
                </div>
            </div>
        </div>
        <div class="p-5 md:p-7">
            <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-6 gap-3">
                <?php foreach ($sensors as $e): ?>
                <div class="entity-card">
                    <p class="text-[9px] text-gray-600 uppercase font-bold tracking-widest mb-1"><?php echo htmlspecialchars($e['friendly_name'] ?? $e['entity_key']); ?></p>
                    <p class="text-lg font-black text-white" id="dyn-state-<?php echo $e['entity_key']; ?>">--</p>
                    <p class="text-[9px] text-gray-600"><?php echo htmlspecialchars($e['display_unit'] ?? ''); ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Info footer -->
    <div class="text-center" data-aos="fade-up">
        <div class="inline-flex items-center gap-6 text-xs text-gray-700">
            <span><i class="fa-solid fa-eye mr-1"></i> Public monitoring</span>
            <span>·</span>
            <span><i class="fa-solid fa-lock mr-1"></i> Pattern-gated controls</span>
            <span>·</span>
            <a href="power.php" class="hover:text-gray-500 transition"><i class="fa-solid fa-bolt mr-1"></i> Full Power Dashboard</a>
        </div>
    </div>

</div><!-- /container -->
</div><!-- /control-page -->


<!-- ══════════════════════════════════════════════════
     PATTERN LOCK MODAL
══════════════════════════════════════════════════ -->
<div id="pattern-modal">
    <div class="pattern-card">
        <!-- Header -->
        <div class="mb-5">
            <div class="w-14 h-14 rounded-2xl bg-blue-600/20 border border-blue-500/30 flex items-center justify-center mx-auto mb-3">
                <i class="fa-solid fa-lock text-blue-400 text-xl" id="modal-lock-icon"></i>
            </div>
            <h3 class="text-lg font-black text-white">Authentication Required</h3>
            <p class="text-xs text-gray-500 mt-1">Draw your pattern to <span id="modal-action-name" class="text-blue-400 font-bold">continue</span></p>
        </div>

        <!-- Dot number reference grid -->
        <div class="mb-3">
            <div class="grid grid-cols-3 gap-1 w-20 mx-auto">
                <?php for($i=1;$i<=9;$i++): ?>
                <div class="text-center text-[9px] font-bold text-gray-600 leading-tight"><?php echo $i; ?></div>
                <?php endfor; ?>
            </div>
            <p class="text-[9px] text-gray-700 text-center mt-1">dot positions (min 4)</p>
        </div>

        <!-- Pattern Canvas -->
        <canvas id="pattern-canvas"></canvas>

        <!-- Live sequence display while drawing -->
        <div id="pattern-live-seq" class="text-xl font-black font-mono tracking-[0.25em] text-blue-400 min-h-[2rem] my-1 transition-all"></div>

        <!-- Message -->
        <div id="pattern-msg" class="text-gray-500">Draw pattern to unlock</div>

        <!-- Attempts indicator -->
        <div class="flex justify-center gap-1.5 mt-2" id="attempt-dots">
            <?php for($i=0;$i<5;$i++): ?>
            <div class="w-1.5 h-1.5 rounded-full bg-gray-700 attempt-dot"></div>
            <?php endfor; ?>
        </div>

        <!-- Cancel -->
        <button onclick="closePattern()"
            class="mt-5 w-full py-2.5 bg-gray-800 hover:bg-gray-700 text-gray-400 font-bold rounded-xl transition text-sm">
            <i class="fa-solid fa-xmark mr-1"></i> Cancel
        </button>
    </div>
</div>


<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
AOS.init({ once: true, duration: 600 });

// ══════════════════════════════════════════════
// ENTITY IDs
// ══════════════════════════════════════════════
const pumpEntities = {
    voltage: '<?php echo $pump['voltage']; ?>',
    amps:    '<?php echo $pump['amps']; ?>',
    watts:   '<?php echo $pump['watts']; ?>',
    kwh:     '<?php echo $pump['kwh']; ?>',
    freq:    '<?php echo $pump['freq']; ?>',
    status:  '<?php echo $pump['status']; ?>',
    start:   '<?php echo $pump['start']; ?>',
    stop:    '<?php echo $pump['stop']; ?>',
};

const dynEntities = <?php
    $map = [];
    foreach ($controlEntities as $e) { $map[$e['entity_key']] = $e['entity_id']; }
    foreach ($gridSwitches as $e) { $map[$e['entity_key']] = $e['entity_id']; }
    foreach ($shopLightsExtra as $e) { $map[$e['entity_key']] = $e['entity_id']; }
    echo json_encode($map);
?>;

// ══════════════════════════════════════════════
// GAUGE DRAWING
// ══════════════════════════════════════════════
function drawGauge(canvasId, value, min, max, unit, name, severity) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return;
    const dpr  = window.devicePixelRatio || 1;
    const rect = canvas.getBoundingClientRect();
    canvas.width  = rect.width  * dpr;
    canvas.height = rect.height * dpr;
    const ctx = canvas.getContext('2d');
    ctx.scale(dpr, dpr);

    const w = rect.width, h = rect.height;
    const cx = w / 2, cy = h * 0.60;
    const r  = Math.min(w, h) * 0.34;
    const lw = r * 0.18;

    const startA = 0.75 * Math.PI;   // lower-left (135°)
    const sweep  = 1.50 * Math.PI;   // 270°
    const endA   = startA + sweep;

    const pct   = Number.isFinite(value) ? Math.max(0, Math.min(1, (value - min) / (max - min))) : 0;
    const valA  = startA + pct * sweep;
    const color = getGaugeColor(value, severity);

    ctx.clearRect(0, 0, w, h);

    // Background track
    ctx.beginPath();
    ctx.arc(cx, cy, r, startA, endA);
    ctx.strokeStyle = 'rgba(255,255,255,0.06)';
    ctx.lineWidth = lw; ctx.lineCap = 'round'; ctx.stroke();

    // Severity zones (subtle ticks)
    const zones = [
        { pct: 0,   color: severity.colors[0] },
        { pct: 0.5, color: severity.colors[1] },
        { pct: 0.8, color: severity.colors[2] },
    ];

    // Value arc
    if (pct > 0.005) {
        ctx.shadowBlur = 18; ctx.shadowColor = color;
        ctx.beginPath();
        ctx.arc(cx, cy, r, startA, valA);
        ctx.strokeStyle = color;
        ctx.lineWidth = lw; ctx.lineCap = 'round'; ctx.stroke();
        ctx.shadowBlur = 0;
    }

    // Needle
    const nx = cx + (r - lw * 0.3) * Math.cos(valA);
    const ny = cy + (r - lw * 0.3) * Math.sin(valA);
    ctx.shadowBlur = 8; ctx.shadowColor = 'rgba(255,255,255,0.6)';
    ctx.beginPath(); ctx.moveTo(cx, cy); ctx.lineTo(nx, ny);
    ctx.strokeStyle = 'rgba(255,255,255,0.9)'; ctx.lineWidth = 2; ctx.lineCap = 'round'; ctx.stroke();
    ctx.shadowBlur = 0;

    // Center dot
    ctx.beginPath(); ctx.arc(cx, cy, lw * 0.35, 0, Math.PI * 2);
    ctx.fillStyle = '#fff'; ctx.fill();

    // Value text
    const valStr = Number.isFinite(value) ? (value >= 100 ? Math.round(value) : value.toFixed(1)) : '--';
    ctx.fillStyle = '#fff';
    ctx.font = `900 ${r * 0.38}px Inter, sans-serif`;
    ctx.textAlign = 'center'; ctx.textBaseline = 'middle';
    ctx.fillText(valStr, cx, cy + r * 0.28);

    // Unit
    ctx.fillStyle = color;
    ctx.font = `bold ${r * 0.18}px Inter, sans-serif`;
    ctx.fillText(unit, cx, cy + r * 0.52);

    // Min label
    ctx.fillStyle = '#374151';
    ctx.font = `${r * 0.15}px Inter, sans-serif`;
    const sx = cx + (r + lw) * Math.cos(startA);
    const sy = cy + (r + lw) * Math.sin(startA);
    ctx.fillText(min, sx + (sx < cx ? -4 : 4), sy);

    // Max label
    const ex = cx + (r + lw) * Math.cos(endA);
    const ey = cy + (r + lw) * Math.sin(endA);
    ctx.fillText(max, ex + (ex > cx ? 4 : -4), ey);
}

function getGaugeColor(value, severity) {
    if (!Number.isFinite(value)) return '#4b5563';
    if (severity.type === 'ascending') {
        if (value >= severity.green) return '#22c55e';
        if (value >= severity.yellow) return '#f59e0b';
        return '#ef4444';
    } else {
        if (value <= severity.green) return '#22c55e';
        if (value <= severity.yellow) return '#f59e0b';
        return '#ef4444';
    }
}

// Gauge state storage
let gaugeVals = { voltage: NaN, amps: NaN };

function renderGauges() {
    drawGauge('gauge-voltage', gaugeVals.voltage, 160, 270, 'V', 'Line Voltage',
        { type: 'ascending', green: 210, yellow: 190, colors: ['#ef4444','#f59e0b','#22c55e'] });
    drawGauge('gauge-amps', gaugeVals.amps, 0, 15, 'A', 'Motor Load',
        { type: 'descending', green: 5, yellow: 10, colors: ['#22c55e','#f59e0b','#ef4444'] });
}

// Initial blank gauges
renderGauges();
window.addEventListener('resize', renderGauges);

// ══════════════════════════════════════════════
// DATA FETCHING
// ══════════════════════════════════════════════
async function fetchEntity(entityId) {
    try {
        const res = await fetch(`api/ha_proxy.php?entity=${encodeURIComponent(entityId)}&_t=${Date.now()}`);
        const data = await res.json();
        return (data && !data.error) ? data.state : null;
    } catch(e) { return null; }
}

function animateVal(elId, newVal) {
    const el = document.getElementById(elId);
    if (!el) return;
    el.classList.remove('val-updated');
    void el.offsetWidth; // reflow
    el.textContent = newVal;
    el.classList.add('val-updated');
}

async function refreshPump() {
    // Voltage
    const v = await fetchEntity(pumpEntities.voltage);
    if (v !== null) {
        gaugeVals.voltage = parseFloat(v);
        renderGauges();
    }

    // Amps
    const a = await fetchEntity(pumpEntities.amps);
    if (a !== null) {
        gaugeVals.amps = parseFloat(a);
        renderGauges();
    }

    // Watts
    const w = await fetchEntity(pumpEntities.watts);
    if (w !== null) animateVal('pump-watts', parseFloat(w).toFixed(0));

    // kWh
    const k = await fetchEntity(pumpEntities.kwh);
    if (k !== null) animateVal('pump-kwh', parseFloat(k).toFixed(2));

    // Freq
    const f = await fetchEntity(pumpEntities.freq);
    if (f !== null) animateVal('pump-freq', parseFloat(f).toFixed(1));

    // Running status
    const s = await fetchEntity(pumpEntities.status);
    updatePumpStatus(s);
}

function updatePumpStatus(state) {
    const badge   = document.getElementById('pump-status-badge');
    const led     = document.getElementById('pump-led');
    const badgeTxt = document.getElementById('pump-status-text');
    const banner  = document.getElementById('pump-status-banner');
    const bLed    = document.getElementById('banner-led');
    const bTitle  = document.getElementById('banner-title');
    const bSub    = document.getElementById('banner-sub');
    const bIcon   = document.getElementById('banner-icon');

    if (state === 'on') {
        // Running
        if(led) { led.className = 'led-green'; }
        if(badgeTxt) badgeTxt.textContent = 'RUNNING';
        if(badge) badge.className = 'flex items-center gap-2 px-3 py-1.5 bg-green-500/10 rounded-full border border-green-500/30 text-xs font-bold text-green-400 transition-all';
        if(banner) banner.className = 'pump-running-banner flex items-center gap-3';
        if(bLed) bLed.className = 'led-green';
        if(bTitle) bTitle.textContent = 'Motor Status: ● RUNNING';
        if(bTitle) bTitle.style.color = '#22c55e';
        if(bSub) bSub.textContent = 'Pump is active — consuming power';
        if(bIcon) bIcon.className = 'fa-solid fa-water text-2xl text-green-400 opacity-60';
    } else if (state === 'off') {
        // Stopped
        if(led) { led.className = 'led-red'; }
        if(badgeTxt) badgeTxt.textContent = 'STOPPED';
        if(badge) badge.className = 'flex items-center gap-2 px-3 py-1.5 bg-red-500/10 rounded-full border border-red-500/30 text-xs font-bold text-red-400 transition-all';
        if(banner) banner.className = 'pump-stopped-banner flex items-center gap-3';
        if(bLed) bLed.className = 'led-red';
        if(bTitle) bTitle.textContent = 'Motor Status: ■ STOPPED';
        if(bTitle) bTitle.style.color = '#ef4444';
        if(bSub) bSub.textContent = 'Pump is idle — no power draw';
        if(bIcon) bIcon.className = 'fa-solid fa-ban text-2xl text-red-400 opacity-40';
    } else {
        if(led) led.className = 'led-gray';
        if(badgeTxt) badgeTxt.textContent = 'UNKNOWN';
    }
}

// Dynamic entities refresh
async function refreshDynamic() {
    for (const [key, entityId] of Object.entries(dynEntities)) {
        const val = await fetchEntity(entityId);
        if (val === null) continue;
        const el = document.getElementById('dyn-state-' + key);
        const sw = document.getElementById('oldsw-' + key);
        const card = document.getElementById('dyn-card-' + key);
        if (val === 'on') {
            if (el) el.innerHTML = '<span class="text-green-400">ON</span>';
            if (sw) sw.classList.add('on');
            if (card) card.style.borderColor = 'rgba(34,197,94,0.3)';
        } else if (val === 'off') {
            if (el) el.innerHTML = '<span class="text-red-400">OFF</span>';
            if (sw) sw.classList.remove('on');
            if (card) card.style.borderColor = '';
        } else if (el) {
            el.textContent = val;
        }
    }
}

// ══════════════════════════════════════════════
// PATTERN LOCK
// ══════════════════════════════════════════════
let patternLock = null;
let pendingAction = null;
let attemptCount = 0;

class PatternLock {
    constructor(canvas, onComplete) {
        this.canvas = canvas;
        this.ctx = canvas.getContext('2d');
        this.onComplete = onComplete;
        this.dots = [];
        this.selected = [];
        this.drawing = false;
        this.cursor = null;
        this.state = 'idle';
        this.setupCanvas();
        this.buildDots();
        this.bindEvents();
        this.render();
    }

    setupCanvas() {
        const dpr = window.devicePixelRatio || 1;
        this.size = this.canvas.offsetWidth;
        this.canvas.width  = this.size * dpr;
        this.canvas.height = this.size * dpr;
        this.ctx.scale(dpr, dpr);
    }

    buildDots() {
        const pad = this.size * 0.17, gap = (this.size - 2 * pad) / 2;
        for (let r = 0; r < 3; r++)
            for (let c = 0; c < 3; c++)
                this.dots.push({ x: pad + c * gap, y: pad + r * gap, id: r * 3 + c + 1, active: false });
    }

    getPos(e) {
        const rect = this.canvas.getBoundingClientRect();
        const t = e.touches ? e.touches[0] : e;
        return {
            x: (t.clientX - rect.left) * (this.size / rect.width),
            y: (t.clientY - rect.top)  * (this.size / rect.height)
        };
    }

    near(pos) { return this.dots.find(d => Math.hypot(d.x - pos.x, d.y - pos.y) < this.size * 0.1); }

    bindEvents() {
        const updateLiveSeq = () => {
            const el = document.getElementById('pattern-live-seq');
            if (el) el.textContent = this.selected.length ? this.selected.join(' - ') : '';
        };
        const start = e => {
            e.preventDefault();
            this.drawing = true; this.selected = [];
            this.dots.forEach(d => d.active = false);
            const pos = this.getPos(e);
            const dot = this.near(pos);
            if (dot) { dot.active = true; this.selected.push(dot.id); }
            this.cursor = pos; this.render(); updateLiveSeq();
        };
        const move = e => {
            if (!this.drawing) return; e.preventDefault();
            this.cursor = this.getPos(e);
            const dot = this.near(this.cursor);
            if (dot && !dot.active) { dot.active = true; this.selected.push(dot.id); }
            this.render(); updateLiveSeq();
        };
        const end = e => {
            if (!this.drawing) return;
            this.drawing = false; this.cursor = null; this.render();
            if (this.selected.length >= 4) {
                this.onComplete(this.selected.join(''));
            } else if (this.selected.length >= 1) {
                // Too few dots — show hint without firing onComplete
                const msg = document.getElementById('pattern-msg');
                if (msg) { msg.textContent = 'Draw at least 4 dots'; msg.style.color = '#f59e0b'; }
                this.showError();
            }
        };
        this.canvas.addEventListener('mousedown',  start);
        this.canvas.addEventListener('mousemove',  move);
        window.addEventListener('mouseup', end);
        this.canvas.addEventListener('touchstart', start, { passive: false });
        this.canvas.addEventListener('touchmove',  move,  { passive: false });
        this.canvas.addEventListener('touchend',   end,   { passive: false });
    }

    render() {
        const ctx = this.ctx, s = this.size;
        ctx.clearRect(0, 0, s, s);
        const col = this.state === 'error' ? { line:'#ef4444', dot:'#ef4444', glow:'rgba(239,68,68,0.3)' }
                  : this.state === 'success' ? { line:'#22c55e', dot:'#22c55e', glow:'rgba(34,197,94,0.3)' }
                  : { line:'#3b82f6', dot:'#3b82f6', glow:'rgba(59,130,246,0.2)' };

        // Lines
        if (this.selected.length > 1) {
            ctx.beginPath();
            this.selected.forEach((id, i) => {
                const d = this.dots.find(x => x.id === id);
                i === 0 ? ctx.moveTo(d.x, d.y) : ctx.lineTo(d.x, d.y);
            });
            ctx.strokeStyle = col.line + '99'; ctx.lineWidth = 3;
            ctx.lineCap = 'round'; ctx.lineJoin = 'round'; ctx.stroke();
        }
        // Trailing line to cursor
        if (this.drawing && this.cursor && this.selected.length > 0) {
            const last = this.dots.find(d => d.id === this.selected[this.selected.length - 1]);
            ctx.beginPath(); ctx.moveTo(last.x, last.y); ctx.lineTo(this.cursor.x, this.cursor.y);
            ctx.strokeStyle = col.line + '44'; ctx.lineWidth = 2; ctx.stroke();
        }
        // Dots
        this.dots.forEach(d => {
            const dotR = s * 0.052;
            if (d.active) {
                ctx.beginPath(); ctx.arc(d.x, d.y, dotR * 2.2, 0, Math.PI * 2);
                ctx.fillStyle = col.glow; ctx.fill();
                ctx.beginPath(); ctx.arc(d.x, d.y, dotR * 1.3, 0, Math.PI * 2);
                ctx.strokeStyle = col.dot; ctx.lineWidth = 2; ctx.stroke();
            } else {
                ctx.beginPath(); ctx.arc(d.x, d.y, dotR * 1.3, 0, Math.PI * 2);
                ctx.strokeStyle = 'rgba(255,255,255,0.12)'; ctx.lineWidth = 1.5; ctx.stroke();
            }
            ctx.beginPath(); ctx.arc(d.x, d.y, dotR * 0.38, 0, Math.PI * 2);
            ctx.fillStyle = d.active ? col.dot : 'rgba(255,255,255,0.45)'; ctx.fill();
            // Dot number label (small, below dot)
            if (!d.active) {
                ctx.font = `${s * 0.038}px Inter, sans-serif`;
                ctx.fillStyle = 'rgba(255,255,255,0.18)';
                ctx.textAlign = 'center'; ctx.textBaseline = 'middle';
                ctx.fillText(d.id, d.x, d.y + dotR * 2.2);
            }
        });
    }

    showError()   { this.state = 'error';   this.render(); setTimeout(() => { this.state='idle'; this.reset(); }, 900); }
    showSuccess() { this.state = 'success'; this.render(); }
    reset() {
        this.selected = []; this.dots.forEach(d => d.active = false);
        this.drawing = false; this.cursor = null; this.state = 'idle'; this.render();
        const el = document.getElementById('pattern-live-seq');
        if (el) el.textContent = '';
    }
}

function askPattern(entityId, action, label, btnType) {
    pendingAction = { entityId, action, label };
    document.getElementById('modal-action-name').textContent = label;
    document.getElementById('pattern-modal').classList.add('open');
    document.getElementById('pattern-msg').textContent = 'Draw pattern to unlock';
    document.getElementById('pattern-msg').style.color = '#6b7280';
    updateAttemptDots();

    if (!patternLock) {
        patternLock = new PatternLock(
            document.getElementById('pattern-canvas'),
            submitPattern
        );
    } else {
        patternLock.reset();
    }
}

function closePattern() {
    document.getElementById('pattern-modal').classList.remove('open');
    pendingAction = null;
    if (patternLock) patternLock.reset();
}

async function submitPattern(patternStr) {
    if (!pendingAction || patternStr.length < 4) return;

    const msg = document.getElementById('pattern-msg');
    msg.textContent = 'Verifying...'; msg.style.color = '#60a5fa';

    try {
        const res = await fetch('api/ha_control_public.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                pattern:   patternStr,
                entity_id: pendingAction.entityId,
                action:    pendingAction.action
            })
        });
        const data = await res.json();

        if (data.success) {
            patternLock.showSuccess();
            const httpCode = data.http_code ? ` (HA: ${data.http_code})` : '';
            msg.textContent = `✓ Command sent!${httpCode}`; msg.style.color = '#22c55e';
            document.getElementById('modal-lock-icon').className = 'fa-solid fa-lock-open text-green-400 text-xl';
            attemptCount = 0; updateAttemptDots();
            setTimeout(() => {
                closePattern();
                document.getElementById('modal-lock-icon').className = 'fa-solid fa-lock text-blue-400 text-xl';
                refreshPump();
            }, 1500);
        } else {
            patternLock.showError();
            attemptCount = Math.min(5, attemptCount + 1);
            updateAttemptDots();
            if (data.locked) {
                msg.textContent = '🔒 Too many attempts. Wait 5 min.'; msg.style.color = '#ef4444';
            } else {
                const rem = data.remaining !== undefined ? data.remaining : '';
                msg.textContent = `✗ Wrong pattern${rem ? ` (${rem} left)` : ''}`; msg.style.color = '#ef4444';
            }
        }
    } catch(e) {
        patternLock.showError();
        msg.textContent = '✗ Connection error'; msg.style.color = '#ef4444';
    }
}

function updateAttemptDots() {
    document.querySelectorAll('.attempt-dot').forEach((dot, i) => {
        dot.style.background = i < attemptCount ? '#ef4444' : '#374151';
    });
}

// Close on backdrop click
document.getElementById('pattern-modal').addEventListener('click', function(e) {
    if (e.target === this) closePattern();
});
// Close on Escape
document.addEventListener('keydown', e => { if (e.key === 'Escape') closePattern(); });

// ══════════════════════════════════════════════
// LIVE CLOCK + AUTO REFRESH
// ══════════════════════════════════════════════
let countdown = 15;
function updateClock() {
    const now = new Date();
    document.getElementById('page-clock').textContent = now.toLocaleTimeString('en-IN', { hour12: false });
}
setInterval(updateClock, 1000); updateClock();

function startCountdown() {
    countdown = 15;
    const el = document.getElementById('refresh-countdown');
    const timer = setInterval(() => {
        countdown--;
        if (el) el.textContent = countdown + 's';
        if (countdown <= 0) {
            clearInterval(timer);
            refreshAll();
        }
    }, 1000);
}

async function refreshAll() {
    await refreshPump();
    await refreshDynamic();
    startCountdown();
}

refreshAll();
</script>

<?php include 'includes/footer.php'; ?>
