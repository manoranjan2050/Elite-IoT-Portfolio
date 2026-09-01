<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
redirectToLogin();

$pageTitle = 'Dashboard';
$message = "";

// Handle Add Project
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_project'])) {
    $title          = sanitize($_POST['title']);
    $description    = sanitize($_POST['description']);
    $category       = sanitize($_POST['category']);
    $status         = ($_POST['status'] ?? 'published') === 'coming_soon' ? 'coming_soon' : 'published';
    $project_link   = sanitize($_POST['project_link']);
    $playstore_link = sanitize($_POST['playstore_link'] ?? '');
    $download_link  = sanitize($_POST['download_link'] ?? '');
    $github_link    = sanitize($_POST['github_link']);
    $changelog      = sanitize($_POST['changelog'] ?? '');

    $uploadFile = function ($field, $prefix) {
        if (isset($_FILES[$field]) && $_FILES[$field]['error'] == 0) {
            $file_name = $prefix . '_' . time() . '_' . basename($_FILES[$field]['name']);
            if (move_uploaded_file($_FILES[$field]['tmp_name'], '../uploads/' . $file_name)) {
                return 'uploads/' . $file_name;
            }
        }
        return null;
    };

    $image_url       = $uploadFile('image', 'card') ?? '';
    $banner_image    = $uploadFile('banner_image', 'banner');
    $playstore_image = $uploadFile('playstore_image', 'playstore');

    $stmt = $pdo->prepare("INSERT INTO projects (title, description, category, status, image_url, banner_image, playstore_image, project_link, playstore_link, download_link, github_link, changelog) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if ($stmt->execute([$title, $description, $category, $status, $image_url, $banner_image, $playstore_image, $project_link, $playstore_link, $download_link, $github_link, $changelog])) {
        $message = ['type' => 'success', 'text' => 'Project added successfully!'];
    } else {
        $message = ['type' => 'error', 'text' => 'Error adding project.'];
    }
}

// Handle Delete Project
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM projects WHERE id = ?");
    $stmt->execute([$id]);
    $message = ['type' => 'success', 'text' => 'Project deleted.'];
}

$projects   = $pdo->query("SELECT * FROM projects ORDER BY created_at DESC")->fetchAll();
$visitStats = getVisitStats($pdo);

// Blog & testimonial counts
$blogCount  = $pdo->query("SELECT COUNT(*) FROM blogs")->fetchColumn();
$testCount  = $pdo->query("SELECT COUNT(*) FROM testimonials")->fetchColumn();
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

    <!-- Stats Row -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="glass-card stat-card p-5 rounded-2xl border border-gray-800">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-xl bg-blue-500/15 flex items-center justify-center">
                    <i class="fa-solid fa-users text-blue-400"></i>
                </div>
                <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">Today</p>
            </div>
            <p class="text-3xl font-black text-white"><?php echo number_format($visitStats['today']); ?></p>
            <p class="text-xs text-gray-600 mt-1">Unique visits</p>
        </div>
        <div class="glass-card stat-card p-5 rounded-2xl border border-gray-800">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-xl bg-purple-500/15 flex items-center justify-center">
                    <i class="fa-solid fa-calendar-week text-purple-400"></i>
                </div>
                <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">7 Days</p>
            </div>
            <p class="text-3xl font-black text-white"><?php echo number_format($visitStats['last_7_days']); ?></p>
            <p class="text-xs text-gray-600 mt-1">Visits this week</p>
        </div>
        <div class="glass-card stat-card p-5 rounded-2xl border border-gray-800">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-xl bg-green-500/15 flex items-center justify-center">
                    <i class="fa-solid fa-chart-line text-green-400"></i>
                </div>
                <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">Total</p>
            </div>
            <p class="text-3xl font-black text-white"><?php echo number_format($visitStats['total']); ?></p>
            <p class="text-xs text-gray-600 mt-1">All-time visits</p>
        </div>
        <div class="glass-card stat-card p-5 rounded-2xl border border-gray-800">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-xl bg-orange-500/15 flex items-center justify-center">
                    <i class="fa-solid fa-folder-open text-orange-400"></i>
                </div>
                <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">Projects</p>
            </div>
            <p class="text-3xl font-black text-white"><?php echo count($projects); ?></p>
            <p class="text-xs text-gray-600 mt-1"><?php echo $blogCount; ?> posts · <?php echo $testCount; ?> reviews</p>
        </div>
    </div>

    <!-- Quick Links Row -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-8">
        <a href="ha_control.php" class="glass-card p-4 rounded-2xl border border-green-500/20 flex items-center gap-3 hover:border-green-500/50 transition group">
            <div class="w-9 h-9 rounded-xl bg-green-500/15 flex items-center justify-center">
                <i class="fa-solid fa-gamepad text-green-400"></i>
            </div>
            <div>
                <p class="text-sm font-bold text-white">Control</p>
                <p class="text-[10px] text-gray-500">HA Panel</p>
            </div>
        </a>
        <a href="ha_settings.php" class="glass-card p-4 rounded-2xl border border-blue-500/20 flex items-center gap-3 hover:border-blue-500/50 transition">
            <div class="w-9 h-9 rounded-xl bg-blue-500/15 flex items-center justify-center">
                <i class="fa-solid fa-sliders text-blue-400"></i>
            </div>
            <div>
                <p class="text-sm font-bold text-white">HA Setup</p>
                <p class="text-[10px] text-gray-500">Settings</p>
            </div>
        </a>
        <a href="power_settings.php" class="glass-card p-4 rounded-2xl border border-yellow-500/20 flex items-center gap-3 hover:border-yellow-500/50 transition">
            <div class="w-9 h-9 rounded-xl bg-yellow-500/15 flex items-center justify-center">
                <i class="fa-solid fa-bolt text-yellow-400"></i>
            </div>
            <div>
                <p class="text-sm font-bold text-white">Entities</p>
                <p class="text-[10px] text-gray-500">Power Config</p>
            </div>
        </a>
        <a href="profile.php" class="glass-card p-4 rounded-2xl border border-purple-500/20 flex items-center gap-3 hover:border-purple-500/50 transition">
            <div class="w-9 h-9 rounded-xl bg-purple-500/15 flex items-center justify-center">
                <i class="fa-solid fa-user-circle text-purple-400"></i>
            </div>
            <div>
                <p class="text-sm font-bold text-white">Profile</p>
                <p class="text-[10px] text-gray-500">My Account</p>
            </div>
        </a>
    </div>

    <!-- Main Grid -->
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

        <!-- Add Project Form -->
        <div class="glass-card rounded-2xl border border-gray-800 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-800 flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-green-500/15 flex items-center justify-center">
                    <i class="fa-solid fa-plus text-green-400 text-sm"></i>
                </div>
                <h2 class="font-bold text-white">Add New Project</h2>
            </div>
            <div class="p-6">
                <form method="POST" action="" enctype="multipart/form-data" class="space-y-4">
                    <input type="hidden" name="add_project" value="1">
                    <div>
                        <label class="block text-xs text-gray-500 uppercase font-bold mb-1.5">Title</label>
                        <input type="text" name="title" required
                            class="w-full px-3 py-2.5 bg-gray-800 border border-gray-700 rounded-xl text-white text-sm placeholder-gray-600"
                            placeholder="Project name">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs text-gray-500 uppercase font-bold mb-1.5">Category</label>
                            <select name="category" required
                                class="w-full px-3 py-2.5 bg-gray-800 border border-gray-700 rounded-xl text-white text-sm">
                                <option value="App">App</option>
                                <option value="Website">Website</option>
                                <option value="Dev">Dev</option>
                                <option value="Coding">Coding</option>
                                <option value="Trading">Trading</option>
                                <option value="PCB Design">PCB Design</option>
                                <option value="IoT">IoT</option>
                                <option value="Business">Business</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 uppercase font-bold mb-1.5">Status</label>
                            <select name="status" required
                                class="w-full px-3 py-2.5 bg-gray-800 border border-gray-700 rounded-xl text-white text-sm">
                                <option value="published">Published</option>
                                <option value="coming_soon">Coming Soon</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 uppercase font-bold mb-1.5">Description</label>
                        <textarea name="description" rows="3"
                            class="w-full px-3 py-2.5 bg-gray-800 border border-gray-700 rounded-xl text-white text-sm placeholder-gray-600 resize-none"
                            placeholder="Short description..."></textarea>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 uppercase font-bold mb-1.5">Card Image</label>
                        <input type="file" name="image" accept="image/*"
                            class="w-full text-sm text-gray-400 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-blue-500/15 file:text-blue-400 hover:file:bg-blue-500/25 file:transition">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 uppercase font-bold mb-1.5">Featured Banner Image <span class="text-gray-600 normal-case font-normal">(optional, overrides card image on detail views)</span></label>
                        <input type="file" name="banner_image" accept="image/*"
                            class="w-full text-sm text-gray-400 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-purple-500/15 file:text-purple-400 hover:file:bg-purple-500/25 file:transition">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 uppercase font-bold mb-1.5">Play Store Screenshot</label>
                        <input type="file" name="playstore_image" accept="image/*"
                            class="w-full text-sm text-gray-400 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-green-500/15 file:text-green-400 hover:file:bg-green-500/25 file:transition">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 uppercase font-bold mb-1.5">Play Store Link</label>
                        <input type="url" name="playstore_link"
                            class="w-full px-3 py-2.5 bg-gray-800 border border-gray-700 rounded-xl text-white text-sm placeholder-gray-600"
                            placeholder="https://play.google.com/store/apps/details?id=...">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 uppercase font-bold mb-1.5">Download Link <span class="text-gray-600 normal-case font-normal">(direct APK / file, optional)</span></label>
                        <input type="url" name="download_link"
                            class="w-full px-3 py-2.5 bg-gray-800 border border-gray-700 rounded-xl text-white text-sm placeholder-gray-600"
                            placeholder="https://...">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 uppercase font-bold mb-1.5">Project / Website URL</label>
                        <input type="url" name="project_link"
                            class="w-full px-3 py-2.5 bg-gray-800 border border-gray-700 rounded-xl text-white text-sm placeholder-gray-600"
                            placeholder="https://...">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 uppercase font-bold mb-1.5">GitHub URL</label>
                        <input type="url" name="github_link"
                            class="w-full px-3 py-2.5 bg-gray-800 border border-gray-700 rounded-xl text-white text-sm placeholder-gray-600"
                            placeholder="https://github.com/...">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 uppercase font-bold mb-1.5">Changelog <span class="text-gray-600 normal-case font-normal">(optional)</span></label>
                        <textarea name="changelog" rows="3"
                            class="w-full px-3 py-2.5 bg-gray-800 border border-gray-700 rounded-xl text-white text-sm placeholder-gray-600 resize-none"
                            placeholder="v1.0.0 - Initial release..."></textarea>
                    </div>
                    <button type="submit"
                        class="w-full py-3 bg-blue-600 hover:bg-blue-500 text-white font-bold rounded-xl transition text-sm flex items-center justify-center gap-2">
                        <i class="fa-solid fa-plus"></i> Add Project
                    </button>
                </form>
            </div>
        </div>

        <!-- Projects List -->
        <div class="xl:col-span-2 glass-card rounded-2xl border border-gray-800 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-800 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-blue-500/15 flex items-center justify-center">
                        <i class="fa-solid fa-folder-open text-blue-400 text-sm"></i>
                    </div>
                    <h2 class="font-bold text-white">Projects <span class="text-sm text-gray-500 font-normal">(<?php echo count($projects); ?>)</span></h2>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-gray-800">
                            <th class="px-6 py-3 text-left text-[10px] uppercase tracking-wider text-gray-500 font-bold">Title</th>
                            <th class="px-4 py-3 text-left text-[10px] uppercase tracking-wider text-gray-500 font-bold">Category</th>
                            <th class="px-4 py-3 text-left text-[10px] uppercase tracking-wider text-gray-500 font-bold">Status</th>
                            <th class="px-4 py-3 text-left text-[10px] uppercase tracking-wider text-gray-500 font-bold">Clicks</th>
                            <th class="px-4 py-3 text-left text-[10px] uppercase tracking-wider text-gray-500 font-bold">Date</th>
                            <th class="px-4 py-3 text-left text-[10px] uppercase tracking-wider text-gray-500 font-bold">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-800/50">
                        <?php foreach ($projects as $project): ?>
                        <tr class="hover:bg-gray-800/30 transition">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <?php if ($project['image_url']): ?>
                                        <img src="../<?php echo $project['image_url']; ?>" class="w-9 h-9 rounded-lg object-cover border border-gray-700">
                                    <?php else: ?>
                                        <div class="w-9 h-9 rounded-lg bg-gray-800 flex items-center justify-center">
                                            <i class="fa-solid fa-image text-gray-600 text-sm"></i>
                                        </div>
                                    <?php endif; ?>
                                    <span class="text-sm font-medium text-white"><?php echo htmlspecialchars($project['title']); ?></span>
                                </div>
                            </td>
                            <td class="px-4 py-4">
                                <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wide bg-blue-500/10 text-blue-400 border border-blue-500/20">
                                    <?php echo $project['category']; ?>
                                </span>
                            </td>
                            <td class="px-4 py-4">
                                <?php if (($project['status'] ?? 'published') === 'coming_soon'): ?>
                                <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wide bg-orange-500/10 text-orange-400 border border-orange-500/20">Coming Soon</span>
                                <?php else: ?>
                                <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wide bg-green-500/10 text-green-400 border border-green-500/20">Published</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-4 text-xs text-gray-400 font-bold">
                                <?php echo number_format($project['click_count'] ?? 0); ?>
                            </td>
                            <td class="px-4 py-4 text-xs text-gray-500">
                                <?php echo date('d M Y', strtotime($project['created_at'])); ?>
                            </td>
                            <td class="px-4 py-4">
                                <div class="flex items-center gap-2">
                                    <a href="edit_project.php?id=<?php echo $project['id']; ?>"
                                        class="px-3 py-1.5 bg-gray-700 hover:bg-gray-600 text-gray-300 hover:text-white rounded-lg text-xs font-medium transition flex items-center gap-1">
                                        <i class="fa-solid fa-pen text-[10px]"></i> Edit
                                    </a>
                                    <a href="?delete=<?php echo $project['id']; ?>"
                                        onclick="return confirm('Delete this project?')"
                                        class="px-3 py-1.5 bg-red-500/10 hover:bg-red-500/20 text-red-400 rounded-lg text-xs font-medium transition flex items-center gap-1">
                                        <i class="fa-solid fa-trash text-[10px]"></i> Del
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($projects)): ?>
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center">
                                <i class="fa-solid fa-folder-open text-gray-700 text-3xl mb-3 block"></i>
                                <p class="text-gray-500 text-sm">No projects yet. Add your first one!</p>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<?php include 'includes/admin_footer.php'; ?>
