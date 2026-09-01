<?php
require_once 'includes/db.php';
try {
    $galleryItems = $pdo->query("SELECT * FROM gallery WHERE show_in_gallery = 1 ORDER BY display_order ASC, created_at DESC")->fetchAll();
} catch (Exception $e) {
    $galleryItems = [];
}

$pageTitle = "Gallery | Manoranjan";
$pageDescription = "A visual look at Manoranjan's projects, workspace, and moments along the way.";
include 'includes/header.php';
?>

    <section class="min-h-screen pt-32 pb-24 px-6">
        <div class="container mx-auto">
            <div class="text-center mb-14" data-aos="fade-up">
                <h1 class="text-5xl font-extrabold mb-4"><span class="text-gradient">Gallery</span></h1>
                <p class="text-gray-400 text-lg max-w-2xl mx-auto">A visual look at my projects, workspace, and moments along the way.</p>
            </div>

            <?php if (!$galleryItems): ?>
            <div class="glass max-w-lg mx-auto p-12 rounded-3xl text-center text-gray-500" data-aos="fade-up">
                <i class="fa-solid fa-images text-4xl mb-4 opacity-40"></i>
                <p>Gallery is empty right now — check back soon.</p>
            </div>
            <?php else: ?>

            <!-- Filter Tabs -->
            <div class="flex justify-center gap-3 mb-10" data-aos="fade-up">
                <button class="gallery-filter active px-6 py-2.5 rounded-full text-sm font-bold transition bg-blue-600 text-white" data-filter="all">All</button>
                <button class="gallery-filter px-6 py-2.5 rounded-full text-sm font-bold transition bg-gray-900 border border-gray-800 text-gray-400 hover:text-white" data-filter="photo">Photos</button>
                <button class="gallery-filter px-6 py-2.5 rounded-full text-sm font-bold transition bg-gray-900 border border-gray-800 text-gray-400 hover:text-white" data-filter="project">Projects</button>
            </div>

            <!-- Grid -->
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4" id="gallery-grid">
                <?php foreach ($galleryItems as $i => $item): ?>
                <div class="gallery-item group relative rounded-2xl overflow-hidden border border-gray-800 aspect-square cursor-pointer"
                     data-category="<?php echo $item['category']; ?>"
                     data-aos="zoom-in" data-aos-delay="<?php echo ($i % 8) * 50; ?>"
                     onclick="openLightbox('<?php echo htmlspecialchars($item['image_url'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($item['title'] ?? '', ENT_QUOTES); ?>')">
                    <img src="<?php echo htmlspecialchars($item['image_url']); ?>" loading="lazy"
                         class="w-full h-full object-cover group-hover:scale-110 transition duration-500">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/0 to-black/0 opacity-0 group-hover:opacity-100 transition flex items-end p-4">
                        <div>
                            <span class="text-[10px] px-2 py-0.5 rounded-full <?php echo $item['category'] === 'project' ? 'bg-purple-500/30 text-purple-300' : 'bg-blue-500/30 text-blue-300'; ?>">
                                <?php echo ucfirst($item['category']); ?>
                            </span>
                            <?php if ($item['title']): ?>
                            <p class="text-white text-sm font-bold mt-1"><?php echo htmlspecialchars($item['title']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Lightbox -->
    <div id="lightbox" class="hidden fixed inset-0 z-[100] bg-black/95 backdrop-blur-sm flex items-center justify-center p-6" onclick="closeLightbox()">
        <button class="absolute top-6 right-6 text-white text-3xl hover:text-blue-400 transition" onclick="closeLightbox()">
            <i class="fa-solid fa-xmark"></i>
        </button>
        <div class="max-w-4xl max-h-[85vh] flex flex-col items-center">
            <img id="lightbox-img" src="" class="max-w-full max-h-[75vh] rounded-2xl object-contain shadow-2xl">
            <p id="lightbox-title" class="text-white text-lg font-bold mt-4"></p>
        </div>
    </div>

    <script>
        // Filter
        const filterBtns = document.querySelectorAll('.gallery-filter');
        const galleryItems = document.querySelectorAll('.gallery-item');
        filterBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                filterBtns.forEach(b => {
                    b.classList.remove('active', 'bg-blue-600', 'text-white');
                    b.classList.add('bg-gray-900', 'border', 'border-gray-800', 'text-gray-400');
                });
                btn.classList.add('active', 'bg-blue-600', 'text-white');
                btn.classList.remove('bg-gray-900', 'border', 'border-gray-800', 'text-gray-400');

                const filter = btn.dataset.filter;
                galleryItems.forEach(item => {
                    item.style.display = (filter === 'all' || item.dataset.category === filter) ? '' : 'none';
                });
            });
        });

        // Lightbox
        function openLightbox(src, title) {
            document.getElementById('lightbox-img').src = src;
            document.getElementById('lightbox-title').textContent = title;
            document.getElementById('lightbox').classList.remove('hidden');
        }
        function closeLightbox() {
            document.getElementById('lightbox').classList.add('hidden');
        }
    </script>

<?php include 'includes/footer.php'; ?>
