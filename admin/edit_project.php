<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
redirectToLogin();

$pageTitle = 'Edit Project';
$message = null;
$project = null;

if (isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
    $stmt->execute([(int)$_GET['id']]);
    $project = $stmt->fetch();
}

if (!$project) {
    header("Location: index.php");
    exit();
}

$id = $project['id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_project'])) {
    $title          = sanitize($_POST['title']);
    $description    = sanitize($_POST['description']);
    $category       = sanitize($_POST['category']);
    $status         = ($_POST['status'] ?? 'published') === 'coming_soon' ? 'coming_soon' : 'published';
    $project_link   = sanitize($_POST['project_link']);
    $playstore_link = sanitize($_POST['playstore_link'] ?? '');
    $download_link  = sanitize($_POST['download_link'] ?? '');
    $github_link    = sanitize($_POST['github_link']);
    $changelog      = sanitize($_POST['changelog'] ?? '');

    $image_url       = $project['image_url'];
    $banner_image    = $project['banner_image'];
    $playstore_image = $project['playstore_image'];

    $uploadFile = function ($field, $prefix) {
        if (isset($_FILES[$field]) && $_FILES[$field]['error'] == 0) {
            $fn = $prefix . '_' . time() . '_' . basename($_FILES[$field]['name']);
            if (move_uploaded_file($_FILES[$field]['tmp_name'], '../uploads/' . $fn)) {
                return 'uploads/' . $fn;
            }
        }
        return null;
    };

    $image_url       = $uploadFile('image', 'card') ?? $image_url;
    $banner_image    = $uploadFile('banner_image', 'banner') ?? $banner_image;
    $playstore_image = $uploadFile('playstore_image', 'playstore') ?? $playstore_image;

    $stmt = $pdo->prepare("UPDATE projects SET title=?, description=?, category=?, status=?, image_url=?, banner_image=?, playstore_image=?, project_link=?, playstore_link=?, download_link=?, github_link=?, changelog=? WHERE id=?");
    if ($stmt->execute([$title, $description, $category, $status, $image_url, $banner_image, $playstore_image, $project_link, $playstore_link, $download_link, $github_link, $changelog, $id])) {
        $message = ['type' => 'success', 'text' => 'Project updated successfully!'];
        $stmt2 = $pdo->prepare("SELECT * FROM projects WHERE id=?");
        $stmt2->execute([$id]);
        $project = $stmt2->fetch();
    } else {
        $message = ['type' => 'error', 'text' => 'Error updating project.'];
    }
}
?>
<?php include 'includes/admin_head.php'; ?>
<?php include 'includes/sidebar.php'; ?>

    <?php if ($message): ?>
    <div class="mb-6 px-4 py-3 rounded-xl text-sm font-medium flex items-center gap-3
        <?php echo $message['type'] === 'success' ? 'bg-green-500/10 text-green-400 border border-green-500/20' : 'bg-red-500/10 text-red-400 border border-red-500/20'; ?>">
        <i class="fa-solid <?php echo $message['type'] === 'success' ? 'fa-circle-check' : 'fa-circle-xmark'; ?>"></i>
        <?php echo $message['text']; ?>
    </div>
    <?php endif; ?>

    <div class="max-w-2xl">
        <div class="glass-card rounded-2xl border border-gray-800 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-800 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-blue-500/15 flex items-center justify-center">
                        <i class="fa-solid fa-pen text-blue-400 text-sm"></i>
                    </div>
                    <h2 class="font-bold text-white">Edit: <?php echo htmlspecialchars($project['title']); ?></h2>
                </div>
                <a href="index.php" class="text-xs text-gray-500 hover:text-gray-300 flex items-center gap-1.5">
                    <i class="fa-solid fa-arrow-left"></i> Back
                </a>
            </div>
            <div class="p-6">
                <form method="POST" enctype="multipart/form-data" class="space-y-5">
                    <input type="hidden" name="update_project" value="1">
                    <div>
                        <label class="block text-xs text-gray-500 uppercase font-bold mb-1.5">Title</label>
                        <input type="text" name="title" required value="<?php echo htmlspecialchars($project['title']); ?>"
                            class="w-full px-3 py-2.5 bg-gray-800 border border-gray-700 rounded-xl text-white text-sm">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs text-gray-500 uppercase font-bold mb-1.5">Category</label>
                            <select name="category" class="w-full px-3 py-2.5 bg-gray-800 border border-gray-700 rounded-xl text-white text-sm">
                                <?php foreach (['App','Website','Dev','Coding','Trading','PCB Design','IoT','Business'] as $cat): ?>
                                <option value="<?php echo $cat; ?>" <?php echo $project['category'] === $cat ? 'selected' : ''; ?>><?php echo $cat; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 uppercase font-bold mb-1.5">Status</label>
                            <select name="status" class="w-full px-3 py-2.5 bg-gray-800 border border-gray-700 rounded-xl text-white text-sm">
                                <option value="published" <?php echo ($project['status'] ?? 'published') === 'published' ? 'selected' : ''; ?>>Published</option>
                                <option value="coming_soon" <?php echo ($project['status'] ?? '') === 'coming_soon' ? 'selected' : ''; ?>>Coming Soon</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 uppercase font-bold mb-1.5">Description</label>
                        <textarea name="description" rows="5"
                            class="w-full px-3 py-2.5 bg-gray-800 border border-gray-700 rounded-xl text-white text-sm resize-none"><?php echo htmlspecialchars($project['description']); ?></textarea>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 uppercase font-bold mb-1.5">Card Image</label>
                        <?php if ($project['image_url']): ?>
                        <div class="mb-3">
                            <img src="../<?php echo $project['image_url']; ?>" class="w-24 h-24 object-cover rounded-xl border border-gray-700">
                            <p class="text-[10px] text-gray-600 mt-1">Current image</p>
                        </div>
                        <?php endif; ?>
                        <input type="file" name="image" accept="image/*"
                            class="w-full text-sm text-gray-400 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-blue-500/15 file:text-blue-400 hover:file:bg-blue-500/25 file:transition">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 uppercase font-bold mb-1.5">Featured Banner Image</label>
                        <?php if (!empty($project['banner_image'])): ?>
                        <div class="mb-3">
                            <img src="../<?php echo $project['banner_image']; ?>" class="w-24 h-24 object-cover rounded-xl border border-gray-700">
                            <p class="text-[10px] text-gray-600 mt-1">Current banner</p>
                        </div>
                        <?php endif; ?>
                        <input type="file" name="banner_image" accept="image/*"
                            class="w-full text-sm text-gray-400 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-purple-500/15 file:text-purple-400 hover:file:bg-purple-500/25 file:transition">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 uppercase font-bold mb-1.5">Play Store Screenshot</label>
                        <?php if (!empty($project['playstore_image'])): ?>
                        <div class="mb-3">
                            <img src="../<?php echo $project['playstore_image']; ?>" class="w-24 h-24 object-cover rounded-xl border border-gray-700">
                            <p class="text-[10px] text-gray-600 mt-1">Current screenshot</p>
                        </div>
                        <?php endif; ?>
                        <input type="file" name="playstore_image" accept="image/*"
                            class="w-full text-sm text-gray-400 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-green-500/15 file:text-green-400 hover:file:bg-green-500/25 file:transition">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 uppercase font-bold mb-1.5">Play Store Link</label>
                        <input type="url" name="playstore_link" value="<?php echo htmlspecialchars($project['playstore_link'] ?? ''); ?>"
                            class="w-full px-3 py-2.5 bg-gray-800 border border-gray-700 rounded-xl text-white text-sm"
                            placeholder="https://play.google.com/store/apps/details?id=...">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 uppercase font-bold mb-1.5">Download Link</label>
                        <input type="url" name="download_link" value="<?php echo htmlspecialchars($project['download_link'] ?? ''); ?>"
                            class="w-full px-3 py-2.5 bg-gray-800 border border-gray-700 rounded-xl text-white text-sm"
                            placeholder="https://...">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 uppercase font-bold mb-1.5">Project / Website URL</label>
                        <input type="url" name="project_link" value="<?php echo htmlspecialchars($project['project_link'] ?? ''); ?>"
                            class="w-full px-3 py-2.5 bg-gray-800 border border-gray-700 rounded-xl text-white text-sm"
                            placeholder="https://...">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 uppercase font-bold mb-1.5">GitHub URL</label>
                        <input type="url" name="github_link" value="<?php echo htmlspecialchars($project['github_link'] ?? ''); ?>"
                            class="w-full px-3 py-2.5 bg-gray-800 border border-gray-700 rounded-xl text-white text-sm"
                            placeholder="https://github.com/...">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 uppercase font-bold mb-1.5">Changelog</label>
                        <textarea name="changelog" rows="4"
                            class="w-full px-3 py-2.5 bg-gray-800 border border-gray-700 rounded-xl text-white text-sm resize-none"
                            placeholder="v1.0.0 - Initial release..."><?php echo htmlspecialchars($project['changelog'] ?? ''); ?></textarea>
                    </div>
                    <div class="flex items-center justify-between px-1 py-2 text-xs text-gray-500">
                        <span><i class="fa-solid fa-mouse-pointer mr-1.5"></i> Total clicks tracked</span>
                        <span class="font-bold text-white"><?php echo number_format($project['click_count'] ?? 0); ?></span>
                    </div>
                    <div class="flex gap-3 pt-2">
                        <button type="submit" class="flex-1 py-3 bg-blue-600 hover:bg-blue-500 text-white font-bold rounded-xl transition text-sm flex items-center justify-center gap-2">
                            <i class="fa-solid fa-save"></i> Save Changes
                        </button>
                        <a href="index.php" class="flex-1 py-3 bg-gray-700 hover:bg-gray-600 text-gray-300 font-bold rounded-xl text-center transition text-sm">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

<?php include 'includes/admin_footer.php'; ?>
