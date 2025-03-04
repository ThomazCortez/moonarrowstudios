<?php
// Start session
session_start();

// Check if user is logged in and is an admin
require_once '../db_connect.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: /moonarrowstudios/../sign_in/sign_in_html.php");
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
    header("Location: /moonarrowstudios");
    exit();
}

// Check if post_ids are provided
if (!isset($_POST['post_ids'])) {
    $_SESSION['error_message'] = "No assets selected for deletion.";
    header("Location: manage_assets.php");
    exit();
}

// Decode the JSON array of post IDs
$post_ids = json_decode($_POST['post_ids']);

// Validate post_ids
if (!is_array($post_ids) || empty($post_ids)) {
    $_SESSION['error_message'] = "Invalid asset IDs provided.";
    header("Location: manage_assets.php");
    exit();
}

// Prepare the SQL query to delete multiple assets
$placeholders = implode(',', array_fill(0, count($post_ids), '?'));
$query = "DELETE FROM assets WHERE id IN ($placeholders)";
$stmt = $conn->prepare($query);

// Bind parameters
$types = str_repeat('i', count($post_ids));
$stmt->bind_param($types, ...$post_ids);

// Execute the query
if ($stmt->execute()) {
    $_SESSION['success_message'] = "Selected assets have been deleted successfully.";
} else {
    $_SESSION['error_message'] = "An error occurred while deleting the assets.";
}

$stmt->close();
$conn->close();

// Redirect back to the manage assets page
header("Location: manage_assets.php");
exit();
?>