<?php
// Start session and include database connection
session_start();
require_once '../db_connect.php';

// Set base URL
$baseUrl = '/moonarrowstudios/';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: " . $baseUrl . "../sign_in/sign_in_html.php");
    exit();
}

// Check if asset ID is provided
if (!isset($_POST['asset_id']) || empty($_POST['asset_id'])) {
    $_SESSION['error_message'] = "No asset ID provided for deletion.";
    header("Location: manage_assets.php");
    exit();
}

$asset_id = (int)$_POST['asset_id'];
$user_id = $_SESSION['user_id'];

try {
    // Verify user can delete this asset (either admin or asset owner)
    $query = "SELECT a.user_id, u.role 
              FROM assets a
              JOIN users u ON a.user_id = u.user_id
              WHERE a.id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $asset_id);
    $stmt->execute();
    $stmt->bind_result($asset_owner_id, $user_role);
    
    if (!$stmt->fetch()) {
        // Asset not found
        $_SESSION['error_message'] = "Asset not found.";
        header("Location: manage_assets.php");
        exit();
    }
    $stmt->close();

    // Check permissions
    if ($user_role !== 'admin' && $asset_owner_id !== $user_id) {
        $_SESSION['error_message'] = "You don't have permission to delete this asset.";
        header("Location: manage_assets.php");
        exit();
    }

    // Start a transaction to ensure data integrity
    $conn->begin_transaction();

    // First, delete all comments associated with the asset
    $deleteCommentsQuery = "DELETE FROM comments WHERE post_id = ?";
    $stmt = $conn->prepare($deleteCommentsQuery);
    $stmt->bind_param("i", $asset_id);
    $stmt->execute();
    $stmt->close();

    // Then delete the asset itself
    $deleteAssetQuery = "DELETE FROM assets WHERE id = ?";
    $stmt = $conn->prepare($deleteAssetQuery);
    $stmt->bind_param("i", $asset_id);
    $stmt->execute();
    $stmt->close();

    // Commit the transaction
    $conn->commit();

    // Set success message
    $_SESSION['success_message'] = "Asset successfully deleted.";
} catch (Exception $e) {
    // Rollback the transaction in case of error
    if (isset($conn) && $conn->ping()) {
        $conn->rollback();
    }

    // Set error message
    $_SESSION['error_message'] = "Error deleting asset: " . $e->getMessage();
}

// Redirect back to appropriate page
if (isset($user_role) && $user_role === 'admin') {
    header("Location: manage_assets.php");
} else {
    // For regular users, redirect to their assets page
    header("Location: " . $baseUrl . "php/profile.php");
}
exit();
?>