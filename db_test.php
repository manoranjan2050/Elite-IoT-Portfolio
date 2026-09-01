<?php
// DB TEST — DELETE AFTER USE
// Visit: yourdomain.com/db_test.php

require_once 'includes/db.php';
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>DB Test</title>
<style>
body { font-family: monospace; background: #0f172a; color: #e2e8f0; padding: 2rem; }
h2,h3 { color: #60a5fa; }
table { border-collapse: collapse; margin-bottom: 2rem; width: 100%; max-width: 900px; }
th { background: #1e293b; color: #94a3b8; padding: 8px 12px; text-align: left; font-size: 0.75rem; text-transform: uppercase; }
td { padding: 8px 12px; border-bottom: 1px solid #1e293b; word-break: break-all; font-size: 0.85rem; }
.ok  { color: #4ade80; }
.err { color: #f87171; }
.warn{ color: #fbbf24; }
</style>
</head>
<body>
<h2>🗄 DB Test</h2>

<?php
// 1. Connection info
echo "<h3>1. Connection</h3>";
try {
    $ver = $pdo->query('SELECT VERSION()')->fetchColumn();
    echo "<p class='ok'>✓ Connected — MySQL $ver</p>";
} catch (Exception $e) {
    echo "<p class='err'>✗ " . htmlspecialchars($e->getMessage()) . "</p>";
}

// 2. ha_settings table
echo "<h3>2. ha_settings table</h3>";
try {
    $rows = $pdo->query("SELECT * FROM ha_settings ORDER BY setting_key")->fetchAll(PDO::FETCH_ASSOC);
    echo "<table><tr><th>id</th><th>setting_key</th><th>setting_value</th><th>updated_at</th></tr>";
    foreach ($rows as $r) {
        $val = $r['setting_value'];
        // Mask token
        if ($r['setting_key'] === 'ha_token' && strlen($val) > 10) {
            $val = substr($val, 0, 8) . '...' . substr($val, -4);
        }
        // Show hash info for pattern
        if ($r['setting_key'] === 'control_pattern') {
            if (empty($val)) {
                $val = '<span class="warn">(empty — will use default 1235)</span>';
            } elseif (substr($val, 0, 4) === '$2y$') {
                $val = '<span class="ok">✓ Valid bcrypt hash</span> <span style="color:#475569">' . htmlspecialchars(substr($val, 0, 20)) . '...</span>';
            } else {
                $val = '<span class="err">✗ NOT a valid bcrypt hash: ' . htmlspecialchars(substr($val, 0, 30)) . '</span>';
            }
        }
        echo "<tr><td>{$r['id']}</td><td>{$r['setting_key']}</td><td>$val</td><td>{$r['updated_at']}</td></tr>";
    }
    echo "</table>";
    if (empty($rows)) echo "<p class='warn'>⚠ Table is empty — run the migration!</p>";
} catch (Exception $e) {
    echo "<p class='err'>✗ " . htmlspecialchars($e->getMessage()) . " — table may not exist</p>";
}

// 3. ha_entities sample
echo "<h3>3. ha_entities (control entities)</h3>";
try {
    $rows = $pdo->query("SELECT entity_key, entity_id, entity_type, site, show_in_control, show_in_power FROM ha_entities WHERE show_in_control = 1 LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
    if ($rows) {
        echo "<table><tr><th>entity_key</th><th>entity_id</th><th>type</th><th>site</th><th>in_control</th><th>in_power</th></tr>";
        foreach ($rows as $r) {
            echo "<tr><td>{$r['entity_key']}</td><td>{$r['entity_id']}</td><td>{$r['entity_type']}</td><td>{$r['site']}</td><td class='ok'>{$r['show_in_control']}</td><td>{$r['show_in_power']}</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='warn'>⚠ No entities with show_in_control=1</p>";
    }
} catch (Exception $e) {
    echo "<p class='err'>✗ " . htmlspecialchars($e->getMessage()) . "</p>";
}

// 4. PHP info
echo "<h3>4. PHP Environment</h3>";
$checks = [
    'PHP Version'            => PHP_VERSION,
    'password_hash exists'   => function_exists('password_hash') ? '✓ YES' : '✗ NO',
    'password_verify exists' => function_exists('password_verify') ? '✓ YES' : '✗ NO',
    'str_starts_with exists' => function_exists('str_starts_with') ? '✓ YES (PHP 8+)' : '⚠ NO (PHP 7.x — OK, we use substr now)',
    'curl enabled'           => function_exists('curl_init') ? '✓ YES' : '✗ NO — HA calls will fail!',
    'session_start works'    => 'YES (page loaded)',
];
echo "<table>";
foreach ($checks as $k => $v) {
    $cls = strpos($v,'✓')!==false ? 'ok' : (strpos($v,'✗')!==false ? 'err' : 'warn');
    echo "<tr><td>$k</td><td class='$cls'>$v</td></tr>";
}
echo "</table>";

// 5. iot_config.php
echo "<h3>5. iot_config.php</h3>";
$cfg = __DIR__ . '/includes/iot_config.php';
if (file_exists($cfg)) {
    echo "<p class='ok'>✓ File exists</p>";
    @include $cfg;
    echo "<p>HA_URL defined: " . (defined('HA_URL') ? '<span class="ok">✓ ' . htmlspecialchars(HA_URL) . '</span>' : '<span class="err">✗ NO</span>') . "</p>";
    echo "<p>HA_TOKEN defined: " . (defined('HA_TOKEN') ? '<span class="ok">✓ (set, ' . strlen(HA_TOKEN) . ' chars)</span>' : '<span class="err">✗ NO</span>') . "</p>";
} else {
    echo "<p class='err'>✗ File missing — HA proxy won't work</p>";
}

// 6. Quick API test
echo "<h3>6. HA API connectivity test</h3>";
try {
    $haUrl = $pdo->query("SELECT setting_value FROM ha_settings WHERE setting_key='ha_url'")->fetchColumn();
    $haToken = $pdo->query("SELECT setting_value FROM ha_settings WHERE setting_key='ha_token'")->fetchColumn();
    if (!empty($haUrl) && !empty($haToken)) {
        $ch = curl_init(rtrim($haUrl,'/') . '/api/');
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>5, CURLOPT_SSL_VERIFYPEER=>false, CURLOPT_HTTPHEADER=>['Authorization: Bearer '.$haToken,'Content-Type: application/json']]);
        $res = curl_exec($ch); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE); $err = curl_error($ch); curl_close($ch);
        if ($err) echo "<p class='err'>✗ cURL error: " . htmlspecialchars($err) . "</p>";
        elseif ($code === 200) echo "<p class='ok'>✓ HA API responded with 200 OK</p>";
        else echo "<p class='warn'>⚠ HA API returned HTTP $code — check URL/token</p>";
    } else {
        echo "<p class='warn'>⚠ ha_url or ha_token not set in DB</p>";
    }
} catch (Exception $e) {
    echo "<p class='err'>✗ " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>

<p style="color:#ef4444;margin-top:3rem;font-weight:bold">⚠ DELETE BOTH db_test.php AND pattern_test.php after debugging!</p>
</body>
</html>
