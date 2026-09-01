<?php

/**
 * Example integration — how OpenPharma (or any product) uses KavachClient.
 * Copy KavachClient.php into your app (e.g. includes/) and adapt this file.
 */

require __DIR__.'/KavachClient.php';

$kavach = new KavachClient([
    'server' => 'http://127.0.0.1:8000',          // production: https://kavach.yourdomain.in
    'product' => 'openpharma',
    'public_key' => 'PASTE_PUBLIC_KEY_HERE',       // from: php artisan kavach:keys
    'storage_dir' => __DIR__.'/storage',           // writable, blocked from web access
    'app_version' => '1.0.0',                      // your app's current version
]);

/* ---- 1. Gate the app (run on every page load / app boot) ---------------- */

$state = $kavach->check();

if (! $state['licensed']) {
    // Show your activation form. When submitted:
    // try {
    //     $kavach->activate($_POST['license_key']);
    //     header('Location: /');
    // } catch (KavachException $e) {
    //     $error = $e->getMessage();   // e.g. "This key is already used on ..."
    // }
    exit('Not licensed: '.$state['reason']);
}

/* ---- 2. Feature gating --------------------------------------------------- */

if ($kavach->isPro()) {
    // enable pro-only features (advanced reports, multi-store, ...)
}

if ($kavach->isTrial()) {
    echo 'Trial: '.$kavach->daysLeft().' days left. Buy a plan to continue.';
}

/* ---- 3. Updates (run daily via cron, or behind an "Update" admin button) - */

$update = $kavach->checkUpdate();

if ($update['update_available']) {
    echo "New version {$update['latest_version']} available!\n";
    echo $update['changelog']."\n";

    $zip = $kavach->downloadUpdate($update);            // verified sha256
    $kavach->installUpdate($zip, dirname(__DIR__), [
        '.env',
        'config.php',
        'storage',
        'uploads',
    ]);
    // then run your DB migration script if the release includes one
    echo "Updated to {$update['latest_version']}\n";
}
