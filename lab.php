<?php
require_once 'includes/db.php';
$pageTitle = "Advanced IoT Lab | Dual-Site Energy Monitor";
include 'includes/header.php';
?>

<style>
    /* Power Flow Styles */
    .flow-dot { stroke-dasharray: 4, 10; stroke: #fff; stroke-width: 2; fill: none; animation: dash 5s linear infinite; opacity: 0; }
    .flow-active { opacity: 1; }
    @keyframes dash { to { stroke-dashoffset: -100; } }
    .flow-reverse { animation-direction: reverse; }
    .node-circle { fill: #0b0f1a; stroke-width: 2; transition: all 0.5s; }
    .node-label { font-size: 8px; font-weight: 800; fill: #9ca3af; text-anchor: middle; pointer-events: none; }
    .node-value { font-size: 11px; font-weight: 900; fill: #fff; text-anchor: middle; pointer-events: none; }
    .flow-container { background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.05); border-radius: 2rem; padding: 2rem; }
    
    /* Animations */
    @keyframes pulse-red { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
    .status-discharging { animation: pulse-red 2s infinite; color: #ef4444; }
    @keyframes spin-slow { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
    .status-charging { animation: spin-slow 4s linear infinite; color: #22c55e; }

    /* Gauge styles */
    .gauge-svg { transform: rotate(-90deg); }
    .gauge-bg { stroke: #1f2937; fill: none; }
    .gauge-fill { stroke-linecap: round; fill: none; transition: stroke-dashoffset 1s ease-out; }
</style>

<section class="min-h-screen pt-32 pb-24 px-6 bg-[#030712]">
    <div class="container mx-auto">
        <div class="max-w-7xl mx-auto" data-aos="fade-up">
            
            <div class="flex flex-col md:flex-row justify-between items-end mb-12 gap-6">
                <div>
                    <h1 class="text-5xl font-extrabold mb-4"><span class="text-gradient">Professional IoT Lab</span></h1>
                    <p class="text-gray-400 text-lg">Multi-site Live Energy Telemetry & Systems</p>
                </div>
                <div class="flex items-center gap-3 bg-green-500/10 text-green-500 px-4 py-2 rounded-full border border-green-500/20">
                    <div class="w-2 h-2 bg-green-500 rounded-full animate-ping"></div>
                    <span class="text-xs font-bold uppercase tracking-widest">LIVE: <span id="last-updated">Updating...</span></span>
                </div>
            </div>

            <!-- SITE 1: SHOP POWER -->
            <div class="mb-24">
                <div class="flex items-center gap-4 mb-8 text-blue-400">
                    <h2 class="text-3xl font-black flex items-center gap-3 tracking-tighter">
                        <i class="fa-solid fa-shop"></i> SHOP POWER SYSTEM
                    </h2>
                    <div class="h-px flex-1 bg-gradient-to-r from-blue-500/20 to-transparent"></div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                    <!-- Column 1: Power Flow (Visual) -->
                    <div class="lg:col-span-4 flow-container">
                        <h3 class="text-[10px] font-bold text-gray-500 uppercase mb-8 tracking-[0.2em] text-center text-blue-500/50">Site A Energy Flow</h3>
                        <div class="flex justify-center">
                            <svg width="260" height="220" viewBox="0 0 300 240" class="max-w-full h-auto">
                                <path d="M150,30 L150,120" class="stroke-gray-800 stroke-[2] fill-none" />
                                <path id="shop-flow-solar" d="M150,30 L150,120" class="flow-dot stroke-orange-500" />
                                <path d="M40,120 L150,120" class="stroke-gray-800 stroke-[2] fill-none" />
                                <path id="shop-flow-grid" d="M40,120 L150,120" class="flow-dot stroke-blue-500" />
                                <path d="M150,120 L150,210" class="stroke-gray-800 stroke-[2] fill-none" />
                                <path id="shop-flow-battery" d="M150,120 L150,210" class="flow-dot stroke-green-500" />
                                <path d="M150,120 L260,120" class="stroke-gray-800 stroke-[2] fill-none" />
                                <path id="shop-flow-home" d="M150,120 L260,120" class="flow-dot stroke-red-500" />
                                <circle cx="150" cy="30" r="28" class="node-circle stroke-orange-500" />
                                <text x="150" y="28" class="node-label">SOLAR</text>
                                <text x="150" y="42" id="shop-flow-val-solar" class="node-value">--W</text>
                                <circle cx="40" cy="120" r="28" class="node-circle stroke-blue-500" />
                                <text x="40" y="118" class="node-label">GRID</text>
                                <text x="40" y="132" id="shop-flow-val-grid" class="node-value">--W</text>
                                <circle cx="150" cy="210" r="28" class="node-circle stroke-green-500" />
                                <text x="150" y="208" class="node-label">STORAGE</text>
                                <text x="150" y="222" id="shop-flow-val-battery" class="node-value">--%</text>
                                <circle cx="260" cy="120" r="28" class="node-circle stroke-red-500" />
                                <text x="260" y="118" class="node-label">LOAD</text>
                                <text x="260" y="132" id="shop-flow-val-home" class="node-value">--W</text>
                                <circle cx="150" cy="120" r="15" class="node-circle stroke-white/10" />
                            </svg>
                        </div>
                    </div>

                    <!-- Column 2 & 3 Combined Grid -->
                    <div class="lg:col-span-8 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        
                        <!-- Row 1 Cards -->
                        <div class="glass p-6 rounded-3xl border-orange-500/10">
                            <p class="text-[9px] font-bold text-gray-500 uppercase mb-2 tracking-widest">Inverter Production</p>
                            <div class="flex justify-between items-baseline"><p class="text-3xl font-black text-white"><span id="shop-pv">--</span>W</p><i class="fa-solid fa-sun text-orange-500"></i></div>
                            <p class="text-[10px] text-gray-400 font-bold uppercase mt-1">Real-time PV</p>
                        </div>
                        <div class="glass p-6 rounded-3xl border-red-500/10">
                            <p class="text-[9px] font-bold text-gray-500 uppercase mb-2 tracking-widest">Consumption</p>
                            <div class="flex justify-between items-baseline"><p class="text-3xl font-black text-white"><span id="shop-load">--</span>W</p><i class="fa-solid fa-bolt text-red-500"></i></div>
                            <p class="text-[10px] text-gray-400 font-bold uppercase mt-1">Shop Load</p>
                        </div>
                        <div class="glass p-6 rounded-3xl border-blue-500/10">
                            <p class="text-[9px] font-bold text-gray-500 uppercase mb-2 tracking-widest">Thermal Status</p>
                            <div class="flex justify-between items-baseline"><p class="text-3xl font-black text-white"><span id="shop-temp">--</span>°C</p><i class="fa-solid fa-temperature-three-quarters text-blue-500"></i></div>
                            <p class="text-[10px] text-gray-400 font-bold uppercase mt-1">Battery Ambient</p>
                        </div>

                        <!-- Row 2: SOC Master Sync -->
                        <div class="glass p-6 rounded-3xl border-green-500/10 md:col-span-2">
                            <div class="flex justify-between items-start mb-6">
                                <div>
                                    <p class="text-[9px] font-bold text-gray-400 uppercase tracking-widest">Master SOC Sync</p>
                                    <h3 class="text-4xl font-black text-white"><span id="shop-total-soc">--</span>%</h3>
                                </div>
                                <div class="bg-green-500/5 p-3 rounded-2xl border border-green-500/10 text-center">
                                    <p class="text-[8px] font-bold text-gray-500 uppercase">Available</p>
                                    <p class="text-sm font-black text-green-500"><span id="shop-total-ah">--</span> Ah</p>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-6">
                                <div class="bg-white/5 p-3 rounded-2xl border border-white/5">
                                    <div class="flex justify-between items-center mb-1"><span class="text-[9px] font-bold text-gray-400">PACK 1</span><span class="text-[10px] font-black text-white" id="shop-p1-soc">--%</span></div>
                                    <div class="w-full h-1 bg-gray-800 rounded-full overflow-hidden"><div id="shop-p1-bar" class="h-full bg-blue-500" style="width: 0%"></div></div>
                                </div>
                                <div class="bg-white/5 p-3 rounded-2xl border border-white/5">
                                    <div class="flex justify-between items-center mb-1"><span class="text-[9px] font-bold text-gray-400">PACK 2</span><span class="text-[10px] font-black text-white" id="shop-p2-soc">--%</span></div>
                                    <div class="w-full h-1 bg-gray-800 rounded-full overflow-hidden"><div id="shop-p2-bar" class="h-full bg-purple-500" style="width: 0%"></div></div>
                                </div>
                            </div>
                        </div>

                        <!-- Row 2: Busbar Sync -->
                        <div class="glass p-6 rounded-3xl border-cyan-500/10">
                            <p class="text-[9px] font-bold text-gray-400 uppercase tracking-widest mb-4">Busbar Sync</p>
                            <h3 class="text-3xl font-black text-white mb-6"><span id="shop-total-amps">--</span>A</h3>
                            <div class="space-y-4">
                                <div class="flex justify-between text-[10px] font-bold">
                                    <span class="text-gray-500">P1 AMPS</span>
                                    <span class="text-blue-400" id="shop-p1-amps">--A</span>
                                </div>
                                <div class="flex justify-between text-[10px] font-bold">
                                    <span class="text-gray-500">P2 AMPS</span>
                                    <span class="text-purple-400" id="shop-p2-amps">--A</span>
                                </div>
                            </div>
                        </div>

                        <!-- Row 3: System Intelligence (Large Card) -->
                        <div class="glass p-6 rounded-3xl border-purple-500/10 md:col-span-3">
                            <div class="flex flex-col md:flex-row items-center justify-between gap-8">
                                <div class="flex-1">
                                    <p class="text-[10px] font-bold text-gray-500 uppercase mb-4 tracking-[0.2em]">System Intelligence</p>
                                    <div class="grid grid-cols-2 gap-8">
                                        <div>
                                            <p class="text-[8px] font-bold text-gray-400 uppercase mb-1">Current State</p>
                                            <div class="flex items-center gap-2">
                                                <i id="icon-status" class="fa-solid fa-bolt text-lg"></i>
                                                <span id="shop-status-text" class="text-xl font-black text-white uppercase tracking-tighter">Standby</span>
                                            </div>
                                        </div>
                                        <div>
                                            <p class="text-[8px] font-bold text-gray-400 uppercase mb-1">Time Remaining</p>
                                            <p class="text-xl font-black text-white" id="shop-time-val">-- mins</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Analog Gauge for Charge -->
                                <div class="flex items-center gap-6">
                                    <div class="text-center">
                                        <div class="relative w-24 h-24">
                                            <svg viewBox="0 0 100 100" class="w-full h-full gauge-svg">
                                                <circle cx="50" cy="50" r="40" stroke-width="10" class="gauge-bg" />
                                                <circle id="intel-gauge" cx="50" cy="50" r="40" stroke-width="10" class="gauge-fill stroke-purple-500" stroke-dasharray="251.2" stroke-dashoffset="251.2" />
                                            </svg>
                                            <div class="absolute inset-0 flex flex-col items-center justify-center">
                                                <span class="text-[10px] font-black text-white uppercase leading-none">Sync</span>
                                                <span class="text-[8px] font-bold text-gray-500 uppercase">Monitor</span>
                                            </div>
                                        </div>
                                        <p class="text-[8px] font-bold text-gray-500 uppercase mt-2">Health Matrix</p>
                                    </div>
                                    
                                    <div class="space-y-2 border-l border-white/10 pl-6">
                                        <div class="flex items-center gap-3">
                                            <i id="icon-backup" class="fa-solid fa-clock-rotate-left text-xs"></i>
                                            <span class="text-[10px] font-bold text-gray-400">Backup: <span id="shop-backup-val" class="text-white">--</span></span>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <i id="icon-charge" class="fa-solid fa-battery-charging text-xs"></i>
                                            <span class="text-[10px] font-bold text-gray-400">To Full: <span id="shop-charge-val" class="text-white">--</span></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <!-- SITE 2: HOME POWER -->
            <div class="mb-20">
                <div class="flex items-center gap-4 mb-8">
                    <h2 class="text-3xl font-black text-purple-400 flex items-center gap-3 tracking-tighter">
                        <i class="fa-solid fa-house-chimney"></i> HOME POWER SYSTEM
                    </h2>
                    <div class="h-px flex-1 bg-gradient-to-r from-purple-500/20 to-transparent"></div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-5 gap-8 items-center">
                    <!-- Flow Chart -->
                    <div class="lg:col-span-2 flow-container">
                        <h3 class="text-[10px] font-bold text-gray-500 uppercase mb-8 tracking-[0.2em] text-center text-purple-500/50">Energy Flow: Site B</h3>
                        <div class="flex justify-center">
                            <svg width="300" height="240" viewBox="0 0 300 240" class="max-w-full h-auto">
                                <path d="M150,30 L150,120" class="stroke-gray-800 stroke-[2] fill-none" />
                                <path id="home-flow-solar" d="M150,30 L150,120" class="flow-dot stroke-orange-500" />
                                <path d="M40,120 L150,120" class="stroke-gray-800 stroke-[2] fill-none" />
                                <path id="home-flow-grid" d="M40,120 L150,120" class="flow-dot stroke-blue-500" />
                                <path d="M150,120 L150,210" class="stroke-gray-800 stroke-[2] fill-none" />
                                <path id="home-flow-battery" d="M150,120 L150,210" class="flow-dot stroke-green-500" />
                                <path d="M150,120 L260,120" class="stroke-gray-800 stroke-[2] fill-none" />
                                <path id="home-flow-home" d="M150,120 L260,120" class="flow-dot stroke-red-500" />
                                <circle cx="150" cy="30" r="28" class="node-circle stroke-orange-500" />
                                <text x="150" y="28" class="node-label">SOLAR</text>
                                <text x="150" y="42" id="home-flow-val-solar" class="node-value">--W</text>
                                <circle cx="40" cy="120" r="28" class="node-circle stroke-blue-500" />
                                <text x="40" y="118" class="node-label">GRID</text>
                                <text x="40" y="132" id="home-flow-val-grid" class="node-value">--W</text>
                                <circle cx="150" cy="210" r="28" class="node-circle stroke-green-500" />
                                <text x="150" y="208" class="node-label">STORAGE</text>
                                <text x="150" y="222" id="home-flow-val-battery" class="node-value">--%</text>
                                <circle cx="260" cy="120" r="28" class="node-circle stroke-red-500" />
                                <text x="260" y="118" class="node-label">HOME</text>
                                <text x="260" y="132" id="home-flow-val-home" class="node-value">--W</text>
                                <circle cx="150" cy="120" r="15" class="node-circle stroke-white/10" />
                            </svg>
                        </div>
                    </div>
                    <!-- Stats Cards -->
                    <div class="lg:col-span-3 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="glass p-6 rounded-2xl border-orange-500/10">
                            <p class="text-[9px] font-bold text-gray-500 uppercase mb-2 tracking-widest">Live Generation</p>
                            <div class="flex justify-between items-baseline"><p class="text-3xl font-black text-white"><span id="home-pv">--</span>W</p><i class="fa-solid fa-bolt-lightning text-orange-500"></i></div>
                        </div>
                        <div class="glass p-6 rounded-2xl border-green-500/10">
                            <p class="text-[9px] font-bold text-gray-500 uppercase mb-2 tracking-widest">Energy Storage</p>
                            <div class="flex justify-between items-baseline"><p class="text-3xl font-black text-white"><span id="home-soc">--</span>%</p><i class="fa-solid fa-battery-half text-green-500"></i></div>
                        </div>
                        <div class="glass p-6 rounded-2xl border-red-500/10">
                            <p class="text-[9px] font-bold text-gray-500 uppercase mb-2 tracking-widest">Consumption</p>
                            <div class="flex justify-between items-baseline"><p class="text-3xl font-black text-white"><span id="home-load">--</span>W</p><i class="fa-solid fa-house-user text-red-500"></i></div>
                        </div>
                        <div class="glass p-6 rounded-2xl border-blue-500/10">
                            <p class="text-[9px] font-bold text-gray-500 uppercase mb-2 tracking-widest">Thermal</p>
                            <div class="flex justify-between items-baseline"><p class="text-3xl font-black text-white"><span id="home-temp">--</span>°C</p><i class="fa-solid fa-temperature-arrow-up text-blue-500"></i></div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

<script>
    const entities = {
        // SHOP
        shop_pv: 'sensor.flin_energy_pv_power',
        shop_total_soc: 'sensor.shop_total_soc',
        shop_total_ah: 'sensor.shop_total_capacity_ah',
        shop_total_amps: 'sensor.shop_total_current',
        shop_p1_soc: 'sensor.shop_battery_pack_one_shop_bms_state_of_charge',
        shop_p2_soc: 'sensor.shop_battery_pack_two_shop_2_bms_state_of_charge',
        shop_p1_amps: 'sensor.shop_battery_pack_one_shop_bms_current',
        shop_p2_amps: 'sensor.shop_battery_pack_two_shop_2_bms_current',
        shop_load: 'sensor.flin_energy_load_power',
        shop_temp: 'sensor.flin_energy_battery_temperature',
        shop_grid: 'sensor.flin_energy_grid_power',
        shop_batt_pwr: 'sensor.flin_energy_battery_power',
        shop_backup: 'sensor.shop_backup_time_remaining',
        shop_to_full: 'sensor.shop_time_to_full_charge',
        
        // HOME
        home_pv: 'sensor.q004719472515009ad05_direct_pv_power',
        home_soc: 'sensor.jkbms_home_bms_state_of_charge',
        home_load: 'sensor.q004719472515009ad05_direct_inverter_out_power',
        home_temp: 'sensor.jkbms_home_bms_temperature_1',
        home_grid: 'sensor.q004719472515009ad05_direct_apparent_power',
        home_batt_pwr: 'sensor.jkbms_home_bms_power'
    };

    function formatVal(val, decimals = 1) {
        if (val === undefined || val === null || isNaN(parseFloat(val))) return "--";
        return parseFloat(val).toFixed(decimals);
    }

    async function updateDashboard() {
        const now = new Date();
        document.getElementById('last-updated').textContent = now.toLocaleTimeString();

        // SHOP UPDATES
        fetchEntity(entities.shop_pv, 'shop-pv', 'state', (val) => {
            const v = formatVal(val, 0);
            document.getElementById('shop-pv').textContent = v;
            document.getElementById('shop-flow-val-solar').textContent = v + 'W';
            toggleFlow('shop-flow-solar', parseFloat(val) > 10);
        });
        
        fetchEntity(entities.shop_total_soc, 'shop-total-soc', 'state', (val) => {
            const v = formatVal(val, 1);
            document.getElementById('shop-total-soc').textContent = v;
            document.getElementById('shop-flow-val-battery').textContent = v + '%';
            // Update Intel Gauge
            const offset = 251.2 - (251.2 * (parseFloat(val) / 100));
            document.getElementById('intel-gauge').style.strokeDashoffset = offset;
        });

        fetchEntity(entities.shop_total_ah, 'shop-total-ah', 'state');
        
        fetchEntity(entities.shop_total_amps, 'shop-total-amps', 'state', (val) => {
            const a = parseFloat(val);
            document.getElementById('shop-total-amps').textContent = formatVal(val, 1);
            
            const iconStatus = document.getElementById('icon-status');
            const statusText = document.getElementById('shop-status-text');
            const iconBackup = document.getElementById('icon-backup');
            const iconCharge = document.getElementById('icon-charge');

            if(a < -0.5) {
                statusText.textContent = "Discharging";
                statusText.className = "text-xl font-black text-red-500 uppercase tracking-tighter";
                iconStatus.className = "fa-solid fa-battery-quarter status-discharging";
                iconBackup.classList.add('status-discharging');
                iconCharge.classList.remove('status-charging');
            } else if(a > 0.5) {
                statusText.textContent = "Charging";
                statusText.className = "text-xl font-black text-green-500 uppercase tracking-tighter";
                iconStatus.className = "fa-solid fa-battery-charging status-charging";
                iconCharge.classList.add('status-charging');
                iconBackup.classList.remove('status-discharging');
            } else {
                statusText.textContent = "Standby";
                statusText.className = "text-xl font-black text-gray-500 uppercase tracking-tighter";
                iconStatus.className = "fa-solid fa-bolt text-gray-700";
                iconBackup.classList.remove('status-discharging');
                iconCharge.classList.remove('status-charging');
            }
        });

        fetchEntity(entities.shop_p1_soc, 'shop-p1-soc', 'state', (val) => {
            document.getElementById('shop-p1-soc').textContent = formatVal(val, 0) + '%';
            document.getElementById('shop-p1-bar').style.width = val + '%';
        });

        fetchEntity(entities.shop_p2_soc, 'shop-p2-soc', 'state', (val) => {
            document.getElementById('shop-p2-soc').textContent = formatVal(val, 0) + '%';
            document.getElementById('shop-p2-bar').style.width = val + '%';
        });

        fetchEntity(entities.shop_p1_amps, 'shop-p1-amps', 'state', (val) => document.getElementById('shop-p1-amps').textContent = formatVal(val, 1) + 'A');
        fetchEntity(entities.shop_p2_amps, 'shop-p2-amps', 'state', (val) => document.getElementById('shop-p2-amps').textContent = formatVal(val, 1) + 'A');
        
        fetchEntity(entities.shop_backup, 'shop-backup-val', 'state', (val) => {
            document.getElementById('shop-backup-val').textContent = val;
            const amps = parseFloat(document.getElementById('shop-total-amps').textContent);
            if(amps < -0.5) document.getElementById('shop-time-val').textContent = val;
        });

        fetchEntity(entities.shop_to_full, 'shop-charge-val', 'state', (val) => {
            document.getElementById('shop-charge-val').textContent = val;
            const amps = parseFloat(document.getElementById('shop-total-amps').textContent);
            if(amps > 0.5) document.getElementById('shop-time-val').textContent = val;
        });

        fetchEntity(entities.shop_load, 'shop-load', 'state', (val) => {
            const v = formatVal(val, 0);
            document.getElementById('shop-load').textContent = v;
            document.getElementById('shop-flow-val-home').textContent = v + 'W';
            toggleFlow('shop-flow-home', parseFloat(val) > 10);
        });
        
        fetchEntity(entities.shop_temp, 'shop-temp', 'state', (val) => document.getElementById('shop-temp').textContent = formatVal(val, 1));
        
        fetchEntity(entities.shop_grid, null, 'state', (val) => {
            const g = parseFloat(val);
            document.getElementById('shop-flow-val-grid').textContent = Math.abs(g).toFixed(0) + 'W';
            toggleFlow('shop-flow-grid', Math.abs(g) > 10, g < 0);
        });
        
        fetchEntity(entities.shop_batt_pwr, null, 'state', (val) => {
            toggleFlow('shop-flow-battery', Math.abs(parseFloat(val)) > 10, parseFloat(val) > 0);
        });

        // HOME UPDATES
        fetchEntity(entities.home_pv, 'home-pv', 'state', (val) => {
            const v = formatVal(val, 0);
            document.getElementById('home-pv').textContent = v;
            document.getElementById('home-flow-val-solar').textContent = v + 'W';
            toggleFlow('home-flow-solar', parseFloat(val) > 10);
        });
        fetchEntity(entities.home_soc, 'home-soc', 'state', (val) => {
            const v = formatVal(val, 1);
            document.getElementById('home-soc').textContent = v;
            document.getElementById('home-flow-val-battery').textContent = v + '%';
        });
        fetchEntity(entities.home_load, 'home-load', 'state', (val) => {
            const v = formatVal(val, 0);
            document.getElementById('home-load').textContent = v;
            document.getElementById('home-flow-val-home').textContent = v + 'W';
            toggleFlow('home-flow-home', parseFloat(val) > 10);
        });
        fetchEntity(entities.home_temp, 'home-temp', 'state', (val) => document.getElementById('home-temp').textContent = formatVal(val, 1));
        
        fetchEntity(entities.home_grid, null, 'state', (val) => {
            const g = parseFloat(val);
            document.getElementById('home-flow-val-grid').textContent = Math.abs(g).toFixed(0) + 'W';
            toggleFlow('home-flow-grid', Math.abs(g) > 10, g < 0);
        });
        fetchEntity(entities.home_batt_pwr, null, 'state', (val) => {
            toggleFlow('home-flow-battery', Math.abs(parseFloat(val)) > 10, parseFloat(val) > 0);
        });
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
</script>

<?php include 'includes/footer.php'; ?>
