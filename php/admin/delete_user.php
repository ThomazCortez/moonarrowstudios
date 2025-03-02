<?php
// Start session
session_start();

// Set base URL
$baseUrl = '/moonarrowstudios/';

// Check if user is logged in and is an admin
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

// Check if the request method is POST and user_id is set
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'])) {
    $user_id_to_delete = (int)$_POST['user_id'];

    // Prevent admin from deleting themselves
    if ($user_id_to_delete === $user_id) {
        $_SESSION['error_message'] = "You cannot delete your own account.";
        header("Location: manage_users.php");
        exit();
    }

    // Prepare the delete query
    $delete_query = "DELETE FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($delete_query);
    $stmt->bind_param("i", $user_id_to_delete);

    // Execute the delete query
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "User deleted successfully.";
    } else {
        $_SESSION['error_message'] = "Error deleting user: " . $stmt->error;
    }

    $stmt->close();
} else {
    $_SESSION['error_message'] = "Invalid request.";
}

// Redirect back to the manage users page
header("Location: manage_users.php");
exit();
?>