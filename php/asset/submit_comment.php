<?php
session_start();

require_once '../../vendor/htmlpurifier/library/HTMLPurifier.auto.php';

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

$stmt = $conn->prepare("INSERT INTO comments_asset (asset_id, user_id, content, parent_id, created_at) VALUES (?, ?, ?, ?, NOW())");
$stmt->bind_param("iisi", $asset_id, $_SESSION['user_id'], $content, $parent_id);
if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'username' => $_SESSION['username'],
        'created_at' => date('F j, Y, g:i A'),
        'content' => $content
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to submit reply.']);
}
$stmt->close();
$conn->close();
?>