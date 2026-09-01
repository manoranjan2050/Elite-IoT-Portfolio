<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
redirectToLogin();

$pageTitle = 'Testimonials';
$message = null;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_testimonial'])) {
    $stmt = $pdo->prepare("INSERT INTO testimonials (client_name, client_role, content) VALUES (?, ?, ?)");
    $message = $stmt->execute([sanitize($_POST['client_name']), sanitize($_POST['client_role']), sanitize($_POST['content'])])
        ? ['type' => 'success', 'text' => 'Testimonial added!']
        : ['type' => 'error',   'text' => 'Error adding testimonial.'];
}

if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM testimonials WHERE id = ?")->execute([(int)$_GET['delete']]);
    $message = ['type' => 'success', 'text' => 'Testimonial deleted.'];
}

$testimonials = $pdo->query("SELECT * FROM testimonials ORDER BY created_at DESC")->fetchAll();
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
        <!-- Add Form -->
        <div class="glass-card rounded-2xl border border-gray-800 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-800 flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-yellow-500/15 flex items-center justify-center">
                    <i class="fa-solid fa-star text-yellow-400 text-sm"></i>
                </div>
                <h2 class="font-bold text-white">Add Testimonial</h2>
            </div>
            <div class="p-6">
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="add_testimonial" value="1">
                    <div>
                        <label class="block text-xs text-gray-500 uppercase font-bold mb-1.5">Client Name</label>
                        <input type="text" name="client_name" required
                            class="w-full px-3 py-2.5 bg-gray-800 border border-gray-700 rounded-xl text-white text-sm"
                            placeholder="John Doe">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 uppercase font-bold mb-1.5">Role / Company</label>
                        <input type="text" name="client_role"
                            class="w-full px-3 py-2.5 bg-gray-800 border border-gray-700 rounded-xl text-white text-sm"
                            placeholder="CEO, TechCorp">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 uppercase font-bold mb-1.5">Feedback</label>
                        <textarea name="content" rows="5" required
                            class="w-full px-3 py-2.5 bg-gray-800 border border-gray-700 rounded-xl text-white text-sm resize-none"
                            placeholder="What they said..."></textarea>
                    </div>
                    <button type="submit" class="w-full py-3 bg-yellow-600 hover:bg-yellow-500 text-white font-bold rounded-xl transition text-sm flex items-center justify-center gap-2">
                        <i class="fa-solid fa-plus"></i> Add Testimonial
                    </button>
                </form>
            </div>
        </div>

        <!-- List -->
        <div class="xl:col-span-2 glass-card rounded-2xl border border-gray-800 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-800 flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-blue-500/15 flex items-center justify-center">
                    <i class="fa-solid fa-quote-left text-blue-400 text-sm"></i>
                </div>
                <h2 class="font-bold text-white">Testimonials <span class="text-gray-500 font-normal text-sm">(<?php echo count($testimonials); ?>)</span></h2>
            </div>
            <div class="p-6 space-y-4">
                <?php foreach ($testimonials as $t): ?>
                <div class="p-5 bg-gray-800/40 rounded-2xl border border-gray-700/50 flex justify-between items-start gap-4">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-2">
                            <div class="w-8 h-8 rounded-full bg-blue-500/20 flex items-center justify-center text-blue-400 font-bold text-sm">
                                <?php echo strtoupper(substr($t['client_name'], 0, 1)); ?>
                            </div>
                            <div>
                                <p class="text-sm font-bold text-white"><?php echo htmlspecialchars($t['client_name']); ?></p>
                                <?php if ($t['client_role']): ?>
                                <p class="text-[10px] text-gray-500"><?php echo htmlspecialchars($t['client_role']); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <p class="text-sm text-gray-400 italic">"<?php echo htmlspecialchars($t['content']); ?>"</p>
                    </div>
                    <a href="?delete=<?php echo $t['id']; ?>" onclick="return confirm('Delete this testimonial?')"
                        class="flex-shrink-0 px-2.5 py-1.5 bg-red-500/10 hover:bg-red-500/20 text-red-400 rounded-lg text-xs font-medium transition">
                        <i class="fa-solid fa-trash"></i>
                    </a>
                </div>
                <?php endforeach; ?>
                <?php if (empty($testimonials)): ?>
                <div class="py-12 text-center">
                    <i class="fa-solid fa-star text-gray-700 text-3xl mb-3 block"></i>
                    <p class="text-gray-500 text-sm">No testimonials yet.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

<?php include 'includes/admin_footer.php'; ?>
