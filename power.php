<?php
require_once 'includes/db.php';
$pageTitle = "Live Power Station | Advanced Energy Command Center";
include 'includes/header.php';
?>

<style>
    /* Power Flow Styles */
    .flow-dot { stroke-dasharray: 4, 10; stroke: #fff; stroke-width: 2; fill: none; animation: dash 5s linear infinite; opacity: 0; transition: opacity 0.5s; }
    .flow-active { opacity: 1; }
    @keyframes dash { to { stroke-dashoffset: -100; } }
    .flow-reverse { animation-direction: reverse; }
    .node-circle { fill: #0b0f1a; stroke-width: 2; transition: all 0.5s; cursor: pointer; }
    .node-circle:hover { stroke-width: 4; filter: brightness(1.2); }
    .node-label { font-size: 8px; font-weight: 800; fill: #9ca3af; text-anchor: middle; pointer-events: none; }
    .node-value { font-size: 11px; font-weight: 900; fill: #fff; text-anchor: middle; pointer-events: none; }
    .flow-container { background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.05); border-radius: 2rem; padding: 1.5rem; }
    
    /* Pulsing Glow Animations */
    @keyframes glow-orange { 0%, 100% { filter: drop-shadow(0 0 5px rgba(249, 115, 22, 0.4)); } 50% { filter: drop-shadow(0 0 15px rgba(249, 115, 22, 0.8)); } }
    @keyframes glow-green { 0%, 100% { filter: drop-shadow(0 0 5px rgba(34, 197, 94, 0.4)); } 50% { filter: drop-shadow(0 0 15px rgba(34, 197, 94, 0.8)); } }
    @keyframes glow-red { 0%, 100% { filter: drop-shadow(0 0 5px rgba(239, 68, 68, 0.4)); } 50% { filter: drop-shadow(0 0 15px rgba(239, 68, 68, 0.8)); } }
    @keyframes glow-blue { 0%, 100% { filter: drop-shadow(0 0 5px rgba(59, 130, 246, 0.4)); } 50% { filter: drop-shadow(0 0 15px rgba(59, 130, 246, 0.8)); } }

    .glow-orange { animation: glow-orange 3s ease-in-out infinite; }
    .glow-green { animation: glow-green 3s ease-in-out infinite; }
    .glow-red { animation: glow-red 3s ease-in-out infinite; }
    .glow-blue { animation: glow-blue 3s ease-in-out infinite; }
    
    /* Animations */
    @keyframes pulse-red { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
    .status-discharging { animation: pulse-red 2s infinite; color: #ef4444; }
    @keyframes spin-slow { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
    .status-charging { animation: spin-slow 4s linear infinite; color: #22c55e; }

    /* Gauge styles */
    .gauge-svg { transform: rotate(-90deg); }
    .gauge-bg { stroke: #1f2937; fill: none; }
    .gauge-fill { stroke-linecap: round; fill: none; transition: stroke-dashoffset 1s ease-out; }

    /* Tab styles */
    .tab-btn.active { background: #3b82f6; color: white; box-shadow: 0 0 20px rgba(59, 130, 246, 0.4); border-color: transparent; }
</style>

<section class="min-h-screen pt-24 md:pt-32 pb-24 px-4 md:px-6 bg-[#030712]">
    <div class="container mx-auto">
        <div class="max-w-7xl mx-auto" data-aos="fade-up">
            
            <!-- HEADER & TAB NAVIGATION -->
            <div class="flex flex-col lg:flex-row justify-between items-center mb-12 gap-8">
                <div class="text-center lg:text-left">
                    <h1 class="text-4xl md:text-5xl font-extrabold mb-4"><span class="text-gradient">Live Power Station</span></h1>
                    <p class="text-gray-400 text-base md:text-lg italic uppercase tracking-widest">Master Command Center</p>
                </div>
                <div class="flex flex-wrap justify-center items-center gap-2 p-1.5 bg-white/5 rounded-2xl border border-white/5 w-full md:w-auto">
                    <button onclick="switchTab('overview')" id="tab-overview" class="tab-btn active px-4 md:px-6 py-3 rounded-xl text-xs md:sm font-bold transition flex items-center gap-2 border border-transparent">
                        <i class="fa-solid fa-chart-line"></i> Overview
                    </button>
                    <button onclick="switchTab('shop')" id="tab-shop" class="tab-btn px-4 md:px-6 py-3 rounded-xl text-xs md:sm font-bold transition flex items-center gap-2 border border-transparent text-gray-400 hover:text-white">
                        <i class="fa-solid fa-shop"></i> Shop Details
                    </button>
                    <button onclick="switchTab('home')" id="tab-home" class="tab-btn px-4 md:px-6 py-3 rounded-xl text-xs md:sm font-bold transition flex items-center gap-2 border border-transparent text-gray-400 hover:text-white">
                        <i class="fa-solid fa-house"></i> Home Details
                    </button>
                </div>
            </div>

            <!-- LIVE ALERT BAR -->
            <div id="live-alert-bar" class="hidden mb-8 p-4 bg-red-600/20 border border-red-600/50 rounded-2xl animate-pulse">
                <p class="text-red-500 font-bold text-center flex items-center justify-center gap-3">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <span id="alert-message">CRITICAL ALERT</span>
                </p>
            </div>

            <!-- VIEW 1: OVERVIEW -->
            <div id="view-overview" class="view-content space-y-12">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Shop Summary -->
                    <div class="glass p-6 md:p-8 rounded-[2rem] border-blue-500/10 relative overflow-hidden">
                        <div class="flex justify-between items-center mb-8">
                            <h3 class="text-blue-400 font-black italic tracking-widest uppercase text-sm">Site A: Shop Power</h3>
                            <span class="px-2 py-1 bg-green-500/10 text-green-500 rounded-full text-[8px] font-bold border border-green-500/20 animate-pulse">LIVE LINK</span>
                        </div>
                        <div class="flex justify-center mb-8">
                            <svg width="240" height="200" viewBox="0 0 300 240" class="max-w-full h-auto">
                                <path d="M150,30 L150,120" class="stroke-gray-800 stroke-[2] fill-none" />
                                <path id="ov-shop-flow-solar" d="M150,30 L150,120" class="flow-dot stroke-orange-500" />
                                <path d="M40,120 L150,120" class="stroke-gray-800 stroke-[2] fill-none" />
                                <path id="ov-shop-flow-grid" d="M40,120 L150,120" class="flow-dot stroke-blue-500" />
                                <path d="M150,120 L150,210" class="stroke-gray-800 stroke-[2] fill-none" />
                                <path id="ov-shop-flow-battery" d="M150,120 L150,210" class="flow-dot stroke-green-500" />
                                <path d="M150,120 L260,120" class="stroke-gray-800 stroke-[2] fill-none" />
                                <path id="ov-shop-flow-home" d="M150,120 L260,120" class="flow-dot stroke-red-500" />
                                
                                <circle cx="150" cy="30" r="28" class="node-circle stroke-orange-500" />
                                <text x="150" y="28" class="node-label">SOLAR</text>
                                <text x="150" y="42" id="ov-shop-val-solar" class="node-value">--W</text>
                                <circle cx="40" cy="120" r="28" class="node-circle stroke-blue-500" />
                                <text x="40" y="118" class="node-label">GRID</text>
                                <text x="40" y="132" id="ov-shop-val-grid" class="node-value">--W</text>
                                <circle cx="150" cy="210" r="28" class="node-circle stroke-green-500" />
                                <text x="150" y="208" class="node-label">STORAGE</text>
                                <text x="150" y="222" id="ov-shop-val-battery" class="node-value">--%</text>
                                <circle cx="260" cy="120" r="28" class="node-circle stroke-red-500" />
                                <text x="260" y="118" class="node-label">LOAD</text>
                                <text x="260" y="132" id="ov-shop-val-load" class="node-value">--W</text>
                                <circle cx="150" cy="120" r="15" class="node-circle stroke-white/10" />
                            </svg>
                        </div>
                        <div class="grid grid-cols-2 md:grid-cols-5 gap-2">
                            <div class="bg-white/5 p-3 rounded-xl border border-white/5 text-center">
                                <p class="text-[7px] text-gray-500 uppercase font-bold mb-1">Solar PV</p>
                                <p class="text-sm font-black text-orange-500"><span id="ov-shop-pv">--</span>W</p>
                            </div>
                            <div class="bg-white/5 p-3 rounded-xl border border-white/5 text-center">
                                <p class="text-[7px] text-gray-500 uppercase font-bold mb-1">Load</p>
                                <p class="text-sm font-black text-red-500"><span id="ov-shop-load">--</span>W</p>
                            </div>
                            <div class="bg-white/5 p-3 rounded-xl border border-white/5 text-center">
                                <p class="text-[7px] text-gray-500 uppercase font-bold mb-1">Total SOC</p>
                                <p class="text-sm font-black text-green-500"><span id="ov-shop-soc">--</span>%</p>
                            </div>
                            <div class="bg-white/5 p-3 rounded-xl border border-white/5 text-center">
                                <p class="text-[7px] text-gray-500 uppercase font-bold mb-1">Busbar</p>
                                <p class="text-sm font-black text-blue-400"><span id="ov-shop-amps">--</span>A</p>
                            </div>
                            <div class="bg-white/5 p-3 rounded-xl border border-white/5 text-center col-span-2 md:col-span-1">
                                <p class="text-[7px] text-gray-500 uppercase font-bold mb-1">Temp</p>
                                <p class="text-sm font-black text-blue-500"><span id="ov-shop-temp">--</span>°C</p>
                            </div>
                        </div>
                    </div>
                    <!-- Home Summary -->
                    <div class="glass p-6 md:p-8 rounded-[2rem] border-purple-500/10 relative overflow-hidden">
                        <div class="flex justify-between items-center mb-8">
                            <h3 class="text-purple-400 font-black italic tracking-widest uppercase text-sm">Site B: Home Power</h3>
                            <span class="px-2 py-1 bg-blue-500/10 text-blue-500 rounded-full text-[8px] font-bold border border-blue-500/20 animate-pulse">LIVE LINK</span>
                        </div>
                        <div class="flex justify-center mb-8">
                            <svg width="240" height="200" viewBox="0 0 300 240" class="max-w-full h-auto">
                                <path d="M150,30 L150,120" class="stroke-gray-800 stroke-[2] fill-none" />
                                <path id="ov-home-flow-solar" d="M150,30 L150,120" class="flow-dot stroke-orange-500" />
                                <path d="M40,120 L150,120" class="stroke-gray-800 stroke-[2] fill-none" />
                                <path id="ov-home-flow-grid" d="M40,120 L150,120" class="flow-dot stroke-blue-500" />
                                <path d="M150,120 L150,210" class="stroke-gray-800 stroke-[2] fill-none" />
                                <path id="ov-home-flow-battery" d="M150,120 L150,210" class="flow-dot stroke-green-500" />
                                <path d="M150,120 L260,120" class="stroke-gray-800 stroke-[2] fill-none" />
                                <path id="ov-home-flow-home" d="M150,120 L260,120" class="flow-dot stroke-red-500" />
                                
                                <circle cx="150" cy="30" r="28" class="node-circle stroke-orange-500" />
                                <text x="150" y="28" class="node-label">SOLAR</text>
                                <text x="150" y="42" id="ov-home-val-solar" class="node-value">--W</text>
                                <circle cx="40" cy="120" r="28" class="node-circle stroke-blue-500" />
                                <text x="40" y="118" class="node-label">GRID</text>
                                <text x="40" y="132" id="ov-home-val-grid" class="node-value">--W</text>
                                <circle cx="150" cy="210" r="28" class="node-circle stroke-green-500" />
                                <text x="150" y="208" class="node-label">STORAGE</text>
                                <text x="150" y="222" id="ov-home-val-battery" class="node-value">--%</text>
                                <circle cx="260" cy="120" r="28" class="node-circle stroke-red-500" />
                                <text x="260" y="118" class="node-label">LOAD</text>
                                <text x="260" y="132" id="ov-home-val-load" class="node-value">--W</text>
                                <circle cx="150" cy="120" r="15" class="node-circle stroke-white/10" />
                            </svg>
                        </div>
                        <div class="grid grid-cols-2 md:grid-cols-5 gap-2">
                            <div class="bg-white/5 p-3 rounded-xl border border-white/5 text-center">
                                <p class="text-[7px] text-gray-500 uppercase font-bold mb-1">House PV</p>
                                <p class="text-sm font-black text-orange-500"><span id="ov-home-pv">--</span>W</p>
                            </div>
                            <div class="bg-white/5 p-3 rounded-xl border border-white/5 text-center">
                                <p class="text-[7px] text-gray-500 uppercase font-bold mb-1">Load</p>
                                <p class="text-sm font-black text-red-500"><span id="ov-home-load">--</span>W</p>
                            </div>
                            <div class="bg-white/5 p-3 rounded-xl border border-white/5 text-center">
                                <p class="text-[7px] text-gray-500 uppercase font-bold mb-1">House SOC</p>
                                <p class="text-sm font-black text-green-500"><span id="ov-home-soc">--</span>%</p>
                            </div>
                            <div class="bg-white/5 p-3 rounded-xl border border-white/5 text-center">
                                <p class="text-[7px] text-gray-500 uppercase font-bold mb-1">Current</p>
                                <p class="text-sm font-black text-blue-400"><span id="ov-home-amps">--</span>A</p>
                            </div>
                            <div class="bg-white/5 p-3 rounded-xl border border-white/5 text-center col-span-2 md:col-span-1">
                                <p class="text-[7px] text-gray-500 uppercase font-bold mb-1">BMS Temp</p>
                                <p class="text-sm font-black text-blue-500"><span id="ov-home-temp">--</span>°C</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- VIEW 2: SHOP DETAILS -->
            <div id="view-shop" class="view-content hidden space-y-8">
                
                <!-- SHOP ANALYTICS CHARTS -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                    <div class="glass p-6 rounded-3xl border-orange-500/10">
                        <h4 class="text-xs font-bold text-gray-500 uppercase mb-6 tracking-widest text-center">Solar Generation (24h)</h4>
                        <canvas id="shop-solar-chart" height="150"></canvas>
                    </div>
                    <div class="glass p-6 rounded-3xl border-green-500/10">
                        <h4 class="text-xs font-bold text-gray-500 uppercase mb-6 tracking-widest text-center">Battery Storage Level (24h)</h4>
                        <canvas id="shop-soc-chart" height="150"></canvas>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Column 1: Master Health -->
                    <div class="lg:col-span-1 space-y-6">
                        <div class="glass p-8 rounded-3xl border-green-500/10 text-center">
                            <p class="text-[10px] font-bold text-gray-500 uppercase mb-6 tracking-widest">Storage Matrix</p>
                            <div class="relative w-48 h-48 mx-auto mb-6">
                                <svg viewBox="0 0 100 100" class="w-full h-full gauge-svg">
                                    <circle cx="50" cy="50" r="45" stroke-width="8" class="gauge-bg" />
                                    <circle id="shop-gauge-circle" cx="50" cy="50" r="45" stroke-width="8" class="gauge-fill stroke-green-500" stroke-dasharray="282.7" stroke-dashoffset="282.7" />
                                </svg>
                                <div class="absolute inset-0 flex flex-col items-center justify-center">
                                    <span class="text-4xl font-black text-white" id="det-shop-soc">--</span>
                                    <span class="text-xs font-bold text-gray-500 uppercase">% SOC</span>
                                </div>
                            </div>
                            <div class="flex justify-between border-t border-white/5 pt-6">
                                <div><p class="text-[8px] text-gray-500 uppercase font-bold mb-1">Sync Amps</p><p class="text-xl font-black text-blue-400"><span id="det-shop-amps">--</span>A</p></div>
                                <div><p class="text-[8px] text-gray-500 uppercase font-bold mb-1">Total Cap</p><p class="text-xl font-black text-white"><span id="det-shop-ah">--</span>Ah</p></div>
                            </div>
                        </div>
                        <div class="glass p-8 rounded-3xl border-purple-500/10">
                            <h4 class="text-xs font-bold text-gray-500 uppercase mb-6 flex items-center gap-2"><i class="fa-solid fa-microchip"></i> System Intel</h4>
                            <div class="space-y-4">
                                <div class="flex justify-between items-center"><span class="text-sm">Status</span><span id="det-shop-status" class="text-[10px] font-black uppercase text-gray-500 italic">Standby</span></div>
                                <div class="flex justify-between items-center"><span class="text-sm text-red-400">Backup Time</span><span id="det-shop-backup" class="text-sm font-black text-white">--</span></div>
                                <div class="flex justify-between items-center"><span class="text-sm text-green-400">To Full</span><span id="det-shop-charge" class="text-sm font-black text-white">--</span></div>
                            </div>
                        </div>
                    </div>

                    <!-- Column 2 & 3: Detailed Pack Monitor -->
                    <div class="lg:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Pack 1 -->
                        <div class="glass p-8 rounded-3xl border-blue-500/5">
                            <div class="flex justify-between items-center mb-8"><h3 class="text-lg font-black text-blue-400 italic">PACK 1 (105Ah)</h3><span id="det-shop-p1-link" class="text-[8px] px-2 py-1 rounded-full bg-gray-800">OFFLINE</span></div>
                            <div class="space-y-6">
                                <div class="flex justify-between items-center border-b border-white/5 pb-4"><span class="text-sm text-gray-400">SOC Level</span><span class="text-xl font-black" id="det-shop-p1-soc">--%</span></div>
                                <div class="flex justify-between items-center border-b border-white/5 pb-4"><span class="text-sm text-gray-400">Current Flow</span><span class="text-xl font-black text-blue-500" id="det-shop-p1-amps">--A</span></div>
                                <div class="flex justify-between items-center border-b border-white/5 pb-4"><span class="text-sm text-gray-400">Cell Drift</span><span class="text-xl font-black" id="det-shop-p1-delta">--V</span></div>
                                <div class="grid grid-cols-2 gap-4 mt-6">
                                    <div class="text-center p-3 bg-white/5 rounded-xl"><p class="text-[8px] text-gray-500 uppercase mb-1">Charge Sw</p><span id="det-shop-p1-sw-c" class="text-[9px] font-bold">--</span></div>
                                    <div class="text-center p-3 bg-white/5 rounded-xl"><p class="text-[8px] text-gray-500 uppercase mb-1">Discharge Sw</p><span id="det-shop-p1-sw-d" class="text-[9px] font-bold">--</span></div>
                                </div>
                            </div>
                        </div>
                        <!-- Pack 2 -->
                        <div class="glass p-8 rounded-3xl border-purple-500/5">
                            <div class="flex justify-between items-center mb-8"><h3 class="text-lg font-black text-purple-400 italic">PACK 2 (100Ah)</h3><span id="det-shop-p2-link" class="text-[8px] px-2 py-1 rounded-full bg-gray-800">OFFLINE</span></div>
                            <div class="space-y-6">
                                <div class="flex justify-between items-center border-b border-white/5 pb-4"><span class="text-sm text-gray-400">SOC Level</span><span class="text-xl font-black" id="det-shop-p2-soc">--%</span></div>
                                <div class="flex justify-between items-center border-b border-white/5 pb-4"><span class="text-sm text-gray-400">Current Flow</span><span class="text-xl font-black text-purple-500" id="det-shop-p2-amps">--A</span></div>
                                <div class="flex justify-between items-center border-b border-white/5 pb-4"><span class="text-sm text-gray-400">Cell Drift</span><span class="text-xl font-black" id="det-shop-p2-delta">--V</span></div>
                                <div class="grid grid-cols-2 gap-4 mt-6">
                                    <div class="text-center p-3 bg-white/5 rounded-xl"><p class="text-[8px] text-gray-500 uppercase mb-1">Charge Sw</p><span id="det-shop-p2-sw-c" class="text-[9px] font-bold">--</span></div>
                                    <div class="text-center p-3 bg-white/5 rounded-xl"><p class="text-[8px] text-gray-500 uppercase mb-1">Discharge Sw</p><span id="det-shop-p2-sw-d" class="text-[9px] font-bold">--</span></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- VIEW 3: HOME DETAILS -->
            <div id="view-home" class="view-content hidden space-y-8">
                
                <!-- HOME ANALYTICS CHARTS -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                    <div class="glass p-6 rounded-3xl border-orange-500/10">
                        <h4 class="text-xs font-bold text-gray-500 uppercase mb-6 tracking-widest text-center">Home PV Generation (24h)</h4>
                        <canvas id="home-solar-chart" height="150"></canvas>
                    </div>
                    <div class="glass p-6 rounded-3xl border-green-500/10">
                        <h4 class="text-xs font-bold text-gray-500 uppercase mb-6 tracking-widest text-center">Home Storage Level (24h)</h4>
                        <canvas id="home-soc-chart" height="150"></canvas>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Column 1: BMS Matrix -->
                    <div class="glass p-10 rounded-3xl border-purple-500/10 flex flex-col justify-center">
                        <h3 class="text-sm font-bold text-gray-500 uppercase mb-12 tracking-widest text-center">BMS Primary Matrix</h3>
                        <div class="space-y-12">
                            <div class="text-center">
                                <p class="text-5xl font-black text-white mb-2"><span id="det-home-soc">--</span>%</p>
                                <p class="text-[10px] text-purple-500 font-bold uppercase tracking-[0.3em]">State of Charge</p>
                            </div>
                            <div class="grid grid-cols-2 gap-8 border-t border-white/5 pt-12">
                                <div class="text-center border-r border-white/5">
                                    <p class="text-2xl font-black text-white mb-1"><span id="det-home-v">--</span>V</p>
                                    <p class="text-[8px] text-gray-500 uppercase">BMS Voltage</p>
                                </div>
                                <div class="text-center">
                                    <p class="text-2xl font-black text-white mb-1"><span id="det-home-amps">--</span>A</p>
                                    <p class="text-[8px] text-gray-500 uppercase">BMS Current</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Column 2 & 3: Inverter & Environment -->
                    <div class="lg:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="glass p-8 rounded-3xl border-orange-500/10">
                            <h4 class="text-sm font-bold text-orange-500 uppercase mb-8 italic tracking-tighter">Inverter Telemetry</h4>
                            <div class="space-y-6">
                                <div class="flex justify-between items-center"><span class="text-sm text-gray-400">Direct PV Power</span><span class="text-xl font-black text-white"><span id="det-home-pv">--</span>W</span></div>
                                <div class="flex justify-between items-center"><span class="text-sm text-gray-400">Grid Consumption</span><span class="text-xl font-black text-white"><span id="det-home-grid">--</span>W</span></div>
                                <div class="flex justify-between items-center border-t border-white/5 pt-6 mt-6"><span class="text-lg font-bold text-white uppercase italic">House Load</span><span class="text-3xl font-black text-red-500"><span id="det-home-inv">--</span>W</span></div>
                            </div>
                        </div>
                        <div class="glass p-8 rounded-3xl border-blue-500/10 flex flex-col justify-between">
                            <div>
                                <h4 class="text-sm font-bold text-blue-500 uppercase mb-8 italic tracking-tighter">Thermal & Logic</h4>
                                <div class="space-y-6">
                                    <div class="flex justify-between items-center"><span class="text-sm text-gray-400">BMS Temp 1</span><span class="text-xl font-black text-white"><span id="det-home-temp">--</span>°C</span></div>
                                    <div class="flex justify-between items-center"><span class="text-sm text-gray-400">Cell Delta</span><span class="text-xl font-black text-white"><span id="det-home-delta">--</span>mV</span></div>
                                </div>
                            </div>
                            <div class="mt-8 pt-8 border-t border-white/5 flex gap-4">
                                <span class="px-4 py-2 bg-green-500/5 text-green-500 border border-green-500/20 rounded-xl text-[10px] font-bold flex-1 text-center">BMS LINK ACTIVE</span>
                                <span class="px-4 py-2 bg-blue-500/5 text-blue-500 border border-blue-500/20 rounded-xl text-[10px] font-bold flex-1 text-center">GRID LINK OK</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

<script>
    const entities = {
        // SHOP MASTER
        shop_total_soc: 'sensor.shop_total_soc',
        shop_total_ah: 'sensor.shop_total_capacity_ah',
        shop_total_amps: 'sensor.shop_total_current',
        shop_backup: 'sensor.shop_backup_time_remaining',
        shop_to_full: 'sensor.shop_time_to_full_charge',
        shop_pv: 'sensor.flin_energy_pv_power',
        shop_load: 'sensor.flin_energy_load_power',
        shop_temp: 'sensor.flin_energy_battery_temperature',
        shop_grid: 'sensor.flin_energy_grid_power',
        shop_batt_pwr: 'sensor.flin_energy_battery_power',
        
        // SHOP PACK 1
        shop_p1_soc: 'sensor.shop_battery_pack_one_shop_bms_state_of_charge',
        shop_p1_amps: 'sensor.shop_battery_pack_one_shop_bms_current',
        shop_p1_delta: 'sensor.shop_battery_pack_one_shop_bms_cell_delta',
        shop_p1_link: 'binary_sensor.shop_battery_pack_one_shop_bms_online_status',
        shop_p1_sw_c: 'switch.shop_battery_pack_one_shop_bms_charging_switch',
        shop_p1_sw_d: 'switch.shop_battery_pack_one_shop_bms_discharging_switch',
        
        // SHOP PACK 2
        shop_p2_soc: 'sensor.shop_battery_pack_two_shop_2_bms_state_of_charge',
        shop_p2_amps: 'sensor.shop_battery_pack_two_shop_2_bms_current',
        shop_p2_delta: 'sensor.shop_battery_pack_two_shop_2_bms_cell_delta',
        shop_p2_link: 'binary_sensor.shop_battery_pack_two_shop_2_bms_online_status',
        shop_p2_sw_c: 'switch.shop_battery_pack_two_shop_2_bms_charging_switch',
        shop_p2_sw_d: 'switch.shop_battery_pack_two_shop_2_bms_discharging_switch',
        
        // HOME SYSTEM
        home_pv: 'sensor.q004719472515009ad05_direct_pv_power',
        home_soc: 'sensor.jkbms_home_bms_state_of_charge',
        home_v: 'sensor.jkbms_home_bms_battery_voltage',
        home_p: 'sensor.jkbms_home_bms_power',
        home_load: 'sensor.q004719472515009ad05_direct_inverter_out_power',
        home_temp: 'sensor.jkbms_home_bms_temperature_1',
        home_delta: 'sensor.jkbms_home_bms_cell_delta',
        home_grid: 'sensor.q004719472515009ad05_direct_apparent_power',
        home_batt_pwr: 'sensor.jkbms_home_bms_power',
        home_amps: 'sensor.jkbms_home_bms_current'
    };

    function switchTab(tabId) {
        document.querySelectorAll('.view-content').forEach(el => el.classList.add('hidden'));
        document.querySelectorAll('.tab-btn').forEach(el => {
            el.classList.remove('active', 'text-white');
            el.classList.add('text-gray-400');
        });
        
        document.getElementById('view-' + tabId).classList.remove('hidden');
        const activeBtn = document.getElementById('tab-' + tabId);
        activeBtn.classList.add('active', 'text-white');
        activeBtn.classList.remove('text-gray-400');
    }

    function formatVal(val, decimals = 1) {
        if (val === undefined || val === null || isNaN(parseFloat(val))) return "--";
        return parseFloat(val).toFixed(decimals);
    }

    // Helper to safely update text content
    function safeUpdateText(ids, value) {
        if (!Array.isArray(ids)) ids = [ids];
        ids.forEach(id => {
            const el = document.getElementById(id);
            if (el) el.textContent = value;
        });
    }

    async function updateDashboard() {
        const now = new Date();
        const lastUpdatedEl = document.getElementById('last-updated');
        if (lastUpdatedEl) lastUpdatedEl.textContent = now.toLocaleTimeString();

        // 1. SHOP MASTER LOGIC
        fetchEntity(entities.shop_pv, null, 'state', (val) => {
            const v = formatVal(val, 0);
            safeUpdateText(['ov-shop-pv', 'ov-shop-val-solar'], v + 'W');
            toggleFlow('ov-shop-flow-solar', parseFloat(val) > 10);
        });

        fetchEntity(entities.shop_total_soc, null, 'state', (val) => {
            const v = formatVal(val, 1);
            safeUpdateText(['ov-shop-soc', 'det-shop-soc', 'ov-shop-val-battery'], v);
            const gauge = document.getElementById('shop-gauge-circle');
            if (gauge) {
                const offset = 282.7 - (282.7 * (parseFloat(val) / 100));
                gauge.style.strokeDashoffset = offset;
            }
        });

        fetchEntity(entities.shop_total_amps, null, 'state', (val) => {
            const a = parseFloat(val);
            const formatted = formatVal(val, 1);
            safeUpdateText(['det-shop-amps', 'ov-shop-amps'], formatted);
            
            const statusText = document.getElementById('det-shop-status');
            if (statusText) {
                if(a < -0.5) { statusText.textContent = "Discharging"; statusText.className = "text-[10px] font-black uppercase text-red-500 italic"; }
                else if(a > 0.5) { statusText.textContent = "Charging"; statusText.className = "text-[10px] font-black uppercase text-green-500 italic"; }
                else { statusText.textContent = "Standby"; statusText.className = "text-[10px] font-black uppercase text-gray-500 italic"; }
            }
        });

        fetchEntity(entities.shop_load, null, 'state', (val) => {
            const v = formatVal(val, 0);
            safeUpdateText(['ov-shop-load', 'ov-shop-val-load'], v + 'W');
            toggleFlow('ov-shop-flow-home', parseFloat(val) > 10);
        });

        fetchEntity(entities.shop_temp, 'ov-shop-temp', 'state', (val) => safeUpdateText(['ov-shop-temp'], formatVal(val, 1)));
        fetchEntity(entities.shop_total_ah, 'det-shop-ah', 'state', (val) => safeUpdateText('det-shop-ah', formatVal(val, 1)));
        fetchEntity(entities.shop_backup, 'det-shop-backup', 'state', (val) => safeUpdateText('det-shop-backup', val));
        fetchEntity(entities.shop_to_full, 'det-shop-charge', 'state', (val) => safeUpdateText('det-shop-charge', val));

        // 2. SHOP PACK 1
        fetchEntity(entities.shop_p1_soc, 'det-shop-p1-soc', 'state', (val) => safeUpdateText('det-shop-p1-soc', formatVal(val, 0) + '%'));
        fetchEntity(entities.shop_p1_amps, 'det-shop-p1-amps', 'state', (val) => safeUpdateText('det-shop-p1-amps', formatVal(val, 1) + 'A'));
        fetchEntity(entities.shop_p1_link, null, 'state', (val) => updateLinkUI('det-shop-p1-link', val));
        fetchEntity(entities.shop_p1_delta, 'det-shop-p1-delta', 'state', (val) => safeUpdateText('det-shop-p1-delta', formatVal(val, 3) + 'V'));
        fetchEntity(entities.shop_p1_sw_c, 'det-shop-p1-sw-c', 'state', (val) => updateSwitchUI('det-shop-p1-sw-c', val));
        fetchEntity(entities.shop_p1_sw_d, 'det-shop-p1-sw-d', 'state', (val) => updateSwitchUI('det-shop-p1-sw-d', val));

        // 3. SHOP PACK 2
        fetchEntity(entities.shop_p2_soc, 'det-shop-p2-soc', 'state', (val) => safeUpdateText('det-shop-p2-soc', formatVal(val, 0) + '%'));
        fetchEntity(entities.shop_p2_amps, 'det-shop-p2-amps', 'state', (val) => safeUpdateText('det-shop-p2-amps', formatVal(val, 1) + 'A'));
        fetchEntity(entities.shop_p2_link, null, 'state', (val) => updateLinkUI('det-shop-p2-link', val));
        fetchEntity(entities.shop_p2_delta, 'det-shop-p2-delta', 'state', (val) => safeUpdateText('det-shop-p2-delta', formatVal(val, 3) + 'V'));
        fetchEntity(entities.shop_p2_sw_c, 'det-shop-p2-sw-c', 'state', (val) => updateSwitchUI('det-shop-p2-sw-c', val));
        fetchEntity(entities.shop_p2_sw_d, 'det-shop-p2-sw-d', 'state', (val) => updateSwitchUI('det-shop-p2-sw-d', val));

        // 4. HOME SYSTEM LOGIC
        fetchEntity(entities.home_pv, null, 'state', (val) => {
            const v = formatVal(val, 0);
            safeUpdateText(['ov-home-pv', 'det-home-pv', 'ov-home-val-solar'], v + 'W');
            toggleFlow('ov-home-flow-solar', parseFloat(val) > 10);
        });

        fetchEntity(entities.home_soc, null, 'state', (val) => {
            const v = formatVal(val, 1);
            safeUpdateText(['ov-home-soc', 'det-home-soc', 'ov-home-val-battery'], v + '%');
        });

        fetchEntity(entities.home_load, null, 'state', (val) => {
            const v = formatVal(val, 0);
            safeUpdateText(['ov-home-load', 'det-home-inv', 'ov-home-val-load'], v + 'W');
            toggleFlow('ov-home-flow-home', parseFloat(val) > 10);
        });

        fetchEntity(entities.home_v, 'det-home-v', 'state', (val) => safeUpdateText('det-home-v', formatVal(val, 1)));
        fetchEntity(entities.home_amps, 'ov-home-amps', 'state', (val) => safeUpdateText('ov-home-amps', formatVal(val, 1)));
        fetchEntity(entities.home_p, 'det-home-p', 'state', (val) => safeUpdateText('det-home-p', formatVal(val, 0)));
        fetchEntity(entities.home_grid, 'det-home-grid', 'state', (val) => safeUpdateText('det-home-grid', formatVal(val, 0)));
        fetchEntity(entities.shop_temp, null, 'state', (val) => {
            const temp = parseFloat(val);
            safeUpdateText(['ov-shop-temp', 'shop-temp'], formatVal(val, 1));

            // --- LIVE ALERT LOGIC ---
            const alertBar = document.getElementById('live-alert-bar');
            const alertMsg = document.getElementById('alert-message');
            if (temp > 37) {
                alertBar.classList.remove('hidden');
                alertMsg.textContent = `⚠️ CRITICAL: High Thermal Load at Shop (${formatVal(val, 1)}°C)`;
            } else {
                alertBar.classList.add('hidden');
            }
        });
        fetchEntity(entities.home_delta, 'det-home-delta', 'state', (val) => safeUpdateText('det-home-delta', (parseFloat(val) * 1000).toFixed(0)));

        // 5. GLOBAL FLOWS
        fetchEntity(entities.shop_grid, null, 'state', (val) => {
            const g = parseFloat(val);
            safeUpdateText(['ov-shop-val-grid'], Math.abs(g).toFixed(0) + 'W');
            toggleFlow('ov-shop-flow-grid', Math.abs(g) > 10, g < 0);
        });

        fetchEntity(entities.home_grid, null, 'state', (val) => {
            const g = parseFloat(val);
            safeUpdateText(['ov-home-val-grid'], Math.abs(g).toFixed(0) + 'W');
            toggleFlow('ov-home-flow-grid', Math.abs(g) > 10, g < 0);
        });

        fetchEntity(entities.shop_batt_pwr, null, 'state', (val) => {
            toggleFlow('ov-shop-flow-battery', Math.abs(parseFloat(val)) > 10, parseFloat(val) > 0);
        });

        fetchEntity(entities.home_batt_pwr, null, 'state', (val) => {
            toggleFlow('ov-home-flow-battery', Math.abs(parseFloat(val)) > 10, parseFloat(val) > 0);
        });
    }

    function updateSwitchUI(id, state) {
        const el = document.getElementById(id);
        if(!el) return;
        if(state === 'on') { el.textContent = "ON"; el.className = "text-green-500 font-black"; }
        else { el.textContent = "OFF"; el.className = "text-red-500 font-black"; }
    }

    function updateLinkUI(id, state) {
        const el = document.getElementById(id);
        if(!el) return;
        if(state === 'on') { el.textContent = "ONLINE"; el.className = "text-[8px] px-2 py-1 rounded-full bg-green-500/10 text-green-500 border border-green-500/20"; }
        else { el.textContent = "OFFLINE"; el.className = "text-[8px] px-2 py-1 rounded-full bg-red-500/10 text-red-500 border border-red-500/20"; }
    }

    function toggleFlow(id, active, reverse = false) {
        const el = document.getElementById(id);
        if(!el) return;
        if(active) {
            el.classList.add('flow-active');
            if(reverse) el.classList.add('flow-reverse');
            else el.classList.remove('flow-reverse');
        } else {
            el.classList.remove('flow-active');
        }
    }

    async function fetchEntity(entityId, elementId, property, callback = null) {
        try {
            const response = await fetch(`api/ha_proxy.php?entity=${entityId}`);
            const data = await response.json();
            if (data.error) return;
            const value = data[property];
            if (callback) callback(value);
            else if (elementId) {
                const el = document.getElementById(elementId);
                if (el) el.textContent = value;
            }
        } catch (error) {}
    }

    updateDashboard();
    setInterval(updateDashboard, 15000);

    // --- CHART.JS ANALYTICS ---
    const ctxSolar = document.getElementById('shop-solar-chart');
    if (ctxSolar) {
        new Chart(ctxSolar, {
            type: 'line',
            data: {
                labels: ['12am', '4am', '8am', '12pm', '4pm', '8pm'],
                datasets: [{
                    label: 'Solar PV (Watts)',
                    data: [0, 0, 150, 800, 450, 0],
                    borderColor: '#f97316',
                    backgroundColor: 'rgba(249, 115, 22, 0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: {
                    y: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#6b7280' } },
                    x: { grid: { display: false }, ticks: { color: '#6b7280' } }
                }
            }
        });
    }

    const ctxSoc = document.getElementById('shop-soc-chart');
    if (ctxSoc) {
        new Chart(ctxSoc, {
            type: 'line',
            data: {
                labels: ['12am', '4am', '8am', '12pm', '4pm', '8pm'],
                datasets: [{
                    label: 'Battery SOC (%)',
                    data: [85, 80, 75, 95, 100, 90],
                    borderColor: '#22c55e',
                    backgroundColor: 'rgba(34, 197, 94, 0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: {
                    y: { min: 0, max: 100, grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#6b7280' } },
                    x: { grid: { display: false }, ticks: { color: '#6b7280' } }
                }
            }
        });
    }
    const ctxHomeSolar = document.getElementById('home-solar-chart');
    if (ctxHomeSolar) {
        new Chart(ctxHomeSolar, {
            type: 'line',
            data: {
                labels: ['12am', '4am', '8am', '12pm', '4pm', '8pm'],
                datasets: [{
                    label: 'House PV (Watts)',
                    data: [0, 0, 200, 1200, 600, 0],
                    borderColor: '#f97316',
                    backgroundColor: 'rgba(249, 115, 22, 0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: {
                    y: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#6b7280' } },
                    x: { grid: { display: false }, ticks: { color: '#6b7280' } }
                }
            }
        });
    }

    const ctxHomeSoc = document.getElementById('home-soc-chart');
    if (ctxHomeSoc) {
        new Chart(ctxHomeSoc, {
            type: 'line',
            data: {
                labels: ['12am', '4am', '8am', '12pm', '4pm', '8pm'],
                datasets: [{
                    label: 'House SOC (%)',
                    data: [90, 85, 80, 92, 100, 95],
                    borderColor: '#22c55e',
                    backgroundColor: 'rgba(34, 197, 94, 0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: {
                    y: { min: 0, max: 100, grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#6b7280' } },
                    x: { grid: { display: false }, ticks: { color: '#6b7280' } }
                }
            }
        });
    }
</script>

<?php include 'includes/footer.php'; ?>
