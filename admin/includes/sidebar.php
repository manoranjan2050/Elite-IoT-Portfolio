<?php
// Get current page for active state
$currentPage = basename($_SERVER['PHP_SELF']);

// Fetch profile photo for sidebar
$adminUser = null;
if (isset($pdo)) {
    try {
        $stmt = $pdo->prepare("SELECT username, full_name, profile_photo FROM admin_users WHERE id = ?");
        $stmt->execute([$_SESSION['admin_id']]);
        $adminUser = $stmt->fetch();
    } catch(Exception $e) {}
}
$displayName = ($adminUser && $adminUser['full_name']) ? $adminUser['full_name'] : ($_SESSION['username'] ?? 'Admin');
$profilePhoto = ($adminUser && $adminUser['profile_photo']) ? '../uploads/' . $adminUser['profile_photo'] : 'https://github.com/manoranjan2050.png';

$unreadMessages = 0;
if (isset($pdo)) {
    try {
        $unreadMessages = (int) $pdo->query("SELECT COUNT(*) FROM messages WHERE status='unread'")->fetchColumn();
    } catch (Exception $e) {}
}
?>

<!-- SIDEBAR -->
<aside id="sidebar" class="fixed inset-y-0 left-0 z-50 w-64 bg-gray-900 border-r border-gray-800 flex flex-col transition-transform duration-300 -translate-x-full lg:translate-x-0">
    <!-- Brand -->
    <div class="p-6 border-b border-gray-800">
        <a href="../../index.php" target="_blank" class="flex items-center gap-3 group">
            <div class="w-9 h-9 rounded-xl bg-blue-600 flex items-center justify-center text-white font-black text-sm">M</div>
            <div>
                <p class="font-extrabold text-white tracking-tight">MANORANJAN</p>
                <p class="text-[10px] text-gray-500 uppercase tracking-widest">.DEV Admin</p>
            </div>
        </a>
    </div>

    <!-- Profile Mini -->
    <div class="px-4 py-4 border-b border-gray-800">
        <a href="profile.php" class="flex items-center gap-3 p-2 rounded-xl hover:bg-gray-800 transition group">
            <img src="<?php echo $profilePhoto; ?>" class="w-9 h-9 rounded-full border-2 border-blue-500/50 object-cover" alt="Profile">
            <div class="flex-1 min-w-0">
                <p class="text-sm font-bold text-white truncate"><?php echo htmlspecialchars($displayName); ?></p>
                <p class="text-[10px] text-gray-500">Administrator</p>
            </div>
            <i class="fa-solid fa-angle-right text-gray-600 group-hover:text-blue-400 transition text-xs"></i>
        </a>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 overflow-y-auto p-4 space-y-1">
        <p class="text-[9px] uppercase tracking-[0.2em] text-gray-600 font-bold px-3 mb-2">Main</p>

        <a href="index.php" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-gray-300 border-l-2 border-transparent <?php echo $currentPage === 'index.php' ? 'active' : ''; ?>">
            <i class="fa-solid fa-gauge w-4 text-center"></i> Dashboard
        </a>
        <a href="projects.php" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-gray-300 border-l-2 border-transparent <?php echo $currentPage === 'projects.php' ? 'active' : ''; ?>">
            <i class="fa-solid fa-folder-open w-4 text-center"></i> Projects
        </a>
        <a href="blog.php" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-gray-300 border-l-2 border-transparent <?php echo $currentPage === 'blog.php' ? 'active' : ''; ?>">
            <i class="fa-solid fa-newspaper w-4 text-center"></i> Blog
        </a>
        <a href="testimonials.php" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-gray-300 border-l-2 border-transparent <?php echo $currentPage === 'testimonials.php' ? 'active' : ''; ?>">
            <i class="fa-solid fa-quote-left w-4 text-center"></i> Testimonials
        </a>
        <a href="gallery.php" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-gray-300 border-l-2 border-transparent <?php echo $currentPage === 'gallery.php' ? 'active' : ''; ?>">
            <i class="fa-solid fa-images w-4 text-center"></i> Gallery
        </a>
        <a href="messages.php" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-gray-300 border-l-2 border-transparent <?php echo $currentPage === 'messages.php' ? 'active' : ''; ?>">
            <i class="fa-solid fa-envelope w-4 text-center"></i> Messages
            <?php if ($unreadMessages > 0): ?>
            <span class="ml-auto bg-blue-500 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full"><?php echo $unreadMessages; ?></span>
            <?php endif; ?>
        </a>

        <p class="text-[9px] uppercase tracking-[0.2em] text-gray-600 font-bold px-3 mt-5 mb-2">Home Assistant</p>

        <a href="ha_settings.php" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-gray-300 border-l-2 border-transparent <?php echo $currentPage === 'ha_settings.php' ? 'active' : ''; ?>">
            <i class="fa-solid fa-sliders w-4 text-center"></i> HA Settings
        </a>
        <a href="power_settings.php" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-gray-300 border-l-2 border-transparent <?php echo $currentPage === 'power_settings.php' ? 'active' : ''; ?>">
            <i class="fa-solid fa-bolt w-4 text-center text-yellow-400"></i> Power Entities
        </a>
        <a href="ha_control.php" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-gray-300 border-l-2 border-transparent <?php echo $currentPage === 'ha_control.php' ? 'active' : ''; ?>">
            <i class="fa-solid fa-gamepad w-4 text-center text-green-400"></i> Control Panel
        </a>

        <p class="text-[9px] uppercase tracking-[0.2em] text-gray-600 font-bold px-3 mt-5 mb-2">Account</p>

        <a href="profile.php" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-gray-300 border-l-2 border-transparent <?php echo $currentPage === 'profile.php' ? 'active' : ''; ?>">
            <i class="fa-solid fa-user-circle w-4 text-center"></i> My Profile
        </a>
    </nav>

    <!-- Bottom Actions -->
    <div class="p-4 border-t border-gray-800 space-y-2">
        <a href="../index.php" target="_blank" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-gray-400 hover:text-blue-400 hover:bg-gray-800 transition">
            <i class="fa-solid fa-arrow-up-right-from-square w-4 text-center"></i> View Website
        </a>
        <a href="../power.php" target="_blank" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-gray-400 hover:text-orange-400 hover:bg-gray-800 transition">
            <i class="fa-solid fa-bolt w-4 text-center"></i> Power Dashboard
        </a>
        <a href="../logout.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-red-400 hover:bg-red-500/10 transition">
            <i class="fa-solid fa-right-from-bracket w-4 text-center"></i> Logout
        </a>
    </div>
</aside>

<!-- MAIN WRAPPER -->
<div class="flex-1 flex flex-col min-h-screen lg:ml-64">
    <!-- Top Bar -->
    <header class="sticky top-0 z-40 bg-gray-900/80 backdrop-blur-md border-b border-gray-800 px-6 py-4 flex items-center justify-between">
        <div class="flex items-center gap-4">
            <!-- Mobile menu toggle -->
            <button id="sidebar-toggle" class="lg:hidden text-gray-400 hover:text-white">
                <i class="fa-solid fa-bars text-xl"></i>
            </button>
            <div>
                <h1 class="text-lg font-bold text-white"><?php echo isset($pageTitle) ? $pageTitle : 'Dashboard'; ?></h1>
                <p class="text-xs text-gray-500"><?php echo date('l, d M Y'); ?></p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <!-- HA Status Badge -->
            <div id="ha-status-badge" class="hidden items-center gap-2 px-3 py-1.5 rounded-full text-xs font-bold bg-gray-800 text-gray-400 border border-gray-700">
                <div class="w-2 h-2 rounded-full bg-gray-500" id="ha-dot"></div>
                <span id="ha-status-text">HA</span>
            </div>
            <a href="profile.php" class="flex items-center gap-2 group">
                <img src="<?php echo $profilePhoto; ?>" class="w-8 h-8 rounded-full border border-gray-700 object-cover" alt="Profile">
            </a>
        </div>
    </header>

    <!-- Page Content Start -->
    <main class="flex-1 p-6 page-content">
