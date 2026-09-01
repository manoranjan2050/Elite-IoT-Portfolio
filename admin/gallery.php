<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
redirectToLogin();

$pageTitle = 'Gallery';
$flash = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_image'])) {
        $title    = sanitize($_POST['title'] ?? '');
        $category = ($_POST['category'] ?? 'photo') === 'project' ? 'project' : 'photo';

        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $allowed)) {
                $filename = 'gallery_' . time() . '_' . mt_rand(1000, 9999) . '.' . $ext;
                $dest = '../uploads/gallery/' . $filename;
                if (!is_dir('../uploads/gallery')) {
                    mkdir('../uploads/gallery', 0755, true);
                }
                if (move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
                    $stmt = $pdo->prepare("INSERT INTO gallery (title, image_url, category) VALUES (?, ?, ?)");
                    $stmt->execute([$title, 'uploads/gallery/' . $filename, $category]);
                    $flash = ['type' => 'success', 'text' => 'Image added to gallery.'];
                } else {
                    $flash = ['type' => 'error', 'text' => 'Failed to upload image.'];
                }
            } else {
                $flash = ['type' => 'error', 'text' => 'Invalid image format. Use JPG, PNG, GIF or WebP.'];
            }
        } else {
            $flash = ['type' => 'error', 'text' => 'Please choose an image to upload.'];
        }
    } elseif (isset($_POST['delete_id'])) {
        $id = (int) $_POST['delete_id'];
        $stmt = $pdo->prepare("SELECT image_url FROM gallery WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if ($row && file_exists('../' . $row['image_url'])) {
            @unlink('../' . $row['image_url']);
        }
        $pdo->prepare("DELETE FROM gallery WHERE id = ?")->execute([$id]);
        $flash = ['type' => 'success', 'text' => 'Image deleted.'];
    } elseif (isset($_POST['toggle_visible_id'])) {
        $id = (int) $_POST['toggle_visible_id'];
        $pdo->prepare("UPDATE gallery SET show_in_gallery = 1 - show_in_gallery WHERE id = ?")->execute([$id]);
        // AJAX request from the card toggle - just confirm, no full page flash needed
        if (!empty($_POST['ajax'])) {
            $stmt = $pdo->prepare("SELECT show_in_gallery FROM gallery WHERE id = ?");
            $stmt->execute([$id]);
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'show_in_gallery' => (int) $stmt->fetchColumn()]);
            exit;
        }
    }
}

$items = $pdo->query("SELECT * FROM gallery ORDER BY display_order ASC, created_at DESC")->fetchAll();
?>
<?php include 'includes/admin_head.php'; ?>
<?php include 'includes/sidebar.php'; ?>

    <?php if ($flash): ?>
    <div class="mb-6 px-4 py-3 rounded-xl text-sm font-medium flex items-center gap-3
        <?php echo $flash['type'] === 'success' ? 'bg-green-500/10 text-green-400 border border-green-500/20' : 'bg-red-500/10 text-red-400 border border-red-500/20'; ?>">
        <i class="fa-solid <?php echo $flash['type'] === 'success' ? 'fa-circle-check' : 'fa-circle-xmark'; ?>"></i>
        <?php echo htmlspecialchars($flash['text']); ?>
    </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Upload Form -->
        <div class="glass-card rounded-2xl border border-gray-800 p-6 h-fit">
            <h3 class="font-bold text-white mb-4 flex items-center gap-2">
                <i class="fa-solid fa-cloud-arrow-up text-blue-400"></i> Add Image
            </h3>
            <form method="POST" enctype="multipart/form-data" class="space-y-4">
                <input type="hidden" name="add_image" value="1">
                <div>
                    <label class="block text-xs text-gray-500 uppercase font-bold mb-1.5">Image</label>
                    <input type="file" name="image" accept="image/*" required
                        class="w-full text-sm text-gray-300 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:bg-blue-600 file:text-white file:text-xs file:font-bold">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 uppercase font-bold mb-1.5">Title (optional)</label>
                    <input type="text" name="title" placeholder="e.g. IDPhotoCraft screenshot"
                        class="w-full px-3 py-2.5 bg-gray-800 border border-gray-700 rounded-xl text-white text-sm placeholder-gray-600">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 uppercase font-bold mb-1.5">Category</label>
                    <select name="category" class="w-full px-3 py-2.5 bg-gray-800 border border-gray-700 rounded-xl text-white text-sm">
                        <option value="photo">Photo</option>
                        <option value="project">Project</option>
                    </select>
                </div>
                <button type="submit" class="w-full px-6 py-2.5 bg-blue-600 hover:bg-blue-500 text-white font-bold rounded-xl transition text-sm flex items-center justify-center gap-2">
                    <i class="fa-solid fa-plus"></i> Add to Gallery
                </button>
            </form>
        </div>

        <!-- Gallery Grid -->
        <div class="lg:col-span-2">
            <div class="glass-card rounded-2xl border border-gray-800 p-6">
                <h3 class="font-bold text-white mb-4"><?php echo count($items); ?> item(s)</h3>
                <?php if (!$items): ?>
                <p class="text-gray-500 text-sm text-center py-10">No images yet. Upload one to get started.</p>
                <?php else: ?>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                    <?php foreach ($items as $item): ?>
                    <div class="relative group rounded-xl overflow-hidden border border-gray-800 aspect-square" id="gallery-card-<?php echo $item['id']; ?>">
                        <img src="../<?php echo htmlspecialchars($item['image_url']); ?>" class="w-full h-full object-cover <?php echo $item['show_in_gallery'] ? '' : 'opacity-40 grayscale'; ?>" id="gallery-img-<?php echo $item['id']; ?>">

                        <!-- Visibility toggle - always visible, top-right (z-20 so the hover overlay below can't swallow its clicks).
                             The whole pill is a <label> (not just the tiny switch) so the tap target is comfortably large on mobile. -->
                        <label class="absolute top-1.5 right-1.5 z-20 flex items-center gap-1.5 px-2 py-1.5 rounded-lg bg-black/70 backdrop-blur-sm cursor-pointer">
                            <span class="text-[8px] font-bold uppercase <?php echo $item['show_in_gallery'] ? 'text-green-400' : 'text-gray-500'; ?>" id="gallery-visible-label-<?php echo $item['id']; ?>">
                                <?php echo $item['show_in_gallery'] ? 'Live' : 'Hidden'; ?>
                            </span>
                            <span class="toggle-switch toggle-switch-sm">
                                <input type="checkbox" onchange="toggleGalleryVisible(<?php echo $item['id']; ?>, this)" <?php echo $item['show_in_gallery'] ? 'checked' : ''; ?>>
                                <span class="toggle-slider"></span>
                            </span>
                        </label>

                        <div class="absolute inset-0 z-10 bg-black/70 opacity-0 group-hover:opacity-100 transition flex flex-col items-center justify-center gap-2 p-2">
                            <span class="text-[10px] px-2 py-0.5 rounded-full <?php echo $item['category'] === 'project' ? 'bg-purple-500/30 text-purple-300' : 'bg-blue-500/30 text-blue-300'; ?>">
                                <?php echo ucfirst($item['category']); ?>
                            </span>
                            <?php if ($item['title']): ?>
                            <p class="text-white text-xs text-center line-clamp-2"><?php echo htmlspecialchars($item['title']); ?></p>
                            <?php endif; ?>
                            <form method="POST" onsubmit="return confirm('Delete this image?');">
                                <input type="hidden" name="delete_id" value="<?php echo $item['id']; ?>">
                                <button type="submit" class="text-xs text-red-400 hover:text-red-300 font-bold">
                                    <i class="fa-solid fa-trash mr-1"></i> Delete
                                </button>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

<script>
async function toggleGalleryVisible(id, checkbox) {
    const img = document.getElementById('gallery-img-' + id);
    const label = document.getElementById('gallery-visible-label-' + id);
    checkbox.disabled = true;
    try {
        const res = await fetch(location.href, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'toggle_visible_id=' + id + '&ajax=1'
        });
        const data = await res.json();
        if (data.success) {
            const visible = data.show_in_gallery === 1;
            checkbox.checked = visible;
            if (img) img.className = 'w-full h-full object-cover' + (visible ? '' : ' opacity-40 grayscale');
            if (label) {
                label.textContent = visible ? 'Live' : 'Hidden';
                label.className = 'text-[8px] font-bold uppercase ' + (visible ? 'text-green-400' : 'text-gray-500');
            }
        }
    } catch (e) {
        checkbox.checked = !checkbox.checked; // revert on failure
    }
    checkbox.disabled = false;
}
</script>

<?php include 'includes/admin_footer.php'; ?>
