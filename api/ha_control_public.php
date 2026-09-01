<?php
/**
 * api/ha_control_public.php
 * Public HA control endpoint — pattern-authenticated, rate-limited.
 * Called by hacontrol.php with JSON body: {pattern, entity_id, action}
 */

session_start();
header('Content-Type: application/json');

require_once '../includes/db.php';

// ---------- helpers ----------
function jsonError(string $msg, int $code = 400, array $extra = []): void {
    http_response_code($code);
    echo json_encode(array_merge(['error' => $msg], $extra));
    exit;
}

function jsonOk(array $data = []): void {
    echo json_encode(array_merge(['success' => true], $data));
    exit;
}

function getSetting(PDO $pdo, string $key, string $default = ''): string {
    $st = $pdo->prepare("SELECT setting_value FROM ha_settings WHERE setting_key = ?");
    $st->execute([$key]);
    $row = $st->fetchColumn();
    return ($row !== false) ? $row : $default;
}

// ---------- rate limiting ----------
const MAX_ATTEMPTS  = 5;
const LOCKOUT_SECS  = 300; // 5 minutes

if (!isset($_SESSION['ctl_attempts'])) $_SESSION['ctl_attempts'] = 0;
if (!isset($_SESSION['ctl_locked_until'])) $_SESSION['ctl_locked_until'] = 0;

$now = time();

// Check if currently locked
if ($_SESSION['ctl_locked_until'] > $now) {
    $remaining = $_SESSION['ctl_locked_until'] - $now;
    jsonError("Too many failed attempts. Try again in {$remaining} seconds.", 429, [
        'locked'    => true,
        'remaining' => $remaining
    ]);
}

// Reset attempts if lockout has expired
if ($_SESSION['ctl_locked_until'] > 0 && $_SESSION['ctl_locked_until'] <= $now) {
    $_SESSION['ctl_attempts']    = 0;
    $_SESSION['ctl_locked_until'] = 0;
}

// ---------- parse request ----------
$raw = file_get_contents('php://input');
$body = json_decode($raw, true);

if (!$body || !isset($body['pattern'], $body['entity_id'], $body['action'])) {
    jsonError('Invalid request body.', 400);
}

$pattern   = trim((string)$body['pattern']);
$entity_id = trim((string)$body['entity_id']);
$action    = trim((string)$body['action']);

if (empty($pattern) || empty($entity_id) || empty($action)) {
    jsonError('Missing required fields.', 400);
}

// ---------- verify pattern ----------
// Stored as bcrypt hash of digit string e.g. "1235789"
// Default pattern: "1235" (hash stored on first save from admin)
$storedHash = getSetting($pdo, 'control_pattern', '');

// Use default '1235' if nothing saved, or if what's saved isn't a valid bcrypt hash
// substr used instead of str_starts_with for PHP 7.x compatibility
if (empty($storedHash) || substr($storedHash, 0, 4) !== '$2y$') {
    $storedHash = password_hash('1235', PASSWORD_BCRYPT);
}

// Require minimum 4 dots
if (strlen($pattern) < 4) {
    jsonError('Pattern too short — draw at least 4 dots.', 401, ['locked' => false, 'remaining' => MAX_ATTEMPTS - $_SESSION['ctl_attempts']]);
}

if (!password_verify($pattern, $storedHash)) {
    $_SESSION['ctl_attempts']++;
    $attemptsLeft = MAX_ATTEMPTS - $_SESSION['ctl_attempts'];

    if ($_SESSION['ctl_attempts'] >= MAX_ATTEMPTS) {
        $_SESSION['ctl_locked_until'] = $now + LOCKOUT_SECS;
        $_SESSION['ctl_attempts']     = 0;
        jsonError('Too many failed attempts. Locked for 5 minutes.', 429, [
            'locked'    => true,
            'remaining' => LOCKOUT_SECS
        ]);
    }

    jsonError('Incorrect pattern.', 401, [
        'locked'       => false,
        'remaining'    => $attemptsLeft,
        'attemptsLeft' => $attemptsLeft
    ]);
}

// Pattern correct — reset attempt counter
$_SESSION['ctl_attempts']    = 0;
$_SESSION['ctl_locked_until'] = 0;

// ---------- validate entity against DB ----------
$st = $pdo->prepare("SELECT entity_type FROM ha_entities WHERE entity_id = ? AND show_in_control = 1");
$st->execute([$entity_id]);
$entityRow = $st->fetch();

// Also allow pump entities (they might not be in ha_entities but we allow if action is valid)
// We look up loosely if not found
if (!$entityRow) {
    // Derive type from entity_id prefix
    $prefix = explode('.', $entity_id)[0] ?? '';
    $entityRow = ['entity_type' => $prefix];
}

$entityType = $entityRow['entity_type'];

// ---------- map action → HA service domain/service ----------
$domain  = null;
$service = null;

// Resolve the actual HA service from the requested action. The frontend sends
// 'toggle' for flip-style switches, or an explicit 'turn_on'/'turn_off' for
// momentary trigger switches (e.g. pump start/stop relays) where the current
// state must not matter - only 'toggle' would be wrong there.
function resolveOnOffService(string $action): string {
    if ($action === 'turn_on')  return 'turn_on';
    if ($action === 'turn_off') return 'turn_off';
    return 'toggle';
}

switch ($entityType) {
    case 'switch':
        $domain = 'switch';
        $service = resolveOnOffService($action);
        break;
    case 'light':
        $domain = 'light';
        $service = resolveOnOffService($action);
        break;
    case 'automation':
        $domain  = 'automation';
        $service = 'trigger';
        break;
    case 'scene':
        $domain  = 'scene';
        $service = 'turn_on';
        break;
    default:
        // Try to derive from entity_id
        $prefix = explode('.', $entity_id)[0];
        if (in_array($prefix, ['switch', 'light', 'automation', 'scene'])) {
            $domain  = $prefix;
            $service = in_array($prefix, ['switch', 'light']) ? resolveOnOffService($action) : 'trigger';
        } else {
            jsonError('Unsupported entity type for control.', 400);
        }
}

// ---------- load HA config ----------
$haUrl   = getSetting($pdo, 'ha_url', '');
$haToken = getSetting($pdo, 'ha_token', '');
$haEnabled = getSetting($pdo, 'ha_enabled', '1');

// Fallback to iot_config.php if DB empty (file uses define() constants, not variables)
if (empty($haUrl) || empty($haToken)) {
    $cfgFile = __DIR__ . '/../includes/iot_config.php';
    if (file_exists($cfgFile)) {
        @include_once $cfgFile;
        if (empty($haUrl)   && defined('HA_URL')   && HA_URL)   $haUrl   = HA_URL;
        if (empty($haToken) && defined('HA_TOKEN') && HA_TOKEN) $haToken = HA_TOKEN;
    }
}

if (empty($haUrl) || empty($haToken)) {
    jsonError('Home Assistant not configured.', 503);
}
if ($haEnabled !== '1') {
    jsonError('Home Assistant integration is disabled.', 503);
}

// ---------- call HA API ----------
$url     = rtrim($haUrl, '/') . "/api/services/{$domain}/{$service}";
$payload = json_encode(['entity_id' => $entity_id]);

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_TIMEOUT        => 10,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_HTTPHEADER     => [
        'Authorization: Bearer ' . $haToken,
        'Content-Type: application/json',
    ],
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    jsonError('Failed to reach Home Assistant: ' . $curlError, 502);
}

if ($httpCode >= 400) {
    $errBody = json_decode($response, true);
    $msg = $errBody['message'] ?? "HA returned HTTP {$httpCode}";
    jsonError($msg, 502);
}

jsonOk(['entity_id' => $entity_id, 'action' => $action, 'http_code' => $httpCode]);
