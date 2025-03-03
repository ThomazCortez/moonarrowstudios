<?php
session_start();
require_once '../db_connect.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id'])) {
    header("Location: ../sign_in/sign_in_html.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$query = "SELECT role FROM users WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($user_role);
$stmt->fetch();
$stmt->close();

if ($user_role !== 'admin') {
    header("Location: /moonarrowstudios/");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_ids'])) {
    $post_ids = json_decode($_POST['post_ids']);
    
    if (is_array($post_ids) && !empty($post_ids)) {
        $placeholders = implode(',', array_fill(0, count($post_ids), '?'));
        $types = str_repeat('i', count($post_ids));
        
        // Delete posts
        $query = "DELETE FROM posts WHERE id IN ($placeholders)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$post_ids);
        $stmt->execute();
        $stmt->close();
        
        // Delete associated comments
        $query = "DELETE FROM comments WHERE post_id IN ($placeholders)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$post_ids);
        $stmt->execute();
        $stmt->close();
        
        header("Location: manage_posts.php?bulk_delete_success=1");
        exit();
    }
}

header("Location: manage_posts.php");
exit();