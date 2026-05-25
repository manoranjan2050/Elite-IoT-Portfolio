<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

$pageTitle = "404 - System Offline | Manoranjan.dev";
include 'includes/header.php';
?>

<section class="min-h-screen flex items-center justify-center px-6 bg-[#030712] relative overflow-hidden">
    <!-- Decorative elements to match theme -->
    <div class="blob top-0 -left-20 opacity-20"></div>
    <div class="blob bottom-0 -right-20 opacity-20" style="background: rgba(168, 85, 247, 0.2);"></div>

    <div class="max-w-2xl w-full text-center relative z-10" data-aos="zoom-in">
        <div class="mb-8">
            <i class="fa-solid fa-microchip text-8xl text-blue-500/20 mb-4"></i>
            <h1 class="text-[12rem] font-black leading-none text-gradient opacity-50 select-none">404</h1>
        </div>
        
        <div class="glass p-10 rounded-[3rem] border-white/5 backdrop-blur-2xl">
            <h2 class="text-3xl font-bold text-white mb-4 italic">Node Path Not Found</h2>
            <p class="text-gray-400 text-lg mb-8 leading-relaxed">
                The requested URL does not exist in our system registry. This link might be broken or the resource has been moved.
            </p>
            
            <div class="flex flex-wrap justify-center gap-4">
                <a href="index.php" class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-4 rounded-full font-bold transition transform hover:scale-105 shadow-lg shadow-blue-500/20 flex items-center gap-2">
                    <i class="fa-solid fa-house"></i> Return Home
                </a>
                <a href="power.php" class="bg-white/5 hover:bg-white/10 text-white px-8 py-4 rounded-full font-bold transition flex items-center gap-2 border border-white/10">
                    <i class="fa-solid fa-bolt"></i> Power Station
                </a>
            </div>
        </div>
        
        <div class="mt-12 text-gray-600 text-sm font-bold tracking-widest uppercase">
            Error Code: <span class="text-blue-500">RESOURCE_NOT_FOUND_EXCEPTION</span>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
