<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (isLoggedIn()) {
    header("Location: admin/index.php");
    exit();
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];

    if (!empty($username) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT id, password FROM admin_users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['username'] = $username;
            header("Location: admin/index.php");
            exit();
        } else {
            $error = "Invalid username or password.";
        }
    } else {
        $error = "Please fill in all fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Manoranjan.dev</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 flex items-center justify-center min-h-screen">
    <div class="bg-gray-800 p-8 rounded-lg shadow-xl w-full max-w-md border border-gray-700">
        <h2 class="text-3xl font-bold text-white mb-6 text-center">Admin Login</h2>
        
        <?php if ($error): ?>
            <div class="bg-red-500 text-white p-3 rounded mb-4 text-center">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-4">
                <label class="block text-gray-400 mb-2" for="username">Username</label>
                <input class="w-full p-3 bg-gray-700 border border-gray-600 rounded text-white focus:outline-none focus:border-blue-500" type="text" name="username" id="username" required>
            </div>
            <div class="mb-6">
                <label class="block text-gray-400 mb-2" for="password">Password</label>
                <input class="w-full p-3 bg-gray-700 border border-gray-600 rounded text-white focus:outline-none focus:border-blue-500" type="password" name="password" id="password" required>
            </div>
            <button class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded transition duration-300" type="submit">
                Login
            </button>
        </form>
        <div class="mt-6 text-center">
            <a href="index.php" class="text-gray-500 hover:text-gray-300 text-sm">Back to Website</a>
        </div>
    </div>
</body>
</html>
