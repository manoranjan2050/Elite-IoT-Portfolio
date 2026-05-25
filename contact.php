<?php
require_once 'includes/db.php';
$pageTitle = "Contact Us | Manoranjan";
include 'includes/header.php';
?>

    <section class="min-h-screen pt-32 pb-24 px-6">
        <div class="container mx-auto">
            <div class="max-w-5xl mx-auto" data-aos="fade-up">
                <div class="text-center mb-16">
                    <h1 class="text-5xl font-extrabold mb-4"><span class="text-gradient">Get In Touch</span></h1>
                    <p class="text-gray-400 text-lg">Have a project or just want to discuss some tech? I'm all ears.</p>
                </div>

                <div class="grid md:grid-cols-3 gap-8">
                    <div class="md:col-span-1 space-y-6">
                        <div class="glass p-8 rounded-3xl border-blue-500/10">
                            <div class="w-12 h-12 bg-blue-500/10 rounded-full flex items-center justify-center text-blue-500 mb-6">
                                <i class="fa-solid fa-envelope"></i>
                            </div>
                            <h4 class="text-xl font-bold mb-2">Email Me</h4>
                            <p class="text-gray-400 text-sm">hello@manoranjan.dev</p>
                        </div>
                        <div class="glass p-8 rounded-3xl border-purple-500/10">
                            <div class="w-12 h-12 bg-purple-500/10 rounded-full flex items-center justify-center text-purple-500 mb-6">
                                <i class="fa-solid fa-phone"></i>
                            </div>
                            <h4 class="text-xl font-bold mb-2">Call Me</h4>
                            <p class="text-gray-400 text-sm">Available for consultations</p>
                        </div>
                        <div class="glass p-8 rounded-3xl border-pink-500/10">
                            <div class="w-12 h-12 bg-pink-500/10 rounded-full flex items-center justify-center text-pink-500 mb-6">
                                <i class="fa-solid fa-location-dot"></i>
                            </div>
                            <h4 class="text-xl font-bold mb-2">Location</h4>
                            <p class="text-gray-400 text-sm">Global / Remote</p>
                        </div>
                    </div>

                    <div class="md:col-span-2">
                        <div class="glass p-10 rounded-3xl border-gray-800">
                            <form id="contact-form" class="space-y-6">
                                <div class="grid md:grid-cols-2 gap-6">
                                    <div class="space-y-2">
                                        <label class="text-sm font-bold text-gray-400 ml-1">Full Name</label>
                                        <input type="text" placeholder="John Doe" class="w-full p-4 bg-gray-900 border border-gray-800 rounded-xl focus:border-blue-500 outline-none transition" required>
                                    </div>
                                    <div class="space-y-2">
                                        <label class="text-sm font-bold text-gray-400 ml-1">Email Address</label>
                                        <input type="email" placeholder="john@example.com" class="w-full p-4 bg-gray-900 border border-gray-800 rounded-xl focus:border-blue-500 outline-none transition" required>
                                    </div>
                                </div>
                                <div class="space-y-2">
                                    <label class="text-sm font-bold text-gray-400 ml-1">Subject</label>
                                    <input type="text" placeholder="Project Inquiry" class="w-full p-4 bg-gray-900 border border-gray-800 rounded-xl focus:border-blue-500 outline-none transition" required>
                                </div>
                                <div class="space-y-2">
                                    <label class="text-sm font-bold text-gray-400 ml-1">Message</label>
                                    <textarea placeholder="Tell me more about your idea..." rows="6" class="w-full p-4 bg-gray-900 border border-gray-800 rounded-xl focus:border-blue-500 outline-none transition" required></textarea>
                                </div>
                                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-5 rounded-xl transition shadow-lg shadow-blue-500/30 transform hover:-translate-y-1">
                                    Send Message <i class="fa-solid fa-paper-plane ml-2"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

<?php include 'includes/footer.php'; ?>
