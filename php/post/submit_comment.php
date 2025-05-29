<?php
session_start();

require_once '../../vendor/htmlpurifier/library/HTMLPurifier.auto.php';

require_once '../notification_functions.php';

$config = HTMLPurifier_Config::createDefault();
$purifier = new HTMLPurifier($config);

$content = $purifier->purify(trim($_POST['content'] ?? ''));

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'You must be logged in to comment.']);
    exit;
}

$post_id = intval($_POST['post_id'] ?? 0);
$content = trim($_POST['content'] ?? '');
$parent_id = isset($_POST['parent_id']) ? intval($_POST['parent_id']) : null;

if (!$post_id || !$content) {
    echo json_encode(['success' => false, 'error' => 'Invalid input.']);
    exit;
}

// Database connection
include '../db_connect.php';

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("INSERT INTO comments (post_id, user_id, content, parent_id, created_at) VALUES (?, ?, ?, ?, NOW())");
$stmt->bind_param("iisi", $post_id, $user_id, $content, $parent_id);

if ($stmt->execute()) {
    $comment_id = $conn->insert_id;

    // Handle notifications
    if ($parent_id) {
        // This is a reply
        $stmt = $conn->prepare("SELECT user_id FROM comments WHERE id = ?");
        $stmt->bind_param("i", $parent_id);
        $stmt->execute();
        $original_commenter = $stmt->get_result()->fetch_assoc();

        if ($original_commenter && $original_commenter['user_id'] != $user_id) {
            notifyNewReply($conn, $user_id, $original_commenter['user_id'], $comment_id, $content);
        }

    } else {
        // This is a new comment on a post
        $stmt = $conn->prepare("SELECT user_id FROM posts WHERE id = ?");
        $stmt->bind_param("i", $post_id);
        $stmt->execute();
        $post_owner = $stmt->get_result()->fetch_assoc();

        if ($post_owner && $post_owner['user_id'] != $user_id) {
            notifyNewComment($conn, $user_id, $post_owner['user_id'], $comment_id, $content, false);
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
