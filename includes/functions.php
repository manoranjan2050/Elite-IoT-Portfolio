<?php
session_start();

function isLoggedIn() {
    return isset($_SESSION['admin_id']);
}

function redirectToLogin() {
    if (!isLoggedIn()) {
        header("Location: ../login.php");
        exit();
    }
}

function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}
?>
