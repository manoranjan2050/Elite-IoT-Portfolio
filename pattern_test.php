<?php
// PATTERN DEBUG TEST — DELETE AFTER USE
// Visit: yourdomain.com/pattern_test.php

require_once 'includes/db.php';

$result = [];

// 1. Read stored hash from DB
$stmt = $pdo->prepare("SELECT setting_value FROM ha_settings WHERE setting_key = 'control_pattern'");
$stmt->execute();
$row = $stmt->fetch();
$storedHash = $row ? $row['setting_value'] : null;

$result['1_row_found']        = ($row !== false) ? 'YES' : 'NO - row missing!';
$result['2_stored_value_raw'] = var_export($storedHash, true);
$result['3_is_empty']         = empty($storedHash) ? 'YES (empty)' : 'NO (has value)';
$result['4_is_bcrypt']        = (substr($storedHash ?? '', 0, 4) === '$2y$') ? 'YES ($2y$...)' : 'NO - not bcrypt!';
$result['5_hash_length']      = strlen($storedHash ?? '');
$result['6_first_10_chars']   = substr($storedHash ?? '', 0, 10);

// 2. What the API would do
if (empty($storedHash) || substr($storedHash, 0, 4) !== '$2y$') {
    $hashToUse = password_hash('1235', PASSWORD_BCRYPT);
    $result['7_hash_used'] = 'FALLBACK — generated fresh hash of "1235"';
} else {
    $hashToUse = $storedHash;
    $result['7_hash_used'] = 'DB HASH — using stored hash';
}

// 3. Test common patterns
$testPatterns = ['1235', '1234', '14789', '1234569', $_GET['test'] ?? ''];
$result['8_php_version'] = PHP_VERSION;
$result['9_password_functions_exist'] = function_exists('password_hash') ? 'YES' : 'NO!';

$result['10_pattern_tests'] = [];
foreach ($testPatterns as $p) {
    if ($p === '') continue;
    $verify = password_verify($p, $hashToUse);
    $result['10_pattern_tests'][$p] = $verify ? '✓ MATCH' : '✗ NO MATCH';
}

// 4. Fresh hash test
$freshHash = password_hash('1235', PASSWORD_BCRYPT);
$result['11_fresh_hash_verify_1235'] = password_verify('1235', $freshHash) ? '✓ PHP password functions working' : '✗ PHP password functions BROKEN';

// 5. Test saving a new hash right now
if (isset($_POST['save_test'])) {
    $pat = trim($_POST['pattern']);
    if (strlen($pat) >= 4) {
        $h = password_hash($pat, PASSWORD_BCRYPT);
        $s2 = $pdo->prepare("INSERT INTO ha_settings (setting_key, setting_value) VALUES ('control_pattern', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $s2->execute([$h, $h]);
        $result['SAVE_TEST'] = "Saved hash for pattern '$pat'. Hash starts: " . substr($h, 0, 20);
        // Immediately verify
        $s3 = $pdo->prepare("SELECT setting_value FROM ha_settings WHERE setting_key = 'control_pattern'");
        $s3->execute();
        $saved = $s3->fetchColumn();
        $result['SAVE_VERIFY'] = password_verify($pat, $saved) ? "✓ Verified '$pat' against saved hash — WORKS!" : "✗ Verify failed after save!";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Pattern Debug</title>
<style>
body { font-family: monospace; background: #0f172a; color: #e2e8f0; padding: 2rem; }
h2 { color: #60a5fa; }
.ok  { color: #4ade80; }
.err { color: #f87171; }
.warn { color: #fbbf24; }
table { border-collapse: collapse; width: 100%; max-width: 800px; }
td { padding: 8px 12px; border-bottom: 1px solid #1e293b; vertical-align: top; }
td:first-child { color: #94a3b8; width: 280px; white-space: nowrap; }
td:last-child { color: #f1f5f9; word-break: break-all; }
form { margin-top: 2rem; max-width: 400px; }
input[type=text] { background: #1e293b; border: 1px solid #334155; color: white; padding: 8px 12px; border-radius: 8px; width: 200px; }
button { background: #3b82f6; color: white; border: none; padding: 8px 16px; border-radius: 8px; cursor: pointer; margin-left: 8px; }
.match { background: rgba(34,197,94,0.1); padding: 4px 8px; border-radius: 4px; }
.nomatch { background: rgba(239,68,68,0.1); padding: 4px 8px; border-radius: 4px; }
</style>
</head>
<body>
<h2>🔍 Pattern Lock Debug</h2>
<table>
<?php foreach ($result as $key => $val): ?>
<?php if ($key === '10_pattern_tests'): ?>
<tr><td><?php echo $key; ?></td><td>
<?php foreach ($val as $p => $v): ?>
<span class="<?php echo strpos($v,'MATCH')!==false&&strpos($v,'NO')===false ? 'match' : 'nomatch'; ?>"><?php echo htmlspecialchars($p); ?> → <?php echo $v; ?></span><br>
<?php endforeach; ?>
</td></tr>
<?php else: ?>
<tr>
  <td><?php echo htmlspecialchars($key); ?></td>
  <td class="<?php
    if (strpos((string)$val, '✓') !== false || $val === 'YES') echo 'ok';
    elseif (strpos((string)$val, '✗') !== false || strpos((string)$val, 'NO') !== false || strpos((string)$val, 'missing') !== false) echo 'err';
    elseif (strpos((string)$val, 'FALLBACK') !== false) echo 'warn';
  ?>"><?php echo htmlspecialchars((string)$val); ?></td>
</tr>
<?php endif; ?>
<?php endforeach; ?>
</table>

<form method="POST">
    <h3 style="color:#a78bfa">Test a specific pattern:</h3>
    <input type="text" name="pattern" placeholder="e.g. 14789" maxlength="20">
    <button type="submit" name="save_test">Save + Verify</button>
    <p style="color:#64748b;font-size:0.8rem">This will hash the pattern, save it to DB, then immediately read it back and verify — full end-to-end test.</p>
</form>

<form method="GET">
    <h3 style="color:#a78bfa">Verify a pattern against current DB hash:</h3>
    <input type="text" name="test" placeholder="e.g. 14789" maxlength="20">
    <button type="submit">Test</button>
    <p style="color:#64748b;font-size:0.8rem">Does NOT change the DB. Just checks if this pattern matches what's stored.</p>
</form>

<p style="color:#ef4444;margin-top:3rem">⚠ DELETE THIS FILE after debugging!</p>
</body>
</html>
