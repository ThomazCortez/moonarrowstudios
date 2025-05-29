<?php
session_start();

require_once '../../vendor/htmlpurifier/library/HTMLPurifier.auto.php';
require_once '../notification_functions.php'; // Include if you want notification support

$config = HTMLPurifier_Config::createDefault();
$purifier = new HTMLPurifier($config);

$content = $purifier->purify(trim($_POST['content'] ?? ''));

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'You must be logged in to comment.']);
    exit;
}

$asset_id = intval($_POST['asset_id'] ?? 0);
$content = trim($_POST['content'] ?? '');
$parent_id = isset($_POST['parent_id']) ? intval($_POST['parent_id']) : null;

if (!$asset_id || !$content) {
    echo json_encode(['success' => false, 'error' => 'Invalid input.']);
    exit;
}

// Database connection
include '../db_connect.php';

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("INSERT INTO comments_asset (asset_id, user_id, content, parent_id, created_at) VALUES (?, ?, ?, ?, NOW())");
$stmt->bind_param("iisi", $asset_id, $user_id, $content, $parent_id);

if ($stmt->execute()) {
    $comment_id = $conn->insert_id;

    // Handle notifications
    if ($parent_id) {
        // Reply to a comment
        $stmt = $conn->prepare("SELECT user_id FROM comments_asset WHERE id = ?");
        $stmt->bind_param("i", $parent_id);
        $stmt->execute();
        $original_commenter = $stmt->get_result()->fetch_assoc();

        if ($original_commenter && $original_commenter['user_id'] != $user_id) {
            notifyNewReply($conn, $user_id, $original_commenter['user_id'], $comment_id, $content);
        }

    } else {
        // New top-level comment on asset
        $stmt = $conn->prepare("SELECT user_id FROM assets WHERE id = ?");
        $stmt->bind_param("i", $asset_id);
        $stmt->execute();
        $asset_owner = $stmt->get_result()->fetch_assoc();

        if ($asset_owner && $asset_owner['user_id'] != $user_id) {
            notifyNewComment($conn, $user_id, $asset_owner['user_id'], $comment_id, $content, true); // true can indicate asset vs post
        }
    }

    echo json_encode([
        'success' => true,
        'username' => $_SESSION['username'],
        'created_at' => date('F j, Y, g:i A'),
        'content' => $content
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to submit comment.']);
}

$stmt->close();
$conn->close();
?>
