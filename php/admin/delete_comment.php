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

// Get current user ID
$current_user_id = $_SESSION['user_id'];

// Verify admin status first
$query = "SELECT role FROM users WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $current_user_id);
$stmt->execute();
$stmt->bind_result($user_role);
$stmt->fetch();
$stmt->close();

$is_admin = ($user_role === 'admin');

// Check if comment ID is provided
if (!isset($_POST['comment_id']) || empty($_POST['comment_id'])) {
    $_SESSION['error_message'] = "Missing comment ID.";
    header("Location: " . ($is_admin ? "manage_comments.php" : $baseUrl));
    exit();
}

$comment_id = (int)$_POST['comment_id'];

// Check if comment type is provided (from edit_comment.php)
$provided_comment_type = isset($_POST['comment_type']) ? $_POST['comment_type'] : '';

// Determine which table the comment belongs to and verify ownership
$comment_type = '';
$is_owner = false;
$comment_user_id = null;

// If comment type is provided, check that table first
if ($provided_comment_type === 'post') {
    $query = "SELECT user_id FROM comments WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $comment_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $comment_type = 'post';
        $comment_user_id = $row['user_id'];
        $is_owner = ($comment_user_id == $current_user_id);
    }
    $stmt->close();
} elseif ($provided_comment_type === 'asset') {
    $query = "SELECT user_id FROM comments_asset WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $comment_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $comment_type = 'asset';
        $comment_user_id = $row['user_id'];
        $is_owner = ($comment_user_id == $current_user_id);
    }
    $stmt->close();
}

// If comment type wasn't provided or comment wasn't found, search both tables
if (empty($comment_type)) {
    // First, check the `comments` table (for post comments)
    $query = "SELECT user_id FROM comments WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $comment_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $comment_type = 'post';
        $comment_user_id = $row['user_id'];
        $is_owner = ($comment_user_id == $current_user_id);
    }
    $stmt->close();

    // If not found in `comments`, check `comments_asset` table
    if (empty($comment_type)) {
        $query = "SELECT user_id FROM comments_asset WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $comment_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $comment_type = 'asset';
            $comment_user_id = $row['user_id'];
            $is_owner = ($comment_user_id == $current_user_id);
        }
        $stmt->close();
    }
}

// Check if comment was found
if (empty($comment_type)) {
    $_SESSION['error_message'] = "Comment not found.";
    header("Location: " . ($is_admin ? "manage_comments.php" : $baseUrl));
    exit();
}

// Verify access rights
if (!$is_admin && !$is_owner) {
    $_SESSION['error_message'] = "You don't have permission to delete this comment.";
    header("Location: " . ($is_admin ? "manage_comments.php" : $baseUrl));
    exit();
}

// Get post_id or asset_id for redirect
$post_id = null;
$asset_id = null;

if ($comment_type === 'post') {
    $query = "SELECT post_id FROM comments WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $comment_id);
    $stmt->execute();
    $stmt->bind_result($post_id);
    $stmt->fetch();
    $stmt->close();
} else {
    $query = "SELECT asset_id FROM comments_asset WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $comment_id);
    $stmt->execute();
    $stmt->bind_result($asset_id);
    $stmt->fetch();
    $stmt->close();
}

// Delete comment from the appropriate table
$deleteQuery = ($comment_type === 'post') ? "DELETE FROM comments WHERE id = ?" : "DELETE FROM comments_asset WHERE id = ?";

$stmt = $conn->prepare($deleteQuery);
$stmt->bind_param("i", $comment_id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        $_SESSION['success_message'] = "Comment deleted successfully.";
    } else {
        $_SESSION['error_message'] = "Comment not found or already deleted.";
    }
} else {
    $_SESSION['error_message'] = "Failed to delete comment: " . $conn->error;
}

$stmt->close();
$conn->close();

// Redirect back to appropriate page
if ($is_admin) {
    header("Location: manage_comments.php");
} else {
    // Redirect user back to the post/asset
    if ($comment_type === 'post' && $post_id) {
        header("Location: " . $baseUrl . "php/view_post.php?id=" . $post_id);
    } elseif ($comment_type === 'asset' && $asset_id) {
        header("Location: " . $baseUrl . "php/view_asset.php?id=" . $asset_id);
    } else {
        // Fallback to profile if post/asset ID not found
        header("Location: " . $baseUrl . "php/profile/profile.php?user_id=" . $current_user_id);
    }
}
exit();
?>