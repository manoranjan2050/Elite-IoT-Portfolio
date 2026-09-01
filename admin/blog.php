<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
redirectToLogin();

$pageTitle = 'Blog';
$message = null;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_blog'])) {
    $title    = sanitize($_POST['title']);
    $content  = sanitize($_POST['content']);
    $category = sanitize($_POST['category']);

    $image_url = "";
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $fn = "blog_" . time() . "_" . basename($_FILES["image"]["name"]);
        if (move_uploaded_file($_FILES["image"]["tmp_name"], "../uploads/" . $fn)) {
            $image_url = "uploads/" . $fn;
        }
    }
    $stmt = $pdo->prepare("INSERT INTO blogs (title, content, category, image_url) VALUES (?, ?, ?, ?)");
    $message = $stmt->execute([$title, $content, $category, $image_url])
        ? ['type' => 'success', 'text' => 'Blog post published!']
        : ['type' => 'error',   'text' => 'Error publishing post.'];
}

if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM blogs WHERE id = ?")->execute([(int)$_GET['delete']]);
    $message = ['type' => 'success', 'text' => 'Post deleted.'];
}

$blogs = $pdo->query("SELECT * FROM blogs ORDER BY created_at DESC")->fetchAll();
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

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <!-- Add Post -->
        <div class="glass-card rounded-2xl border border-gray-800 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-800 flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-purple-500/15 flex items-center justify-center">
                    <i class="fa-solid fa-plus text-purple-400 text-sm"></i>
                </div>
                <h2 class="font-bold text-white">New Post</h2>
            </div>
            <div class="p-6">
                <form method="POST" enctype="multipart/form-data" class="space-y-4">
                    <input type="hidden" name="add_blog" value="1">
                    <div>
                        <label class="block text-xs text-gray-500 uppercase font-bold mb-1.5">Title</label>
                        <input type="text" name="title" required
                            class="w-full px-3 py-2.5 bg-gray-800 border border-gray-700 rounded-xl text-white text-sm"
                            placeholder="Post title">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 uppercase font-bold mb-1.5">Category</label>
                        <select name="category" class="w-full px-3 py-2.5 bg-gray-800 border border-gray-700 rounded-xl text-white text-sm">
                            <option value="Trading">Trading</option>
                            <option value="IoT">IoT</option>
                            <option value="Business">Business</option>
                            <option value="Tech">Tech</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 uppercase font-bold mb-1.5">Content</label>
                        <textarea name="content" rows="8" required
                            class="w-full px-3 py-2.5 bg-gray-800 border border-gray-700 rounded-xl text-white text-sm resize-none"
                            placeholder="Write your post..."></textarea>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 uppercase font-bold mb-1.5">Cover Image</label>
                        <input type="file" name="image" accept="image/*"
                            class="w-full text-sm text-gray-400 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-purple-500/15 file:text-purple-400 hover:file:bg-purple-500/25 file:transition">
                    </div>
                    <button type="submit" class="w-full py-3 bg-purple-600 hover:bg-purple-500 text-white font-bold rounded-xl transition text-sm flex items-center justify-center gap-2">
                        <i class="fa-solid fa-paper-plane"></i> Publish Post
                    </button>
                </form>
            </div>
        </div>

        <!-- Posts List -->
        <div class="xl:col-span-2 glass-card rounded-2xl border border-gray-800 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-800 flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-blue-500/15 flex items-center justify-center">
                    <i class="fa-solid fa-newspaper text-blue-400 text-sm"></i>
                </div>
                <h2 class="font-bold text-white">Posts <span class="text-gray-500 font-normal text-sm">(<?php echo count($blogs); ?>)</span></h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-800 text-[10px] uppercase tracking-wider text-gray-500">
                            <th class="px-6 py-3 text-left font-bold">Title</th>
                            <th class="px-4 py-3 text-left font-bold">Category</th>
                            <th class="px-4 py-3 text-left font-bold">Date</th>
                            <th class="px-4 py-3 text-left font-bold">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-800/50">
                        <?php foreach ($blogs as $post): ?>
                        <tr class="hover:bg-gray-800/20 transition">
                            <td class="px-6 py-4 font-medium text-white max-w-[200px] truncate"><?php echo htmlspecialchars($post['title']); ?></td>
                            <td class="px-4 py-4">
                                <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase bg-purple-500/10 text-purple-400 border border-purple-500/20">
                                    <?php echo htmlspecialchars($post['category']); ?>
                                </span>
                            </td>
                            <td class="px-4 py-4 text-xs text-gray-500"><?php echo date('d M Y', strtotime($post['created_at'])); ?></td>
                            <td class="px-4 py-4">
                                <a href="?delete=<?php echo $post['id']; ?>" onclick="return confirm('Delete this post?')"
                                    class="px-3 py-1.5 bg-red-500/10 hover:bg-red-500/20 text-red-400 rounded-lg text-xs font-medium transition flex items-center gap-1 w-fit">
                                    <i class="fa-solid fa-trash text-[10px]"></i> Delete
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($blogs)): ?>
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center">
                                <i class="fa-solid fa-newspaper text-gray-700 text-3xl mb-3 block"></i>
                                <p class="text-gray-500 text-sm">No blog posts yet.</p>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<?php include 'includes/admin_footer.php'; ?>
