<?php
// verify_email_change.php
session_start();
include 'db_connect.php';

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    $stmt = $conn->prepare("SELECT * FROM email_changes WHERE token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $change = $stmt->get_result()->fetch_assoc();

    if ($change) {
        // Update email
        $stmt = $conn->prepare("UPDATE users SET email = ? WHERE user_id = ?");
        $stmt->bind_param("si", $change['new_email'], $change['user_id']);
        $stmt->execute();
        
        // Delete token
        $stmt = $conn->prepare("DELETE FROM email_changes WHERE token = ?");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        
        $_SESSION['success_message'] = "Email updated successfully!";
    } else {
        $_SESSION['error_message'] = "Invalid or expired token.";
    }
}

header("Location: settings.php?tab=security");
exit();
?>