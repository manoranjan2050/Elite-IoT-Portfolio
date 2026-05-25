<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle : "Manoranjan | Developer, Trader, IoT Dev & Businessman"; ?></title>
    <meta name="description" content="Portfolio of Manoranjan - Developer, Coder, Trader, PCB Designer, IoT Developer, and Businessman.">
    
    <!-- CSS and Fonts -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Inter', sans-serif; }
        .glass { background: rgba(17, 24, 39, 0.7); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.1); }
        .text-gradient { background: linear-gradient(to right, #60a5fa, #a855f7, #ec4899); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .blob { position: absolute; width: 500px; height: 500px; background: rgba(59, 130, 246, 0.2); filter: blur(80px); border-radius: 50%; z-index: -1; animation: move 20s infinite alternate; }
        @keyframes move { from { transform: translate(-10%, -10%); } to { transform: translate(10%, 10%); } }
    </style>
</head>
<body class="bg-gray-950 text-gray-100 overflow-x-hidden">

    <!-- IoT Lab Status Floating Widget -->
    <div class="fixed bottom-6 left-6 z-50 pointer-events-none md:pointer-events-auto">
        <div class="glass p-4 rounded-2xl shadow-2xl border-blue-500/30 flex items-center gap-4 transition-all hover:scale-105 group">
            <div class="relative">
                <div class="w-3 h-3 bg-green-500 rounded-full animate-ping absolute"></div>
                <div class="w-3 h-3 bg-green-500 rounded-full relative"></div>
            </div>
            <div>
                <p class="text-[10px] uppercase tracking-widest text-gray-500 font-bold">IoT Lab Status</p>
                <p class="text-sm font-bold text-white flex items-center gap-2">
                    ACTIVE <span class="text-blue-400" id="lab-temp">24°C</span>
                </p>
            </div>
        </div>
    </div>

    <!-- Decorative Blobs -->
    <div class="blob top-0 -left-20"></div>
    <div class="blob bottom-0 -right-20" style="background: rgba(168, 85, 247, 0.2);"></div>

    <!-- Navigation -->
    <nav class="fixed w-full z-50 glass transition-all duration-300 py-4">
        <div class="container mx-auto px-6 flex justify-between items-center">
            <a href="index.php" class="text-2xl font-extrabold tracking-tighter text-white">MANORANJAN<span class="text-blue-500">.DEV</span></a>
            
            <!-- Desktop Menu -->
            <div class="hidden md:flex space-x-8 font-medium">
                <a href="index.php" class="hover:text-blue-400 transition">Home</a>
                <a href="about.php" class="hover:text-blue-400 transition">About</a>
                <a href="power.php" class="hover:text-blue-400 transition text-blue-400 font-bold"><i class="fa-solid fa-bolt mr-2"></i>Power</a>
                <a href="index.php#projects" class="hover:text-blue-400 transition">Projects</a>
                <a href="index.php#blog" class="hover:text-blue-400 transition">Blog</a>
                <a href="contact.php" class="hover:text-blue-400 transition">Contact</a>
            </div>

            <!-- Mobile Toggle -->
            <button id="mobile-menu-btn" class="md:hidden text-2xl text-white focus:outline-none">
                <i class="fa-solid fa-bars-staggered"></i>
            </button>
        </div>

        <!-- Mobile Menu Overlay -->
        <div id="mobile-menu" class="hidden fixed inset-0 top-[72px] bg-gray-950/95 backdrop-blur-xl z-40 flex flex-col p-8 space-y-6 text-xl font-bold border-t border-white/5 md:hidden">
            <a href="index.php" class="mobile-link hover:text-blue-500">Home</a>
            <a href="about.php" class="mobile-link hover:text-blue-500">About</a>
            <a href="power.php" class="mobile-link text-blue-400"><i class="fa-solid fa-bolt mr-2"></i>Power</a>
            <a href="index.php#projects" class="mobile-link hover:text-blue-500">Projects</a>
            <a href="index.php#blog" class="mobile-link hover:text-blue-500">Blog</a>
            <a href="contact.php" class="mobile-link hover:text-blue-500">Contact</a>
        </div>
    </nav>

    <script>
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
            links.forEach(l => l.addEventListener('click', () => {
                menu.classList.add('hidden');
                const icon = btn.querySelector('i');
                icon.classList.add('fa-bars-staggered');
                icon.classList.remove('fa-xmark');
            }));
        });
    </script>
