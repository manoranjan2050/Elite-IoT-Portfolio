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

/**
 * Tracks a unique visit per session in the database
 */
function trackVisit($pdo) {
    // Only count once per session
    if (isset($_SESSION['has_visited_today'])) return;

    $today = date('Y-m-d');
    try {
        $stmt = $pdo->prepare("INSERT INTO site_visits (visit_date, visit_count) 
                               VALUES (?, 1) 
                               ON DUPLICATE KEY UPDATE visit_count = visit_count + 1");
        $stmt->execute([$today]);
        $_SESSION['has_visited_today'] = true;
    } catch (PDOException $e) {
        // If table doesn't exist, create it (silent fail or auto-create)
        if ($e->getCode() == '42S02') { // Table not found
            $pdo->exec("CREATE TABLE IF NOT EXISTS site_visits (
                visit_date DATE PRIMARY KEY,
                visit_count INT DEFAULT 0
            )");
            // Retry once
            $stmt = $pdo->prepare("INSERT INTO site_visits (visit_date, visit_count) VALUES (?, 1)");
            $stmt->execute([$today]);
            $_SESSION['has_visited_today'] = true;
        }
    }
}

/**
 * Gets visit statistics for the admin panel
 */
function getVisitStats($pdo) {
    $stats = [
        'today' => 0,
        'total' => 0,
        'last_7_days' => 0
    ];

    try {
        // Today's visits
        $stmt = $pdo->prepare("SELECT visit_count FROM site_visits WHERE visit_date = ?");
        $stmt->execute([date('Y-m-d')]);
        $row = $stmt->fetch();
        $stats['today'] = $row ? $row['visit_count'] : 0;

        // Total visits
        $stats['total'] = $pdo->query("SELECT SUM(visit_count) FROM site_visits")->fetchColumn() ?: 0;

        // Last 7 days
        $stmt = $pdo->prepare("SELECT SUM(visit_count) FROM site_visits WHERE visit_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
        $stmt->execute();
        $stats['last_7_days'] = $stmt->fetchColumn() ?: 0;
    } catch (PDOException $e) {
        // Table might not exist yet
    }

    return $stats;
}

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
 * Send an email via SMTP (PHPMailer + Zoho, config in mail_config.php)
 */
function sendMail($to, $subject, $htmlBody, $replyTo = null) {
    $configPath = __DIR__ . '/mail_config.php';
    if (!file_exists($configPath)) return false;
    require_once $configPath;

    require_once __DIR__ . '/PHPMailer/Exception.php';
    require_once __DIR__ . '/PHPMailer/PHPMailer.php';
    require_once __DIR__ . '/PHPMailer/SMTP.php';

    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_SECURE === 'ssl'
            ? \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS
            : \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;

        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($to);
        if ($replyTo) {
            $mail->addReplyTo($replyTo);
        }

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;
        $mail->AltBody = strip_tags($htmlBody);

        $mail->send();
        return true;
    } catch (\Exception $e) {
        error_log('sendMail failed: ' . $mail->ErrorInfo);
        return false;
    }
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
