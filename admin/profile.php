<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
redirectToLogin();

$pageTitle = 'My Profile';
$message = null;

// Fetch current user
$stmt = $pdo->prepare("SELECT * FROM admin_users WHERE id = ?");
$stmt->execute([$_SESSION['admin_id']]);
$user = $stmt->fetch();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $full_name = sanitize($_POST['full_name']);
    $email     = sanitize($_POST['email']);
    $mobile    = sanitize($_POST['mobile']);
    $bio       = sanitize($_POST['bio']);

    $photo_field = $user['profile_photo'];

    // Profile photo upload
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === 0) {
        $allowed = ['jpg','jpeg','png','gif','webp'];
        $ext = strtolower(pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $filename = 'profile_' . $_SESSION['admin_id'] . '_' . time() . '.' . $ext;
            $dest = "../uploads/" . $filename;
            if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $dest)) {
                $photo_field = $filename;
            }
        } else {
            $message = ['type' => 'error', 'text' => 'Invalid image format. Use JPG, PNG or WebP.'];
        }
    }

    if (!$message) {
        try {
            $stmt = $pdo->prepare("UPDATE admin_users SET full_name=?, email=?, mobile=?, bio=?, profile_photo=? WHERE id=?");
            $stmt->execute([$full_name, $email, $mobile, $bio, $photo_field, $_SESSION['admin_id']]);
            $message = ['type' => 'success', 'text' => 'Profile updated successfully!'];
            // Refresh user
            $stmt2 = $pdo->prepare("SELECT * FROM admin_users WHERE id=?");
            $stmt2->execute([$_SESSION['admin_id']]);
            $user = $stmt2->fetch();
        } catch (PDOException $e) {
            $message = ['type' => 'error', 'text' => 'Database error: ' . $e->getMessage()];
        }
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current  = $_POST['current_password'];
    $new      = $_POST['new_password'];
    $confirm  = $_POST['confirm_password'];

    if (!password_verify($current, $user['password'])) {
        $message = ['type' => 'error', 'text' => 'Current password is incorrect.'];
    } elseif (strlen($new) < 6) {
        $message = ['type' => 'error', 'text' => 'New password must be at least 6 characters.'];
    } elseif ($new !== $confirm) {
        $message = ['type' => 'error', 'text' => 'Passwords do not match.'];
    } else {
        $hash = password_hash($new, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE admin_users SET password=? WHERE id=?");
        $stmt->execute([$hash, $_SESSION['admin_id']]);
        $message = ['type' => 'success', 'text' => 'Password changed successfully!'];
    }
}

$profilePhoto = ($user['profile_photo']) ? '../uploads/' . $user['profile_photo'] : 'https://github.com/manoranjan2050.png';
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

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Profile Card / Avatar -->
        <div class="glass-card rounded-2xl border border-gray-800 p-6 flex flex-col items-center text-center">
            <div class="relative mb-4">
                <img id="photo-preview" src="<?php echo $profilePhoto; ?>" class="w-32 h-32 rounded-full border-4 border-blue-500/40 object-cover shadow-lg">
                <label for="photo-input" class="absolute bottom-1 right-1 w-8 h-8 bg-blue-600 hover:bg-blue-500 rounded-full flex items-center justify-center cursor-pointer transition shadow">
                    <i class="fa-solid fa-camera text-white text-xs"></i>
                </label>
            </div>
            <h2 class="text-xl font-black text-white mb-1">
                <?php echo $user['full_name'] ? htmlspecialchars($user['full_name']) : htmlspecialchars($user['username']); ?>
            </h2>
            <p class="text-sm text-gray-500 mb-1">@<?php echo htmlspecialchars($user['username']); ?></p>
            <span class="px-3 py-1 bg-blue-500/10 text-blue-400 border border-blue-500/20 rounded-full text-xs font-bold">Administrator</span>

            <?php if ($user['bio']): ?>
            <p class="text-sm text-gray-400 mt-4 leading-relaxed italic">"<?php echo htmlspecialchars($user['bio']); ?>"</p>
            <?php endif; ?>

            <div class="w-full mt-6 pt-6 border-t border-gray-800 space-y-3">
                <?php if ($user['email']): ?>
                <div class="flex items-center gap-3 text-sm text-gray-400">
                    <i class="fa-solid fa-envelope w-5 text-gray-600"></i>
                    <span><?php echo htmlspecialchars($user['email']); ?></span>
                </div>
                <?php endif; ?>
                <?php if ($user['mobile']): ?>
                <div class="flex items-center gap-3 text-sm text-gray-400">
                    <i class="fa-solid fa-mobile-screen w-5 text-gray-600"></i>
                    <span><?php echo htmlspecialchars($user['mobile']); ?></span>
                </div>
                <?php endif; ?>
                <div class="flex items-center gap-3 text-sm text-gray-400">
                    <i class="fa-solid fa-calendar w-5 text-gray-600"></i>
                    <span>Joined <?php echo date('M Y', strtotime($user['created_at'])); ?></span>
                </div>
            </div>
        </div>

        <!-- Edit Form -->
        <div class="lg:col-span-2 space-y-6">

            <!-- Profile Info -->
            <div class="glass-card rounded-2xl border border-gray-800 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-800 flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-blue-500/15 flex items-center justify-center">
                        <i class="fa-solid fa-user text-blue-400 text-sm"></i>
                    </div>
                    <h3 class="font-bold text-white">Profile Information</h3>
                </div>
                <div class="p-6">
                    <form method="POST" enctype="multipart/form-data" class="space-y-4">
                        <input type="hidden" name="update_profile" value="1">
                        <input type="file" id="photo-input" name="profile_photo" accept="image/*" class="hidden">

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs text-gray-500 uppercase font-bold mb-1.5">Full Name</label>
                                <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>"
                                    class="w-full px-3 py-2.5 bg-gray-800 border border-gray-700 rounded-xl text-white text-sm placeholder-gray-600"
                                    placeholder="Your full name">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 uppercase font-bold mb-1.5">Username</label>
                                <input type="text" value="<?php echo htmlspecialchars($user['username']); ?>" disabled
                                    class="w-full px-3 py-2.5 bg-gray-800/50 border border-gray-700/50 rounded-xl text-gray-500 text-sm cursor-not-allowed">
                                <p class="text-[10px] text-gray-600 mt-1">Username cannot be changed</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs text-gray-500 uppercase font-bold mb-1.5">Email Address</label>
                                <input type="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>"
                                    class="w-full px-3 py-2.5 bg-gray-800 border border-gray-700 rounded-xl text-white text-sm placeholder-gray-600"
                                    placeholder="you@email.com">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 uppercase font-bold mb-1.5">Mobile Number</label>
                                <input type="tel" name="mobile" value="<?php echo htmlspecialchars($user['mobile'] ?? ''); ?>"
                                    class="w-full px-3 py-2.5 bg-gray-800 border border-gray-700 rounded-xl text-white text-sm placeholder-gray-600"
                                    placeholder="+91 9999999999">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs text-gray-500 uppercase font-bold mb-1.5">Bio / Tagline</label>
                            <textarea name="bio" rows="3"
                                class="w-full px-3 py-2.5 bg-gray-800 border border-gray-700 rounded-xl text-white text-sm placeholder-gray-600 resize-none"
                                placeholder="A short bio about yourself..."><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                        </div>

                        <button type="submit"
                            class="w-full md:w-auto px-6 py-2.5 bg-blue-600 hover:bg-blue-500 text-white font-bold rounded-xl transition text-sm flex items-center gap-2">
                            <i class="fa-solid fa-save"></i> Save Profile
                        </button>
                    </form>
                </div>
            </div>

            <!-- Change Password -->
            <div class="glass-card rounded-2xl border border-gray-800 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-800 flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-orange-500/15 flex items-center justify-center">
                        <i class="fa-solid fa-lock text-orange-400 text-sm"></i>
                    </div>
                    <h3 class="font-bold text-white">Change Password</h3>
                </div>
                <div class="p-6">
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="change_password" value="1">
                        <div>
                            <label class="block text-xs text-gray-500 uppercase font-bold mb-1.5">Current Password</label>
                            <div class="relative">
                                <input type="password" name="current_password" required id="pw-current"
                                    class="w-full px-3 py-2.5 bg-gray-800 border border-gray-700 rounded-xl text-white text-sm pr-10"
                                    placeholder="••••••••">
                                <button type="button" onclick="togglePw('pw-current')" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-300">
                                    <i class="fa-solid fa-eye text-xs"></i>
                                </button>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs text-gray-500 uppercase font-bold mb-1.5">New Password</label>
                                <div class="relative">
                                    <input type="password" name="new_password" required id="pw-new" minlength="6"
                                        class="w-full px-3 py-2.5 bg-gray-800 border border-gray-700 rounded-xl text-white text-sm pr-10"
                                        placeholder="Min 6 characters">
                                    <button type="button" onclick="togglePw('pw-new')" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-300">
                                        <i class="fa-solid fa-eye text-xs"></i>
                                    </button>
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 uppercase font-bold mb-1.5">Confirm Password</label>
                                <div class="relative">
                                    <input type="password" name="confirm_password" required id="pw-confirm"
                                        class="w-full px-3 py-2.5 bg-gray-800 border border-gray-700 rounded-xl text-white text-sm pr-10"
                                        placeholder="Repeat password">
                                    <button type="button" onclick="togglePw('pw-confirm')" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-300">
                                        <i class="fa-solid fa-eye text-xs"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div id="pw-strength" class="hidden">
                            <div class="flex gap-1 mb-1">
                                <div class="h-1 flex-1 rounded-full bg-gray-700" id="ps1"></div>
                                <div class="h-1 flex-1 rounded-full bg-gray-700" id="ps2"></div>
                                <div class="h-1 flex-1 rounded-full bg-gray-700" id="ps3"></div>
                                <div class="h-1 flex-1 rounded-full bg-gray-700" id="ps4"></div>
                            </div>
                            <p id="pw-strength-text" class="text-[10px] text-gray-500"></p>
                        </div>
                        <button type="submit"
                            class="w-full md:w-auto px-6 py-2.5 bg-orange-600 hover:bg-orange-500 text-white font-bold rounded-xl transition text-sm flex items-center gap-2">
                            <i class="fa-solid fa-key"></i> Update Password
                        </button>
                    </form>
                </div>
            </div>

        </div>
    </div>

<script>
    // Photo preview
    document.getElementById('photo-input').addEventListener('change', function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = (e) => {
                document.getElementById('photo-preview').src = e.target.result;
            };
            reader.readAsDataURL(file);
        }
    });

    // Toggle password visibility
    function togglePw(id) {
        const el = document.getElementById(id);
        el.type = el.type === 'password' ? 'text' : 'password';
    }

    // Password strength
    document.getElementById('pw-new').addEventListener('input', function() {
        const val = this.value;
        const strengthEl = document.getElementById('pw-strength');
        const text = document.getElementById('pw-strength-text');
        strengthEl.classList.remove('hidden');

        let score = 0;
        if (val.length >= 6) score++;
        if (val.length >= 10) score++;
        if (/[A-Z]/.test(val) && /[a-z]/.test(val)) score++;
        if (/[0-9]/.test(val) && /[^A-Za-z0-9]/.test(val)) score++;

        const colors = ['bg-red-500', 'bg-orange-500', 'bg-yellow-500', 'bg-green-500'];
        const labels = ['Weak', 'Fair', 'Good', 'Strong'];
        for (let i = 1; i <= 4; i++) {
            const bar = document.getElementById('ps' + i);
            bar.className = 'h-1 flex-1 rounded-full ' + (i <= score ? colors[score - 1] : 'bg-gray-700');
        }
        text.textContent = val.length > 0 ? 'Strength: ' + (labels[score - 1] || 'Too short') : '';
    });
</script>

<?php include 'includes/admin_footer.php'; ?>
