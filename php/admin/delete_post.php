<?php
// Start session
session_start();

// Set base URL
$baseUrl = '/moonarrowstudios/';

// Check if user is logged in and is an admin
require_once '../db_connect.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: " . $baseUrl . "../sign_in/sign_in_html.php");
    exit();
}

// Verify admin status
$user_id = $_SESSION['user_id'];
$query = "SELECT role FROM users WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($user_role);
$stmt->fetch();
$stmt->close();

// Redirect if not admin
if ($user_role !== 'admin') {
    header("Location: " . $baseUrl);
    exit();
}

// Check if post ID is provided
if (!isset($_POST['post_id']) || empty($_POST['post_id'])) {
    header("Location: manage_posts.php");
    exit();
}

$post_id = (int)$_POST['post_id'];

// Delete the post
$query = "DELETE FROM posts WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $post_id);

if ($stmt->execute()) {
    // Successfully deleted
    $_SESSION['message'] = "Post deleted successfully.";
} else {
    // Error occurred
    $_SESSION['error'] = "Error deleting post.";
}

$stmt->close();
$conn->close();

// Redirect back to manage posts page
header("Location: manage_posts.php");
exit();
?>