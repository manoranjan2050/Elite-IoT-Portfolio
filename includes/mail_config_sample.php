<?php
/**
 * Sample Mail (SMTP) Configuration
 *
 * Instructions:
 * 1. Copy this file to 'mail_config.php'
 * 2. Fill in your SMTP credentials below
 */

define('SMTP_HOST', 'smtp.zoho.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your_email@example.com');
define('SMTP_PASSWORD', 'your_smtp_password');
define('SMTP_FROM_EMAIL', 'your_email@example.com');
define('SMTP_FROM_NAME', 'Your Name');
define('SMTP_SECURE', 'tls'); // 'tls' or 'ssl'

// Where contact form notifications should be delivered
define('CONTACT_NOTIFY_EMAIL', 'your_email@example.com');
