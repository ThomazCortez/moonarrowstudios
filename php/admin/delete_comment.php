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

// Determine which table the comment belongs to
$comment_type = ''; // To track whether the comment is from `comments` or `comments_asset`

// First, check the `comments` table (for post comments)
$query = "SELECT id FROM comments WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $comment_id);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    $comment_type = 'post';
} else {
    // If not found in `comments`, check the `comments_asset` table (for asset comments)
    $query = "SELECT id FROM comments_asset WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $comment_id);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $comment_type = 'asset';
    } else {
        // Comment not found in either table
        $_SESSION['error_message'] = "Comment not found.";
        header("Location: manage_comments.php");
        exit();
    }
}

$stmt->close();

// Delete comment from the appropriate table
if ($comment_type === 'post') {
    $deleteQuery = "DELETE FROM comments WHERE id = ?";
} else {
    $deleteQuery = "DELETE FROM comments_asset WHERE id = ?";
}

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