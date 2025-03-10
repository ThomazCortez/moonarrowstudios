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

// Check if comment ID is provided
if (!isset($_POST['comment_id']) || empty($_POST['comment_id'])) {
    $_SESSION['error_message'] = "Missing comment ID.";
    header("Location: manage_comments.php");
    exit();
}

$comment_id = (int)$_POST['comment_id'];

// First, get post_id to maintain referential integrity (if needed for logging or notifications)
$getPostIdQuery = "SELECT post_id FROM comments WHERE id = ?";
$stmt = $conn->prepare($getPostIdQuery);
$stmt->bind_param("i", $comment_id);
$stmt->execute();
$stmt->bind_result($post_id);
$stmt->fetch();
$stmt->close();

// Delete comment
$deleteQuery = "DELETE FROM comments WHERE id = ?";
$stmt = $conn->prepare($deleteQuery);
$stmt->bind_param("i", $comment_id);

if ($stmt->execute()) {
    $_SESSION['success_message'] = "Comment deleted successfully.";
} else {
    $_SESSION['error_message'] = "Failed to delete comment: " . $conn->error;
}

$stmt->close();
$conn->close();

// Redirect back to comments management page
header("Location: manage_comments.php");
exit();
?>