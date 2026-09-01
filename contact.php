<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

$pageTitle = "Contact Us | Manoranjan";
$pageDescription = "Get in touch with Manoranjan for project inquiries, collaborations, or tech discussions.";
$formMessage = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $name    = sanitize($_POST['name'] ?? '');
    $email   = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $subject = sanitize($_POST['subject'] ?? '');
    $body    = sanitize($_POST['message'] ?? '');

    if (!$name || !filter_var($email, FILTER_VALIDATE_EMAIL) || !$subject || !$body) {
        $formMessage = ['type' => 'error', 'text' => 'Please fill in all fields with a valid email address.'];
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO messages (name, email, subject, message, ip_address) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $email, $subject, $body, $_SERVER['REMOTE_ADDR'] ?? null]);

            // Notify admin by email (best-effort, doesn't block the success response)
            $notifyEmail = defined('CONTACT_NOTIFY_EMAIL') ? CONTACT_NOTIFY_EMAIL : null;
            if (!$notifyEmail && file_exists(__DIR__ . '/includes/mail_config.php')) {
                require_once __DIR__ . '/includes/mail_config.php';
                $notifyEmail = defined('CONTACT_NOTIFY_EMAIL') ? CONTACT_NOTIFY_EMAIL : null;
            }
            if ($notifyEmail) {
                $html = "<h3>New contact message</h3>
                    <p><b>Name:</b> " . htmlspecialchars($name) . "</p>
                    <p><b>Email:</b> " . htmlspecialchars($email) . "</p>
                    <p><b>Subject:</b> " . htmlspecialchars($subject) . "</p>
                    <p><b>Message:</b><br>" . nl2br(htmlspecialchars($body)) . "</p>";
                sendMail($notifyEmail, 'New message: ' . $subject, $html, $email);
            }

            // Telegram notification (best-effort)
            sendTelegram("📩 <b>New contact message</b>\nFrom: {$name} ({$email})\nSubject: {$subject}\n\n{$body}");

            $formMessage = ['type' => 'success', 'text' => "Thanks {$name}! Your message has been sent. I'll get back to you soon."];
        } catch (PDOException $e) {
            $formMessage = ['type' => 'error', 'text' => 'Something went wrong. Please try again later.'];
        }
    }
}

include 'includes/header.php';
?>

    <section class="min-h-screen pt-32 pb-24 px-6">
        <div class="container mx-auto">
            <div class="max-w-5xl mx-auto" data-aos="fade-up">
                <div class="text-center mb-16">
                    <h1 class="text-5xl font-extrabold mb-4"><span class="text-gradient">Get In Touch</span></h1>
                    <p class="text-gray-400 text-lg">Have a project or just want to discuss some tech? I'm all ears.</p>
                </div>

                <?php if ($formMessage): ?>
                <div class="max-w-3xl mx-auto mb-10 px-5 py-4 rounded-2xl text-sm font-medium flex items-center gap-3 border
                    <?php echo $formMessage['type'] === 'success' ? 'bg-green-500/10 text-green-400 border-green-500/20' : 'bg-red-500/10 text-red-400 border-red-500/20'; ?>">
                    <i class="fa-solid <?php echo $formMessage['type'] === 'success' ? 'fa-circle-check' : 'fa-circle-xmark'; ?>"></i>
                    <?php echo htmlspecialchars($formMessage['text']); ?>
                </div>
                <?php endif; ?>

                <div class="grid md:grid-cols-3 gap-8">
                    <div class="md:col-span-1 space-y-6">
                        <div class="glass p-8 rounded-3xl border-blue-500/10">
                            <div class="w-12 h-12 bg-blue-500/10 rounded-full flex items-center justify-center text-blue-500 mb-6">
                                <i class="fa-solid fa-envelope"></i>
                            </div>
                            <h4 class="text-xl font-bold mb-2">Email Me</h4>
                            <p class="text-gray-400 text-sm">
                                <a href="mailto:iam@manoranjan.dev" class="hover:text-blue-400 transition block">iam@manoranjan.dev</a>
                                <a href="mailto:manoranjan2050@live.com" class="hover:text-blue-400 transition block mt-1">manoranjan2050@live.com</a>
                            </p>
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
                            <form id="contact-form" method="POST" class="space-y-6">
                                <input type="hidden" name="send_message" value="1">
                                <div class="grid md:grid-cols-2 gap-6">
                                    <div class="space-y-2">
                                        <label class="text-sm font-bold text-gray-400 ml-1">Full Name</label>
                                        <input type="text" name="name" placeholder="John Doe" class="w-full p-4 bg-gray-900 border border-gray-800 rounded-xl focus:border-blue-500 outline-none transition" required>
                                    </div>
                                    <div class="space-y-2">
                                        <label class="text-sm font-bold text-gray-400 ml-1">Email Address</label>
                                        <input type="email" name="email" placeholder="john@example.com" class="w-full p-4 bg-gray-900 border border-gray-800 rounded-xl focus:border-blue-500 outline-none transition" required>
                                    </div>
                                </div>
                                <div class="space-y-2">
                                    <label class="text-sm font-bold text-gray-400 ml-1">Subject</label>
                                    <input type="text" name="subject" placeholder="Project Inquiry" class="w-full p-4 bg-gray-900 border border-gray-800 rounded-xl focus:border-blue-500 outline-none transition" required>
                                </div>
                                <div class="space-y-2">
                                    <label class="text-sm font-bold text-gray-400 ml-1">Message</label>
                                    <textarea name="message" placeholder="Tell me more about your idea..." rows="6" class="w-full p-4 bg-gray-900 border border-gray-800 rounded-xl focus:border-blue-500 outline-none transition" required></textarea>
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
