<?php
require_once 'functions.php';
if (isset($pdo)) {
    trackVisit($pdo);
}
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-LJWV043VT1"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());
      gtag('config', 'G-LJWV043VT1');
    </script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle : "Manoranjan | Developer, Trader, IoT Dev & Businessman"; ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($pageDescription ?? 'Portfolio of Manoranjan - Developer, Coder, Trader, PCB Designer, IoT Developer, and Businessman.'); ?>">
    <link rel="canonical" href="https://manoranjan.dev<?php echo $_SERVER['REQUEST_URI'] === '/index.php' ? '/' : strtok($_SERVER['REQUEST_URI'], '?'); ?>">
    <meta property="og:title" content="<?php echo htmlspecialchars($pageTitle ?? 'Manoranjan.dev'); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($pageDescription ?? 'Portfolio of Manoranjan - Developer, Coder, Trader, PCB Designer, IoT Developer, and Businessman.'); ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://manoranjan.dev<?php echo strtok($_SERVER['REQUEST_URI'], '?'); ?>">
    <meta property="og:image" content="https://manoranjan.dev/assets/favicon.png">
    <meta name="twitter:card" content="summary">

    <link rel="icon" type="image/png" href="/assets/favicon.png">
    <link rel="shortcut icon" href="/assets/favicon.ico">
    <link rel="apple-touch-icon" href="/assets/favicon.png">

    <link rel="stylesheet" href="/assets/css/tailwind.css?v=<?php echo filemtime(__DIR__ . '/../assets/css/tailwind.css'); ?>">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="bg-gray-950 text-gray-100 overflow-x-hidden">

    <!-- Scroll Progress Bar -->
    <div id="scroll-progress"></div>

    <!-- IoT Lab Status Floating Widget -->
    <div class="fixed bottom-6 left-6 z-50 pointer-events-none md:pointer-events-auto">
        <a href="power.php" class="glass p-3 md:p-4 rounded-2xl shadow-2xl border-blue-500/30 flex items-center gap-3 transition-all hover:scale-105 group hover:border-blue-500/60 block">
            <div class="relative flex-shrink-0">
                <div class="w-3 h-3 bg-green-500 rounded-full ping-slow absolute" id="iot-ping"></div>
                <div class="w-3 h-3 bg-green-500 rounded-full relative" id="iot-dot"></div>
            </div>
            <div class="hidden md:block">
                <p class="text-[10px] uppercase tracking-widest text-gray-500 font-bold">IoT Lab</p>
                <p class="text-sm font-bold text-white flex items-center gap-2">
                    <span id="iot-status">ACTIVE</span>
                    <span class="text-blue-400" id="lab-temp">--°C</span>
                </p>
            </div>
        </a>
    </div>

    <!-- Decorative Blobs -->
    <div class="blob top-0 -left-20"></div>
    <div class="blob bottom-0 -right-20" style="background: rgba(168, 85, 247, 0.2);"></div>

    <!-- Navigation -->
    <nav class="fixed w-full z-50 glass transition-all duration-300 py-4" id="main-nav">
        <div class="container mx-auto px-6 flex justify-between items-center">
            <a href="index.php" class="text-2xl font-extrabold tracking-tighter text-white group">
                MANORANJAN<span class="text-blue-500 group-hover:text-blue-400 transition">.DEV</span>
            </a>

            <!-- Desktop Menu -->
            <div class="hidden md:flex space-x-8 font-medium items-center">
                <?php
                $currentFile = basename($_SERVER['PHP_SELF']);
                $navLinks = [
                    'index.php'      => 'Home',
                    'about.php'      => 'About',
                    'power.php'      => 'Power',
                    'hacontrol.php'  => 'Control',
                    'projects.php'   => 'Projects',
                    'gallery.php'    => 'Gallery',
                    '#blog'          => 'Blog',
                    'contact.php'    => 'Contact',
                ];
                foreach ($navLinks as $href => $label):
                    $isActive   = ($href === $currentFile);
                    $isPower    = ($href === 'power.php');
                    $isControl  = ($href === 'hacontrol.php');
                    $cls = "nav-link hover:text-blue-400 transition text-sm " . ($isActive ? 'active text-blue-400' : 'text-gray-300') . ($isPower || $isControl ? ' font-bold' : '');
                    $finalHref = (strpos($href, '#') === 0) ? 'index.php' . $href : $href;
                ?>
                <a href="<?php echo $finalHref; ?>" class="<?php echo $cls; ?>">
                    <?php if ($isPower): ?><i class="fa-solid fa-bolt mr-1 text-yellow-400 text-xs"></i><?php endif; ?>
                    <?php if ($isControl): ?><i class="fa-solid fa-sliders mr-1 text-purple-400 text-xs"></i><?php endif; ?>
                    <?php echo $label; ?>
                </a>
                <?php endforeach; ?>
                <?php if (isLoggedIn()): ?>
                <a href="admin/index.php" class="px-3 py-1.5 bg-blue-600/20 hover:bg-blue-600/40 text-blue-400 border border-blue-500/30 rounded-lg text-xs font-bold transition flex items-center gap-1.5">
                    <i class="fa-solid fa-gauge text-[10px]"></i> Admin
                </a>
                <?php endif; ?>
            </div>

            <!-- Mobile Toggle -->
            <button id="mobile-menu-btn" class="md:hidden text-2xl text-white focus:outline-none">
                <i class="fa-solid fa-bars-staggered"></i>
            </button>
        </div>

        <!-- Mobile Menu Overlay -->
        <div id="mobile-menu" class="hidden fixed inset-0 top-[72px] bg-gray-950/97 backdrop-blur-xl z-40 flex flex-col p-8 space-y-4 text-xl font-bold border-t border-white/5 md:hidden">
            <a href="index.php" class="mobile-link hover:text-blue-500 transition py-2 border-b border-gray-800/50">Home</a>
            <a href="about.php" class="mobile-link hover:text-blue-500 transition py-2 border-b border-gray-800/50">About</a>
            <a href="power.php" class="mobile-link text-blue-400 py-2 border-b border-gray-800/50">
                <i class="fa-solid fa-bolt mr-2 text-yellow-400"></i>Power
            </a>
            <a href="hacontrol.php" class="mobile-link text-purple-400 py-2 border-b border-gray-800/50">
                <i class="fa-solid fa-sliders mr-2 text-purple-400"></i>Control
            </a>
            <a href="projects.php" class="mobile-link hover:text-blue-500 transition py-2 border-b border-gray-800/50">Projects</a>
            <a href="gallery.php" class="mobile-link hover:text-blue-500 transition py-2 border-b border-gray-800/50">Gallery</a>
            <a href="index.php#blog" class="mobile-link hover:text-blue-500 transition py-2 border-b border-gray-800/50">Blog</a>
            <a href="contact.php" class="mobile-link hover:text-blue-500 transition py-2">Contact</a>
            <?php if (isLoggedIn()): ?>
            <a href="admin/index.php" class="mobile-link text-blue-400 py-2 flex items-center gap-2">
                <i class="fa-solid fa-gauge"></i> Admin Panel
            </a>
            <?php endif; ?>
        </div>
    </nav>

    <script>
        // Mobile menu
        const btn = document.getElementById('mobile-menu-btn');
        const menu = document.getElementById('mobile-menu');
        const links = document.querySelectorAll('.mobile-link');

        btn.addEventListener('click', () => {
            menu.classList.toggle('hidden');
            const icon = btn.querySelector('i');
            icon.classList.toggle('fa-bars-staggered');
            icon.classList.toggle('fa-xmark');
        });
        links.forEach(link => {
            link.addEventListener('click', () => {
                menu.classList.add('hidden');
                const icon = btn.querySelector('i');
                icon.classList.add('fa-bars-staggered');
                icon.classList.remove('fa-xmark');
            });
        });

        // Scroll progress bar
        window.addEventListener('scroll', () => {
            const scrolled = (window.scrollY / (document.body.scrollHeight - window.innerHeight)) * 100;
            document.getElementById('scroll-progress').style.width = scrolled + '%';
        });

        // Compact nav on scroll
        window.addEventListener('scroll', () => {
            const nav = document.getElementById('main-nav');
            if (window.scrollY > 50) {
                nav.classList.add('py-2');
                nav.classList.remove('py-4');
            } else {
                nav.classList.add('py-4');
                nav.classList.remove('py-2');
            }
        });

        // Fetch IoT lab temp for widget
        async function fetchLabTemp() {
            try {
                const res = await fetch('api/ha_proxy.php?entity=sensor.flin_energy_battery_temperature&_t=' + Date.now());
                const data = await res.json();
                if (data.state && !data.error) {
                    document.getElementById('lab-temp').textContent = parseFloat(data.state).toFixed(1) + '°C';
                }
            } catch(e) {}
        }
        fetchLabTemp();
        setInterval(fetchLabTemp, 60000);
    </script>
