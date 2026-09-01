<?php
require_once 'includes/db.php';
// Fetch data from DB
try {
    $projects = $pdo->query("SELECT * FROM projects ORDER BY (status = 'coming_soon') ASC, created_at DESC LIMIT 6")->fetchAll();
    $totalProjectCount = (int) $pdo->query("SELECT COUNT(*) FROM projects")->fetchColumn();
    $testimonials = $pdo->query("SELECT * FROM testimonials ORDER BY created_at DESC LIMIT 6")->fetchAll();
    $blogs = $pdo->query("SELECT * FROM blogs ORDER BY created_at DESC LIMIT 3")->fetchAll();
} catch (Exception $e) {
    $projects = $testimonials = $blogs = [];
}

$heroPhoto = 'https://github.com/manoranjan2050.png';
try {
    $hp = $pdo->query("SELECT profile_photo FROM admin_users ORDER BY id ASC LIMIT 1")->fetch();
    if ($hp && $hp['profile_photo']) {
        $heroPhoto = 'uploads/' . $hp['profile_photo'];
    }
} catch (Exception $e) {}

$pageTitle = "Manoranjan | Developer, Trader, IoT Dev & Businessman";
$pageDescription = "Manoranjan is a full-stack developer, IoT engineer, and PCB designer building Android apps, web platforms, and smart home tools under MTP Code.";
include 'includes/header.php';
?>

    <!-- Hero Section -->
    <section id="home" class="min-h-screen flex items-center pt-20 px-6 relative overflow-hidden">
        <canvas id="hero-dots" class="absolute inset-0 w-full h-full pointer-events-none opacity-70"></canvas>
        <div class="container mx-auto grid md:grid-cols-2 gap-12 items-center">
            <div data-aos="fade-right" data-aos-duration="1000">
                <h2 class="text-blue-500 font-bold tracking-widest uppercase mb-4">Welcome to my world</h2>
                <h1 class="text-5xl md:text-7xl font-extrabold mb-6 leading-tight">
                    I'm a <span class="text-gradient" id="typewriter">Developer</span>
                </h1>
                <p class="text-xl text-gray-400 mb-8 max-w-lg leading-relaxed">
                    A multi-disciplinary innovator specializing in high-performance coding, strategic trading, IoT solutions, and PCB design. I bridge the gap between hardware and software.
                </p>
                <div class="flex flex-wrap gap-4 mb-8">
                    <a href="power.php" class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-4 rounded-full font-bold transition transform hover:scale-105 shadow-lg shadow-blue-500/20">View My Work</a>
                    <a href="contact.php" class="border border-gray-700 hover:bg-gray-800 text-white px-8 py-4 rounded-full font-bold transition">Let's Talk</a>
                </div>
                <div class="flex items-center gap-4">
                    <a href="https://github.com/manoranjan2050" target="_blank" title="GitHub" class="w-11 h-11 glass rounded-xl flex items-center justify-center text-gray-400 hover:text-white hover:-translate-y-1 transition transform"><i class="fa-brands fa-github"></i></a>
                    <a href="https://linkedin.com/in/manoranjan2050" target="_blank" title="LinkedIn" class="w-11 h-11 glass rounded-xl flex items-center justify-center text-gray-400 hover:text-blue-400 hover:-translate-y-1 transition transform"><i class="fa-brands fa-linkedin"></i></a>
                    <a href="https://me.developers.google.com/u/manoranjan2050" target="_blank" title="Google Developer Profile" class="w-11 h-11 glass rounded-xl flex items-center justify-center text-gray-400 hover:text-green-400 hover:-translate-y-1 transition transform"><i class="fa-brands fa-google"></i></a>
                </div>
            </div>
            <div class="relative" data-aos="zoom-in" data-aos-duration="1000">
                <div class="w-full aspect-square glass rounded-3xl overflow-hidden relative group">
                    <img src="<?php echo htmlspecialchars($heroPhoto); ?>" class="w-full h-full object-cover group-hover:scale-110 transition duration-500" alt="Manoranjan">
                    <div class="absolute -bottom-6 -right-6 w-32 h-32 bg-blue-500/20 rounded-full blur-2xl"></div>
                    <div class="absolute -top-6 -left-6 w-24 h-24 bg-purple-500/20 rounded-full blur-2xl"></div>
                </div>
            </div>
        </div>
    </section>

    <!-- About Snippet -->
    <section class="py-24 px-6">
        <div class="container mx-auto flex flex-col md:flex-row items-center gap-12">
            <div class="md:w-1/2" data-aos="fade-right">
                <h2 class="text-4xl font-bold mb-6">Innovative Solutions for a <span class="text-blue-500">Digital World</span></h2>
                <p class="text-gray-400 text-lg leading-relaxed mb-8">
                    I am a multi-disciplinary expert combining the worlds of software development, hardware engineering, and financial markets. My goal is to build technology that matters.
                </p>
                <a href="about.php" class="inline-block bg-gray-900 border border-gray-800 hover:border-blue-500 text-white px-8 py-4 rounded-full font-bold transition">Learn More About Me</a>
            </div>
            <div class="md:w-1/2 grid grid-cols-2 gap-4" data-aos="fade-left">
                <div class="glass p-6 rounded-2xl text-center">
                    <i class="fa-solid fa-microchip text-3xl text-blue-500 mb-2"></i>
                    <p class="text-sm font-bold">IoT Expert</p>
                </div>
                <div class="glass p-6 rounded-2xl text-center">
                    <i class="fa-solid fa-chart-line text-3xl text-purple-500 mb-2"></i>
                    <p class="text-sm font-bold">Trader</p>
                </div>
                <div class="glass p-6 rounded-2xl text-center">
                    <i class="fa-solid fa-code text-3xl text-pink-500 mb-2"></i>
                    <p class="text-sm font-bold">Coder</p>
                </div>
                <div class="glass p-6 rounded-2xl text-center">
                    <i class="fa-solid fa-briefcase text-3xl text-yellow-500 mb-2"></i>
                    <p class="text-sm font-bold">Business</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Projects Section -->
    <section id="projects" class="py-24 px-6 bg-gray-900/50">
        <div class="container mx-auto">
            <div class="text-center mb-16" data-aos="fade-up">
                <h2 class="text-4xl font-bold mb-4">Featured Projects</h2>
                <p class="text-gray-400">A collection of my recent works across different domains.</p>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php foreach ($projects as $i => $project): $aosDelay = ($i % 6) * 60; include 'includes/project-card.php'; endforeach; ?>
                <?php if (empty($projects)): ?>
                    <div class="glass p-12 rounded-2xl col-span-full text-center border-dashed border-2 border-gray-700">
                        <p class="text-gray-500 italic">Projects are currently being updated.</p>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($totalProjectCount > 6): ?>
            <div class="text-center mt-14" data-aos="fade-up">
                <a href="projects.php" class="inline-flex items-center gap-2 bg-gray-900 border border-gray-800 hover:border-blue-500 text-white px-8 py-4 rounded-full font-bold transition">
                    View All <?php echo $totalProjectCount; ?> Projects <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Blog Section -->
    <section id="blog" class="py-24 px-6">
        <div class="container mx-auto">
            <div class="text-center mb-16" data-aos="fade-up">
                <h2 class="text-4xl font-bold mb-4">Latest Insights</h2>
                <p class="text-gray-400">Trading strategies, IoT trends, and business growth.</p>
            </div>
            <div class="grid md:grid-cols-3 gap-8">
                <?php foreach ($blogs as $post): ?>
                    <div class="glass rounded-2xl overflow-hidden group border border-gray-800" data-aos="fade-up">
                        <div class="h-48 overflow-hidden">
                            <?php if ($post['image_url']): ?>
                                <img src="<?php echo $post['image_url']; ?>" alt="Blog Image" class="w-full h-full object-cover group-hover:scale-110 transition duration-500">
                            <?php else: ?>
                                <div class="w-full h-full bg-gradient-to-br from-purple-900/40 to-blue-900/40 flex items-center justify-center">
                                    <i class="fa-solid fa-newspaper text-5xl text-gray-700"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="p-6">
                            <span class="text-xs font-bold text-blue-500 uppercase tracking-widest mb-2 block"><?php echo $post['category']; ?></span>
                            <h3 class="text-xl font-bold mb-3"><?php echo $post['title']; ?></h3>
                            <p class="text-gray-500 text-sm mb-4 line-clamp-3"><?php echo strip_tags($post['content']); ?></p>
                            <a href="post.php?id=<?php echo $post['id']; ?>" class="text-white font-bold text-sm hover:text-blue-400 transition">Read Article <i class="fa-solid fa-arrow-right ml-2"></i></a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <script>
        // Typewriter Effect
        const roles = ["Developer", "Coder", "Trader", "PCB Designer", "IoT Dev", "Businessman"];
        let roleIndex = 0;
        let charIndex = 0;
        let isDeleting = false;
        const typeTarget = document.getElementById("typewriter");

        function type() {
            const currentRole = roles[roleIndex];
            if (isDeleting) {
                typeTarget.textContent = currentRole.substring(0, charIndex - 1);
                charIndex--;
            } else {
                typeTarget.textContent = currentRole.substring(0, charIndex + 1);
                charIndex++;
            }

            let typeSpeed = isDeleting ? 100 : 200;
            if (!isDeleting && charIndex === currentRole.length) {
                typeSpeed = 2000;
                isDeleting = true;
            } else if (isDeleting && charIndex === 0) {
                isDeleting = false;
                roleIndex = (roleIndex + 1) % roles.length;
                typeSpeed = 500;
            }

            setTimeout(type, typeSpeed);
        }
        type();
    </script>

    <!-- Interactive Dot-Grid Hero Background -->
    <script>
    (function() {
        const canvas = document.getElementById('hero-dots');
        if (!canvas) return;
        const ctx = canvas.getContext('2d');
        const heroSection = canvas.closest('section');

        let w, h, dots = [];
        const spacing = 34;
        const mouse = { x: -9999, y: -9999 };

        function resize() {
            w = canvas.width = heroSection.offsetWidth;
            h = canvas.height = heroSection.offsetHeight;
            dots = [];
            for (let y = spacing; y < h; y += spacing) {
                for (let x = spacing; x < w; x += spacing) {
                    dots.push({ x, y, baseX: x, baseY: y });
                }
            }
        }

        function draw() {
            ctx.clearRect(0, 0, w, h);
            for (const d of dots) {
                const dx = mouse.x - d.baseX;
                const dy = mouse.y - d.baseY;
                const dist = Math.sqrt(dx * dx + dy * dy);
                const radius = 140;

                let size = 1.4;
                let alpha = 0.18;
                let color = '96,165,250'; // blue-400

                if (dist < radius) {
                    const force = (radius - dist) / radius;
                    size = 1.4 + force * 3.2;
                    alpha = 0.18 + force * 0.65;
                    color = force > 0.5 ? '192,132,252' : '96,165,250'; // purple near cursor, blue further

                    // gentle repel
                    d.x = d.baseX - dx * force * 0.15;
                    d.y = d.baseY - dy * force * 0.15;
                } else {
                    d.x += (d.baseX - d.x) * 0.1;
                    d.y += (d.baseY - d.y) * 0.1;
                }

                ctx.beginPath();
                ctx.arc(d.x, d.y, size, 0, Math.PI * 2);
                ctx.fillStyle = `rgba(${color},${alpha})`;
                ctx.fill();
            }
            requestAnimationFrame(draw);
        }

        heroSection.addEventListener('mousemove', (e) => {
            const rect = heroSection.getBoundingClientRect();
            mouse.x = e.clientX - rect.left;
            mouse.y = e.clientY - rect.top;
        });
        heroSection.addEventListener('mouseleave', () => {
            mouse.x = -9999;
            mouse.y = -9999;
        });

        window.addEventListener('resize', resize);
        resize();
        draw();
    })();
    </script>

<?php include 'includes/footer.php'; ?>
