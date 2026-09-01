<?php
/**
 * ============================================================
 *  manoranjan.dev — Database Migration Runner
 *  Upload this file, open it in browser, run, then DELETE it.
 *  DO NOT leave this file on the server permanently.
 * ============================================================
 */

// ── SECURITY KEY ── Change this before uploading ──
define('MIGRATE_KEY', 'manoranjan2025');

// ── Auto-delete after successful run? ──
define('AUTO_DELETE', false); // set true to delete this file after success

session_start();

$authenticated = isset($_SESSION['migrate_auth']) && $_SESSION['migrate_auth'] === true;
$result        = [];
$error         = '';
$success       = false;

// Login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['auth_key'])) {
    if (trim($_POST['auth_key']) === MIGRATE_KEY) {
        $_SESSION['migrate_auth'] = true;
        $authenticated = true;
    } else {
        $error = 'Wrong security key.';
    }
}

// Run migration
if ($authenticated && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_migration'])) {
    require_once 'includes/db.php';

    // Full SQL split into statements
    $sql_statements = [

        // ── 1. ALTER admin_users ──
        "ALTER TABLE admin_users
            ADD COLUMN IF NOT EXISTS full_name VARCHAR(100) DEFAULT NULL AFTER username",
        "ALTER TABLE admin_users
            ADD COLUMN IF NOT EXISTS email VARCHAR(150) DEFAULT NULL AFTER full_name",
        "ALTER TABLE admin_users
            ADD COLUMN IF NOT EXISTS mobile VARCHAR(20) DEFAULT NULL AFTER email",
        "ALTER TABLE admin_users
            ADD COLUMN IF NOT EXISTS profile_photo VARCHAR(255) DEFAULT NULL AFTER mobile",
        "ALTER TABLE admin_users
            ADD COLUMN IF NOT EXISTS bio TEXT DEFAULT NULL AFTER profile_photo",

        // ── 2. ha_settings table ──
        "CREATE TABLE IF NOT EXISTS ha_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(100) NOT NULL UNIQUE,
            setting_value TEXT,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )",
        "INSERT IGNORE INTO ha_settings (setting_key, setting_value) VALUES
            ('ha_url',      'https://power.mtpretails.in'),
            ('ha_token',    ''),
            ('ha_enabled',  '1'),
            ('site_a_name', 'Shop'),
            ('site_b_name', 'Home')",

        // ── 3. ha_entities table ──
        "CREATE TABLE IF NOT EXISTS ha_entities (
            id INT AUTO_INCREMENT PRIMARY KEY,
            entity_key VARCHAR(100) NOT NULL UNIQUE,
            entity_id  VARCHAR(200) NOT NULL,
            friendly_name VARCHAR(100) DEFAULT NULL,
            entity_type ENUM('sensor','switch','light','binary_sensor','automation','scene','other') DEFAULT 'sensor',
            site ENUM('shop','home','global') DEFAULT 'shop',
            display_unit VARCHAR(20) DEFAULT NULL,
            show_in_control TINYINT(1) DEFAULT 0,
            show_in_power   TINYINT(1) DEFAULT 1,
            display_order INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "INSERT IGNORE INTO ha_entities
            (entity_key,entity_id,friendly_name,entity_type,site,display_unit,show_in_control,show_in_power,display_order)
         VALUES
            ('shop_total_soc','sensor.shop_total_soc','Shop Total SOC','sensor','shop','%',0,1,1),
            ('shop_total_ah','sensor.shop_total_capacity_ah','Shop Total Capacity','sensor','shop','Ah',0,1,2),
            ('shop_total_amps','sensor.shop_total_current','Shop Total Current','sensor','shop','A',0,1,3),
            ('shop_backup','sensor.shop_backup_time_remaining','Shop Backup Time','sensor','shop','',0,1,4),
            ('shop_to_full','sensor.shop_time_to_full_charge','Shop Time to Full','sensor','shop','',0,1,5),
            ('shop_pv','sensor.flin_energy_pv_power','Shop Solar PV','sensor','shop','W',0,1,6),
            ('shop_load','sensor.flin_energy_load_power','Shop Load Power','sensor','shop','W',0,1,7),
            ('shop_temp','sensor.flin_energy_battery_temperature','Shop Battery Temp','sensor','shop','°C',0,1,8),
            ('shop_grid','sensor.flin_energy_grid_power','Shop Grid Power','sensor','shop','W',0,1,9),
            ('shop_batt_pwr','sensor.flin_energy_battery_power','Shop Battery Power','sensor','shop','W',0,1,10),
            ('shop_p1_soc','sensor.shop_battery_pack_one_shop_bms_state_of_charge','Pack 1 SOC','sensor','shop','%',0,1,11),
            ('shop_p1_amps','sensor.shop_battery_pack_one_shop_bms_current','Pack 1 Current','sensor','shop','A',0,1,12),
            ('shop_p1_delta','sensor.shop_battery_pack_one_shop_bms_cell_delta','Pack 1 Cell Delta','sensor','shop','V',0,1,13),
            ('shop_p1_link','binary_sensor.shop_battery_pack_one_shop_bms_online_status','Pack 1 Online','binary_sensor','shop','',0,1,14),
            ('shop_p1_sw_c','switch.shop_battery_pack_one_shop_bms_charging_switch','Pack 1 Charge Switch','switch','shop','',1,1,15),
            ('shop_p1_sw_d','switch.shop_battery_pack_one_shop_bms_discharging_switch','Pack 1 Discharge Switch','switch','shop','',1,1,16),
            ('shop_p2_soc','sensor.shop_battery_pack_two_shop_2_bms_state_of_charge','Pack 2 SOC','sensor','shop','%',0,1,17),
            ('shop_p2_amps','sensor.shop_battery_pack_two_shop_2_bms_current','Pack 2 Current','sensor','shop','A',0,1,18),
            ('shop_p2_delta','sensor.shop_battery_pack_two_shop_2_bms_cell_delta','Pack 2 Cell Delta','sensor','shop','V',0,1,19),
            ('shop_p2_link','binary_sensor.shop_battery_pack_two_shop_2_bms_online_status','Pack 2 Online','binary_sensor','shop','',0,1,20),
            ('shop_p2_sw_c','switch.shop_battery_pack_two_shop_2_bms_charging_switch','Pack 2 Charge Switch','switch','shop','',1,1,21),
            ('shop_p2_sw_d','switch.shop_battery_pack_two_shop_2_bms_discharging_switch','Pack 2 Discharge Switch','switch','shop','',1,1,22),
            ('home_pv','sensor.q004719472515009ad05_direct_pv_power','Home Solar PV','sensor','home','W',0,1,23),
            ('home_soc','sensor.jkbms_home_bms_state_of_charge','Home BMS SOC','sensor','home','%',0,1,24),
            ('home_v','sensor.jkbms_home_bms_battery_voltage','Home BMS Voltage','sensor','home','V',0,1,25),
            ('home_p','sensor.jkbms_home_bms_power','Home BMS Power','sensor','home','W',0,1,26),
            ('home_load','sensor.q004719472515009ad05_direct_inverter_out_power','Home Load Power','sensor','home','W',0,1,27),
            ('home_temp','sensor.jkbms_home_bms_temperature_1','Home BMS Temp','sensor','home','°C',0,1,28),
            ('home_delta','sensor.jkbms_home_bms_cell_delta','Home Cell Delta','sensor','home','mV',0,1,29),
            ('home_grid','sensor.q004719472515009ad05_direct_apparent_power','Home Grid Power','sensor','home','W',0,1,30),
            ('home_batt_pwr','sensor.jkbms_home_bms_power','Home Battery Power','sensor','home','W',0,1,31),
            ('home_amps','sensor.jkbms_home_bms_current','Home BMS Current','sensor','home','A',0,1,32)",
    ];

    $allOk = true;
    foreach ($sql_statements as $index => $sql) {
        $sql = trim($sql);
        if (empty($sql)) continue;
        try {
            $pdo->exec($sql);
            // Summarise what ran
            if (stripos($sql, 'ALTER TABLE') !== false) {
                preg_match('/ADD COLUMN\s+(\w+)/i', $sql, $m);
                $col = $m[1] ?? 'column';
                $result[] = ['ok' => true, 'msg' => "ALTER admin_users — added column <b>$col</b>"];
            } elseif (stripos($sql, 'CREATE TABLE') !== false) {
                preg_match('/CREATE TABLE IF NOT EXISTS\s+(\w+)/i', $sql, $m);
                $tbl = $m[1] ?? 'table';
                $result[] = ['ok' => true, 'msg' => "CREATE TABLE <b>$tbl</b>"];
            } elseif (stripos($sql, 'INSERT IGNORE INTO ha_settings') !== false) {
                $result[] = ['ok' => true, 'msg' => "INSERT default rows → <b>ha_settings</b>"];
            } elseif (stripos($sql, 'INSERT IGNORE INTO ha_entities') !== false) {
                $result[] = ['ok' => true, 'msg' => "INSERT 32 default entities → <b>ha_entities</b>"];
            } else {
                $result[] = ['ok' => true, 'msg' => "Statement #" . ($index + 1) . " OK"];
            }
        } catch (PDOException $e) {
            $msg = $e->getMessage();
            // "Duplicate column" is not a real error — column already exists
            if (stripos($msg, 'Duplicate column') !== false || stripos($msg, 'already exists') !== false) {
                preg_match('/ADD COLUMN\s+(\w+)/i', $sql, $m);
                $col = $m[1] ?? 'column';
                $result[] = ['ok' => true, 'msg' => "Column <b>$col</b> already exists — skipped"];
            } else {
                $result[] = ['ok' => false, 'msg' => "ERROR: " . htmlspecialchars($msg)];
                $allOk = false;
            }
        }
    }

    if ($allOk) {
        $success = true;
        if (AUTO_DELETE) {
            @unlink(__FILE__);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DB Migration — manoranjan.dev</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .glass { background: rgba(17,24,39,0.85); backdrop-filter: blur(20px); border: 1px solid rgba(255,255,255,0.08); }
        @keyframes fadeIn { from{opacity:0;transform:translateY(16px)} to{opacity:1;transform:translateY(0)} }
        .fade-in { animation: fadeIn 0.5s ease forwards; }
        @keyframes spin { to{transform:rotate(360deg)} }
        .spin { animation: spin 1s linear infinite; }
    </style>
</head>
<body class="bg-gray-950 text-gray-100 min-h-screen flex items-center justify-center p-4">

<div class="w-full max-w-xl fade-in">

    <!-- Header -->
    <div class="text-center mb-6">
        <div class="w-14 h-14 rounded-2xl bg-blue-600 flex items-center justify-center mx-auto mb-3 shadow-lg shadow-blue-500/30">
            <i class="fa-solid fa-database text-xl text-white"></i>
        </div>
        <h1 class="text-2xl font-black text-white">DB Migration Runner</h1>
        <p class="text-sm text-gray-500 mt-1">manoranjan.dev · Run once, then delete this file</p>
    </div>

    <!-- Security warning -->
    <div class="mb-5 px-4 py-3 bg-yellow-500/10 border border-yellow-500/30 rounded-xl text-xs text-yellow-400 flex items-start gap-3">
        <i class="fa-solid fa-triangle-exclamation mt-0.5 flex-shrink-0"></i>
        <span><b>Security reminder:</b> Delete <code>db_migrate.php</code> from your server immediately after use. Anyone with the URL can access this.</span>
    </div>

    <?php if (!$authenticated): ?>
    <!-- Auth Form -->
    <div class="glass rounded-2xl p-6">
        <p class="text-sm text-gray-400 mb-4">Enter the security key to continue:</p>
        <?php if ($error): ?>
        <div class="mb-4 px-4 py-2 bg-red-500/10 border border-red-500/30 rounded-xl text-sm text-red-400 flex items-center gap-2">
            <i class="fa-solid fa-circle-xmark"></i> <?php echo $error; ?>
        </div>
        <?php endif; ?>
        <form method="POST" class="flex gap-3">
            <input type="password" name="auth_key" autofocus required
                class="flex-1 px-4 py-2.5 bg-gray-800 border border-gray-700 rounded-xl text-white text-sm"
                placeholder="Security key">
            <button type="submit" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-500 text-white font-bold rounded-xl transition text-sm">
                Unlock
            </button>
        </form>
        <p class="text-[10px] text-gray-600 mt-3">Default key: <code>manoranjan2025</code> — change it in the file before uploading.</p>
    </div>

    <?php elseif (!empty($result)): ?>
    <!-- Results -->
    <div class="glass rounded-2xl overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-800 flex items-center gap-3">
            <?php if ($success): ?>
            <div class="w-8 h-8 rounded-lg bg-green-500/15 flex items-center justify-center">
                <i class="fa-solid fa-circle-check text-green-400"></i>
            </div>
            <h2 class="font-bold text-green-400">Migration Complete!</h2>
            <?php else: ?>
            <div class="w-8 h-8 rounded-lg bg-red-500/15 flex items-center justify-center">
                <i class="fa-solid fa-circle-xmark text-red-400"></i>
            </div>
            <h2 class="font-bold text-red-400">Migration finished with errors</h2>
            <?php endif; ?>
        </div>
        <div class="p-4 space-y-2 max-h-80 overflow-y-auto">
            <?php foreach ($result as $r): ?>
            <div class="flex items-start gap-3 px-3 py-2 rounded-lg <?php echo $r['ok'] ? 'bg-green-500/5' : 'bg-red-500/10'; ?>">
                <i class="fa-solid <?php echo $r['ok'] ? 'fa-check text-green-400' : 'fa-xmark text-red-400'; ?> mt-0.5 flex-shrink-0 text-xs"></i>
                <span class="text-xs text-gray-300"><?php echo $r['msg']; ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php if ($success): ?>
        <div class="px-5 py-4 border-t border-gray-800 space-y-3">
            <div class="px-4 py-3 bg-green-500/10 border border-green-500/20 rounded-xl text-xs text-green-400">
                <b>✓ All done!</b> Database updated successfully. Now:
                <ol class="mt-2 space-y-1 list-decimal list-inside text-green-300/80">
                    <li>Go to <a href="admin/ha_settings.php" class="underline font-bold">Admin → HA Settings</a> and save your LAT token</li>
                    <li><b>Delete this file</b> from your server (FTP or File Manager)</li>
                </ol>
            </div>
            <div class="flex gap-3">
                <a href="admin/index.php" class="flex-1 py-2.5 bg-blue-600 hover:bg-blue-500 text-white font-bold rounded-xl transition text-sm text-center">
                    <i class="fa-solid fa-gauge mr-1"></i> Go to Admin
                </a>
                <?php if (!AUTO_DELETE): ?>
                <form method="POST" class="flex-1">
                    <input type="hidden" name="run_migration" value="1">
                    <button type="button" onclick="deleteSelf()"
                        class="w-full py-2.5 bg-red-600/20 hover:bg-red-600/40 text-red-400 font-bold rounded-xl transition text-sm border border-red-500/20">
                        <i class="fa-solid fa-trash mr-1"></i> Delete This File
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <?php else: ?>
    <!-- Run Form -->
    <div class="glass rounded-2xl overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-800">
            <h2 class="font-bold text-white flex items-center gap-2">
                <i class="fa-solid fa-list-check text-blue-400"></i> Migration Plan
            </h2>
            <p class="text-xs text-gray-500 mt-1">The following changes will be applied to your database:</p>
        </div>
        <div class="p-4 space-y-2">
            <?php
            $steps = [
                ['fa-table-columns', 'blue',   'ALTER admin_users — add: full_name, email, mobile, profile_photo, bio'],
                ['fa-table',         'green',  'CREATE TABLE ha_settings (HA connection config)'],
                ['fa-circle-plus',   'green',  'INSERT 5 default rows into ha_settings'],
                ['fa-table',         'yellow', 'CREATE TABLE ha_entities (HA entity ID management)'],
                ['fa-circle-plus',   'yellow', 'INSERT 32 default entities into ha_entities'],
            ];
            foreach ($steps as [$icon, $color, $label]):
            ?>
            <div class="flex items-center gap-3 px-3 py-2.5 bg-gray-800/40 rounded-xl">
                <div class="w-7 h-7 rounded-lg bg-<?php echo $color; ?>-500/15 flex items-center justify-center flex-shrink-0">
                    <i class="fa-solid <?php echo $icon; ?> text-<?php echo $color; ?>-400 text-xs"></i>
                </div>
                <span class="text-xs text-gray-300"><?php echo $label; ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="px-5 py-4 border-t border-gray-800">
            <form method="POST" onsubmit="startRun(this)">
                <input type="hidden" name="run_migration" value="1">
                <button type="submit" id="run-btn"
                    class="w-full py-3 bg-blue-600 hover:bg-blue-500 text-white font-black rounded-xl transition flex items-center justify-center gap-2">
                    <i class="fa-solid fa-play" id="run-icon"></i>
                    <span id="run-text">Run Migration Now</span>
                </button>
            </form>
            <p class="text-[10px] text-gray-600 text-center mt-2">Safe to run multiple times — uses IF NOT EXISTS and INSERT IGNORE</p>
        </div>
    </div>
    <?php endif; ?>

</div>

<script>
    function startRun(form) {
        const btn  = document.getElementById('run-btn');
        const icon = document.getElementById('run-icon');
        const txt  = document.getElementById('run-text');
        btn.disabled = true;
        icon.className = 'fa-solid fa-spinner spin';
        txt.textContent = 'Running migration...';
    }

    function deleteSelf() {
        if (!confirm('Delete db_migrate.php from the server now?')) return;
        fetch('?delete_self=1').then(() => {
            alert('File deleted. Redirecting to admin...');
            window.location.href = 'admin/index.php';
        });
    }
</script>

<?php
// Handle self-delete via fetch
if (isset($_GET['delete_self']) && $authenticated) {
    @unlink(__FILE__);
    echo '<script>window.location.href="admin/index.php"</script>';
    exit;
}
?>
</body>
</html>
