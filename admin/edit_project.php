<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

redirectToLogin();

$message = "";
$project = null;

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
    $stmt->execute([$id]);
    $project = $stmt->fetch();
}

if (!$project) {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_project'])) {
    $title = sanitize($_POST['title']);
    $description = sanitize($_POST['description']);
    $category = sanitize($_POST['category']);
    $project_link = sanitize($_POST['project_link']);
    $github_link = sanitize($_POST['github_link']);
    
    $image_url = $project['image_url'];
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $target_dir = "../uploads/";
        $file_name = time() . "_" . basename($_FILES["image"]["name"]);
        $target_file = $target_dir . $file_name;
        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            $image_url = "uploads/" . $file_name;
        }
    }

    $stmt = $pdo->prepare("UPDATE projects SET title = ?, description = ?, category = ?, image_url = ?, project_link = ?, github_link = ? WHERE id = ?");
    if ($stmt->execute([$title, $description, $category, $image_url, $project_link, $github_link, $id])) {
        $message = "Project updated successfully!";
        // Refresh project data
        $stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
        $stmt->execute([$id]);
        $project = $stmt->fetch();
    } else {
        $message = "Error updating project.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Project - Manoranjan.dev</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <nav class="bg-gray-900 text-white p-4 shadow-lg">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-xl font-bold">Edit Project</h1>
            <div>
                <a href="index.php" class="mr-4 hover:underline">Back to Dashboard</a>
                <a href="../logout.php" class="bg-red-600 hover:bg-red-700 px-4 py-2 rounded transition">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mx-auto p-6 flex justify-center">
        <div class="bg-white p-8 rounded-lg shadow-md border border-gray-200 w-full max-w-2xl">
            <?php if ($message): ?>
                <div class="bg-blue-500 text-white p-4 rounded mb-6">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="update_project" value="1">
                <div class="mb-4">
                    <label class="block text-gray-700 font-medium mb-1">Title</label>
                    <input type="text" name="title" value="<?php echo $project['title']; ?>" class="w-full p-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:outline-none" required>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 font-medium mb-1">Category</label>
                    <select name="category" class="w-full p-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:outline-none" required>
                        <option value="Dev" <?php if($project['category'] == 'Dev') echo 'selected'; ?>>Dev</option>
                        <option value="Coding" <?php if($project['category'] == 'Coding') echo 'selected'; ?>>Coding</option>
                        <option value="Trading" <?php if($project['category'] == 'Trading') echo 'selected'; ?>>Trading</option>
                        <option value="PCB Design" <?php if($project['category'] == 'PCB Design') echo 'selected'; ?>>PCB Design</option>
                        <option value="IoT" <?php if($project['category'] == 'IoT') echo 'selected'; ?>>IoT</option>
                        <option value="Business" <?php if($project['category'] == 'Business') echo 'selected'; ?>>Business</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 font-medium mb-1">Description</label>
                    <textarea name="description" rows="5" class="w-full p-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:outline-none"><?php echo $project['description']; ?></textarea>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 font-medium mb-1">Current Image</label>
                    <?php if ($project['image_url']): ?>
                        <img src="../<?php echo $project['image_url']; ?>" class="w-32 h-32 object-cover rounded mb-2 border">
                    <?php else: ?>
                        <p class="text-gray-500 text-sm italic mb-2">No image uploaded.</p>
                    <?php endif; ?>
                    <label class="block text-gray-700 font-medium mb-1">Change Image</label>
                    <input type="file" name="image" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 font-medium mb-1">Project Link</label>
                    <input type="url" name="project_link" value="<?php echo $project['project_link']; ?>" class="w-full p-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>
                <div class="mb-6">
                    <label class="block text-gray-700 font-medium mb-1">GitHub Link</label>
                    <input type="url" name="github_link" value="<?php echo $project['github_link']; ?>" class="w-full p-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>
                <div class="flex gap-4">
                    <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded transition">
                        Save Changes
                    </button>
                    <a href="index.php" class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-3 rounded text-center transition">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
    <?php include 'includes/footer.php'; ?>

