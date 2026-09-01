<?php
require_once 'includes/db.php';

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM blogs WHERE id = ?");
$stmt->execute([$id]);
$post = $stmt->fetch();

if (!$post) {
    header("Location: /404.php");
    exit();
}

$related = $pdo->prepare("SELECT * FROM blogs WHERE id != ? ORDER BY created_at DESC LIMIT 3");
$related->execute([$id]);
$relatedPosts = $related->fetchAll();

$pageTitle = htmlspecialchars($post['title']) . " | Manoranjan";
$pageDescription = mb_substr(strip_tags($post['content']), 0, 155);
include 'includes/header.php';
?>

    <article class="min-h-screen pt-32 pb-24 px-6">
        <div class="container mx-auto">
            <div class="max-w-3xl mx-auto" data-aos="fade-up">
                <a href="/index.php#blog" class="text-blue-400 hover:text-white text-sm font-bold mb-6 inline-flex items-center gap-2 transition">
                    <i class="fa-solid fa-arrow-left"></i> Back to Insights
                </a>

                <span class="text-xs font-bold text-blue-500 uppercase tracking-widest mb-3 block"><?php echo htmlspecialchars($post['category']); ?></span>
                <h1 class="text-4xl md:text-5xl font-extrabold mb-6 leading-tight"><?php echo htmlspecialchars($post['title']); ?></h1>
                <p class="text-gray-500 text-sm mb-10"><i class="fa-solid fa-calendar mr-2"></i><?php echo date('F j, Y', strtotime($post['created_at'])); ?></p>

                <?php if ($post['image_url']): ?>
                <div class="rounded-3xl overflow-hidden mb-10 glass">
                    <img src="<?php echo htmlspecialchars($post['image_url']); ?>" alt="<?php echo htmlspecialchars($post['title']); ?>" class="w-full h-auto object-cover">
                </div>
                <?php endif; ?>

                <div class="prose prose-invert prose-lg max-w-none text-gray-300 leading-relaxed space-y-6">
                    <?php echo $post['content']; ?>
                </div>

                <div class="mt-16 pt-8 border-t border-gray-800 flex flex-wrap gap-4">
                    <a href="/contact.php" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-full font-bold transition">
                        <i class="fa-solid fa-comment mr-2"></i> Get in Touch
                    </a>
                    <a href="/projects.php" class="border border-gray-700 hover:bg-gray-800 text-white px-6 py-3 rounded-full font-bold transition">
                        View My Projects
                    </a>
                </div>
            </div>

            <?php if ($relatedPosts): ?>
            <div class="max-w-5xl mx-auto mt-20">
                <h2 class="text-2xl font-bold mb-8 text-center" data-aos="fade-up">More Insights</h2>
                <div class="grid md:grid-cols-3 gap-8">
                    <?php foreach ($relatedPosts as $rp): ?>
                    <a href="post.php?id=<?php echo $rp['id']; ?>" class="glass rounded-2xl overflow-hidden group border border-gray-800 block" data-aos="fade-up">
                        <div class="h-40 overflow-hidden">
                            <?php if ($rp['image_url']): ?>
                                <img src="<?php echo htmlspecialchars($rp['image_url']); ?>" class="w-full h-full object-cover group-hover:scale-110 transition duration-500">
                            <?php else: ?>
                                <div class="w-full h-full bg-gradient-to-br from-purple-900/40 to-blue-900/40 flex items-center justify-center">
                                    <i class="fa-solid fa-newspaper text-3xl text-gray-700"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="p-5">
                            <h3 class="font-bold group-hover:text-blue-400 transition"><?php echo htmlspecialchars($rp['title']); ?></h3>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </article>

<?php include 'includes/footer.php'; ?>
