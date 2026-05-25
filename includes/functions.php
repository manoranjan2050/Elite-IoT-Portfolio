<?php
session_start();

// Session timeout: 1 hour (3600 seconds)
$timeout = 3600;
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
    session_unset();
    session_destroy();
    header("Location: ../login.php?timeout=1");
    exit();
}
$_SESSION['last_activity'] = time();

function isLoggedIn() {
    return isset($_SESSION['admin_id']);
}

function redirectToLogin() {
    if (!isLoggedIn()) {
        header("Location: ../login.php");
        exit();
    }
}

function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

/**
 * Send a notification to Telegram
 */
function sendTelegram($message) {
    $configPath = __DIR__ . '/telegram_config.php';
    if (!file_exists($configPath)) return false;
    
    require_once $configPath;
    
    $url = "https://api.telegram.org/bot" . TELEGRAM_BOT_TOKEN . "/sendMessage";
    $data = [
        'chat_id' => TELEGRAM_CHAT_ID,
        'text' => $message,
        'parse_mode' => 'HTML'
    ];

    $options = [
        'http' => [
            'method'  => 'POST',
            'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
            'content' => http_build_query($data),
        ]
    ];
    $context  = stream_context_create($options);
    return file_get_contents($url, false, $context);
}
?>
