<?php
/**
 * Home Assistant Secure API Proxy
 * This script hides your HA URL and Token from the public.
 */

// Include private configuration
$configPath = '../includes/iot_config.php';

if (file_exists($configPath)) {
    require_once $configPath;
} else {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Configuration file not found. Please setup includes/iot_config.php']);
    exit;
}

header('Content-Type: application/json');

// Get the entity_id from the request
$entity_id = isset($_GET['entity']) ? $_GET['entity'] : null;

if (!$entity_id) {
    echo json_encode(['error' => 'No entity ID provided']);
    exit;
}

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, HA_URL . "/api/states/" . $entity_id);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer " . HA_TOKEN,
    "Content-Type: application/json"
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if (curl_errno($ch)) {
    echo json_encode(['error' => curl_error($ch)]);
} else {
    echo $response;
}

curl_close($ch);
?>
