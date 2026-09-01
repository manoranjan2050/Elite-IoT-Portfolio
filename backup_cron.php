<?php
/**
 * Automated database backup.
 * Safe to run via CLI cron with no arguments. If hit over HTTP, requires a secret key
 * so it can't be triggered by anyone browsing the site.
 */

define('BACKUP_SECRET_KEY', 'LR0ZOaBlNXKcmfjIVDhxrqkUAvno6E5J');

$isCli = (php_sapi_name() === 'cli');
if (!$isCli) {
    $key = $_GET['key'] ?? '';
    if (!hash_equals(BACKUP_SECRET_KEY, $key)) {
        http_response_code(403);
        exit('Forbidden');
    }
}

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$backupDir = dirname(__DIR__) . '/backups';
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0700, true);
}

$timestamp = date('Y-m-d_His');
$sqlFile = "$backupDir/manoranjan_dev_$timestamp.sql";
$gzFile = "$sqlFile.gz";

try {
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

    $out = fopen($sqlFile, 'w');
    fwrite($out, "-- manoranjan.dev database backup\n-- Generated: $timestamp\n\n");
    fwrite($out, "SET FOREIGN_KEY_CHECKS=0;\n\n");

    foreach ($tables as $table) {
        $createRow = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_ASSOC);
        $createSql = $createRow['Create Table'] ?? null;
        if (!$createSql) continue;

        fwrite($out, "DROP TABLE IF EXISTS `$table`;\n");
        fwrite($out, $createSql . ";\n\n");

        $rowCount = (int) $pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
        if ($rowCount === 0) continue;

        $stmt = $pdo->query("SELECT * FROM `$table`");
        $batch = [];
        $batchSize = 200;
        $columns = null;

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if ($columns === null) {
                $columns = array_map(fn($c) => "`$c`", array_keys($row));
            }
            $values = array_map(function ($v) use ($pdo) {
                if ($v === null) return 'NULL';
                return $pdo->quote($v);
            }, array_values($row));
            $batch[] = '(' . implode(',', $values) . ')';

            if (count($batch) >= $batchSize) {
                fwrite($out, "INSERT INTO `$table` (" . implode(',', $columns) . ") VALUES\n" . implode(",\n", $batch) . ";\n\n");
                $batch = [];
            }
        }
        if ($batch) {
            fwrite($out, "INSERT INTO `$table` (" . implode(',', $columns) . ") VALUES\n" . implode(",\n", $batch) . ";\n\n");
        }
    }

    fwrite($out, "SET FOREIGN_KEY_CHECKS=1;\n");
    fclose($out);

    // gzip it
    $data = file_get_contents($sqlFile);
    $gz = gzopen($gzFile, 'w9');
    gzwrite($gz, $data);
    gzclose($gz);
    unlink($sqlFile);

    // Rotate: keep the last 14 backups
    $existing = glob("$backupDir/manoranjan_dev_*.sql.gz");
    sort($existing);
    while (count($existing) > 14) {
        $old = array_shift($existing);
        @unlink($old);
    }

    $sizeKb = round(filesize($gzFile) / 1024, 1);
    $msg = "Backup OK: manoranjan_dev_$timestamp.sql.gz ({$sizeKb} KB), " . count($tables) . " tables.";
    if (function_exists('sendTelegram')) {
        sendTelegram("manoranjan.dev DB backup\n" . $msg);
    }
    echo $msg . "\n";
} catch (Exception $e) {
    $errMsg = "Backup FAILED: " . $e->getMessage();
    if (function_exists('sendTelegram')) {
        sendTelegram("manoranjan.dev DB backup\n" . $errMsg);
    }
    echo $errMsg . "\n";
    if (isset($out) && is_resource($out)) fclose($out);
    if (file_exists($sqlFile)) @unlink($sqlFile);
}
