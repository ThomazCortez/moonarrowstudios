<?php
// fetch_user_preview.php - Create this new file
session_start();
require 'db_connect.php';

if (isset($_GET['user_id'])) {
    $user_id = $_GET['user_id'];
    
    $stmt = $conn->prepare("
        SELECT username, profile_picture, banner, 
               DATE_FORMAT(created_at, '%M %Y') as formatted_join_date,
               (SELECT COUNT(*) FROM follows WHERE following_id = users.user_id) as follower_count
        FROM users 
        WHERE user_id = ?
    ");
    
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    
    header('Content-Type: application/json');
    echo json_encode($user);
    exit;
}