<?php
// verify_password_change.php
session_start();
include 'db_connect.php';

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    $stmt = $conn->prepare("SELECT * FROM password_resets WHERE token = ? AND purpose = 'change'");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $reset = $stmt->get_result()->fetch_assoc();

    if ($reset && $reset['new_password']) {
        // Update password
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
        $stmt->bind_param("ss", $reset['new_password'], $reset['email']);
        $stmt->execute();
        
        // Delete token
        $stmt = $conn->prepare("DELETE FROM password_resets WHERE token = ?");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        
        // Redirect to settings with success message
        $_SESSION['success_message'] = "Password updated successfully!";
    } else {
        $_SESSION['error_message'] = "Invalid or expired token.";
    }
} else {
    $_SESSION['error_message'] = "Token missing.";
}

header("Location: settings.php?tab=security");
exit();
?>