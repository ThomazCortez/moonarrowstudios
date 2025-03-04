<?php
// Start session and include database connection
session_start();
require_once '../db_connect.php';

// Set base URL
$baseUrl = '/moonarrowstudios/';

// Check if user is logged in and is an admin
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

// Check if post ID is provided
if (!isset($_POST['post_id']) || empty($_POST['post_id'])) {
    $_SESSION['error_message'] = "No asset ID provided for deletion.";
    header("Location: manage_assets.php");
    exit();
}

$post_id = (int)$_POST['post_id'];

try {
    // Start a transaction to ensure data integrity
    $conn->begin_transaction();

    // First, delete all comments associated with the asset
    $deleteCommentsQuery = "DELETE FROM comments WHERE post_id = ?";
    $stmt = $conn->prepare($deleteCommentsQuery);
    $stmt->bind_param("i", $post_id);
    $stmt->execute();
    $stmt->close();

    // Then delete the asset itself
    $deleteAssetQuery = "DELETE FROM assets WHERE id = ?";
    $stmt = $conn->prepare($deleteAssetQuery);
    $stmt->bind_param("i", $post_id);
    $stmt->execute();
    $stmt->close();

    // Commit the transaction
    $conn->commit();

    // Set success message
    $_SESSION['success_message'] = "Asset successfully deleted.";
} catch (Exception $e) {
    // Rollback the transaction in case of error
    $conn->rollback();

    // Set error message
    $_SESSION['error_message'] = "Error deleting asset: " . $e->getMessage();
}

// Redirect back to manage assets page
header("Location: manage_assets.php");
exit();
?>