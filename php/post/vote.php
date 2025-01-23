<?php
session_start();
header('Content-Type: application/json');

// Database connection
include '../db_connect.php';

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'You must be logged in to vote']);
    exit;
}

$user_id = $_SESSION['user_id'];
$post_id = intval($_POST['post_id']);
$vote_type = $_POST['vote_type']; // 'upvote' or 'downvote'

if (!in_array($vote_type, ['upvote', 'downvote']) || !$post_id) {
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

// Check if the user has already voted on this post
$stmt = $conn->prepare("SELECT vote_type FROM post_votes WHERE user_id = ? AND post_id = ?");
$stmt->bind_param("ii", $user_id, $post_id);
$stmt->execute();
$result = $stmt->get_result();
$current_vote = $result->fetch_assoc();

if ($current_vote) {
    if ($current_vote['vote_type'] === $vote_type) {
        // User clicked the same vote button, so remove the vote
        $stmt = $conn->prepare("DELETE FROM post_votes WHERE user_id = ? AND post_id = ?");
        $stmt->bind_param("ii", $user_id, $post_id);
        $stmt->execute();

        $column = $vote_type === 'upvote' ? 'upvotes' : 'downvotes';
        $stmt = $conn->prepare("UPDATE posts SET $column = $column - 1 WHERE id = ?");
        $stmt->bind_param("i", $post_id);
        $stmt->execute();
    } else {
        // User is switching votes
        $stmt = $conn->prepare("UPDATE post_votes SET vote_type = ? WHERE user_id = ? AND post_id = ?");
        $stmt->bind_param("sii", $vote_type, $user_id, $post_id);
        $stmt->execute();

        // Adjust the post vote counts
        $stmt = $conn->prepare("
            UPDATE posts
            SET upvotes = upvotes + IF(? = 'upvote', 1, -1),
                downvotes = downvotes + IF(? = 'downvote', 1, -1)
            WHERE id = ?
        ");
        $stmt->bind_param("ssi", $vote_type, $vote_type, $post_id);
        $stmt->execute();
    }
} else {
    // User hasn't voted yet, add their vote
    $stmt = $conn->prepare("INSERT INTO post_votes (user_id, post_id, vote_type) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $user_id, $post_id, $vote_type);
    $stmt->execute();

    $column = $vote_type === 'upvote' ? 'upvotes' : 'downvotes';
    $stmt = $conn->prepare("UPDATE posts SET $column = $column + 1 WHERE id = ?");
    $stmt->bind_param("i", $post_id);
    $stmt->execute();
}

// Fetch updated counts
$stmt = $conn->prepare("SELECT upvotes, downvotes FROM posts WHERE id = ?");
$stmt->bind_param("i", $post_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

$score = $result['upvotes'] - $result['downvotes'];
echo json_encode([
    'success' => true,
    'upvotes' => $result['upvotes'],
    'downvotes' => $result['downvotes'],
    'score' => $score,
]);

$conn->close();
?>