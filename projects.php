<?php
require_once 'includes/db.php';

$categoryFilter = $_GET['category'] ?? 'all';
try {
    if ($categoryFilter !== 'all') {
        $stmt = $pdo->prepare("SELECT * FROM projects WHERE category = ? ORDER BY created_at DESC");
        $stmt->execute([$categoryFilter]);
        $projects = $stmt->fetchAll();
    } else {
        $projects = $pdo->query("SELECT * FROM projects ORDER BY created_at DESC")->fetchAll();
    }
    $categories = $pdo->query("SELECT DISTINCT category FROM projects ORDER BY category ASC")->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    $projects = [];
    $categories = [];
}

$pageTitle = "Projects | Manoranjan";
$pageDescription = "Explore Manoranjan's Android apps, websites, and business tools — IDPhotoCraft, Calc2Pay, ThermalDesk, MTP Code, and more.";
include 'includes/header.php';
?>

    <section class="min-h-screen pt-32 pb-24 px-6">
        <div class="container mx-auto">
            <div class="text-center mb-14" data-aos="fade-up">
                <h1 class="text-5xl font-extrabold mb-4"><span class="text-gradient">All Projects</span></h1>
                <p class="text-gray-400 text-lg max-w-2xl mx-auto">Apps, websites, and tools I've built across coding, IoT, trading, and business.</p>
            </div>

            <!-- Filter Tabs -->
            <div class="flex flex-wrap justify-center gap-3 mb-14" data-aos="fade-up">
                <a href="projects.php" class="px-6 py-2.5 rounded-full text-sm font-bold transition <?php echo $categoryFilter === 'all' ? 'bg-blue-600 text-white' : 'bg-gray-900 border border-gray-800 text-gray-400 hover:text-white'; ?>">All</a>
                <?php foreach ($categories as $cat): ?>
                <a href="projects.php?category=<?php echo urlencode($cat); ?>" class="px-6 py-2.5 rounded-full text-sm font-bold transition <?php echo $categoryFilter === $cat ? 'bg-blue-600 text-white' : 'bg-gray-900 border border-gray-800 text-gray-400 hover:text-white'; ?>">
                    <?php echo htmlspecialchars($cat); ?>
                </a>
                <?php endforeach; ?>
            </div>

            <?php if (!$projects): ?>
            <div class="glass max-w-lg mx-auto p-12 rounded-3xl text-center text-gray-500">
                <i class="fa-solid fa-folder-open text-4xl mb-4 opacity-40"></i>
                <p>No projects in this category yet.</p>
            </div>
            <?php else: ?>

            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php foreach ($projects as $i => $project): $aosDelay = ($i % 6) * 60; include 'includes/project-card.php'; endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </section>

<?php include 'includes/footer.php'; ?>
