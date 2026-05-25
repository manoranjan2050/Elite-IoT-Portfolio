<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

redirectToLogin();

$message = "";

// Handle Add Testimonial
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_testimonial'])) {
    $client_name = sanitize($_POST['client_name']);
    $client_role = sanitize($_POST['client_role']);
    $content = sanitize($_POST['content']);

    $stmt = $pdo->prepare("INSERT INTO testimonials (client_name, client_role, content) VALUES (?, ?, ?)");
    if ($stmt->execute([$client_name, $client_role, $content])) {
        $message = "Testimonial added successfully!";
    } else {
        $message = "Error adding testimonial.";
    }
}

// Handle Delete Testimonial
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM testimonials WHERE id = ?");
    $stmt->execute([$id]);
    $message = "Testimonial deleted.";
}

// Fetch all testimonials
$testimonials = $pdo->query("SELECT * FROM testimonials ORDER BY created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Testimonials - Manoranjan.dev</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <nav class="bg-gray-900 text-white p-4 shadow-lg">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-xl font-bold">Manage Testimonials</h1>
            <div class="flex items-center">
                <a href="../index.php" target="_blank" class="mr-6 text-sm bg-gray-800 hover:bg-gray-700 px-3 py-1 rounded transition text-blue-400 font-bold border border-blue-900">View Site <i class="fa-solid fa-arrow-up-right-from-square ml-1 text-[10px]"></i></a>
                <a href="index.php" class="mr-4 hover:underline">Projects</a>
                <a href="blog.php" class="mr-4 hover:underline">Blog</a>
                <a href="../logout.php" class="bg-red-600 hover:bg-red-700 px-4 py-2 rounded transition">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mx-auto p-6">
        <?php if ($message): ?>
            <div class="bg-blue-500 text-white p-4 rounded mb-6">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="bg-white p-6 rounded-lg shadow-md border border-gray-200">
                <h2 class="text-2xl font-semibold mb-4 text-gray-800">Add Testimonial</h2>
                <form method="POST" action="">
                    <input type="hidden" name="add_testimonial" value="1">
                    <div class="mb-4">
                        <label class="block text-gray-700 font-medium mb-1">Client Name</label>
                        <input type="text" name="client_name" class="w-full p-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:outline-none" required>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 font-medium mb-1">Client Role/Company</label>
                        <input type="text" name="client_role" class="w-full p-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 font-medium mb-1">Feedback Content</label>
                        <textarea name="content" rows="4" class="w-full p-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:outline-none" required></textarea>
                    </div>
                    <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-2 rounded transition">
                        Add Testimonial
                    </button>
                </form>
            </div>

            <div class="md:col-span-2 bg-white p-6 rounded-lg shadow-md border border-gray-200">
                <h2 class="text-2xl font-semibold mb-4 text-gray-800">Current Testimonials</h2>
                <div class="space-y-4">
                    <?php foreach ($testimonials as $t): ?>
                        <div class="p-4 border rounded hover:bg-gray-50 transition flex justify-between items-start">
                            <div>
                                <p class="font-bold text-gray-800"><?php echo $t['client_name']; ?> <span class="font-normal text-gray-500 text-sm">- <?php echo $t['client_role']; ?></span></p>
                                <p class="text-gray-600 mt-2 italic">"<?php echo $t['content']; ?>"</p>
                            </div>
                            <a href="?delete=<?php echo $t['id']; ?>" class="text-red-600 hover:text-red-800 text-sm font-bold ml-4" onclick="return confirm('Delete this testimonial?')">Delete</a>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($testimonials)): ?>
                        <p class="text-center text-gray-500 py-4">No testimonials yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php include 'includes/footer.php'; ?>

