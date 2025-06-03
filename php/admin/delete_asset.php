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
    // Get asset details and user role in one query
    $query = "SELECT a.user_id, a.preview_image, a.asset_file, u.role 
              FROM assets a
              JOIN users u ON u.user_id = ?
              WHERE a.id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $user_id, $asset_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // Asset not found
        $_SESSION['error_message'] = "Asset not found.";
        header("Location: manage_assets.php");
        exit();
    }
    
    $asset_data = $result->fetch_assoc();
    $stmt->close();

    $asset_owner_id = $asset_data['user_id'];
    $user_role = $asset_data['role'];
    $preview_image = $asset_data['preview_image'];
    $asset_file = $asset_data['asset_file'];

    // Check permissions
    if ($user_role !== 'admin' && $asset_owner_id !== $user_id) {
        $_SESSION['error_message'] = "You don't have permission to delete this asset.";
        header("Location: manage_assets.php");
        exit();
    }

    // Start a transaction to ensure data integrity
    $conn->begin_transaction();

    // Delete associated files first
    if (!empty($preview_image) && file_exists('../../' . $preview_image)) {
        unlink('../../' . $preview_image);
    }
    
    if (!empty($asset_file) && file_exists('../../' . $asset_file)) {
        unlink('../../' . $asset_file);
    }

    // Delete all comments associated with the asset (fix table name)
    $deleteCommentsQuery = "DELETE FROM comments_asset WHERE asset_id = ?";
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
    // For regular users, redirect to their assets page or profile
    header("Location: " . $baseUrl . "php/profile.php");
}
exit();
?>