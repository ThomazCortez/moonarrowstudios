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

// Check if post IDs are provided
if (!isset($_POST['post_ids'])) {
    header("Location: " . $baseUrl . "admin/manage_posts.php");
    exit();
}

// Decode the JSON array of post IDs
$post_ids = json_decode($_POST['post_ids']);

// Validate post IDs
if (!is_array($post_ids) || empty($post_ids)) {
    header("Location: " . $baseUrl . "admin/manage_posts.php");
    exit();
}

// Prepare the SQL statement to delete posts
$placeholders = implode(',', array_fill(0, count($post_ids), '?'));
$query = "DELETE FROM posts WHERE id IN ($placeholders)";
$stmt = $conn->prepare($query);

// Bind parameters
$types = str_repeat('i', count($post_ids));
$stmt->bind_param($types, ...$post_ids);

// Execute the deletion
if ($stmt->execute()) {
    $_SESSION['success_message'] = 'Selected posts have been deleted successfully.';
} else {
    $_SESSION['error_message'] = 'An error occurred while deleting the posts.';
}

$stmt->close();
$conn->close();

// Redirect back to manage posts page
header("Location: " . $baseUrl . "php/admin/manage_posts.php");
exit();
?>