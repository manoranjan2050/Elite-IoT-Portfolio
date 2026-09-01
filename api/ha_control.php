<?php
/**
 * Home Assistant Control API
 * Handles POST requests to toggle switches, lights, trigger automations and scenes
 */

session_start();
header('Content-Type: application/json');

// Must be logged in
if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Load HA config
$configPath = '../includes/iot_config.php';
if (!file_exists($configPath)) {
    echo json_encode(['error' => 'HA config not found. Set it in Admin → HA Settings.']);
    exit;
}
require_once $configPath;

// Parse JSON body
$body = json_decode(file_get_contents('php://input'), true);
if (!$body) {
    echo json_encode(['error' => 'Invalid request body']);
    exit;
}

$entity_id   = isset($body['entity_id'])   ? trim($body['entity_id'])   : null;
$action      = isset($body['action'])      ? trim($body['action'])      : 'toggle';
$entity_type = isset($body['entity_type']) ? trim($body['entity_type']) : null;

if (!$entity_id) {
    echo json_encode(['error' => 'No entity_id provided']);
    exit;
}

// Determine HA service domain and service name
$domain  = strtok($entity_id, '.');
$service = $action;

// Map action to HA service call
if ($action === 'toggle') {
    $service = 'toggle';
} elseif ($action === 'turn_on') {
    $service = 'turn_on';
} elseif ($action === 'turn_off') {
    $service = 'turn_off';
} elseif ($action === 'trigger' && $entity_type === 'automation') {
    $domain  = 'automation';
    $service = 'trigger';
} elseif ($action === 'turn_on' && $entity_type === 'scene') {
    $domain  = 'scene';
    $service = 'turn_on';
} elseif ($entity_type === 'scene') {
    $domain  = 'scene';
    $service = 'turn_on';
}

$url = HA_URL . "/api/services/" . $domain . "/" . $service;

$payload = json_encode(['entity_id' => $entity_id]);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer " . HA_TOKEN,
    "Content-Type: application/json"
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response  = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_err  = curl_error($ch);
curl_close($ch);

if ($curl_err) {
    echo json_encode(['error' => 'cURL error: ' . $curl_err]);
    exit;
}

if ($http_code >= 200 && $http_code < 300) {
    echo json_encode(['success' => true, 'code' => $http_code]);
} else {
    echo json_encode(['error' => 'HA returned HTTP ' . $http_code, 'response' => $response]);
}
?>
