<?php
// config.php - Database configuration and helper functions
session_start();

// --- Database Configuration ---
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', ''); // Default XAMPP password is empty
define('DB_NAME', 'mindmap_generator');

// --- Application Configuration ---
define('BASE_URL', 'http://localhost/projectMindmap/');

// --- Database Connection ---
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}

// --- Helper Functions ---
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}
function requireLogin() {
    if (!isLoggedIn()) {
        redirect('login.php');
    }
}
function redirect($path) {
    header('Location: ' . BASE_URL . $path);
    exit;
}
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}
?>