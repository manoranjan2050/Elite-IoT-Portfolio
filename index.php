<?php
require_once 'includes/db.php';
// Fetch data from DB
try {
    $projects = $pdo->query("SELECT * FROM projects ORDER BY created_at DESC")->fetchAll();
    $testimonials = $pdo->query("SELECT * FROM testimonials ORDER BY created_at DESC LIMIT 6")->fetchAll();
    $blogs = $pdo->query("SELECT * FROM blogs ORDER BY created_at DESC LIMIT 3")->fetchAll();
} catch (Exception $e) {
    $projects = $testimonials = $blogs = [];
}

$pageTitle = "Manoranjan | Developer, Trader, IoT Dev & Businessman";
include 'includes/header.php';
?>

    <!-- Hero Section -->
    <section id="home" class="min-h-screen flex items-center pt-20 px-6">
        <div class="container mx-auto grid md:grid-cols-2 gap-12 items-center">
            <div data-aos="fade-right" data-aos-duration="1000">
                <h2 class="text-blue-500 font-bold tracking-widest uppercase mb-4">Welcome to my world</h2>
                <h1 class="text-5xl md:text-7xl font-extrabold mb-6 leading-tight">
                    I'm a <span class="text-gradient" id="typewriter">Developer</span>
                </h1>
                <p class="text-xl text-gray-400 mb-8 max-w-lg leading-relaxed">
                    A multi-disciplinary innovator specializing in high-performance coding, strategic trading, IoT solutions, and PCB design. I bridge the gap between hardware and software.
                </p>
                <div class="flex flex-wrap gap-4">
                    <a href="power.php" class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-4 rounded-full font-bold transition transform hover:scale-105 shadow-lg shadow-blue-500/20">View My Work</a>
                    <a href="contact.php" class="border border-gray-700 hover:bg-gray-800 text-white px-8 py-4 rounded-full font-bold transition">Let's Talk</a>
                </div>
            </div>
            <div class="relative" data-aos="zoom-in" data-aos-duration="1000">
                <div class="w-full aspect-square glass rounded-3xl overflow-hidden relative group">
                    <img src="https://github.com/manoranjan2050.png" class="w-full h-full object-cover group-hover:scale-110 transition duration-500" alt="Manoranjan">
                    <div class="absolute -bottom-6 -right-6 w-32 h-32 bg-blue-500/20 rounded-full blur-2xl"></div>
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
                <?php foreach ($projects as $project): ?>
                    <div class="glass rounded-2xl overflow-hidden group hover:scale-[1.02] transition duration-300" data-aos="fade-up">
                        <div class="h-48 bg-gray-800 relative overflow-hidden">
                            <?php if ($project['image_url']): ?>
                                <img src="<?php echo $project['image_url']; ?>" alt="<?php echo $project['title']; ?>" class="w-full h-full object-cover group-hover:scale-110 transition duration-500">
                            <?php else: ?>
                                <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-gray-800 to-gray-900">
                                    <i class="fa-solid fa-folder-open text-5xl text-gray-700"></i>
                                </div>
                            <?php endif; ?>
                            <div class="absolute top-4 right-4 bg-blue-600 text-white text-xs font-bold px-3 py-1 rounded-full uppercase">
                                <?php echo $project['category']; ?>
                            </div>
                        </div>
                        <div class="p-6">
                            <h3 class="text-xl font-bold mb-2"><?php echo $project['title']; ?></h3>
                            <p class="text-gray-400 text-sm mb-6 line-clamp-3"><?php echo $project['description']; ?></p>
                            <div class="flex gap-4">
                                <?php if ($project['project_link']): ?>
                                    <a href="<?php echo $project['project_link']; ?>" target="_blank" class="text-blue-400 hover:text-white transition text-sm font-bold"><i class="fa-solid fa-link mr-2"></i>Live Demo</a>
                                <?php endif; ?>
                                <?php if ($project['github_link']): ?>
                                    <a href="<?php echo $project['github_link']; ?>" target="_blank" class="text-gray-400 hover:text-white transition text-sm font-bold"><i class="fa-brands fa-github mr-2"></i>Code</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($projects)): ?>
                    <div class="glass p-12 rounded-2xl col-span-full text-center border-dashed border-2 border-gray-700">
                        <p class="text-gray-500 italic">Projects are currently being updated.</p>
                    </div>
                <?php endif; ?>
            </div>
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
                            <p class="text-gray-500 text-sm mb-4 line-clamp-3"><?php echo $post['content']; ?></p>
                            <a href="#" class="text-white font-bold text-sm hover:text-blue-400 transition">Read Article <i class="fa-solid fa-arrow-right ml-2"></i></a>
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

<?php include 'includes/footer.php'; ?>
