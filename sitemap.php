<?php
require_once 'includes/db.php';
header('Content-Type: application/xml; charset=utf-8');

$baseUrl = 'https://manoranjan.dev';

$staticPages = [
    ['loc' => '/', 'priority' => '1.0', 'changefreq' => 'weekly'],
    ['loc' => '/about.php', 'priority' => '0.8', 'changefreq' => 'monthly'],
    ['loc' => '/projects.php', 'priority' => '0.9', 'changefreq' => 'weekly'],
    ['loc' => '/gallery.php', 'priority' => '0.6', 'changefreq' => 'monthly'],
    ['loc' => '/contact.php', 'priority' => '0.7', 'changefreq' => 'yearly'],
    ['loc' => '/power.php', 'priority' => '0.5', 'changefreq' => 'daily'],
];

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

foreach ($staticPages as $p) {
    echo "  <url>\n";
    echo "    <loc>{$baseUrl}{$p['loc']}</loc>\n";
    echo "    <changefreq>{$p['changefreq']}</changefreq>\n";
    echo "    <priority>{$p['priority']}</priority>\n";
    echo "  </url>\n";
}

try {
    $blogs = $pdo->query("SELECT id, created_at FROM blogs ORDER BY created_at DESC")->fetchAll();
    foreach ($blogs as $b) {
        echo "  <url>\n";
        echo "    <loc>{$baseUrl}/post.php?id={$b['id']}</loc>\n";
        echo "    <lastmod>" . date('Y-m-d', strtotime($b['created_at'])) . "</lastmod>\n";
        echo "    <priority>0.6</priority>\n";
        echo "  </url>\n";
    }
} catch (Exception $e) {}

echo '</urlset>';
