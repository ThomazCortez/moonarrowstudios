<?php
// Start session
session_start();

// Set base URL
$baseUrl = '/moonarrowstudios/';

// Check if user is logged in and is an admin
require_once '../db_connect.php';

// For PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../../vendor/autoload.php'; // Adjust path as needed for PHPMailer

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

// Check if the user ID to edit is provided
if (!isset($_GET['id'])) {
    $_SESSION['error_message'] = "User ID not provided.";
    header("Location: manage_users.php");
    exit();
}

$edit_user_id = (int)$_GET['id'];

// Fetch user details
$query = "SELECT user_id, username, email, role, status FROM users WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $edit_user_id);
$stmt->execute();
$stmt->bind_result($user_id, $username, $email, $role, $status);
$stmt->fetch();
$stmt->close();

// If the user doesn't exist, redirect
if (!$user_id) {
    $_SESSION['error_message'] = "User not found.";
    header("Location: manage_users.php");
    exit();
}

// Function to send status change email
function sendStatusChangeEmail($email, $username, $newStatus, $reason) {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; // Replace with your SMTP server
        $mail->SMTPAuth   = true;
        $mail->Username   = 'moonarrowstudios@gmail.com'; // Replace with your email
        $mail->Password   = 'jbws akjv bxvr xxac'; // Replace with your email password
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;
        
        // Recipients
        $mail->setFrom('noreply@moonarrowstudios.com', 'MoonArrow Studios');
        $mail->addAddress($email, $username);
        
        // Content
        $mail->isHTML(true);
        
        $statusText = $newStatus === 'suspended' ? 'Account Suspended' : 'Account Reactivated';
        $actionText = $newStatus === 'suspended' ? 'suspended' : 'reactivated';
        $colorCode = $newStatus === 'suspended' ? '#dc3545' : '#28a745';
        
        $mail->Subject = "MoonArrow Studios - Account Status Change: $statusText";
        
        $mail->Body = '
        <div style="font-family: Arial, sans-serif; text-align: center; background-color: #18141D; padding: 20px; color: #FFFFFF;">
            <div style="max-width: 500px; margin: auto; background-color: #24222A; border: 1px solid #333; border-radius: 8px; padding: 30px; box-shadow: 0 2px 5px rgba(0, 0, 0, 0.5);">
                <!-- Logo Section -->
                <div style="margin-bottom: 20px;">
                    <img src="https://i.ibb.co/q0Y1L5q/horizontal-logo.png" alt="MoonArrow Studios Logo" style="width: 120px; height: auto;">
                </div>
                <!-- Content Section -->
                <h2 style="font-size: 20px; color: ' . $colorCode . '; margin-bottom: 10px;">' . $statusText . '</h2>
                <p style="font-size: 14px; color: #CCCCCC; line-height: 1.6;">
                    Your account has been ' . $actionText . '.
                </p>
                <div style="background-color: rgba(0,0,0,0.2); border-left: 4px solid ' . $colorCode . '; padding: 10px; text-align: left; margin: 20px 0;">
                    <p style="font-size: 14px; color: #CCCCCC; line-height: 1.6; margin: 0;">
                        <strong>Reason:</strong> ' . htmlspecialchars($reason) . '
                    </p>
                </div>
                <p style="font-size: 14px; color: #CCCCCC; line-height: 1.6;">
                    If you have any questions, please contact our support team.
                </p>
                <hr style="border-top: 1px solid #444; margin: 20px 0;">
                <p style="font-size: 12px; color: #555555;">
                    &copy; 2024 MoonArrow Studios. All rights reserved.
                </p>
            </div>
        </div>';
        
        $mail->AltBody = "Your account has been $actionText.\n\nReason: $reason\n\nIf you have any questions, please contact our support team.";
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        return "Mail could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate input
    $new_username = trim($_POST['username']);
    $new_email = trim($_POST['email']);
    $new_role = $_POST['role'];
    $new_status = $_POST['status'];
    $status_reason = isset($_POST['status_reason']) ? trim($_POST['status_reason']) : '';
    
    // Validate inputs
    if (empty($new_username) || empty($new_email)) {
        $_SESSION['error_message'] = "Username and email are required.";
    } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error_message'] = "Invalid email format.";
    } elseif ($status !== $new_status && empty($status_reason)) {
        $_SESSION['error_message'] = "Please provide a reason for changing the user's status.";
    } else {
        // Check if status has changed
        $status_changed = ($status !== $new_status);
        
        // Update user details in the database
        $update_query = "UPDATE users SET username = ?, email = ?, role = ?, status = ? WHERE user_id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("ssssi", $new_username, $new_email, $new_role, $new_status, $edit_user_id);
        
        if ($stmt->execute()) {
            // If status has changed, send an email notification
            if ($status_changed && !empty($status_reason)) {
                $email_result = sendStatusChangeEmail($new_email, $new_username, $new_status, $status_reason);
                
                if ($email_result === true) {
                    $_SESSION['success_message'] = "User updated successfully and notification email sent.";
                } else {
                    $_SESSION['success_message'] = "User updated successfully but failed to send email: " . $email_result;
                }
            } else {
                $_SESSION['success_message'] = "User updated successfully.";
            }
            
            // Log the status change in a status_changes table (optional)
            if ($status_changed && !empty($status_reason)) {
                // First, check if the status_changes table exists, create it if it doesn't
                $check_table_query = "SHOW TABLES LIKE 'status_changes'";
                $result = $conn->query($check_table_query);
                
                if ($result->num_rows == 0) {
                    // Create the table if it doesn't exist
                    $create_table_query = "CREATE TABLE status_changes (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        user_id INT NOT NULL,
                        changed_by INT NOT NULL,
                        old_status VARCHAR(20) NOT NULL,
                        new_status VARCHAR(20) NOT NULL,
                        reason TEXT NOT NULL,
                        change_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        FOREIGN KEY (user_id) REFERENCES users(user_id),
                        FOREIGN KEY (changed_by) REFERENCES users(user_id)
                    )";
                    $conn->query($create_table_query);
                }
                
                // Insert the status change record
                $log_query = "INSERT INTO status_changes (user_id, changed_by, old_status, new_status, reason) 
                              VALUES (?, ?, ?, ?, ?)";
                $log_stmt = $conn->prepare($log_query);
                $log_stmt->bind_param("iisss", $edit_user_id, $_SESSION['user_id'], $status, $new_status, $status_reason);
                $log_stmt->execute();
                $log_stmt->close();
            }
            
            header("Location: manage_users.php");
            exit();
        } else {
            $_SESSION['error_message'] = "Error updating user: " . $stmt->error;
        }
        
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MoonArrow Studios - Edit User</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="icon" href="<?php echo $baseUrl; ?>media/moon.ico" type="image/x-icon" />
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <style>
        .form-container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #444;
            border-radius: 10px;
            background-color: #2d2d2d;
        }
        .status-reason {
            display: none;
        }
    </style>
</head>
<body>
    <?php include('../header.php'); ?>

    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="mb-0">Edit User</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="manage_users.php">Manage Users</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Edit User</li>
                    </ol>
                </nav>
            </div>
        </div>

        <!-- Edit User Form -->
        <div class="form-container bg-gradient">
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger">
                    <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
                </div>
            <?php endif; ?>

            <form action="edit_user.php?id=<?php echo $edit_user_id; ?>" method="POST">
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="role" class="form-label">Role</label>
                    <select class="form-select" id="role" name="role">
                        <option value="user" <?php echo $role === 'user' ? 'selected' : ''; ?>>User</option>
                        <option value="admin" <?php echo $role === 'admin' ? 'selected' : ''; ?>>Admin</option>
                    </select>
                </div>
                
                <!-- Status selection with hidden current status for comparison -->
                <input type="hidden" id="current_status" value="<?php echo htmlspecialchars($status); ?>">
                <div class="mb-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="suspended" <?php echo $status === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                    </select>
                </div>
                
                <!-- Status reason field (initially hidden, shows when status changes) -->
                <div id="status_reason_container" class="mb-3 status-reason">
                    <label for="status_reason" class="form-label">Reason for Status Change</label>
                    <textarea class="form-control" id="status_reason" name="status_reason" rows="3" placeholder="Provide a reason for changing the user's status"></textarea>
                    <div class="form-text text-warning">This reason will be included in the email notification sent to the user.</div>
                </div>
                
                <div class="d-flex justify-content-end">
                    <a href="manage_users.php" class="btn btn-secondary me-2">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- JavaScript for showing/hiding the reason field -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const statusSelect = document.getElementById('status');
            const currentStatus = document.getElementById('current_status').value;
            const reasonContainer = document.getElementById('status_reason_container');
            
            // Check on page load
            checkStatusChange();
            
            // Check on status change
            statusSelect.addEventListener('change', checkStatusChange);
            
            function checkStatusChange() {
                if (statusSelect.value !== currentStatus) {
                    reasonContainer.style.display = 'block';
                } else {
                    reasonContainer.style.display = 'none';
                }
            }
        });
    </script>
</body>
</html>