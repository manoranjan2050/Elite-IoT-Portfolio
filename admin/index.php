<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

redirectToLogin();

$message = "";

// Handle Add Project
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_project'])) {
    $title = sanitize($_POST['title']);
    $description = sanitize($_POST['description']);
    $category = sanitize($_POST['category']);
    $project_link = sanitize($_POST['project_link']);
    $github_link = sanitize($_POST['github_link']);
    
    // Simple image upload
    $image_url = "";
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $target_dir = "../uploads/";
        $file_name = time() . "_" . basename($_FILES["image"]["name"]);
        $target_file = $target_dir . $file_name;
        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            $image_url = "uploads/" . $file_name;
        }
    }

    $stmt = $pdo->prepare("INSERT INTO projects (title, description, category, image_url, project_link, github_link) VALUES (?, ?, ?, ?, ?, ?)");
    if ($stmt->execute([$title, $description, $category, $image_url, $project_link, $github_link])) {
        $message = "Project added successfully!";
    } else {
        $message = "Error adding project.";
    }
}

// Handle Delete Project
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM projects WHERE id = ?");
    $stmt->execute([$id]);
    $message = "Project deleted.";
}

// Fetch all projects
$projects = $pdo->query("SELECT * FROM projects ORDER BY created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Manoranjan.dev</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <nav class="bg-gray-900 text-white p-4 shadow-lg">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-xl font-bold">Manoranjan.dev Admin</h1>
            <div class="flex items-center">
                <a href="../index.php" target="_blank" class="mr-6 text-sm bg-gray-800 hover:bg-gray-700 px-3 py-1 rounded transition text-blue-400 font-bold border border-blue-900">View Site <i class="fa-solid fa-arrow-up-right-from-square ml-1 text-[10px]"></i></a>
                <a href="index.php" class="mr-4 hover:text-blue-400 font-bold">Projects</a>
                <a href="testimonials.php" class="mr-4 hover:text-blue-400">Testimonials</a>
                <a href="blog.php" class="mr-8 hover:text-blue-400">Blog</a>
                <span class="mr-4 text-gray-400">|</span>
                <span class="mr-4 text-sm hidden md:inline">Logged in as <?php echo $_SESSION['username']; ?></span>
                <a href="../logout.php" class="bg-red-600 hover:bg-red-700 px-4 py-2 rounded transition text-sm">Logout</a>
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
            <!-- Add Project Form -->
            <div class="bg-white p-6 rounded-lg shadow-md border border-gray-200">
                <h2 class="text-2xl font-semibold mb-4 text-gray-800">Add New Project</h2>
                <form method="POST" action="" enctype="multipart/form-data">
                    <input type="hidden" name="add_project" value="1">
                    <div class="mb-4">
                        <label class="block text-gray-700 font-medium mb-1">Title</label>
                        <input type="text" name="title" class="w-full p-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:outline-none" required>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 font-medium mb-1">Category</label>
                        <select name="category" class="w-full p-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:outline-none" required>
                            <option value="Dev">Dev</option>
                            <option value="Coding">Coding</option>
                            <option value="Trading">Trading</option>
                            <option value="PCB Design">PCB Design</option>
                            <option value="IoT">IoT</option>
                            <option value="Business">Business</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 font-medium mb-1">Description</label>
                        <textarea name="description" rows="3" class="w-full p-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:outline-none"></textarea>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 font-medium mb-1">Project Image</label>
                        <input type="file" name="image" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 font-medium mb-1">Project Link</label>
                        <input type="url" name="project_link" class="w-full p-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    </div>
                    <div class="mb-6">
                        <label class="block text-gray-700 font-medium mb-1">GitHub Link</label>
                        <input type="url" name="github_link" class="w-full p-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    </div>
                    <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-2 rounded transition">
                        Add Project
                    </button>
                </form>
            </div>

            <!-- Projects List -->
            <div class="md:col-span-2 bg-white p-6 rounded-lg shadow-md border border-gray-200">
                <h2 class="text-2xl font-semibold mb-4 text-gray-800">Current Projects</h2>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-gray-100 border-b">
                                <th class="p-3">Title</th>
                                <th class="p-3">Category</th>
                                <th class="p-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($projects as $project): ?>
                                <tr class="border-b hover:bg-gray-50 transition">
                                    <td class="p-3 font-medium"><?php echo $project['title']; ?></td>
                                    <td class="p-3">
                                        <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs"><?php echo $project['category']; ?></span>
                                    </td>
                                    <td class="p-3 text-sm">
                                        <a href="edit_project.php?id=<?php echo $project['id']; ?>" class="text-blue-600 hover:text-blue-800 font-semibold mr-3">Edit</a>
                                        <a href="?delete=<?php echo $project['id']; ?>" class="text-red-600 hover:text-red-800 font-semibold" onclick="return confirm('Are you sure?')">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($projects)): ?>
                                <tr>
                                    <td colspan="3" class="p-4 text-center text-gray-500">No projects added yet.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php include 'includes/footer.php'; ?>

