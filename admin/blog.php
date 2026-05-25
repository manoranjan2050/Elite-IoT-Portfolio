<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

redirectToLogin();

$message = "";

// Handle Add Blog Post
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_blog'])) {
    $title = sanitize($_POST['title']);
    $content = sanitize($_POST['content']);
    $category = sanitize($_POST['category']);
    
    $image_url = "";
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $target_dir = "../uploads/";
        $file_name = "blog_" . time() . "_" . basename($_FILES["image"]["name"]);
        $target_file = $target_dir . $file_name;
        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            $image_url = "uploads/" . $file_name;
        }
    }

    $stmt = $pdo->prepare("INSERT INTO blogs (title, content, category, image_url) VALUES (?, ?, ?, ?)");
    if ($stmt->execute([$title, $content, $category, $image_url])) {
        $message = "Blog post published!";
    } else {
        $message = "Error publishing post.";
    }
}

// Handle Delete Blog Post
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM blogs WHERE id = ?");
    $stmt->execute([$id]);
    $message = "Post deleted.";
}

// Fetch all blog posts
$blogs = $pdo->query("SELECT * FROM blogs ORDER BY created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Blog - Manoranjan.dev</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <nav class="bg-gray-900 text-white p-4 shadow-lg">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-xl font-bold">Manage Blog</h1>
            <div class="flex items-center">
                <a href="../index.php" target="_blank" class="mr-6 text-sm bg-gray-800 hover:bg-gray-700 px-3 py-1 rounded transition text-blue-400 font-bold border border-blue-900">View Site <i class="fa-solid fa-arrow-up-right-from-square ml-1 text-[10px]"></i></a>
                <a href="index.php" class="mr-4 hover:underline">Projects</a>
                <a href="testimonials.php" class="mr-4 hover:underline">Testimonials</a>
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

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Add Post -->
            <div class="bg-white p-6 rounded-lg shadow-md border border-gray-200">
                <h2 class="text-2xl font-semibold mb-4 text-gray-800">New Insight</h2>
                <form method="POST" action="" enctype="multipart/form-data">
                    <input type="hidden" name="add_blog" value="1">
                    <div class="mb-4">
                        <label class="block text-gray-700 font-medium mb-1">Title</label>
                        <input type="text" name="title" class="w-full p-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:outline-none" required>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 font-medium mb-1">Category</label>
                        <select name="category" class="w-full p-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:outline-none">
                            <option value="Trading">Trading</option>
                            <option value="IoT">IoT</option>
                            <option value="Business">Business</option>
                            <option value="Tech">Tech</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 font-medium mb-1">Content</label>
                        <textarea name="content" rows="10" class="w-full p-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:outline-none" required></textarea>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 font-medium mb-1">Cover Image</label>
                        <input type="file" name="image" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    </div>
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 rounded transition">
                        Publish Post
                    </button>
                </form>
            </div>

            <!-- Posts List -->
            <div class="lg:col-span-2 bg-white p-6 rounded-lg shadow-md border border-gray-200">
                <h2 class="text-2xl font-semibold mb-4 text-gray-800">Published Insights</h2>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-gray-100 border-b">
                                <th class="p-3">Title</th>
                                <th class="p-3">Category</th>
                                <th class="p-3">Date</th>
                                <th class="p-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($blogs as $post): ?>
                                <tr class="border-b hover:bg-gray-50 transition">
                                    <td class="p-3 font-medium"><?php echo $post['title']; ?></td>
                                    <td class="p-3">
                                        <span class="px-2 py-1 bg-purple-100 text-purple-800 rounded text-xs"><?php echo $post['category']; ?></span>
                                    </td>
                                    <td class="p-3 text-sm text-gray-500"><?php echo date('M d, Y', strtotime($post['created_at'])); ?></td>
                                    <td class="p-3 text-sm">
                                        <a href="?delete=<?php echo $post['id']; ?>" class="text-red-600 hover:text-red-800 font-semibold" onclick="return confirm('Delete this post?')">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($blogs)): ?>
                                <tr>
                                    <td colspan="4" class="p-4 text-center text-gray-500">No blog posts yet.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php include 'includes/footer.php'; ?>

