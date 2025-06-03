<?php
// Start session
session_start();

// Set base URL
$baseUrl = '/moonarrowstudios/';

// Check if user is logged in
require_once '../db_connect.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: " . $baseUrl . "../sign_in/sign_in_html.php");
    exit();
}

// Check if post ID is provided
if (!isset($_POST['post_id']) || empty($_POST['post_id'])) {
    header("Location: manage_posts.php");
    exit();
}

$post_id = (int)$_POST['post_id'];
$user_id = $_SESSION['user_id'];

// Verify user can delete this post (either admin or post owner)
$query = "SELECT p.user_id, u.role 
          FROM posts p
          JOIN users u ON p.user_id = u.user_id
          WHERE p.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $post_id);
$stmt->execute();
$stmt->bind_result($post_owner_id, $user_role);
$stmt->fetch();
$stmt->close();

// Redirect if user is not admin and not the post owner
if ($user_role !== 'admin' && $post_owner_id !== $user_id) {
    header("Location: " . $baseUrl);
    exit();
}

// Delete the post
$query = "DELETE FROM posts WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $post_id);

if ($stmt->execute()) {
    // Successfully deleted
    $_SESSION['success_message'] = "Post deleted successfully.";
} else {
    // Error occurred
    $_SESSION['error_messages'] = ["Error deleting post."];
}

$stmt->close();
$conn->close();

// Redirect back to appropriate page
if ($user_role === 'admin') {
    header("Location: manage_posts.php");
} else {
    header("Location: " . $baseUrl . "php/profile.php");
}
exit();
?>