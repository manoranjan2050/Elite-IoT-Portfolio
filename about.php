<?php
require_once 'includes/db.php';
$pageTitle = "About Me | Manoranjan";
$pageDescription = "Manoranjan is a full-stack developer and IoT engineer with 11+ years of experience, running MTP Code.";

$aboutPhoto = 'https://github.com/manoranjan2050.png';
try {
    $ap = $pdo->query("SELECT profile_photo FROM admin_users ORDER BY id ASC LIMIT 1")->fetch();
    if ($ap && $ap['profile_photo']) {
        $aboutPhoto = 'uploads/' . $ap['profile_photo'];
    }
} catch (Exception $e) {}

include 'includes/header.php';
?>

    <section class="min-h-screen pt-32 pb-24 px-6">
        <div class="container mx-auto">
            <div class="max-w-4xl mx-auto" data-aos="fade-up">
                <h1 class="text-5xl font-extrabold mb-8"><span class="text-gradient">About Me</span></h1>
                
                <div class="grid md:grid-cols-2 gap-12 items-start mb-16">
                    <div class="order-2 md:order-1">
                        <p class="text-xl text-gray-400 leading-relaxed mb-6">
                            I am <span class="text-white font-bold">Manoranjan</span>, a versatile technologist and entrepreneur with a passion for building systems that bridge the gap between digital logic and physical reality.
                        </p>
                        <p class="text-gray-400 leading-relaxed mb-6">
                            My journey began as a coder, but my curiosity quickly led me to explore the worlds of hardware through IoT and PCB design, and finance through strategic trading. Today, I run <span class="text-white font-bold">MTP Code</span>, balancing these disciplines to create innovative solutions for complex problems.
                        </p>
                    </div>
                    <div class="order-1 md:order-2 flex justify-center">
                        <img src="<?php echo htmlspecialchars($aboutPhoto); ?>" class="w-64 h-64 rounded-3xl object-cover shadow-2xl border-4 border-blue-500/20" alt="Manoranjan">
                    </div>
                </div>

                <div class="grid md:grid-cols-2 gap-8 mb-16">
                    <div class="glass p-8 rounded-3xl border-blue-500/20">
                        <h3 class="text-2xl font-bold mb-6 text-white">Quick Facts</h3>
                        <ul class="space-y-4 text-gray-400">
                            <li class="flex items-center gap-3">
                                <i class="fa-solid fa-check text-blue-500"></i> Full-stack developer — Laravel, Next.js, Flutter & Kotlin.
                            </li>
                            <li class="flex items-center gap-3">
                                <i class="fa-solid fa-check text-blue-500"></i> Custom PCB Design & IoT Prototyping.
                            </li>
                            <li class="flex items-center gap-3">
                                <i class="fa-solid fa-check text-blue-500"></i> Data-driven Trading Strategies.
                            </li>
                            <li class="flex items-center gap-3">
                                <i class="fa-solid fa-check text-blue-500"></i> 11+ Years in Tech Entrepreneurship.
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="grid md:grid-cols-3 gap-8 mb-16">
                    <div class="glass p-8 rounded-2xl">
                        <h4 class="text-3xl font-extrabold text-blue-500 mb-2">50+</h4>
                        <p class="text-gray-500 font-bold uppercase tracking-wider text-xs">Projects Completed</p>
                    </div>
                    <div class="glass p-8 rounded-2xl">
                        <h4 class="text-3xl font-extrabold text-purple-500 mb-2">10+</h4>
                        <p class="text-gray-500 font-bold uppercase tracking-wider text-xs">IoT Solutions</p>
                    </div>
                    <div class="glass p-8 rounded-2xl">
                        <h4 class="text-3xl font-extrabold text-pink-500 mb-2">100%</h4>
                        <p class="text-gray-500 font-bold uppercase tracking-wider text-xs">Client Satisfaction</p>
                    </div>
                </div>

                <div class="space-y-12">
                    <h2 class="text-3xl font-bold">My Mission</h2>
                    <p class="text-xl text-gray-400 italic border-l-4 border-blue-500 pl-6 leading-relaxed">
                        "To leverage cutting-edge technology and data-driven insights to build products that empower businesses and simplify human interaction with technology."
                    </p>
                </div>
            </div>
        </div>
    </section>

<?php include 'includes/footer.php'; ?>
