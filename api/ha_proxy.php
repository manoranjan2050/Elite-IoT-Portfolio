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

// --- TELEGRAM ALERT TRIGGER ---
if ($http_code == 200 && $response) {
    $data = json_decode($response, true);
    if (isset($data['state'])) {
        $state = (float)$data['state'];
        
        // Example: Shop Battery Temp Alert
        if ($entity_id == 'sensor.flin_energy_battery_temperature' && $state > 37) {
            require_once '../includes/functions.php';
            // Simple session-based anti-spam (preventing alerts every 15s)
            if (!isset($_SESSION['last_alert_time']) || (time() - $_SESSION['last_alert_time'] > 1800)) {
                $msg = "<b>⚠️ CRITICAL POWER ALERT</b>\n\n";
                $msg .= "Site: <b>Shop Power Station</b>\n";
                $msg .= "Type: <b>High Thermal Load</b>\n";
                $msg .= "Temp: <b>" . $state . "°C</b>\n";
                $msg .= "Time: " . date('Y-m-d H:i:s');
                sendTelegram($msg);
                $_SESSION['last_alert_time'] = time();
            }
        }
    }
}
// ------------------------------

if (curl_errno($ch)) {
    echo json_encode(['error' => curl_error($ch)]);
} else {
    echo $response;
}

curl_close($ch);
?>
