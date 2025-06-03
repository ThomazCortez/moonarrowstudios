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

// Check if comment IDs are provided
if (!isset($_POST['comment_ids']) || empty($_POST['comment_ids'])) {
    $_SESSION['error_message'] = "No comments selected for deletion.";
    header("Location: manage_comments.php");
    exit();
}

// Decode the JSON array of comment IDs
$comment_ids = json_decode($_POST['comment_ids'], true);

if (!is_array($comment_ids) || empty($comment_ids)) {
    $_SESSION['error_message'] = "Invalid comment selection.";
    header("Location: manage_comments.php");
    exit();
}

// Prepare placeholders and types for SQL queries
$placeholders = implode(',', array_fill(0, count($comment_ids), '?'));
$types = str_repeat('i', count($comment_ids));

// Initialize counters for deleted comments
$deleted_count = 0;
$error_message = '';

// Delete comments from the `comments` table (for post comments)
$deletePostCommentsQuery = "DELETE FROM comments WHERE id IN ($placeholders)";
$stmt = $conn->prepare($deletePostCommentsQuery);
$stmt->bind_param($types, ...$comment_ids);

if ($stmt->execute()) {
    $deleted_count += $stmt->affected_rows;
} else {
    $error_message .= "Failed to delete post comments: " . $conn->error . " ";
}

$stmt->close();

// Delete comments from the `comments_asset` table (for asset comments)
$deleteAssetCommentsQuery = "DELETE FROM comments_asset WHERE id IN ($placeholders)";
$stmt = $conn->prepare($deleteAssetCommentsQuery);
$stmt->bind_param($types, ...$comment_ids);

if ($stmt->execute()) {
    $deleted_count += $stmt->affected_rows;
} else {
    $error_message .= "Failed to delete asset comments: " . $conn->error . " ";
}

$stmt->close();

// Set success or error message
if ($deleted_count > 0) {
    $_SESSION['success_message'] = "Successfully deleted $deleted_count comments.";
} else {
    $_SESSION['error_messages'] = [$error_message ?: "No comments were deleted."];
}

$conn->close();

// Redirect back to comments management page
header("Location: manage_comments.php");
exit();
?>