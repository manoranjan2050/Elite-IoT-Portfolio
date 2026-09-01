<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
redirectToLogin();

$pageTitle = 'Messages';
$flash = null;

// Mark as read / reply / delete actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['reply_id'])) {
        $id = (int) $_POST['reply_id'];
        $replyText = sanitize($_POST['reply_text'] ?? '');

        $stmt = $pdo->prepare("SELECT * FROM messages WHERE id = ?");
        $stmt->execute([$id]);
        $msg = $stmt->fetch();

        if ($msg && $replyText) {
            $html = "<p>Hi " . htmlspecialchars($msg['name']) . ",</p>"
                . "<p>" . nl2br(htmlspecialchars($replyText)) . "</p>"
                . "<hr><p style='color:#888;font-size:12px;'>In reply to your message: \"" . htmlspecialchars($msg['subject']) . "\"</p>";

            $sent = sendMail($msg['email'], 'Re: ' . $msg['subject'], $html);

            $upd = $pdo->prepare("UPDATE messages SET status='replied', reply_text=?, replied_at=NOW() WHERE id=?");
            $upd->execute([$replyText, $id]);

            $flash = $sent
                ? ['type' => 'success', 'text' => 'Reply sent to ' . $msg['email']]
                : ['type' => 'error', 'text' => 'Reply saved, but the email could not be sent. Check mail_config.php.'];
        }
    } elseif (isset($_POST['delete_id'])) {
        $stmt = $pdo->prepare("DELETE FROM messages WHERE id = ?");
        $stmt->execute([(int) $_POST['delete_id']]);
        $flash = ['type' => 'success', 'text' => 'Message deleted.'];
    } elseif (isset($_POST['mark_read_id'])) {
        $stmt = $pdo->prepare("UPDATE messages SET status='read' WHERE id = ? AND status='unread'");
        $stmt->execute([(int) $_POST['mark_read_id']]);
    }
}

$messages = $pdo->query("SELECT * FROM messages ORDER BY created_at DESC")->fetchAll();
$unreadCount = 0;
foreach ($messages as $m) {
    if ($m['status'] === 'unread') $unreadCount++;
}
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

    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-xl font-bold text-white">Inbox</h2>
            <p class="text-sm text-gray-500"><?php echo count($messages); ?> total &middot; <?php echo $unreadCount; ?> unread</p>
        </div>
    </div>

    <?php if (!$messages): ?>
    <div class="glass-card rounded-2xl border border-gray-800 p-12 text-center text-gray-500">
        <i class="fa-solid fa-inbox text-4xl mb-3 opacity-40"></i>
        <p>No messages yet.</p>
    </div>
    <?php else: ?>

    <div class="space-y-4">
        <?php foreach ($messages as $m): ?>
        <div class="glass-card rounded-2xl border <?php echo $m['status'] === 'unread' ? 'border-blue-500/40' : 'border-gray-800'; ?> overflow-hidden">
            <button type="button" onclick="toggleMsg(<?php echo $m['id']; ?>)" class="w-full text-left px-6 py-4 flex items-center justify-between gap-4 hover:bg-gray-800/40 transition">
                <div class="flex items-center gap-4 min-w-0">
                    <?php if ($m['status'] === 'unread'): ?>
                        <span class="w-2.5 h-2.5 rounded-full bg-blue-500 flex-shrink-0"></span>
                    <?php elseif ($m['status'] === 'replied'): ?>
                        <span class="w-2.5 h-2.5 rounded-full bg-green-500 flex-shrink-0"></span>
                    <?php else: ?>
                        <span class="w-2.5 h-2.5 rounded-full bg-gray-700 flex-shrink-0"></span>
                    <?php endif; ?>
                    <div class="min-w-0">
                        <p class="font-bold text-white text-sm truncate"><?php echo htmlspecialchars($m['subject']); ?></p>
                        <p class="text-xs text-gray-500 truncate"><?php echo htmlspecialchars($m['name']); ?> &lt;<?php echo htmlspecialchars($m['email']); ?>&gt;</p>
                    </div>
                </div>
                <div class="flex items-center gap-3 flex-shrink-0">
                    <span class="text-xs text-gray-600"><?php echo date('d M, H:i', strtotime($m['created_at'])); ?></span>
                    <i class="fa-solid fa-chevron-down text-gray-600 text-xs" id="chev-<?php echo $m['id']; ?>"></i>
                </div>
            </button>

            <div id="msg-<?php echo $m['id']; ?>" class="hidden border-t border-gray-800 px-6 py-5 space-y-4">
                <p class="text-gray-300 text-sm whitespace-pre-line leading-relaxed"><?php echo htmlspecialchars($m['message']); ?></p>

                <?php if ($m['reply_text']): ?>
                <div class="bg-green-500/5 border border-green-500/20 rounded-xl p-4">
                    <p class="text-xs font-bold text-green-400 mb-1"><i class="fa-solid fa-reply mr-1"></i> Your reply (<?php echo date('d M, H:i', strtotime($m['replied_at'])); ?>)</p>
                    <p class="text-sm text-gray-300 whitespace-pre-line"><?php echo htmlspecialchars($m['reply_text']); ?></p>
                </div>
                <?php endif; ?>

                <form method="POST" class="flex flex-col md:flex-row gap-3">
                    <input type="hidden" name="reply_id" value="<?php echo $m['id']; ?>">
                    <textarea name="reply_text" rows="2" placeholder="Type your reply..."
                        class="flex-1 px-3 py-2.5 bg-gray-800 border border-gray-700 rounded-xl text-white text-sm placeholder-gray-600 resize-none" required></textarea>
                    <button type="submit" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-500 text-white font-bold rounded-xl transition text-sm flex items-center justify-center gap-2 whitespace-nowrap">
                        <i class="fa-solid fa-paper-plane"></i> Send Reply
                    </button>
                </form>

                <div class="flex justify-end">
                    <form method="POST" onsubmit="return confirm('Delete this message?');">
                        <input type="hidden" name="delete_id" value="<?php echo $m['id']; ?>">
                        <button type="submit" class="text-xs text-red-400 hover:text-red-300 font-bold">
                            <i class="fa-solid fa-trash mr-1"></i> Delete
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

<script>
function toggleMsg(id) {
    const el = document.getElementById('msg-' + id);
    const chev = document.getElementById('chev-' + id);
    el.classList.toggle('hidden');
    chev.classList.toggle('fa-chevron-down');
    chev.classList.toggle('fa-chevron-up');

    if (!el.classList.contains('hidden')) {
        fetch('', { method: 'POST', headers: {'Content-Type':'application/x-www-form-urlencoded'}, body: 'mark_read_id=' + id });
    }
}
</script>

<?php include 'includes/admin_footer.php'; ?>
