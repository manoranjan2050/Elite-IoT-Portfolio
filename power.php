<?php
require_once 'includes/db.php';
$pageTitle = "Live Power Station | Advanced Energy Command Center";
include 'includes/header.php';
?>

<style>
    /* ── Background Grid ── */
    .power-grid-bg {
        position: fixed; inset: 0; z-index: 0; pointer-events: none;
        background-image: linear-gradient(rgba(59,130,246,0.03) 1px, transparent 1px),
                          linear-gradient(90deg, rgba(59,130,246,0.03) 1px, transparent 1px);
        background-size: 48px 48px;
    }
    .power-blob-1 { position: fixed; width: 600px; height: 600px; top: -100px; left: -200px;
        background: radial-gradient(circle, rgba(59,130,246,0.08) 0%, transparent 70%);
        border-radius: 50%; z-index: 0; pointer-events: none;
        animation: blobDrift1 25s ease-in-out infinite alternate; }
    .power-blob-2 { position: fixed; width: 500px; height: 500px; bottom: -100px; right: -100px;
        background: radial-gradient(circle, rgba(168,85,247,0.07) 0%, transparent 70%);
        border-radius: 50%; z-index: 0; pointer-events: none;
        animation: blobDrift2 30s ease-in-out infinite alternate; }
    .power-blob-3 { position: fixed; width: 400px; height: 400px; top: 50%; left: 50%;
        transform: translate(-50%, -50%);
        background: radial-gradient(circle, rgba(34,197,94,0.04) 0%, transparent 70%);
        border-radius: 50%; z-index: 0; pointer-events: none;
        animation: blobDrift3 20s ease-in-out infinite alternate; }
    @keyframes blobDrift1 { from { transform: translate(0,0) scale(1); } to { transform: translate(60px,40px) scale(1.1); } }
    @keyframes blobDrift2 { from { transform: translate(0,0) scale(1); } to { transform: translate(-50px,-30px) scale(1.08); } }
    @keyframes blobDrift3 { from { transform: translate(-50%,-50%) scale(1); } to { transform: translate(-50%,-50%) scale(1.15); } }

    /* ── Flow Animations ── */
    .flow-dot { stroke-dasharray: 4, 10; stroke-width: 2.5; fill: none;
        animation: dash 5s linear infinite; opacity: 0; transition: opacity 0.5s; }
    .flow-active { opacity: 1; }
    @keyframes dash { to { stroke-dashoffset: -100; } }
    .flow-reverse { animation-direction: reverse; }
    .node-circle { fill: rgba(3,7,18,0.9); stroke-width: 2; transition: all 0.5s; cursor: pointer; }
    .node-circle:hover { stroke-width: 4; filter: brightness(1.3); }
    .node-label { font-size: 7px; font-weight: 800; fill: #6b7280; text-anchor: middle; pointer-events: none; letter-spacing: 0.05em; }
    .node-value { font-size: 10px; font-weight: 900; fill: #f9fafb; text-anchor: middle; pointer-events: none; }

    /* ── Glow Pulses ── */
    @keyframes glow-orange { 0%,100%{filter:drop-shadow(0 0 4px rgba(249,115,22,.4));}50%{filter:drop-shadow(0 0 14px rgba(249,115,22,.9));} }
    @keyframes glow-green  { 0%,100%{filter:drop-shadow(0 0 4px rgba(34,197,94,.4));}50%{filter:drop-shadow(0 0 14px rgba(34,197,94,.9));} }
    @keyframes glow-red    { 0%,100%{filter:drop-shadow(0 0 4px rgba(239,68,68,.4));}50%{filter:drop-shadow(0 0 14px rgba(239,68,68,.9));} }
    @keyframes glow-blue   { 0%,100%{filter:drop-shadow(0 0 4px rgba(59,130,246,.4));}50%{filter:drop-shadow(0 0 14px rgba(59,130,246,.9));} }
    .glow-orange { animation: glow-orange 3s ease-in-out infinite; }
    .glow-green  { animation: glow-green  3s ease-in-out infinite; }
    .glow-red    { animation: glow-red    3s ease-in-out infinite; }
    .glow-blue   { animation: glow-blue   3s ease-in-out infinite; }

    /* ── Gauge ── */
    .gauge-svg { transform: rotate(-90deg); }
    .gauge-bg   { stroke: rgba(255,255,255,0.06); fill: none; }
    .gauge-fill { stroke-linecap: round; fill: none; transition: stroke-dashoffset 1.4s cubic-bezier(.4,0,.2,1), stroke 0.8s ease; }

    /* ── Cards ── */
    .power-card {
        background: rgba(255,255,255,0.025);
        backdrop-filter: blur(14px);
        border: 1px solid rgba(255,255,255,0.07);
        border-radius: 1.75rem;
        transition: border-color 0.3s, box-shadow 0.3s;
    }
    .power-card:hover { border-color: rgba(59,130,246,0.2); box-shadow: 0 0 30px rgba(59,130,246,0.06); }
    .power-card-glow-blue  { box-shadow: inset 0 0 60px rgba(59,130,246,0.03); }
    .power-card-glow-purple{ box-shadow: inset 0 0 60px rgba(168,85,247,0.03); }
    .power-card-glow-green { box-shadow: inset 0 0 60px rgba(34,197,94,0.03); }

    /* ── Stat mini-chips ── */
    .stat-chip { background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.06);
        border-radius: 0.875rem; transition: all 0.3s; }
    .stat-chip:hover { background: rgba(255,255,255,0.07); transform: translateY(-1px); }

    /* ── Tab bar ── */
    .tab-btn { transition: all 0.25s; border: 1px solid transparent; }
    .tab-btn.active { background: linear-gradient(135deg,#3b82f6,#6366f1);
        color: #fff; box-shadow: 0 4px 20px rgba(59,130,246,0.35); }
    .tab-btn:not(.active):hover { background: rgba(255,255,255,0.06); color: #e5e7eb; }

    /* ── Value transition ── */
    .val-update { transition: color 0.4s, transform 0.2s; }
    .val-flash  { color: #60a5fa !important; transform: scale(1.05); }

    /* ── Countdown ring ── */
    @keyframes countdown-ring {
        from { stroke-dashoffset: 0; }
        to   { stroke-dashoffset: 88; }
    }
    .countdown-ring { animation: countdown-ring 15s linear; stroke-linecap: round; }

    /* ── Battery bar ── */
    .batt-bar { height: 6px; border-radius: 3px; background: rgba(255,255,255,0.06); overflow: hidden; }
    .batt-bar-fill { height: 100%; border-radius: 3px; transition: width 1.2s cubic-bezier(.4,0,.2,1); }

    /* ── Mini animated cell battery ── */
    .cell-batt-wrap { display: flex; flex-direction: column; align-items: center; gap: 3px; }
    .cell-batt {
        position: relative;
        width: 22px; height: 46px;
        border-radius: 4px 4px 2px 2px;
        background: rgba(255,255,255,0.03);
        border: 1px solid rgba(255,255,255,0.12);
        overflow: hidden;
    }
    .cell-batt::before {
        content: '';
        position: absolute; top: -4px; left: 50%; transform: translateX(-50%);
        width: 10px; height: 4px;
        background: rgba(255,255,255,0.14);
        border-radius: 2px 2px 0 0;
    }
    .cell-batt-fill {
        position: absolute; bottom: 0; left: 0; right: 0;
        height: 0%;
        transition: height 0.8s cubic-bezier(.4,0,.2,1), background 0.6s ease;
        background: linear-gradient(180deg, rgba(255,255,255,0.25), transparent 40%), #ef4444;
    }
    .cell-batt-fill::after {
        content: '';
        position: absolute; inset: 0;
        background: linear-gradient(180deg, transparent 60%, rgba(255,255,255,0.12) 100%);
        animation: cellShimmer 2.4s ease-in-out infinite;
    }
    @keyframes cellShimmer { 0%,100% { opacity: 0.5; } 50% { opacity: 1; } }
    .cell-batt.hi-cell { box-shadow: 0 0 8px rgba(251,146,60,0.6); border-color: rgba(251,146,60,0.5); }
    .cell-batt.lo-cell { box-shadow: 0 0 8px rgba(96,165,250,0.6); border-color: rgba(96,165,250,0.5); }
    .cell-batt-volt { font-size: 8px; font-weight: 900; color: #d1d5db; font-variant-numeric: tabular-nums; }
    .cell-batt-label { font-size: 6px; color: #6b7280; font-weight: 700; text-transform: uppercase; }

    /* ── Status badge pulse ── */
    @keyframes pulseBadge { 0%,100%{opacity:1;} 50%{opacity:0.6;} }
    .badge-live { animation: pulseBadge 2s infinite; }

    /* ── Section divider ── */
    .section-divider { height: 1px; background: linear-gradient(90deg,transparent,rgba(255,255,255,0.07),transparent); }

    /* ── Chart container ── */
    .chart-wrap { position: relative; }
    .chart-wrap::before {
        content: '';
        position: absolute; inset: -1px;
        border-radius: 1.5rem;
        background: linear-gradient(135deg, rgba(59,130,246,0.05), transparent, rgba(168,85,247,0.05));
        pointer-events: none; z-index: 0;
    }

    /* ── Scroll-in ── */
    [data-aos] { position: relative; z-index: 1; }

    /* ── Overview site card animated border glow ── */
    @keyframes shopBorderGlow {
        0%,100% { box-shadow: 0 0 0 1px rgba(59,130,246,0.12), inset 0 0 50px rgba(59,130,246,0.03); }
        50%      { box-shadow: 0 0 0 1px rgba(59,130,246,0.38), 0 0 40px rgba(59,130,246,0.1), inset 0 0 60px rgba(59,130,246,0.07); }
    }
    @keyframes homeBorderGlow {
        0%,100% { box-shadow: 0 0 0 1px rgba(168,85,247,0.12), inset 0 0 50px rgba(168,85,247,0.03); }
        50%      { box-shadow: 0 0 0 1px rgba(168,85,247,0.38), 0 0 40px rgba(168,85,247,0.1), inset 0 0 60px rgba(168,85,247,0.07); }
    }
    .shop-card { animation: shopBorderGlow 5s ease-in-out infinite; border-color: rgba(59,130,246,0.15) !important; }
    .home-card { animation: homeBorderGlow 5s ease-in-out infinite; border-color: rgba(168,85,247,0.15) !important; }

    /* ── Metric mini-tiles ── */
    .metric-tile { transition: background 0.3s, transform 0.2s; }
    .metric-tile:hover { transform: translateY(-2px); }

    /* ── Charge state badge ── */
    .charge-badge-charging    { background: rgba(34,197,94,0.15);  color: #4ade80; border-color: rgba(34,197,94,0.3); }
    .charge-badge-discharging { background: rgba(239,68,68,0.15);  color: #f87171; border-color: rgba(239,68,68,0.3); }
    .charge-badge-standby     { background: rgba(107,114,128,0.12); color: #9ca3af; border-color: rgba(107,114,128,0.2); }

    /* ── Canvas gauge glow ── */
    .gauge-canvas { filter: drop-shadow(0 0 12px rgba(59,130,246,0.2)); transition: filter 0.6s; }
    .gauge-canvas-home { filter: drop-shadow(0 0 12px rgba(168,85,247,0.2)); }

    /* ── Detail button ── */
    .detail-btn { transition: all 0.2s; }
    .detail-btn:hover { transform: translateY(-1px); }
    .detail-btn:active { transform: scale(0.98); }
</style>

<!-- Background decorations -->
<div class="power-grid-bg"></div>
<div class="power-blob-1"></div>
<div class="power-blob-2"></div>
<div class="power-blob-3"></div>

<section class="relative min-h-screen pt-24 md:pt-32 pb-28 px-4 md:px-6 bg-[#020816]">
    <div class="relative z-10 container mx-auto">
        <div class="max-w-7xl mx-auto">

            <!-- ═══════════════════ HERO HEADER ═══════════════════ -->
            <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-10 gap-6" data-aos="fade-down">
                <div>
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-10 h-10 rounded-2xl bg-blue-500/10 border border-blue-500/20 flex items-center justify-center">
                            <i class="fa-solid fa-bolt text-yellow-400"></i>
                        </div>
                        <span class="text-xs font-bold text-gray-500 uppercase tracking-[0.25em]">Energy Command Center</span>
                    </div>
                    <h1 class="text-4xl md:text-5xl font-extrabold mb-2">
                        <span class="text-gradient">Live Power Station</span>
                    </h1>
                    <p class="text-gray-500 text-sm flex items-center gap-3">
                        <span class="flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-green-400 badge-live inline-block"></span>
                            Live · Auto-refreshes every 15s
                        </span>
                        <span class="text-gray-700">|</span>
                        <span id="live-clock" class="text-blue-400 font-mono text-xs tabular-nums"></span>
                    </p>
                </div>

                <!-- Tab navigation -->
                <div class="flex items-center gap-2 p-1.5 bg-white/[0.03] rounded-2xl border border-white/[0.06] w-full lg:w-auto overflow-x-auto" style="-webkit-overflow-scrolling:touch;scrollbar-width:none;">
                    <button onclick="switchTab('overview')" id="tab-overview" class="tab-btn active px-3 md:px-6 py-2.5 rounded-xl text-xs font-bold flex items-center gap-1.5 flex-shrink-0 min-h-[40px]">
                        <i class="fa-solid fa-chart-line"></i> <span class="hidden sm:inline">Overview</span><span class="sm:hidden">Ovrvw</span>
                    </button>
                    <button onclick="switchTab('shop')" id="tab-shop" class="tab-btn px-3 md:px-6 py-2.5 rounded-xl text-xs font-bold flex items-center gap-1.5 text-gray-400 flex-shrink-0 min-h-[40px]">
                        <i class="fa-solid fa-shop"></i> <span class="hidden sm:inline">Shop Details</span><span class="sm:hidden">Shop</span>
                    </button>
                    <button onclick="switchTab('home')" id="tab-home" class="tab-btn px-3 md:px-6 py-2.5 rounded-xl text-xs font-bold flex items-center gap-1.5 text-gray-400 flex-shrink-0 min-h-[40px]">
                        <i class="fa-solid fa-house"></i> <span class="hidden sm:inline">Home Details</span><span class="sm:hidden">Home</span>
                    </button>
                    <!-- Refresh countdown -->
                    <div class="ml-auto flex items-center gap-2 px-3 flex-shrink-0">
                        <svg width="20" height="20" viewBox="0 0 28 28" class="-rotate-90">
                            <circle cx="14" cy="14" r="11" fill="none" stroke="rgba(255,255,255,0.06)" stroke-width="2.5"/>
                            <circle id="refresh-ring" cx="14" cy="14" r="11" fill="none"
                                stroke="#3b82f6" stroke-width="2.5" stroke-dasharray="69.1" stroke-dashoffset="0"
                                style="transition:stroke-dashoffset 1s linear;"/>
                        </svg>
                        <span id="refresh-countdown" class="text-[10px] text-gray-600 font-mono tabular-nums w-4">15</span>
                    </div>
                </div>
            </div>

            <!-- ═══════════════════ LIVE ALERT BAR ═══════════════════ -->
            <div id="live-alert-bar" class="hidden mb-6 p-4 bg-red-500/10 border border-red-500/30 rounded-2xl">
                <p class="text-red-400 font-bold text-center flex items-center justify-center gap-3 text-sm">
                    <i class="fa-solid fa-triangle-exclamation animate-bounce"></i>
                    <span id="alert-message">CRITICAL ALERT</span>
                </p>
            </div>

            <!-- ═══════════════════ COMBINED TOP STATS ═══════════════════ -->
            <div id="top-stats-bar" class="grid grid-cols-2 md:grid-cols-4 gap-2 sm:gap-3 mb-6 sm:mb-8" data-aos="fade-up" data-aos-delay="100">
                <div class="stat-chip p-3 sm:p-4 flex items-center gap-2 sm:gap-3">
                    <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-xl bg-orange-500/10 flex items-center justify-center flex-shrink-0">
                        <i class="fa-solid fa-sun text-orange-400 text-xs sm:text-sm"></i>
                    </div>
                    <div class="min-w-0">
                        <p class="text-[9px] sm:text-[10px] text-gray-500 uppercase font-bold truncate">Total Solar</p>
                        <p class="text-base sm:text-lg font-black text-white tabular-nums"><span id="stat-total-pv" class="val-update">--</span><span class="text-xs text-gray-500 font-normal ml-0.5">W</span></p>
                    </div>
                </div>
                <div class="stat-chip p-3 sm:p-4 flex items-center gap-2 sm:gap-3">
                    <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-xl bg-red-500/10 flex items-center justify-center flex-shrink-0">
                        <i class="fa-solid fa-plug text-red-400 text-xs sm:text-sm"></i>
                    </div>
                    <div class="min-w-0">
                        <p class="text-[9px] sm:text-[10px] text-gray-500 uppercase font-bold truncate">Total Load</p>
                        <p class="text-base sm:text-lg font-black text-white tabular-nums"><span id="stat-total-load" class="val-update">--</span><span class="text-xs text-gray-500 font-normal ml-0.5">W</span></p>
                    </div>
                </div>
                <div class="stat-chip p-3 sm:p-4 flex items-center gap-2 sm:gap-3">
                    <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-xl bg-green-500/10 flex items-center justify-center flex-shrink-0">
                        <i class="fa-solid fa-battery-three-quarters text-green-400 text-xs sm:text-sm"></i>
                    </div>
                    <div class="min-w-0">
                        <p class="text-[9px] sm:text-[10px] text-gray-500 uppercase font-bold truncate">Shop SOC</p>
                        <p class="text-base sm:text-lg font-black text-white tabular-nums"><span id="stat-shop-soc" class="val-update">--</span><span class="text-xs text-gray-500 font-normal ml-0.5">%</span></p>
                    </div>
                </div>
                <div class="stat-chip p-3 sm:p-4 flex items-center gap-2 sm:gap-3">
                    <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-xl bg-purple-500/10 flex items-center justify-center flex-shrink-0">
                        <i class="fa-solid fa-house-signal text-purple-400 text-xs sm:text-sm"></i>
                    </div>
                    <div class="min-w-0">
                        <p class="text-[9px] sm:text-[10px] text-gray-500 uppercase font-bold truncate">Home SOC</p>
                        <p class="text-base sm:text-lg font-black text-white tabular-nums"><span id="stat-home-soc" class="val-update">--</span><span class="text-xs text-gray-500 font-normal ml-0.5">%</span></p>
                    </div>
                </div>
            </div>

            <!-- ═══════════════════ VIEW 1: OVERVIEW ═══════════════════ -->
            <div id="view-overview" class="view-content space-y-6">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                    <!-- ── SITE A : SHOP ── -->
                    <div class="power-card shop-card p-5 md:p-7" data-aos="fade-right">

                        <!-- Card header -->
                        <div class="flex justify-between items-center mb-5">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-2xl bg-blue-500/15 border border-blue-500/20 flex items-center justify-center">
                                    <i class="fa-solid fa-shop text-blue-400"></i>
                                </div>
                                <div>
                                    <h3 class="text-blue-400 font-black uppercase tracking-wider text-sm leading-tight">Site A · Shop</h3>
                                    <p class="text-gray-600 text-[10px] font-bold">Solar + BMS Storage</p>
                                </div>
                            </div>
                            <span class="flex items-center gap-1.5 px-2.5 py-1 bg-green-500/10 text-green-400 rounded-full text-[8px] font-bold border border-green-500/20 badge-live">
                                <span class="w-1.5 h-1.5 rounded-full bg-green-400 inline-block"></span> LIVE
                            </span>
                        </div>

                        <!-- Gauge + Metrics row -->
                        <div class="flex flex-col sm:flex-row items-center sm:items-start gap-5 mb-5">

                            <!-- Canvas SOC gauge -->
                            <div class="flex flex-col items-center flex-shrink-0">
                                <canvas id="ov-canvas-shop" width="160" height="160" class="gauge-canvas" style="width:160px;height:160px;display:block;"></canvas>
                                <span id="ov-shop-charge-badge" class="mt-2.5 px-3 py-1 rounded-full text-[9px] font-bold border charge-badge-standby">STANDBY</span>
                            </div>

                            <!-- 2×2 metric tiles -->
                            <div class="flex-1 w-full grid grid-cols-2 gap-2.5">
                                <div class="metric-tile rounded-xl p-3 bg-orange-500/[0.06] border border-orange-500/[0.12]">
                                    <div class="flex items-center gap-1.5 mb-2">
                                        <i class="fa-solid fa-sun text-orange-400 text-[11px]"></i>
                                        <span class="text-[9px] text-gray-500 uppercase font-bold tracking-wide">Solar</span>
                                    </div>
                                    <p class="text-xl font-black text-orange-400 tabular-nums leading-none mb-2">
                                        <span id="ov-shop-pv" class="val-update">--</span><span class="text-[10px] font-normal text-gray-600 ml-0.5">W</span>
                                    </p>
                                    <div class="h-1 rounded-full bg-white/[0.05] overflow-hidden">
                                        <div id="ov-shop-pv-bar" class="h-full rounded-full transition-all duration-1000" style="width:0%;background:linear-gradient(90deg,#f97316,#facc15)"></div>
                                    </div>
                                </div>
                                <div class="metric-tile rounded-xl p-3 bg-red-500/[0.06] border border-red-500/[0.12]">
                                    <div class="flex items-center gap-1.5 mb-2">
                                        <i class="fa-solid fa-plug text-red-400 text-[11px]"></i>
                                        <span class="text-[9px] text-gray-500 uppercase font-bold tracking-wide">Load</span>
                                    </div>
                                    <p class="text-xl font-black text-red-400 tabular-nums leading-none mb-2">
                                        <span id="ov-shop-load" class="val-update">--</span><span class="text-[10px] font-normal text-gray-600 ml-0.5">W</span>
                                    </p>
                                    <div class="h-1 rounded-full bg-white/[0.05] overflow-hidden">
                                        <div id="ov-shop-load-bar" class="h-full rounded-full transition-all duration-1000" style="width:0%;background:linear-gradient(90deg,#ef4444,#f87171)"></div>
                                    </div>
                                </div>
                                <div class="metric-tile rounded-xl p-3 bg-blue-500/[0.06] border border-blue-500/[0.12]">
                                    <div class="flex items-center gap-1.5 mb-2">
                                        <i class="fa-solid fa-bolt text-blue-400 text-[11px]"></i>
                                        <span class="text-[9px] text-gray-500 uppercase font-bold tracking-wide">Batt A</span>
                                    </div>
                                    <p class="text-xl font-black text-blue-400 tabular-nums leading-none mb-2">
                                        <span id="ov-shop-amps" class="val-update">--</span><span class="text-[10px] font-normal text-gray-600 ml-0.5">A</span>
                                    </p>
                                    <div class="h-1 rounded-full bg-white/[0.05] overflow-hidden">
                                        <div class="h-full rounded-full" style="width:50%;background:linear-gradient(90deg,#3b82f6,#22d3ee)"></div>
                                    </div>
                                </div>
                                <div class="metric-tile rounded-xl p-3 bg-cyan-500/[0.06] border border-cyan-500/[0.12]">
                                    <div class="flex items-center gap-1.5 mb-2">
                                        <i class="fa-solid fa-temperature-half text-cyan-400 text-[11px]"></i>
                                        <span class="text-[9px] text-gray-500 uppercase font-bold tracking-wide">Temp</span>
                                    </div>
                                    <p class="text-xl font-black text-cyan-400 tabular-nums leading-none mb-2">
                                        <span id="ov-shop-temp" class="val-update">--</span><span class="text-[10px] font-normal text-gray-600 ml-0.5">°C</span>
                                    </p>
                                    <div class="h-1 rounded-full bg-white/[0.05] overflow-hidden">
                                        <div id="ov-shop-temp-bar" class="h-full rounded-full transition-all duration-700" style="width:55%;background:linear-gradient(90deg,#06b6d4,#34d399)"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Pack status strip + SOC bar -->
                        <div class="border-t border-white/[0.05] pt-4 space-y-2.5">
                            <div class="grid grid-cols-2 gap-2.5">
                                <div class="flex items-center gap-2 bg-white/[0.025] rounded-lg px-3 py-2 border border-white/[0.04]">
                                    <span class="w-2 h-2 rounded-full bg-blue-400 flex-shrink-0"></span>
                                    <span class="text-[9px] text-gray-500 uppercase font-bold">Pack 1</span>
                                    <span id="ov-shop-p1-soc" class="text-xs font-black text-white tabular-nums ml-auto">--%</span>
                                </div>
                                <div class="flex items-center gap-2 bg-white/[0.025] rounded-lg px-3 py-2 border border-white/[0.04]">
                                    <span class="w-2 h-2 rounded-full bg-purple-400 flex-shrink-0"></span>
                                    <span class="text-[9px] text-gray-500 uppercase font-bold">Pack 2</span>
                                    <span id="ov-shop-p2-soc" class="text-xs font-black text-white tabular-nums ml-auto">--%</span>
                                </div>
                            </div>
                            <div class="batt-bar"><div id="shop-soc-bar" class="batt-bar-fill" style="width:0%;background:linear-gradient(90deg,#22c55e,#4ade80)"></div></div>
                        </div>

                        <!-- View details button -->
                        <button onclick="switchTab('shop')" class="detail-btn mt-4 w-full py-3 rounded-xl text-[11px] font-bold text-blue-400 border border-blue-500/15 bg-blue-500/[0.04] hover:bg-blue-500/[0.08] flex items-center justify-center gap-2">
                            View Shop Details <i class="fa-solid fa-arrow-right text-[9px]"></i>
                        </button>

                        <!-- Compat IDs for JS (hidden) -->
                        <span id="ov-shop-soc" class="hidden"></span>
                        <span id="ov-shop-val-solar" class="hidden"></span>
                        <span id="ov-shop-val-grid" class="hidden"></span>
                        <span id="ov-shop-val-battery" class="hidden"></span>
                        <span id="ov-shop-val-load" class="hidden"></span>
                    </div>

                    <!-- ── SITE B : HOME ── -->
                    <div class="power-card home-card p-5 md:p-7" data-aos="fade-left">

                        <!-- Card header -->
                        <div class="flex justify-between items-center mb-5">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-2xl bg-purple-500/15 border border-purple-500/20 flex items-center justify-center">
                                    <i class="fa-solid fa-house text-purple-400"></i>
                                </div>
                                <div>
                                    <h3 class="text-purple-400 font-black uppercase tracking-wider text-sm leading-tight">Site B · Home</h3>
                                    <p class="text-gray-600 text-[10px] font-bold">Solar + BMS Storage</p>
                                </div>
                            </div>
                            <span class="flex items-center gap-1.5 px-2.5 py-1 bg-purple-500/10 text-purple-400 rounded-full text-[8px] font-bold border border-purple-500/20 badge-live">
                                <span class="w-1.5 h-1.5 rounded-full bg-purple-400 inline-block"></span> LIVE
                            </span>
                        </div>

                        <!-- Gauge + Metrics row -->
                        <div class="flex flex-col sm:flex-row items-center sm:items-start gap-5 mb-5">

                            <!-- Canvas SOC gauge -->
                            <div class="flex flex-col items-center flex-shrink-0">
                                <canvas id="ov-canvas-home" width="160" height="160" class="gauge-canvas gauge-canvas-home" style="width:160px;height:160px;display:block;"></canvas>
                                <span id="ov-home-charge-badge" class="mt-2.5 px-3 py-1 rounded-full text-[9px] font-bold border charge-badge-standby">STANDBY</span>
                            </div>

                            <!-- 2×2 metric tiles -->
                            <div class="flex-1 w-full grid grid-cols-2 gap-2.5">
                                <div class="metric-tile rounded-xl p-3 bg-orange-500/[0.06] border border-orange-500/[0.12]">
                                    <div class="flex items-center gap-1.5 mb-2">
                                        <i class="fa-solid fa-sun text-orange-400 text-[11px]"></i>
                                        <span class="text-[9px] text-gray-500 uppercase font-bold tracking-wide">Solar</span>
                                    </div>
                                    <p class="text-xl font-black text-orange-400 tabular-nums leading-none mb-2">
                                        <span id="ov-home-pv" class="val-update">--</span><span class="text-[10px] font-normal text-gray-600 ml-0.5">W</span>
                                    </p>
                                    <div class="h-1 rounded-full bg-white/[0.05] overflow-hidden">
                                        <div id="ov-home-pv-bar" class="h-full rounded-full transition-all duration-1000" style="width:0%;background:linear-gradient(90deg,#f97316,#facc15)"></div>
                                    </div>
                                </div>
                                <div class="metric-tile rounded-xl p-3 bg-red-500/[0.06] border border-red-500/[0.12]">
                                    <div class="flex items-center gap-1.5 mb-2">
                                        <i class="fa-solid fa-plug text-red-400 text-[11px]"></i>
                                        <span class="text-[9px] text-gray-500 uppercase font-bold tracking-wide">Load</span>
                                    </div>
                                    <p class="text-xl font-black text-red-400 tabular-nums leading-none mb-2">
                                        <span id="ov-home-load" class="val-update">--</span><span class="text-[10px] font-normal text-gray-600 ml-0.5">W</span>
                                    </p>
                                    <div class="h-1 rounded-full bg-white/[0.05] overflow-hidden">
                                        <div id="ov-home-load-bar" class="h-full rounded-full transition-all duration-1000" style="width:0%;background:linear-gradient(90deg,#ef4444,#f87171)"></div>
                                    </div>
                                </div>
                                <div class="metric-tile rounded-xl p-3 bg-purple-500/[0.06] border border-purple-500/[0.12]">
                                    <div class="flex items-center gap-1.5 mb-2">
                                        <i class="fa-solid fa-bolt text-purple-400 text-[11px]"></i>
                                        <span class="text-[9px] text-gray-500 uppercase font-bold tracking-wide">Voltage</span>
                                    </div>
                                    <p class="text-xl font-black text-purple-400 tabular-nums leading-none mb-2">
                                        <span id="ov-home-v-mini" class="val-update">--</span><span class="text-[10px] font-normal text-gray-600 ml-0.5">V</span>
                                    </p>
                                    <div class="h-1 rounded-full bg-white/[0.05] overflow-hidden">
                                        <div class="h-full rounded-full" style="width:70%;background:linear-gradient(90deg,#a855f7,#c084fc)"></div>
                                    </div>
                                </div>
                                <div class="metric-tile rounded-xl p-3 bg-cyan-500/[0.06] border border-cyan-500/[0.12]">
                                    <div class="flex items-center gap-1.5 mb-2">
                                        <i class="fa-solid fa-temperature-half text-cyan-400 text-[11px]"></i>
                                        <span class="text-[9px] text-gray-500 uppercase font-bold tracking-wide">Temp</span>
                                    </div>
                                    <p class="text-xl font-black text-cyan-400 tabular-nums leading-none mb-2">
                                        <span id="ov-home-temp" class="val-update">--</span><span class="text-[10px] font-normal text-gray-600 ml-0.5">°C</span>
                                    </p>
                                    <div class="h-1 rounded-full bg-white/[0.05] overflow-hidden">
                                        <div class="h-full rounded-full" style="width:55%;background:linear-gradient(90deg,#06b6d4,#34d399)"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Stats strip + SOC bar -->
                        <div class="border-t border-white/[0.05] pt-4 space-y-2.5">
                            <div class="grid grid-cols-3 gap-2">
                                <div class="text-center bg-white/[0.025] rounded-lg py-2 border border-white/[0.04]">
                                    <p class="text-[8px] text-gray-600 uppercase font-bold mb-0.5">Current</p>
                                    <p class="text-sm font-black text-white tabular-nums"><span id="ov-home-amps" class="val-update">--</span><span class="text-[9px] text-gray-600">A</span></p>
                                </div>
                                <div class="text-center bg-white/[0.025] rounded-lg py-2 border border-white/[0.04]">
                                    <p class="text-[8px] text-gray-600 uppercase font-bold mb-0.5">BMS Pwr</p>
                                    <p class="text-sm font-black text-purple-400 tabular-nums"><span id="ov-home-bms-pwr" class="val-update">--</span><span class="text-[9px] text-gray-600">W</span></p>
                                </div>
                                <div class="text-center bg-white/[0.025] rounded-lg py-2 border border-white/[0.04]">
                                    <p class="text-[8px] text-gray-600 uppercase font-bold mb-0.5">Grid</p>
                                    <p class="text-sm font-black text-blue-400 tabular-nums"><span id="ov-home-grid-mini" class="val-update">--</span><span class="text-[9px] text-gray-600">W</span></p>
                                </div>
                            </div>
                            <div class="batt-bar"><div id="home-soc-bar" class="batt-bar-fill" style="width:0%;background:linear-gradient(90deg,#a855f7,#c084fc)"></div></div>
                        </div>

                        <!-- View details button -->
                        <button onclick="switchTab('home')" class="detail-btn mt-4 w-full py-3 rounded-xl text-[11px] font-bold text-purple-400 border border-purple-500/15 bg-purple-500/[0.04] hover:bg-purple-500/[0.08] flex items-center justify-center gap-2">
                            View Home Details <i class="fa-solid fa-arrow-right text-[9px]"></i>
                        </button>

                        <!-- Compat IDs for JS (hidden) -->
                        <span id="ov-home-soc" class="hidden"></span>
                        <span id="ov-home-val-solar" class="hidden"></span>
                        <span id="ov-home-val-grid" class="hidden"></span>
                        <span id="ov-home-val-battery" class="hidden"></span>
                        <span id="ov-home-val-load" class="hidden"></span>
                    </div>
                </div>
            </div>

            <!-- ═══════════════════ VIEW 2: SHOP DETAILS ═══════════════════ -->
            <div id="view-shop" class="view-content hidden space-y-6">

                <!-- Charts row -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6" data-aos="fade-up">
                    <div class="power-card p-6 chart-wrap">
                        <h4 class="text-xs font-bold text-gray-500 uppercase mb-5 tracking-widest flex items-center gap-2">
                            <i class="fa-solid fa-sun text-orange-400"></i> Solar Generation (24h)
                        </h4>
                        <canvas id="shop-solar-chart" height="140"></canvas>
                    </div>
                    <div class="power-card p-6 chart-wrap">
                        <h4 class="text-xs font-bold text-gray-500 uppercase mb-5 tracking-widest flex items-center gap-2">
                            <i class="fa-solid fa-battery-full text-green-400"></i> Battery Level (24h)
                        </h4>
                        <canvas id="shop-soc-chart" height="140"></canvas>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Master Health -->
                    <div class="power-card power-card-glow-green p-8 text-center" data-aos="fade-right">
                        <p class="text-[10px] font-bold text-gray-500 uppercase mb-5 tracking-widest">Storage Matrix</p>

                        <!-- Animated SOC gauge -->
                        <div class="relative w-44 h-44 mx-auto mb-5">
                            <svg viewBox="0 0 100 100" class="w-full h-full gauge-svg">
                                <circle cx="50" cy="50" r="42" stroke-width="7" class="gauge-bg"/>
                                <circle id="shop-gauge-circle" cx="50" cy="50" r="42" stroke-width="7"
                                    class="gauge-fill stroke-green-500"
                                    stroke-dasharray="263.9" stroke-dashoffset="263.9"/>
                            </svg>
                            <div class="absolute inset-0 flex flex-col items-center justify-center">
                                <span class="text-4xl font-black text-white tabular-nums" id="det-shop-soc">--</span>
                                <span class="text-[9px] font-bold text-gray-500 uppercase tracking-widest">% SOC</span>
                            </div>
                        </div>

                        <!-- SOC color bar -->
                        <div class="batt-bar mb-5">
                            <div id="shop-gauge-bar" class="batt-bar-fill" style="width:0%;background:linear-gradient(90deg,#22c55e,#4ade80)"></div>
                        </div>

                        <div class="grid grid-cols-2 gap-4 border-t border-white/5 pt-5">
                            <div>
                                <p class="text-[8px] text-gray-500 uppercase font-bold mb-1">Bus Current</p>
                                <p class="text-2xl font-black text-blue-400 tabular-nums"><span id="det-shop-amps">--</span><span class="text-sm">A</span></p>
                            </div>
                            <div>
                                <p class="text-[8px] text-gray-500 uppercase font-bold mb-1">Capacity</p>
                                <p class="text-2xl font-black text-white tabular-nums"><span id="det-shop-ah">--</span><span class="text-sm">Ah</span></p>
                            </div>
                        </div>

                        <div class="mt-5 p-3 rounded-xl bg-white/[0.03] border border-white/[0.06] space-y-2">
                            <div class="flex justify-between text-xs">
                                <span class="text-gray-500">Status</span>
                                <span id="det-shop-status" class="font-bold text-gray-400 italic">Standby</span>
                            </div>
                            <div class="flex justify-between text-xs">
                                <span class="text-red-400">Backup Time</span>
                                <span id="det-shop-backup" class="font-bold text-white">--</span>
                            </div>
                            <div class="flex justify-between text-xs">
                                <span class="text-green-400">To Full</span>
                                <span id="det-shop-charge" class="font-bold text-white">--</span>
                            </div>
                        </div>
                    </div>

                    <!-- Pack details -->
                    <div class="lg:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Pack 1 -->
                        <div class="power-card p-6" data-aos="fade-up" data-aos-delay="50">
                            <div class="flex justify-between items-center mb-6">
                                <h3 class="text-base font-black text-blue-400 italic">PACK 1 <span class="text-gray-600 text-sm">105Ah</span></h3>
                                <span id="det-shop-p1-link" class="text-[8px] px-2 py-1 rounded-full bg-gray-800 text-gray-500">OFFLINE</span>
                            </div>
                            <div class="space-y-4">
                                <div>
                                    <div class="flex justify-between text-sm mb-1.5">
                                        <span class="text-gray-400">SOC</span>
                                        <span class="font-black text-white tabular-nums" id="det-shop-p1-soc">--%</span>
                                    </div>
                                    <div class="batt-bar"><div id="p1-soc-bar" class="batt-bar-fill bg-blue-500" style="width:0%"></div></div>
                                </div>
                                <div class="flex justify-between items-center py-2 border-b border-white/[0.04]">
                                    <span class="text-sm text-gray-400">Current</span>
                                    <span class="text-lg font-black text-blue-400 tabular-nums" id="det-shop-p1-amps">--A</span>
                                </div>
                                <div class="flex justify-between items-center py-2 border-b border-white/[0.04]">
                                    <span class="text-sm text-gray-400">Cell Delta</span>
                                    <span class="text-lg font-black tabular-nums" id="det-shop-p1-delta">--V</span>
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div class="text-center p-2.5 rounded-xl bg-white/[0.03] border border-white/[0.06]">
                                        <p class="text-[7px] text-gray-500 uppercase mb-1">Charge Sw</p>
                                        <span id="det-shop-p1-sw-c" class="text-[9px] font-black">--</span>
                                    </div>
                                    <div class="text-center p-2.5 rounded-xl bg-white/[0.03] border border-white/[0.06]">
                                        <p class="text-[7px] text-gray-500 uppercase mb-1">Discharge Sw</p>
                                        <span id="det-shop-p1-sw-d" class="text-[9px] font-black">--</span>
                                    </div>
                                </div>

                                <!-- Extra pack stats -->
                                <div class="grid grid-cols-3 gap-2">
                                    <div class="text-center p-2 rounded-xl bg-white/[0.03] border border-white/[0.06]">
                                        <p class="text-[7px] text-gray-500 uppercase mb-1">Voltage</p>
                                        <span id="det-shop-p1-v" class="text-[10px] font-black text-white">--</span>
                                    </div>
                                    <div class="text-center p-2 rounded-xl bg-white/[0.03] border border-white/[0.06]">
                                        <p class="text-[7px] text-gray-500 uppercase mb-1">Power</p>
                                        <span id="det-shop-p1-pwr" class="text-[10px] font-black text-white">--</span>
                                    </div>
                                    <div class="text-center p-2 rounded-xl bg-white/[0.03] border border-white/[0.06]">
                                        <p class="text-[7px] text-gray-500 uppercase mb-1">SOH</p>
                                        <span id="det-shop-p1-soh" class="text-[10px] font-black text-white">--</span>
                                    </div>
                                    <div class="text-center p-2 rounded-xl bg-white/[0.03] border border-white/[0.06]">
                                        <p class="text-[7px] text-gray-500 uppercase mb-1">Cycles</p>
                                        <span id="det-shop-p1-cyc" class="text-[10px] font-black text-white">--</span>
                                    </div>
                                    <div class="text-center p-2 rounded-xl bg-white/[0.03] border border-white/[0.06]">
                                        <p class="text-[7px] text-gray-500 uppercase mb-1">Design Ah</p>
                                        <span id="det-shop-p1-design" class="text-[10px] font-black text-white">--</span>
                                    </div>
                                    <div class="text-center p-2 rounded-xl bg-white/[0.03] border border-white/[0.06]">
                                        <p class="text-[7px] text-gray-500 uppercase mb-1">Remain Ah</p>
                                        <span id="det-shop-p1-remain" class="text-[10px] font-black text-white">--</span>
                                    </div>
                                </div>
                                <div class="grid grid-cols-3 gap-2">
                                    <div class="text-center p-2 rounded-xl bg-white/[0.03] border border-white/[0.06]">
                                        <p class="text-[7px] text-gray-500 uppercase mb-1">Temp 1</p>
                                        <span id="det-shop-p1-t1" class="text-[10px] font-black text-white">--</span>
                                    </div>
                                    <div class="text-center p-2 rounded-xl bg-white/[0.03] border border-white/[0.06]">
                                        <p class="text-[7px] text-gray-500 uppercase mb-1">Temp 2</p>
                                        <span id="det-shop-p1-t2" class="text-[10px] font-black text-white">--</span>
                                    </div>
                                    <div class="text-center p-2 rounded-xl bg-white/[0.03] border border-white/[0.06]">
                                        <p class="text-[7px] text-gray-500 uppercase mb-1">MOSFET</p>
                                        <span id="det-shop-p1-tmos" class="text-[10px] font-black text-white">--</span>
                                    </div>
                                </div>

                                <!-- 16-cell voltage grid -->
                                <div>
                                    <div class="flex flex-wrap justify-between items-center gap-x-2 gap-y-1 mb-2">
                                        <p class="text-[9px] text-gray-500 uppercase font-bold">16 Cells</p>
                                        <p class="text-[8px] text-gray-600">avg <span id="det-shop-p1-cavg">--</span> · Δ high <span id="det-shop-p1-chigh">--</span> low <span id="det-shop-p1-clow">--</span></p>
                                    </div>
                                    <div class="grid grid-cols-8 gap-1.5" id="det-shop-p1-cells"></div>
                                </div>
                            </div>
                        </div>
                        <!-- Pack 2 -->
                        <div class="power-card p-6" data-aos="fade-up" data-aos-delay="100">
                            <div class="flex justify-between items-center mb-6">
                                <h3 class="text-base font-black text-purple-400 italic">PACK 2 <span class="text-gray-600 text-sm">100Ah</span></h3>
                                <span id="det-shop-p2-link" class="text-[8px] px-2 py-1 rounded-full bg-gray-800 text-gray-500">OFFLINE</span>
                            </div>
                            <div class="space-y-4">
                                <div>
                                    <div class="flex justify-between text-sm mb-1.5">
                                        <span class="text-gray-400">SOC</span>
                                        <span class="font-black text-white tabular-nums" id="det-shop-p2-soc">--%</span>
                                    </div>
                                    <div class="batt-bar"><div id="p2-soc-bar" class="batt-bar-fill bg-purple-500" style="width:0%"></div></div>
                                </div>
                                <div class="flex justify-between items-center py-2 border-b border-white/[0.04]">
                                    <span class="text-sm text-gray-400">Current</span>
                                    <span class="text-lg font-black text-purple-400 tabular-nums" id="det-shop-p2-amps">--A</span>
                                </div>
                                <div class="flex justify-between items-center py-2 border-b border-white/[0.04]">
                                    <span class="text-sm text-gray-400">Cell Delta</span>
                                    <span class="text-lg font-black tabular-nums" id="det-shop-p2-delta">--V</span>
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div class="text-center p-2.5 rounded-xl bg-white/[0.03] border border-white/[0.06]">
                                        <p class="text-[7px] text-gray-500 uppercase mb-1">Charge Sw</p>
                                        <span id="det-shop-p2-sw-c" class="text-[9px] font-black">--</span>
                                    </div>
                                    <div class="text-center p-2.5 rounded-xl bg-white/[0.03] border border-white/[0.06]">
                                        <p class="text-[7px] text-gray-500 uppercase mb-1">Discharge Sw</p>
                                        <span id="det-shop-p2-sw-d" class="text-[9px] font-black">--</span>
                                    </div>
                                </div>

                                <!-- Extra pack stats -->
                                <div class="grid grid-cols-3 gap-2">
                                    <div class="text-center p-2 rounded-xl bg-white/[0.03] border border-white/[0.06]">
                                        <p class="text-[7px] text-gray-500 uppercase mb-1">Voltage</p>
                                        <span id="det-shop-p2-v" class="text-[10px] font-black text-white">--</span>
                                    </div>
                                    <div class="text-center p-2 rounded-xl bg-white/[0.03] border border-white/[0.06]">
                                        <p class="text-[7px] text-gray-500 uppercase mb-1">Power</p>
                                        <span id="det-shop-p2-pwr" class="text-[10px] font-black text-white">--</span>
                                    </div>
                                    <div class="text-center p-2 rounded-xl bg-white/[0.03] border border-white/[0.06]">
                                        <p class="text-[7px] text-gray-500 uppercase mb-1">SOH</p>
                                        <span id="det-shop-p2-soh" class="text-[10px] font-black text-white">--</span>
                                    </div>
                                    <div class="text-center p-2 rounded-xl bg-white/[0.03] border border-white/[0.06]">
                                        <p class="text-[7px] text-gray-500 uppercase mb-1">Cycles</p>
                                        <span id="det-shop-p2-cyc" class="text-[10px] font-black text-white">--</span>
                                    </div>
                                    <div class="text-center p-2 rounded-xl bg-white/[0.03] border border-white/[0.06]">
                                        <p class="text-[7px] text-gray-500 uppercase mb-1">Design Ah</p>
                                        <span id="det-shop-p2-design" class="text-[10px] font-black text-white">--</span>
                                    </div>
                                    <div class="text-center p-2 rounded-xl bg-white/[0.03] border border-white/[0.06]">
                                        <p class="text-[7px] text-gray-500 uppercase mb-1">Remain Ah</p>
                                        <span id="det-shop-p2-remain" class="text-[10px] font-black text-white">--</span>
                                    </div>
                                </div>
                                <div class="grid grid-cols-3 gap-2">
                                    <div class="text-center p-2 rounded-xl bg-white/[0.03] border border-white/[0.06]">
                                        <p class="text-[7px] text-gray-500 uppercase mb-1">Temp 1</p>
                                        <span id="det-shop-p2-t1" class="text-[10px] font-black text-white">--</span>
                                    </div>
                                    <div class="text-center p-2 rounded-xl bg-white/[0.03] border border-white/[0.06]">
                                        <p class="text-[7px] text-gray-500 uppercase mb-1">Temp 2</p>
                                        <span id="det-shop-p2-t2" class="text-[10px] font-black text-white">--</span>
                                    </div>
                                    <div class="text-center p-2 rounded-xl bg-white/[0.03] border border-white/[0.06]">
                                        <p class="text-[7px] text-gray-500 uppercase mb-1">MOSFET</p>
                                        <span id="det-shop-p2-tmos" class="text-[10px] font-black text-white">--</span>
                                    </div>
                                </div>

                                <!-- 16-cell voltage grid -->
                                <div>
                                    <div class="flex flex-wrap justify-between items-center gap-x-2 gap-y-1 mb-2">
                                        <p class="text-[9px] text-gray-500 uppercase font-bold">16 Cells</p>
                                        <p class="text-[8px] text-gray-600">avg <span id="det-shop-p2-cavg">--</span> · Δ high <span id="det-shop-p2-chigh">--</span> low <span id="det-shop-p2-clow">--</span></p>
                                    </div>
                                    <div class="grid grid-cols-8 gap-1.5" id="det-shop-p2-cells"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Overall System Check -->
                    <div class="power-card p-6 md:p-8 mt-6 lg:col-span-3" data-aos="fade-up">
                        <div class="flex flex-wrap justify-between items-center gap-3 mb-6">
                            <h3 class="text-lg font-black text-white italic">OVERALL <span class="text-gray-600 text-base">200Ah Bank</span></h3>
                            <span id="shop-system-check" class="text-[10px] px-3.5 py-2 rounded-full bg-gray-800 text-gray-500 font-bold uppercase tracking-widest">Checking...</span>
                        </div>
                        <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-3 md:gap-4">
                            <div class="text-center p-3 rounded-xl bg-white/[0.03] border border-white/[0.06]">
                                <p class="text-[9px] text-gray-500 uppercase mb-1.5 tracking-wide">SOC</p>
                                <span id="det-shop-bank-soc" class="text-base font-black text-white">--</span>
                            </div>
                            <div class="text-center p-3 rounded-xl bg-white/[0.03] border border-white/[0.06]">
                                <p class="text-[9px] text-gray-500 uppercase mb-1.5 tracking-wide">Voltage</p>
                                <span id="det-shop-bank-v" class="text-base font-black text-white">--</span>
                            </div>
                            <div class="text-center p-3 rounded-xl bg-white/[0.03] border border-white/[0.06]">
                                <p class="text-[9px] text-gray-500 uppercase mb-1.5 tracking-wide">Current</p>
                                <span id="det-shop-bank-amps" class="text-base font-black text-white">--</span>
                            </div>
                            <div class="text-center p-3 rounded-xl bg-white/[0.03] border border-white/[0.06]">
                                <p class="text-[9px] text-gray-500 uppercase mb-1.5 tracking-wide">Power</p>
                                <span id="det-shop-bank-pwr" class="text-base font-black text-white">--</span>
                                <p id="det-shop-bank-pwr-dir" class="text-[8px] font-bold uppercase tracking-wide mt-0.5"></p>
                            </div>
                            <div class="text-center p-3 rounded-xl bg-white/[0.03] border border-white/[0.06]">
                                <p class="text-[9px] text-gray-500 uppercase mb-1.5 tracking-wide">SOH</p>
                                <span id="det-shop-bank-soh" class="text-base font-black text-white">--</span>
                            </div>
                            <div class="text-center p-3 rounded-xl bg-white/[0.03] border border-white/[0.06]">
                                <p class="text-[9px] text-gray-500 uppercase mb-1.5 tracking-wide">Design</p>
                                <span id="det-shop-bank-design" class="text-base font-black text-white">--</span>
                            </div>
                            <div class="text-center p-3 rounded-xl bg-white/[0.03] border border-white/[0.06]">
                                <p class="text-[9px] text-gray-500 uppercase mb-1.5 tracking-wide">Remaining</p>
                                <span id="det-shop-bank-remain" class="text-base font-black text-white">--</span>
                            </div>
                            <div class="text-center p-3 rounded-xl bg-white/[0.03] border border-white/[0.06]">
                                <p class="text-[9px] text-gray-500 uppercase mb-1.5 tracking-wide">Temp</p>
                                <span id="det-shop-bank-temp" class="text-base font-black text-white">--</span>
                            </div>
                        </div>
                        <div id="shop-check-list" class="mt-5 grid grid-cols-1 sm:grid-cols-2 gap-2.5"></div>
                    </div>
                </div>
            </div>

            <!-- ═══════════════════ VIEW 3: HOME DETAILS ═══════════════════ -->
            <div id="view-home" class="view-content hidden space-y-6">

                <!-- Charts -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6" data-aos="fade-up">
                    <div class="power-card p-6 chart-wrap">
                        <h4 class="text-xs font-bold text-gray-500 uppercase mb-5 tracking-widest flex items-center gap-2">
                            <i class="fa-solid fa-sun text-orange-400"></i> Home PV (24h)
                        </h4>
                        <canvas id="home-solar-chart" height="140"></canvas>
                    </div>
                    <div class="power-card p-6 chart-wrap">
                        <h4 class="text-xs font-bold text-gray-500 uppercase mb-5 tracking-widest flex items-center gap-2">
                            <i class="fa-solid fa-battery-full text-purple-400"></i> Home Storage (24h)
                        </h4>
                        <canvas id="home-soc-chart" height="140"></canvas>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- BMS Primary -->
                    <div class="power-card power-card-glow-purple p-8 flex flex-col justify-center" data-aos="fade-right">
                        <h3 class="text-xs font-bold text-gray-500 uppercase mb-8 tracking-widest text-center">BMS Primary Matrix</h3>
                        <div class="text-center mb-8">
                            <p class="text-6xl font-black text-white tabular-nums"><span id="det-home-soc">--</span><span class="text-2xl text-gray-500">%</span></p>
                            <p class="text-[9px] text-purple-400 font-bold uppercase tracking-[0.3em] mt-2">State of Charge</p>
                            <div class="mt-4 batt-bar">
                                <div id="home-soc-gauge-bar" class="batt-bar-fill" style="width:0%;background:linear-gradient(90deg,#a855f7,#c084fc)"></div>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4 border-t border-white/5 pt-6">
                            <div class="text-center">
                                <p class="text-2xl font-black text-white tabular-nums"><span id="det-home-v">--</span><span class="text-sm">V</span></p>
                                <p class="text-[8px] text-gray-500 uppercase mt-1">Voltage</p>
                            </div>
                            <div class="text-center border-l border-white/5">
                                <p class="text-2xl font-black text-white tabular-nums"><span id="det-home-amps">--</span><span class="text-sm">A</span></p>
                                <p class="text-[8px] text-gray-500 uppercase mt-1">Current</p>
                            </div>
                        </div>
                    </div>

                    <!-- Inverter + Thermal -->
                    <div class="lg:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="power-card p-7" data-aos="fade-up" data-aos-delay="50">
                            <h4 class="text-xs font-bold text-orange-400 uppercase mb-6 flex items-center gap-2 italic tracking-wider">
                                <i class="fa-solid fa-microchip"></i> Inverter Telemetry
                            </h4>
                            <div class="space-y-5">
                                <div>
                                    <div class="flex justify-between text-sm mb-1">
                                        <span class="text-gray-400">Direct PV</span>
                                        <span class="font-black text-orange-400 tabular-nums"><span id="det-home-pv">--</span>W</span>
                                    </div>
                                    <div class="batt-bar"><div id="home-pv-bar" class="batt-bar-fill bg-orange-500" style="width:0%"></div></div>
                                </div>
                                <div class="flex justify-between items-center border-b border-white/[0.04] pb-4">
                                    <span class="text-sm text-gray-400">Grid Power</span>
                                    <span class="text-lg font-black text-blue-400 tabular-nums"><span id="det-home-grid">--</span>W</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm font-bold text-white uppercase italic">House Load</span>
                                    <span class="text-3xl font-black text-red-400 tabular-nums"><span id="det-home-inv">--</span>W</span>
                                </div>
                            </div>
                        </div>

                        <div class="power-card p-7" data-aos="fade-up" data-aos-delay="100">
                            <h4 class="text-xs font-bold text-blue-400 uppercase mb-6 flex items-center gap-2 italic tracking-wider">
                                <i class="fa-solid fa-temperature-half"></i> Thermal & Logic
                            </h4>
                            <div class="space-y-5">
                                <div class="flex justify-between items-center border-b border-white/[0.04] pb-4">
                                    <span class="text-sm text-gray-400">BMS Temp</span>
                                    <span class="text-2xl font-black text-cyan-400 tabular-nums"><span id="det-home-temp">--</span>°C</span>
                                </div>
                                <div class="flex justify-between items-center border-b border-white/[0.04] pb-4">
                                    <span class="text-sm text-gray-400">Cell Delta</span>
                                    <span class="text-2xl font-black text-white tabular-nums"><span id="det-home-delta">--</span>mV</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-400">BMS Power</span>
                                    <span class="text-2xl font-black text-purple-400 tabular-nums"><span id="det-home-p">--</span>W</span>
                                </div>
                            </div>
                            <div class="mt-6 grid grid-cols-2 gap-2">
                                <span class="p-2 bg-green-500/5 text-green-500 border border-green-500/20 rounded-xl text-[9px] font-bold text-center">BMS ACTIVE</span>
                                <span class="p-2 bg-blue-500/5 text-blue-500 border border-blue-500/20 rounded-xl text-[9px] font-bold text-center">GRID LINK OK</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Last updated -->
            <p class="text-center text-[10px] text-gray-700 mt-8">Last updated: <span id="last-updated">--</span></p>

        </div>
    </div>
</section>

<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    AOS.init({ once: true, duration: 600 });

    // ── Entities ──
    const entities = {
        shop_total_soc:  'sensor.shop_total_soc',
        shop_total_ah:   'sensor.shop_total_capacity_ah',
        shop_total_amps: 'sensor.shop_total_current',
        shop_backup:     'sensor.shop_backup_time_remaining',
        shop_to_full:    'sensor.shop_time_to_full_charge',
        shop_pv:         'sensor.flin_fution_inverter_pv_power',
        shop_load:       'sensor.flin_fution_inverter_ac_out_active_power',
        shop_temp:       'sensor.battery_bank_200ah_temperature_1',
        shop_grid:       'sensor.flin_fution_inverter_grid_power',
        shop_batt_pwr:   'sensor.flin_fution_inverter_battery_power',
        shop_p1_soc:     'sensor.shop_battery_pack_one_shop_bms_state_of_charge',
        shop_p1_amps:    'sensor.shop_battery_pack_one_shop_bms_current',
        shop_p1_delta:   'sensor.shop_battery_pack_one_shop_bms_cell_delta',
        shop_p1_link:    'binary_sensor.shop_battery_pack_one_shop_bms_online_status',
        shop_p1_sw_c:    'switch.shop_battery_pack_one_shop_bms_charging_switch',
        shop_p1_sw_d:    'switch.shop_battery_pack_one_shop_bms_discharging_switch',
        shop_p2_soc:     'sensor.shop_battery_pack_two_shop_2_bms_state_of_charge',
        shop_p2_amps:    'sensor.shop_battery_pack_two_shop_2_bms_current',
        shop_p2_delta:   'sensor.shop_battery_pack_two_shop_2_bms_cell_delta',
        shop_p2_link:    'binary_sensor.shop_battery_pack_two_shop_2_bms_online_status',
        shop_p2_sw_c:    'switch.shop_battery_pack_two_shop_2_bms_charging_switch',
        shop_p2_sw_d:    'switch.shop_battery_pack_two_shop_2_bms_discharging_switch',

        // Pack 1 - raw JK BMS detail (voltage/power/temps/cycles/capacity + 16 cells)
        shop_p1_v:       'sensor.jk_bms_1_100ah_bms1_battery_voltage',
        shop_p1_pwr:     'sensor.jk_bms_1_100ah_bms1_battery_power',
        shop_p1_soh:     'sensor.jk_bms_1_100ah_bms1_state_of_health',
        shop_p1_cyc:     'sensor.jk_bms_1_100ah_bms1_cycle_count',
        shop_p1_design:  'sensor.jk_bms_1_100ah_bms1_design_capacity',
        shop_p1_remain:  'sensor.jk_bms_1_100ah_bms1_remaining_capacity',
        shop_p1_t1:      'sensor.jk_bms_1_100ah_bms1_temperature_1',
        shop_p1_t2:      'sensor.jk_bms_1_100ah_bms1_temperature_2',
        shop_p1_tmos:    'sensor.jk_bms_1_100ah_bms1_temperature_mos',
        shop_p1_cavg:    'sensor.jk_bms_1_100ah_bms1_cell_voltage_average',
        shop_p1_chigh:   'sensor.jk_bms_1_100ah_bms1_cell_voltage_highest',
        shop_p1_clow:    'sensor.jk_bms_1_100ah_bms1_cell_voltage_lowest',

        // Pack 2 - raw JK BMS detail
        shop_p2_v:       'sensor.jk_bms_2_100ah_bms2_battery_voltage',
        shop_p2_pwr:     'sensor.jk_bms_2_100ah_bms2_battery_power',
        shop_p2_soh:     'sensor.jk_bms_2_100ah_bms2_state_of_health',
        shop_p2_cyc:     'sensor.jk_bms_2_100ah_bms2_cycle_count',
        shop_p2_design:  'sensor.jk_bms_2_100ah_bms2_design_capacity',
        shop_p2_remain:  'sensor.jk_bms_2_100ah_bms2_remaining_capacity',
        shop_p2_t1:      'sensor.jk_bms_2_100ah_bms2_temperature_1',
        shop_p2_t2:      'sensor.jk_bms_2_100ah_bms2_temperature_2',
        shop_p2_tmos:    'sensor.jk_bms_2_100ah_bms2_temperature_mos',
        shop_p2_cavg:    'sensor.jk_bms_2_100ah_bms2_cell_voltage_average',
        shop_p2_chigh:   'sensor.jk_bms_2_100ah_bms2_cell_voltage_highest',
        shop_p2_clow:    'sensor.jk_bms_2_100ah_bms2_cell_voltage_lowest',

        // Combined shop bank (200Ah total) - overall system numbers
        shop_bank_v:       'sensor.battery_bank_200ah_battery_voltage',
        shop_bank_amps:    'sensor.battery_bank_200ah_battery_current',
        shop_bank_pwr:     'sensor.battery_bank_200ah_battery_power',
        shop_bank_soc:     'sensor.battery_bank_200ah_state_of_charge',
        shop_bank_soh:     'sensor.battery_bank_200ah_state_of_health',
        shop_bank_t1:      'sensor.battery_bank_200ah_temperature_1',
        shop_bank_t2:      'sensor.battery_bank_200ah_temperature_2',
        shop_bank_tmos:    'sensor.battery_bank_200ah_temperature_mos',
        shop_bank_design:  'sensor.battery_bank_200ah_total_design_capacity',
        shop_bank_remain:  'sensor.battery_bank_200ah_total_remaining_capacity',

        home_pv:         'sensor.q004719472515009ad05_direct_pv_power',
        home_soc:        'sensor.jkbms_home_bms_state_of_charge',
        home_v:          'sensor.jkbms_home_bms_battery_voltage',
        home_p:          'sensor.jkbms_home_bms_power',
        home_load:       'sensor.q004719472515009ad05_direct_inverter_out_power',
        home_temp:       'sensor.jkbms_home_bms_temperature_1',
        home_delta:      'sensor.jkbms_home_bms_cell_delta',
        home_grid:       'sensor.q004719472515009ad05_direct_apparent_power',
        home_batt_pwr:   'sensor.jkbms_home_bms_power',
        home_amps:       'sensor.jkbms_home_bms_current'
    };

    // ── Helpers ──
    function fmt(val, d = 1) {
        if (val === undefined || val === null || isNaN(parseFloat(val))) return '--';
        return parseFloat(val).toFixed(d);
    }

    function setText(ids, value) {
        if (!Array.isArray(ids)) ids = [ids];
        ids.forEach(id => {
            const el = document.getElementById(id);
            if (!el) return;
            if (el.textContent !== value) {
                el.classList.add('val-flash');
                el.textContent = value;
                setTimeout(() => el.classList.remove('val-flash'), 400);
            }
        });
    }

    function setBar(id, pct) {
        const el = document.getElementById(id);
        if (el) el.style.width = Math.min(100, Math.max(0, pct)) + '%';
    }

    function socColor(soc) {
        if (soc >= 60) return { stroke: '#22c55e', bar: 'linear-gradient(90deg,#22c55e,#4ade80)' };
        if (soc >= 30) return { stroke: '#eab308', bar: 'linear-gradient(90deg,#eab308,#facc15)' };
        return { stroke: '#ef4444', bar: 'linear-gradient(90deg,#ef4444,#f87171)' };
    }

    function toggleFlow(id, active, reverse = false, wattage = 0) {
        const el = document.getElementById(id);
        if (!el) return;
        if (active) {
            el.classList.add('flow-active');
            el.classList.toggle('flow-reverse', reverse);
            el.style.animationDuration = Math.max(0.8, 5 - Math.abs(wattage) / 500) + 's';
        } else {
            el.classList.remove('flow-active');
        }
    }

    function updateNodeGlow(id, active, glowClass) {
        const el = document.getElementById(id);
        if (!el) return;
        if (active) el.classList.add(glowClass); else el.classList.remove(glowClass);
    }

    function updateSwitchUI(id, state) {
        const el = document.getElementById(id);
        if (!el) return;
        el.textContent = state === 'on' ? 'ON' : 'OFF';
        el.className = state === 'on' ? 'text-[9px] font-black text-green-400' : 'text-[9px] font-black text-red-500';
    }

    function updateLinkUI(id, state) {
        const el = document.getElementById(id);
        if (!el) return;
        if (state === 'on') {
            el.textContent = 'ONLINE';
            el.className = 'text-[8px] px-2 py-1 rounded-full bg-green-500/10 text-green-400 border border-green-500/20';
        } else {
            el.textContent = 'OFFLINE';
            el.className = 'text-[8px] px-2 py-1 rounded-full bg-red-500/10 text-red-500 border border-red-500/20';
        }
    }

    async function fetchEntity(entityId, cb) {
        try {
            const res = await fetch(`api/ha_proxy.php?entity=${entityId}&_t=${Date.now()}`);
            const data = await res.json();
            if (!data.error && cb) cb(data.state);
        } catch(e) {}
    }

    // Latest known min/max cell voltage per pack, filled in by fetchPackCells()
    const packCellStats = {};

    // LiFePO4 cell voltage -> fill % and color (2.80V empty .. 3.65V full)
    function cellVoltToVisual(v) {
        const MIN = 2.80, MAX = 3.65;
        const pct = Math.max(0, Math.min(100, ((v - MIN) / (MAX - MIN)) * 100));
        let color;
        if (v < 3.00)      color = '#ef4444'; // red - low
        else if (v < 3.20) color = '#f97316'; // orange - getting low
        else if (v < 3.30) color = '#eab308'; // yellow - ok
        else if (v < 3.45) color = '#22c55e'; // green - healthy
        else                color = '#38bdf8'; // blue - full / high
        return { pct, color };
    }

    // Fetch all 16 cell voltages for a JK BMS pack and render as animated mini batteries
    async function fetchPackCells(prefix, containerId) {
        const container = document.getElementById(containerId);
        if (!container) return;
        if (!container.dataset.built) {
            container.innerHTML = '';
            for (let i = 1; i <= 16; i++) {
                const cell = document.createElement('div');
                cell.className = 'cell-batt-wrap';
                cell.innerHTML = `
                    <div class="cell-batt" id="${containerId}-c${i}-batt">
                        <div class="cell-batt-fill" id="${containerId}-c${i}-fill"></div>
                        <span class="cell-batt-label" style="position:absolute;top:1px;left:0;right:0;text-align:center;">${i}</span>
                    </div>
                    <p class="cell-batt-volt" id="${containerId}-c${i}">--</p>
                `;
                container.appendChild(cell);
            }
            container.dataset.built = '1';
        }
        // Parallel fetch all 16 cells, mark the highest/lowest for at-a-glance imbalance
        const results = await Promise.all(
            Array.from({length: 16}, (_, idx) =>
                fetch(`api/ha_proxy.php?entity=sensor.${prefix}_cell_${idx + 1}&_t=${Date.now()}`)
                    .then(r => r.json()).catch(() => null)
            )
        );
        const nums = results.map(r => r && !r.error ? parseFloat(r.state) : NaN).filter(n => Number.isFinite(n));
        const max = nums.length ? Math.max(...nums) : null;
        const min = nums.length ? Math.min(...nums) : null;
        packCellStats[prefix] = { min, max };
        results.forEach((r, idx) => {
            const i = idx + 1;
            const fillEl  = document.getElementById(`${containerId}-c${i}-fill`);
            const battEl  = document.getElementById(`${containerId}-c${i}-batt`);
            const voltEl  = document.getElementById(`${containerId}-c${i}`);
            if (!r || r.error) return;
            const v = parseFloat(r.state);
            if (!Number.isFinite(v)) return;

            if (voltEl) voltEl.textContent = v.toFixed(3);
            const { pct, color } = cellVoltToVisual(v);
            if (fillEl) { fillEl.style.height = pct + '%'; fillEl.style.background = `linear-gradient(180deg, rgba(255,255,255,0.25), transparent 40%), ${color}`; }
            if (battEl) {
                battEl.classList.remove('hi-cell', 'lo-cell');
                if (v === max) battEl.classList.add('hi-cell');
                else if (v === min) battEl.classList.add('lo-cell');
            }
        });
    }

    // Overall pass/fail health check for the Shop 200Ah bank
    async function runShopSystemCheck() {
        const badge = document.getElementById('shop-system-check');
        const list = document.getElementById('shop-check-list');
        if (!badge || !list) return;

        const checks = [];
        const fetchState = (id) => fetch(`api/ha_proxy.php?entity=${id}&_t=${Date.now()}`).then(r=>r.json()).catch(()=>null);
        const asNum = (r) => r && !r.error ? parseFloat(r.state) : NaN;

        const [p1Link, p2Link, p1Delta, p2Delta,
               p1T1, p1T2, p1Tmos, p2T1, p2T2, p2Tmos,
               p1Soh, p2Soh] = await Promise.all([
            fetchState(entities.shop_p1_link), fetchState(entities.shop_p2_link),
            fetchState(entities.shop_p1_delta), fetchState(entities.shop_p2_delta),
            fetchState(entities.shop_p1_t1), fetchState(entities.shop_p1_t2), fetchState(entities.shop_p1_tmos),
            fetchState(entities.shop_p2_t1), fetchState(entities.shop_p2_t2), fetchState(entities.shop_p2_tmos),
            fetchState(entities.shop_p1_soh), fetchState(entities.shop_p2_soh),
        ]);

        checks.push({ ok: p1Link && p1Link.state === 'on', label: 'Pack 1 BMS communication' });
        checks.push({ ok: p2Link && p2Link.state === 'on', label: 'Pack 2 BMS communication' });

        const d1 = asNum(p1Delta), d2 = asNum(p2Delta);
        checks.push({ ok: !Number.isFinite(d1) || d1 < 0.05, label: 'Pack 1 cell balance (Δ < 0.05V)' });
        checks.push({ ok: !Number.isFinite(d2) || d2 < 0.05, label: 'Pack 2 cell balance (Δ < 0.05V)' });

        // Temperature safety (0-45°C safe operating range for LiFePO4)
        const tempOk = (t) => !Number.isFinite(t) || (t >= 0 && t <= 45);
        const temps1 = [asNum(p1T1), asNum(p1T2), asNum(p1Tmos)];
        const temps2 = [asNum(p2T1), asNum(p2T2), asNum(p2Tmos)];
        checks.push({ ok: temps1.every(tempOk), label: 'Pack 1 temperature in safe range (0-45°C)' });
        checks.push({ ok: temps2.every(tempOk), label: 'Pack 2 temperature in safe range (0-45°C)' });

        // State of health
        const soh1 = asNum(p1Soh), soh2 = asNum(p2Soh);
        checks.push({ ok: !Number.isFinite(soh1) || soh1 >= 80, label: 'Pack 1 health (SOH ≥ 80%)' });
        checks.push({ ok: !Number.isFinite(soh2) || soh2 >= 80, label: 'Pack 2 health (SOH ≥ 80%)' });

        // Individual cell voltage extremes (from the last cell-grid fetch)
        const s1 = packCellStats['jk_bms_1_100ah_bms1'];
        const s2 = packCellStats['jk_bms_2_100ah_bms2'];
        const cellOk = (v) => v == null || (v >= 2.90 && v <= 3.60);
        checks.push({ ok: !s1 || (cellOk(s1.min) && cellOk(s1.max)), label: 'Pack 1 all cells within 2.90-3.60V' });
        checks.push({ ok: !s2 || (cellOk(s2.min) && cellOk(s2.max)), label: 'Pack 2 all cells within 2.90-3.60V' });

        const allOk = checks.every(c => c.ok);
        badge.textContent = allOk ? '✓ ALL SYSTEMS OK' : '⚠ ISSUES FOUND';
        badge.className = 'text-[9px] px-3 py-1.5 rounded-full font-bold uppercase tracking-widest ' +
            (allOk ? 'bg-green-500/10 text-green-400 border border-green-500/20' : 'bg-red-500/10 text-red-400 border border-red-500/20');

        list.innerHTML = checks.map(c => `
            <div class="flex items-center gap-2.5 p-2.5 rounded-lg bg-white/[0.03] border border-white/[0.06] text-xs ${c.ok ? 'text-gray-400' : 'text-red-400'}">
                <i class="fa-solid ${c.ok ? 'fa-circle-check text-green-500' : 'fa-triangle-exclamation text-red-500'}"></i>
                <span>${c.label}</span>
            </div>
        `).join('');
    }

    // ── Refresh ring ──
    let countdown = 15;
    const ringCircumference = 69.1;
    function tickCountdown() {
        countdown--;
        if (countdown <= 0) { countdown = 15; updateDashboard(); }
        const ring = document.getElementById('refresh-ring');
        const ct   = document.getElementById('refresh-countdown');
        if (ring) ring.style.strokeDashoffset = ringCircumference * (1 - countdown / 15);
        if (ct) ct.textContent = countdown;
    }
    setInterval(tickCountdown, 1000);

    // ── Live clock ──
    function tickClock() {
        const el = document.getElementById('live-clock');
        if (el) el.textContent = new Date().toLocaleTimeString();
    }
    setInterval(tickClock, 1000);
    tickClock();

    // ── Tab switch ──
    function switchTab(id) {
        document.querySelectorAll('.view-content').forEach(el => el.classList.add('hidden'));
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('active', 'text-white');
            btn.classList.add('text-gray-400');
        });
        document.getElementById('view-' + id)?.classList.remove('hidden');
        const btn = document.getElementById('tab-' + id);
        if (btn) { btn.classList.add('active', 'text-white'); btn.classList.remove('text-gray-400'); }
    }

    // ── Canvas SOC Overview Gauge ──
    function drawOvGauge(canvasId, soc) {
        const canvas = document.getElementById(canvasId);
        if (!canvas) return;
        const dpr  = window.devicePixelRatio || 1;
        const size = 160;
        canvas.style.width  = size + 'px';
        canvas.style.height = size + 'px';
        canvas.width  = size * dpr;
        canvas.height = size * dpr;
        const ctx = canvas.getContext('2d');
        ctx.setTransform(1,0,0,1,0,0);
        ctx.scale(dpr, dpr);

        const cx = size / 2, cy = size / 2;
        const trackR = size * 0.37;
        const trackW = size * 0.09;
        const startA = Math.PI * 0.75;
        const totalA = Math.PI * 1.5;
        const endA   = startA + totalA;
        const safeSoc = Math.min(100, Math.max(0, soc));
        const fillA  = startA + totalA * safeSoc / 100;
        const c = socColor(soc);

        ctx.clearRect(0, 0, size, size);

        // Background track
        ctx.beginPath();
        ctx.arc(cx, cy, trackR, startA, endA);
        ctx.strokeStyle = 'rgba(255,255,255,0.05)';
        ctx.lineWidth = trackW;
        ctx.lineCap  = 'butt';
        ctx.stroke();

        // Tick marks
        for (let i = 0; i <= 10; i++) {
            const a = startA + totalA * i / 10;
            const r1 = trackR + trackW * 0.75, r2 = trackR + trackW * 1.4;
            ctx.beginPath();
            ctx.moveTo(cx + r1 * Math.cos(a), cy + r1 * Math.sin(a));
            ctx.lineTo(cx + r2 * Math.cos(a), cy + r2 * Math.sin(a));
            ctx.strokeStyle = 'rgba(255,255,255,0.1)';
            ctx.lineWidth = 1.5; ctx.lineCap = 'butt';
            ctx.stroke();
        }

        if (soc > 0) {
            // Glow layer
            ctx.save();
            ctx.shadowColor = c.stroke; ctx.shadowBlur = 20;
            ctx.beginPath();
            ctx.arc(cx, cy, trackR, startA, fillA);
            ctx.strokeStyle = c.stroke;
            ctx.lineWidth = trackW + 2;
            ctx.lineCap = 'round';
            ctx.stroke();
            ctx.restore();

            // Crisp fill
            ctx.beginPath();
            ctx.arc(cx, cy, trackR, startA, fillA);
            ctx.strokeStyle = c.stroke;
            ctx.lineWidth = trackW;
            ctx.lineCap = 'round';
            ctx.stroke();

            // Bright tip dot
            ctx.beginPath();
            ctx.arc(
                cx + trackR * Math.cos(fillA),
                cy + trackR * Math.sin(fillA),
                trackW * 0.55, 0, Math.PI * 2
            );
            ctx.fillStyle = c.stroke;
            ctx.shadowColor = c.stroke; ctx.shadowBlur = 12;
            ctx.fill();
            ctx.shadowBlur = 0;
        }

        // Center radial glow
        const rgb = soc >= 60 ? '34,197,94' : soc >= 30 ? '234,179,8' : '239,68,68';
        const grad = ctx.createRadialGradient(cx, cy, 0, cx, cy, trackR - trackW / 2);
        grad.addColorStop(0,   `rgba(${rgb},${soc > 0 ? 0.13 : 0})`);
        grad.addColorStop(0.6, `rgba(${rgb},0.04)`);
        grad.addColorStop(1,   'transparent');
        ctx.beginPath();
        ctx.arc(cx, cy, trackR - trackW / 2, 0, Math.PI * 2);
        ctx.fillStyle = grad;
        ctx.fill();

        // SOC number
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillStyle = soc > 0 ? '#f9fafb' : '#374151';
        ctx.font = `900 ${Math.floor(size * 0.21)}px system-ui,-apple-system,sans-serif`;
        ctx.fillText(soc > 0 ? soc.toFixed(1) : '--', cx, cy - size * 0.04);

        // % SOC label
        ctx.fillStyle = '#6b7280';
        ctx.font = `700 ${Math.floor(size * 0.085)}px system-ui,sans-serif`;
        ctx.fillText('% SOC', cx, cy + size * 0.17);
    }

    // ── Dashboard update ──
    let shopPvVal = 0, homeLoadVal = 0;

    // Combined Total Solar / Total Load top-bar stats - updated from each source's own
    // callback (not read synchronously) since fetchEntity() is async and fire-and-forget.
    const combinedTotals = { shopPv: 0, homePv: 0, shopLoad: 0, homeLoad: 0 };
    function updateCombinedTotals() {
        setText('stat-total-pv',   Math.round(combinedTotals.shopPv + combinedTotals.homePv));
        setText('stat-total-load', Math.round(combinedTotals.shopLoad + combinedTotals.homeLoad));
    }

    async function updateDashboard() {
        document.getElementById('last-updated').textContent = new Date().toLocaleTimeString();

        // SHOP PV
        fetchEntity(entities.shop_pv, (val) => {
            shopPvVal = parseFloat(val) || 0;
            const v = fmt(val, 0);
            setText(['ov-shop-pv', 'ov-shop-val-solar'], v + 'W');
            setBar('ov-shop-pv-bar', Math.min(100, shopPvVal / 20));
            updateNodeGlow('node-shop-solar', shopPvVal > 10, 'glow-orange');
            toggleFlow('ov-shop-flow-solar', shopPvVal > 10, false, shopPvVal);
            combinedTotals.shopPv = shopPvVal;
            updateCombinedTotals();
        });

        // SHOP SOC
        fetchEntity(entities.shop_total_soc, (val) => {
            const soc = parseFloat(val) || 0;
            const v   = fmt(val, 1);
            setText(['ov-shop-soc', 'det-shop-soc', 'ov-shop-val-battery', 'stat-shop-soc'], v);
            setBar('shop-soc-bar', soc); setBar('shop-gauge-bar', soc);
            updateNodeGlow('node-shop-battery', true, 'glow-green');
            // Canvas overview gauge
            drawOvGauge('ov-canvas-shop', soc);
            // SOC bar color
            const c = socColor(soc);
            const sb = document.getElementById('shop-soc-bar');
            if (sb) sb.style.background = c.bar;
            // SVG detail gauge
            const gauge = document.getElementById('shop-gauge-circle');
            if (gauge) {
                gauge.style.strokeDashoffset = 263.9 - (263.9 * soc / 100);
                gauge.style.stroke = c.stroke;
                const bar = document.getElementById('shop-gauge-bar');
                if (bar) bar.style.background = c.bar;
            }
        });

        // SHOP AMPS
        fetchEntity(entities.shop_total_amps, (val) => {
            const a = parseFloat(val) || 0;
            setText(['det-shop-amps', 'ov-shop-amps'], fmt(val, 1));
            // Overview charge badge
            const badge = document.getElementById('ov-shop-charge-badge');
            if (badge) {
                if (a < -0.5) { badge.textContent = 'DISCHARGING'; badge.className = 'mt-2.5 px-3 py-1 rounded-full text-[9px] font-bold border charge-badge-discharging'; }
                else if (a > 0.5) { badge.textContent = 'CHARGING'; badge.className = 'mt-2.5 px-3 py-1 rounded-full text-[9px] font-bold border charge-badge-charging'; }
                else { badge.textContent = 'STANDBY'; badge.className = 'mt-2.5 px-3 py-1 rounded-full text-[9px] font-bold border charge-badge-standby'; }
            }
            const st = document.getElementById('det-shop-status');
            if (st) {
                if (a < -0.5) { st.textContent = 'Discharging'; st.className = 'font-bold text-red-400 italic'; }
                else if (a > 0.5) { st.textContent = 'Charging'; st.className = 'font-bold text-green-400 italic'; }
                else { st.textContent = 'Standby'; st.className = 'font-bold text-gray-500 italic'; }
            }
        });

        // SHOP LOAD
        fetchEntity(entities.shop_load, (val) => {
            const pwr = parseFloat(val) || 0;
            setText(['ov-shop-load', 'ov-shop-val-load'], fmt(val, 0) + 'W');
            setBar('ov-shop-load-bar', Math.min(100, pwr / 30));
            updateNodeGlow('node-shop-load', pwr > 10, 'glow-red');
            toggleFlow('ov-shop-flow-home', pwr > 10, false, pwr);
            combinedTotals.shopLoad = pwr;
            updateCombinedTotals();
        });

        fetchEntity(entities.shop_temp, (val) => {
            setText(['ov-shop-temp'], fmt(val, 1));
            const temp = parseFloat(val);
            const alertBar = document.getElementById('live-alert-bar');
            if (alertBar) {
                if (temp > 37) {
                    alertBar.classList.remove('hidden');
                    document.getElementById('alert-message').textContent = `⚠ CRITICAL: High Thermal Load at Shop (${fmt(val,1)}°C)`;
                } else { alertBar.classList.add('hidden'); }
            }
        });

        fetchEntity(entities.shop_total_ah,  (v) => setText('det-shop-ah', fmt(v, 1)));
        fetchEntity(entities.shop_backup,     (v) => setText('det-shop-backup', v));
        fetchEntity(entities.shop_to_full,    (v) => setText('det-shop-charge', v));

        // SHOP PACKS
        fetchEntity(entities.shop_p1_soc,  (v) => { setText(['det-shop-p1-soc','ov-shop-p1-soc'], fmt(v,0)+'%'); setBar('p1-soc-bar', parseFloat(v)||0); });
        fetchEntity(entities.shop_p1_amps, (v) => setText('det-shop-p1-amps', fmt(v,1)+'A'));
        fetchEntity(entities.shop_p1_link, (v) => updateLinkUI('det-shop-p1-link', v));
        fetchEntity(entities.shop_p1_delta,(v) => setText('det-shop-p1-delta', fmt(v,3)+'V'));
        fetchEntity(entities.shop_p1_sw_c, (v) => updateSwitchUI('det-shop-p1-sw-c', v));
        fetchEntity(entities.shop_p1_sw_d, (v) => updateSwitchUI('det-shop-p1-sw-d', v));

        fetchEntity(entities.shop_p2_soc,  (v) => { setText(['det-shop-p2-soc','ov-shop-p2-soc'], fmt(v,0)+'%'); setBar('p2-soc-bar', parseFloat(v)||0); });
        fetchEntity(entities.shop_p2_amps, (v) => setText('det-shop-p2-amps', fmt(v,1)+'A'));
        fetchEntity(entities.shop_p2_link, (v) => updateLinkUI('det-shop-p2-link', v));
        fetchEntity(entities.shop_p2_delta,(v) => setText('det-shop-p2-delta', fmt(v,3)+'V'));
        fetchEntity(entities.shop_p2_sw_c, (v) => updateSwitchUI('det-shop-p2-sw-c', v));
        fetchEntity(entities.shop_p2_sw_d, (v) => updateSwitchUI('det-shop-p2-sw-d', v));

        // SHOP PACKS - extra JK BMS detail
        fetchEntity(entities.shop_p1_v,      (v) => setText('det-shop-p1-v', fmt(v,2)+'V'));
        fetchEntity(entities.shop_p1_pwr,    (v) => setText('det-shop-p1-pwr', fmt(v,0)+'W'));
        fetchEntity(entities.shop_p1_soh,    (v) => setText('det-shop-p1-soh', fmt(v,0)+'%'));
        fetchEntity(entities.shop_p1_cyc,    (v) => setText('det-shop-p1-cyc', fmt(v,0)));
        fetchEntity(entities.shop_p1_design, (v) => setText('det-shop-p1-design', fmt(v,0)));
        fetchEntity(entities.shop_p1_remain, (v) => setText('det-shop-p1-remain', fmt(v,0)));
        fetchEntity(entities.shop_p1_t1,     (v) => setText('det-shop-p1-t1', fmt(v,1)+'°'));
        fetchEntity(entities.shop_p1_t2,     (v) => setText('det-shop-p1-t2', fmt(v,1)+'°'));
        fetchEntity(entities.shop_p1_tmos,   (v) => setText('det-shop-p1-tmos', fmt(v,1)+'°'));
        fetchEntity(entities.shop_p1_cavg,   (v) => setText('det-shop-p1-cavg', fmt(v,3)+'V'));
        fetchEntity(entities.shop_p1_chigh,  (v) => setText('det-shop-p1-chigh', fmt(v,3)));
        fetchEntity(entities.shop_p1_clow,   (v) => setText('det-shop-p1-clow', fmt(v,3)));

        fetchEntity(entities.shop_p2_v,      (v) => setText('det-shop-p2-v', fmt(v,2)+'V'));
        fetchEntity(entities.shop_p2_pwr,    (v) => setText('det-shop-p2-pwr', fmt(v,0)+'W'));
        fetchEntity(entities.shop_p2_soh,    (v) => setText('det-shop-p2-soh', fmt(v,0)+'%'));
        fetchEntity(entities.shop_p2_cyc,    (v) => setText('det-shop-p2-cyc', fmt(v,0)));
        fetchEntity(entities.shop_p2_design, (v) => setText('det-shop-p2-design', fmt(v,0)));
        fetchEntity(entities.shop_p2_remain, (v) => setText('det-shop-p2-remain', fmt(v,0)));
        fetchEntity(entities.shop_p2_t1,     (v) => setText('det-shop-p2-t1', fmt(v,1)+'°'));
        fetchEntity(entities.shop_p2_t2,     (v) => setText('det-shop-p2-t2', fmt(v,1)+'°'));
        fetchEntity(entities.shop_p2_tmos,   (v) => setText('det-shop-p2-tmos', fmt(v,1)+'°'));
        fetchEntity(entities.shop_p2_cavg,   (v) => setText('det-shop-p2-cavg', fmt(v,3)+'V'));
        fetchEntity(entities.shop_p2_chigh,  (v) => setText('det-shop-p2-chigh', fmt(v,3)));
        fetchEntity(entities.shop_p2_clow,   (v) => setText('det-shop-p2-clow', fmt(v,3)));

        // Individual cells (16 per pack)
        fetchPackCells('jk_bms_1_100ah_bms1', 'det-shop-p1-cells');
        fetchPackCells('jk_bms_2_100ah_bms2', 'det-shop-p2-cells');

        // Overall 200Ah bank
        fetchEntity(entities.shop_bank_soc,    (v) => setText('det-shop-bank-soc', fmt(v,0)+'%'));
        fetchEntity(entities.shop_bank_v,      (v) => setText('det-shop-bank-v', fmt(v,2)+'V'));
        fetchEntity(entities.shop_bank_amps,   (v) => setText('det-shop-bank-amps', fmt(v,1)+'A'));
        fetchEntity(entities.shop_bank_pwr,    (v) => {
            setText('det-shop-bank-pwr', fmt(v,0)+'W');
            const dirEl = document.getElementById('det-shop-bank-pwr-dir');
            if (dirEl) {
                const p = parseFloat(v) || 0;
                dirEl.textContent = p < -5 ? 'Discharging' : p > 5 ? 'Charging' : 'Idle';
                dirEl.className = 'text-[8px] font-bold uppercase tracking-wide mt-0.5 ' +
                    (p < -5 ? 'text-orange-400' : p > 5 ? 'text-green-400' : 'text-gray-600');
            }
        });
        fetchEntity(entities.shop_bank_soh,    (v) => setText('det-shop-bank-soh', fmt(v,0)+'%'));
        fetchEntity(entities.shop_bank_design, (v) => setText('det-shop-bank-design', fmt(v,0)+'Ah'));
        fetchEntity(entities.shop_bank_remain, (v) => setText('det-shop-bank-remain', fmt(v,0)+'Ah'));
        fetchEntity(entities.shop_bank_t1,     (v) => setText('det-shop-bank-temp', fmt(v,1)+'°'));

        runShopSystemCheck();

        // HOME
        fetchEntity(entities.home_pv, (val) => {
            homeLoadVal = parseFloat(val) || 0;
            setText(['ov-home-pv', 'det-home-pv', 'ov-home-val-solar'], fmt(val,0)+'W');
            setBar('home-pv-bar', Math.min(100, homeLoadVal / 15));
            setBar('ov-home-pv-bar', Math.min(100, homeLoadVal / 30));
            toggleFlow('ov-home-flow-solar', homeLoadVal > 10);
            combinedTotals.homePv = homeLoadVal;
            updateCombinedTotals();
        });

        fetchEntity(entities.home_soc, (val) => {
            const soc = parseFloat(val) || 0;
            setText(['ov-home-soc', 'det-home-soc', 'ov-home-val-battery', 'stat-home-soc'], fmt(val,1));
            setBar('home-soc-bar', soc); setBar('home-soc-gauge-bar', soc);
            // Canvas overview gauge
            drawOvGauge('ov-canvas-home', soc);
            const ch = socColor(soc);
            const hsb = document.getElementById('home-soc-bar');
            if (hsb) hsb.style.background = ch.bar;
        });

        fetchEntity(entities.home_load, (val) => {
            const pwr = parseFloat(val) || 0;
            setText(['ov-home-load', 'det-home-inv', 'ov-home-val-load'], fmt(val,0)+'W');
            setBar('ov-home-load-bar', Math.min(100, pwr / 30));
            toggleFlow('ov-home-flow-home', pwr > 10);
            combinedTotals.homeLoad = pwr;
            updateCombinedTotals();
        });

        fetchEntity(entities.home_v,    (v) => setText(['det-home-v','ov-home-v-mini'], fmt(v,1)));
        fetchEntity(entities.home_amps, (v) => {
            setText(['ov-home-amps','det-home-amps'], fmt(v,1));
            const homeA = parseFloat(v) || 0;
            const hb = document.getElementById('ov-home-charge-badge');
            if (hb) {
                if (homeA < -0.5) { hb.textContent = 'DISCHARGING'; hb.className = 'mt-2.5 px-3 py-1 rounded-full text-[9px] font-bold border charge-badge-discharging'; }
                else if (homeA > 0.5) { hb.textContent = 'CHARGING'; hb.className = 'mt-2.5 px-3 py-1 rounded-full text-[9px] font-bold border charge-badge-charging'; }
                else { hb.textContent = 'STANDBY'; hb.className = 'mt-2.5 px-3 py-1 rounded-full text-[9px] font-bold border charge-badge-standby'; }
            }
        });
        fetchEntity(entities.home_p,    (v) => setText(['det-home-p','ov-home-bms-pwr'], fmt(v,0)));
        fetchEntity(entities.home_grid, (v) => {
            const gv = Math.abs(parseFloat(v)||0).toFixed(0)+'W';
            setText(['ov-home-val-grid','det-home-grid','ov-home-grid-mini'], gv);
            toggleFlow('ov-home-flow-grid', Math.abs(parseFloat(v)||0) > 10, (parseFloat(v)||0) < 0);
        });
        fetchEntity(entities.home_temp, (v) => setText(['ov-home-temp','det-home-temp'], fmt(v,1)));
        fetchEntity(entities.home_delta,(v) => setText('det-home-delta', ((parseFloat(v)||0)*1000).toFixed(0)));

        fetchEntity(entities.shop_grid, (val) => {
            const g = parseFloat(val)||0;
            setText('ov-shop-val-grid', Math.abs(g).toFixed(0)+'W');
            toggleFlow('ov-shop-flow-grid', Math.abs(g) > 10, g < 0);
        });
        fetchEntity(entities.shop_batt_pwr, (val) => toggleFlow('ov-shop-flow-battery', Math.abs(parseFloat(val)||0) > 10, (parseFloat(val)||0) > 0));
        fetchEntity(entities.home_batt_pwr, (val) => toggleFlow('ov-home-flow-battery', Math.abs(parseFloat(val)||0) > 10, (parseFloat(val)||0) > 0));
    }

    // Draw placeholder gauges immediately (before first fetch)
    drawOvGauge('ov-canvas-shop', 0);
    drawOvGauge('ov-canvas-home', 0);
    updateDashboard();

    // ── Charts with gradients ──
    function makeGradient(ctx, color1, color2) {
        const g = ctx.createLinearGradient(0, 0, 0, 180);
        g.addColorStop(0, color1); g.addColorStop(1, color2); return g;
    }

    const chartDefaults = {
        responsive: true,
        plugins: { legend: { display: false }, tooltip: {
            backgroundColor: 'rgba(15,23,42,0.95)',
            borderColor: 'rgba(255,255,255,0.08)', borderWidth: 1,
            titleColor: '#9ca3af', bodyColor: '#f3f4f6',
            padding: 10, cornerRadius: 10,
        }},
        scales: {
            y: { grid: { color: 'rgba(255,255,255,0.04)', drawBorder: false }, ticks: { color: '#4b5563', font: { size: 10 } } },
            x: { grid: { display: false }, ticks: { color: '#4b5563', font: { size: 10 } } }
        },
        elements: { point: { radius: 3, hoverRadius: 6, borderWidth: 0 } },
        animation: { duration: 800, easing: 'easeInOutQuart' }
    };

    function makeChart(id, labels, data, color1, color2) {
        const el = document.getElementById(id);
        if (!el) return;
        const ctx = el.getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels,
                datasets: [{ data, borderColor: color1, backgroundColor: makeGradient(ctx, color1 + '30', color1 + '00'),
                    fill: true, tension: 0.4 }]
            },
            options: { ...chartDefaults }
        });
    }

    const labels24 = ['12am','2am','4am','6am','8am','10am','12pm','2pm','4pm','6pm','8pm','10pm'];
    makeChart('shop-solar-chart', labels24, [0,0,0,0,120,450,820,900,600,200,0,0],  '#f97316', '#f9731600');
    makeChart('shop-soc-chart',   labels24, [85,80,76,73,70,80,92,100,100,95,90,87], '#22c55e', '#22c55e00');
    makeChart('home-solar-chart', labels24, [0,0,0,0,200,800,1400,1500,1000,400,0,0],'#f97316','#f9731600');
    makeChart('home-soc-chart',   labels24, [90,86,82,78,75,82,94,100,100,98,96,93], '#a855f7', '#a855f700');
</script>

<?php include 'includes/footer.php'; ?>
