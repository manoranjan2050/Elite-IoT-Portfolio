<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (isLoggedIn()) {
    header("Location: admin/index.php");
    exit();
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];

    if (!empty($username) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT id, username, password, full_name, profile_photo FROM admin_users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            header("Location: admin/index.php");
            exit();
        } else {
            $error = "Invalid username or password.";
        }
    } else {
        $error = "Please fill in all fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login — Manoranjan.dev</title>
    <link rel="stylesheet" href="/assets/css/tailwind.css?v=<?php echo filemtime(__DIR__ . '/assets/css/tailwind.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .glass {
            background: rgba(17, 24, 39, 0.85);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.08);
        }
        @keyframes blob-move {
            0%,100% { transform: translate(0,0) scale(1); }
            33%      { transform: translate(30px,-50px) scale(1.05); }
            66%      { transform: translate(-20px,20px) scale(0.95); }
        }
        .blob { animation: blob-move 15s infinite ease-in-out; }
        .blob2 { animation: blob-move 18s infinite ease-in-out reverse; animation-delay: -5s; }
        .blob3 { animation: blob-move 20s infinite ease-in-out; animation-delay: -10s; }

        @keyframes float-in {
            from { opacity: 0; transform: translateY(30px) scale(0.97); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }
        .login-card { animation: float-in 0.6s ease forwards; }

        input { outline: none !important; }
        input:focus { border-color: #3b82f6 !important; box-shadow: 0 0 0 3px rgba(59,130,246,0.15); }
        .btn-login {
            background: linear-gradient(135deg, #3b82f6, #6366f1);
            transition: all 0.2s;
        }
        .btn-login:hover {
            background: linear-gradient(135deg, #2563eb, #4f46e5);
            transform: translateY(-1px);
            box-shadow: 0 8px 25px rgba(59,130,246,0.4);
        }
        .btn-login:active { transform: translateY(0); }

        @keyframes shake {
            0%,100% { transform: translateX(0); }
            20%,60%  { transform: translateX(-6px); }
            40%,80%  { transform: translateX(6px); }
        }
        .shake { animation: shake 0.4s ease; }

        /* Particle dots */
        .dot { position: absolute; border-radius: 50%; opacity: 0; animation: dot-blink 3s infinite; }
        @keyframes dot-blink { 0%,100%{opacity:0;} 50%{opacity:0.6;} }
    </style>
</head>
<body class="bg-gray-950 text-gray-100 min-h-screen flex items-center justify-center overflow-hidden relative">

    <!-- Animated background blobs -->
    <div class="blob absolute w-[500px] h-[500px] rounded-full bg-blue-600/20 blur-[100px] -top-32 -left-32 pointer-events-none"></div>
    <div class="blob2 absolute w-[400px] h-[400px] rounded-full bg-purple-600/15 blur-[100px] bottom-0 right-0 pointer-events-none"></div>
    <div class="blob3 absolute w-[300px] h-[300px] rounded-full bg-indigo-600/10 blur-[80px] top-1/2 left-1/2 pointer-events-none"></div>

    <!-- Grid overlay -->
    <div class="absolute inset-0 opacity-[0.03] pointer-events-none"
         style="background-image: linear-gradient(rgba(255,255,255,0.5) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.5) 1px, transparent 1px); background-size: 40px 40px;"></div>

    <div class="relative z-10 w-full max-w-md px-4">
        <div class="login-card glass rounded-3xl shadow-2xl p-8 md:p-10">

            <!-- Header -->
            <div class="text-center mb-8">
                <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-blue-600 to-indigo-600 flex items-center justify-center mx-auto mb-4 shadow-lg shadow-blue-500/30">
                    <i class="fa-solid fa-shield-halved text-2xl text-white"></i>
                </div>
                <h1 class="text-2xl font-black text-white tracking-tight">Admin Login</h1>
                <p class="text-sm text-gray-500 mt-1">manoranjan<span class="text-blue-400">.dev</span> control panel</p>
            </div>

            <!-- Error -->
            <?php if ($error): ?>
            <div id="error-box" class="mb-6 px-4 py-3 bg-red-500/10 border border-red-500/30 rounded-xl text-sm text-red-400 flex items-center gap-3">
                <i class="fa-solid fa-triangle-exclamation flex-shrink-0"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>

            <!-- Form -->
            <form method="POST" action="" id="login-form" class="space-y-5">
                <div>
                    <label class="block text-xs text-gray-500 uppercase font-bold mb-2 tracking-wider">Username</label>
                    <div class="relative">
                        <div class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-500">
                            <i class="fa-solid fa-user text-sm"></i>
                        </div>
                        <input
                            type="text"
                            name="username"
                            id="username"
                            autofocus
                            required
                            autocomplete="username"
                            class="w-full pl-10 pr-4 py-3 bg-gray-800/70 border border-gray-700 rounded-xl text-white text-sm placeholder-gray-600 transition"
                            placeholder="Enter username">
                    </div>
                </div>

                <div>
                    <label class="block text-xs text-gray-500 uppercase font-bold mb-2 tracking-wider">Password</label>
                    <div class="relative">
                        <div class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-500">
                            <i class="fa-solid fa-lock text-sm"></i>
                        </div>
                        <input
                            type="password"
                            name="password"
                            id="password"
                            required
                            autocomplete="current-password"
                            class="w-full pl-10 pr-12 py-3 bg-gray-800/70 border border-gray-700 rounded-xl text-white text-sm placeholder-gray-600 transition"
                            placeholder="Enter password">
                        <button type="button" onclick="togglePw()" class="absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-300 transition">
                            <i class="fa-solid fa-eye text-sm" id="pw-eye"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" id="login-btn" class="btn-login w-full py-3.5 rounded-xl text-white font-bold text-sm flex items-center justify-center gap-2">
                    <i class="fa-solid fa-right-to-bracket"></i>
                    <span id="btn-text">Sign In</span>
                </button>
            </form>

            <!-- Back link -->
            <div class="mt-6 text-center">
                <a href="index.php" class="text-sm text-gray-600 hover:text-gray-400 transition flex items-center justify-center gap-2">
                    <i class="fa-solid fa-arrow-left text-xs"></i> Back to Website
                </a>
            </div>

            <!-- Security note -->
            <div class="mt-6 pt-5 border-t border-gray-800">
                <p class="text-[10px] text-gray-700 text-center flex items-center justify-center gap-2">
                    <i class="fa-solid fa-shield-check"></i>
                    Secure admin area — Unauthorized access is prohibited
                </p>
            </div>
        </div>
    </div>

<script>
    function togglePw() {
        const pw = document.getElementById('password');
        const eye = document.getElementById('pw-eye');
        if (pw.type === 'password') {
            pw.type = 'text';
            eye.className = 'fa-solid fa-eye-slash text-sm';
        } else {
            pw.type = 'password';
            eye.className = 'fa-solid fa-eye text-sm';
        }
    }

    // Loading state on submit
    document.getElementById('login-form').addEventListener('submit', function() {
        const btn = document.getElementById('login-btn');
        const txt = document.getElementById('btn-text');
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i><span>Authenticating...</span>';
    });

    // Shake on error
    <?php if ($error): ?>
    document.getElementById('login-form').classList.add('shake');
    <?php endif; ?>
</script>
</body>
</html>
