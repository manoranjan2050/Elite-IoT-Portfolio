<?php
/**
 * Renders one project card. Expects $project (array) and optional $aosDelay (int) in scope.
 */
$isComingSoon = ($project['status'] ?? 'published') === 'coming_soon';
$cardImage = $project['banner_image'] ?: $project['image_url'];
?>
<div class="glass rounded-2xl overflow-hidden group hover:scale-[1.02] transition duration-300" data-aos="fade-up" <?php if (isset($aosDelay)) echo 'data-aos-delay="' . (int)$aosDelay . '"'; ?>>
    <div class="h-48 bg-gray-800 relative overflow-hidden">
        <?php if ($cardImage): ?>
            <img src="<?php echo htmlspecialchars($cardImage); ?>" alt="<?php echo htmlspecialchars($project['title']); ?>" class="w-full h-full object-cover group-hover:scale-110 transition duration-500 <?php echo $isComingSoon ? 'grayscale opacity-60' : ''; ?>">
        <?php else: ?>
            <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-gray-800 to-gray-900">
                <i class="fa-solid fa-folder-open text-5xl text-gray-700"></i>
            </div>
        <?php endif; ?>

        <div class="absolute top-4 right-4 bg-blue-600 text-white text-xs font-bold px-3 py-1 rounded-full uppercase">
            <?php echo htmlspecialchars($project['category']); ?>
        </div>

        <?php if ($isComingSoon): ?>
        <div class="absolute top-4 left-4 bg-orange-500/90 text-white text-[10px] font-bold px-3 py-1 rounded-full uppercase flex items-center gap-1.5">
            <i class="fa-solid fa-clock text-[9px]"></i> Coming Soon
        </div>
        <?php else: ?>
        <div class="absolute top-4 left-4 bg-green-500/90 text-white text-[10px] font-bold px-3 py-1 rounded-full uppercase flex items-center gap-1.5">
            <i class="fa-solid fa-circle-check text-[9px]"></i> Published
        </div>
        <?php endif; ?>
    </div>
    <div class="p-6">
        <h3 class="text-xl font-bold mb-2"><?php echo htmlspecialchars($project['title']); ?></h3>
        <p class="text-gray-400 text-sm mb-6 line-clamp-3"><?php echo htmlspecialchars($project['description']); ?></p>

        <?php if ($isComingSoon): ?>
        <div class="inline-flex items-center gap-2 text-gray-500 text-sm font-bold">
            <i class="fa-solid fa-hourglass-half"></i> Launching soon
        </div>
        <?php else: ?>
        <div class="flex flex-wrap gap-3">
            <?php if (!empty($project['playstore_link'])): ?>
            <a href="go.php?id=<?php echo $project['id']; ?>&type=playstore" target="_blank"
               class="relative inline-flex items-center gap-2 bg-gradient-to-r from-green-600 to-emerald-500 hover:from-green-500 hover:to-emerald-400 text-white text-xs font-bold px-4 py-2.5 rounded-xl transition transform hover:-translate-y-0.5 shadow-lg shadow-green-500/20 btn-glow">
                <i class="fa-brands fa-google-play"></i> Get on Play
            </a>
            <?php endif; ?>
            <?php if (!empty($project['download_link'])): ?>
            <a href="go.php?id=<?php echo $project['id']; ?>&type=download" target="_blank"
               class="inline-flex items-center gap-2 bg-gray-800 hover:bg-gray-700 text-white text-xs font-bold px-4 py-2.5 rounded-xl transition">
                <i class="fa-solid fa-download"></i> Download
            </a>
            <?php endif; ?>
            <?php if (!empty($project['project_link']) && empty($project['playstore_link'])): ?>
            <a href="go.php?id=<?php echo $project['id']; ?>&type=project" target="_blank"
               class="inline-flex items-center gap-2 text-blue-400 hover:text-white transition text-sm font-bold">
                <i class="fa-solid fa-link"></i> Visit / Live
            </a>
            <?php endif; ?>
            <?php if (!empty($project['github_link'])): ?>
            <a href="go.php?id=<?php echo $project['id']; ?>&type=github" target="_blank"
               class="inline-flex items-center gap-2 text-gray-400 hover:text-white transition text-sm font-bold">
                <i class="fa-brands fa-github"></i> Code
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>
