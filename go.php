<?php
/**
 * Click-tracking redirector for project CTA buttons (Play Store / Demo / GitHub / Download).
 * Usage: go.php?id=5&type=playstore
 */
require_once 'includes/db.php';

$id   = (int) ($_GET['id'] ?? 0);
$type = $_GET['type'] ?? 'project';

$allowedTypes = ['playstore', 'project', 'github', 'download'];
if (!in_array($type, $allowedTypes)) {
    $type = 'project';
}

$columnMap = [
    'playstore' => 'playstore_link',
    'project'   => 'project_link',
    'github'    => 'github_link',
    'download'  => 'download_link',
];
$column = $columnMap[$type];

$stmt = $pdo->prepare("SELECT {$column} AS target FROM projects WHERE id = ?");
$stmt->execute([$id]);
$row = $stmt->fetch();

if (!$row || !$row['target']) {
    header("Location: /projects.php");
    exit();
}

// Track the click (best-effort, never blocks the redirect)
try {
    $pdo->prepare("UPDATE projects SET click_count = click_count + 1 WHERE id = ?")->execute([$id]);
} catch (Exception $e) {}

header("Location: " . $row['target']);
exit();
