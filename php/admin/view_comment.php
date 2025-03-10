<?php
session_start();

// Database connection
require '../db_connect.php';

// Fetch the comment by ID
$comment_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$stmt = $conn->prepare("SELECT * FROM comments WHERE id = ?");
$stmt->bind_param("i", $comment_id);
$stmt->execute();
$result = $stmt->get_result();
$comment = $result->fetch_assoc();

if (!$comment) {
    echo "<h1>Comment not found</h1>";
    exit;
}

// Determine if the comment is on a post or an asset
if ($comment['post_id']) {
    // Check if the comment is a reply
    if ($comment['parent_id']) {
        // Redirect to view_post.php with the post ID, parent comment ID, and reply ID
        header("Location: ../view_post.php?id=" . $comment['post_id'] . "&comment=" . $comment['parent_id'] . "&reply=" . $comment['id']);
    } else {
        // Redirect to view_post.php with the post ID and comment ID
        header("Location: ../view_post.php?id=" . $comment['post_id'] . "&comment=" . $comment['id']);
    }
} elseif ($comment['asset_id']) {
    // Check if the comment is a reply
    if ($comment['parent_id']) {
        // Redirect to view_asset.php with the asset ID, parent comment ID, and reply ID
        header("Location: ../view_asset.php?id=" . $comment['asset_id'] . "&comment=" . $comment['parent_id'] . "&reply=" . $comment['id']);
    } else {
        // Redirect to view_asset.php with the asset ID and comment ID
        header("Location: ../view_asset.php?id=" . $comment['asset_id'] . "&comment=" . $comment['id']);
    }
} else {
    echo "<h1>Invalid comment</h1>";
    exit;
}
?>