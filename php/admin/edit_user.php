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
    <style>
        :root {
            --color-canvas-default: #ffffff;
            --color-canvas-subtle: #f6f8fa;
            --color-border-default: #d0d7de;
            --color-border-muted: #d8dee4;
            --color-btn-primary-bg: #2da44e;
            --color-btn-primary-hover-bg: #2c974b;
            --color-fg-default: #1F2328;
            --color-fg-muted: #656d76;
            --color-accent-fg: #0969da;
            --color-input-bg: #ffffff;
            --color-card-bg: #ffffff;
            --color-card-border: #d0d7de;
            --color-header-bg: #f6f8fa;
            --color-modal-bg: #ffffff;
        }

        @media (prefers-color-scheme: dark) {
            :root {
                --color-canvas-default: #0d1117;
                --color-canvas-subtle: #161b22;
                --color-border-default: #30363d;
                --color-border-muted: #21262d;
                --color-btn-primary-bg: #238636;
                --color-btn-primary-hover-bg: #2ea043;
                --color-fg-default: #c9d1d9;
                --color-fg-muted: #8b949e;
                --color-accent-fg: #58a6ff;
                --color-input-bg: #0d1117;
                --color-card-bg: #161b22;
                --color-card-border: #30363d;
                --color-header-bg: #161b22;
                --color-modal-bg: #161b22;
            }
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", "Noto Sans", Helvetica, Arial, sans-serif;
            background-color: var(--color-canvas-default);
            color: var(--color-fg-default);
            line-height: 1.5;
            font-size: 14px;
        }

        .editor-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
        }

        .editor-header {
            background-color: var(--color-card-bg);
            border: 1px solid var(--color-card-border);
            border-radius: 6px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .editor-title {
            font-size: 24px;
            font-weight: 600;
            color: var(--color-fg-default);
            margin-bottom: 8px;
        }

        .breadcrumb {
            background: none;
            padding: 0;
            margin: 0;
            font-size: 14px;
        }

        .breadcrumb-item a {
            color: var(--color-accent-fg);
            text-decoration: none;
        }

        .breadcrumb-item.active {
            color: var(--color-fg-muted);
        }

        .user-meta {
            font-size: 12px;
            color: var(--color-fg-muted);
            margin-top: 12px;
            display: flex;
            gap: 16px;
        }

        .editor-form {
            background-color: var(--color-card-bg);
            border: 1px solid var(--color-card-border);
            border-radius: 6px;
            padding: 24px;
        }

        .form-section {
            margin-bottom: 24px;
        }

        .form-section:last-child {
            margin-bottom: 0;
        }

        .section-title {
            font-size: 16px;
            font-weight: 600;
            color: var(--color-fg-default);
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-control, .form-select {
            padding: 8px 12px;
            font-size: 14px;
            line-height: 20px;
            color: var(--color-fg-default);
            background-color: var(--color-input-bg);
            border: 1px solid var(--color-border-default);
            border-radius: 6px;
            transition: border-color .15s ease-in-out, box-shadow .15s ease-in-out;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--color-accent-fg);
            outline: none;
            box-shadow: 0 0 0 3px rgba(9, 105, 218, 0.3);
        }

        .form-label {
            font-weight: 500;
            font-size: 14px;
            color: var(--color-fg-default);
            margin-bottom: 6px;
        }

        .btn {
            border-radius: 6px;
            padding: 6px 16px;
            font-size: 14px;
            font-weight: 500;
            line-height: 20px;
            transition: .2s cubic-bezier(0.3, 0, 0.5, 1);
            border: 1px solid transparent;
        }

        .btn-primary {
            background-color: var(--color-btn-primary-bg);
            border-color: var(--color-btn-primary-bg);
            color: #ffffff;
        }

        .btn-primary:hover {
            background-color: var(--color-btn-primary-hover-bg);
            border-color: var(--color-btn-primary-hover-bg);
        }

        .btn-outline-secondary {
            border-color: var(--color-border-default);
            color: var(--color-fg-default);
        }

        .btn-outline-secondary:hover {
            background-color: var(--color-canvas-subtle);
            border-color: var(--color-border-default);
            color: var(--color-fg-default);
        }

        .btn-danger {
            background-color: #da3633;
            border-color: #da3633;
            color: #ffffff;
        }

        .form-actions {
            display: flex;
            gap: 12px;
            padding-top: 20px;
            border-top: 1px solid var(--color-border-muted);
            margin-top: 24px;
        }

        .two-column {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .status-reason {
            display: none;
        }

        /* Alerts */
        .alert-container {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1050;
            pointer-events: none;
        }

        .custom-alert {
            position: relative;
            margin: 16px auto;
            max-width: 500px;
            border-radius: 6px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            pointer-events: auto;
            overflow: hidden;
            transform: translateY(-100%);
            opacity: 0;
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.3s linear;
        }

        .custom-alert.show {
            transform: translateY(0);
            opacity: 1;
        }

        .custom-alert.hiding {
            transform: translateY(-100%);
            opacity: 0;
        }

        .custom-alert .progress {
            position: absolute;
            bottom: 0;
            left: 0;
            height: 3px;
            width: 100%;
            border-radius: 0;
            background-color: rgba(0, 0, 0, 0.1);
            padding: 0;
            margin: 0;
        }

        .custom-alert .progress-bar {
            transition: width linear 5000ms;
            width: 100%;
            height: 100%;
        }

        .custom-alert-content {
            display: flex;
            align-items: center;
            padding: 12px 16px;
        }

        .custom-alert-icon {
            margin-right: 12px;
            font-size: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .custom-alert-message {
            flex-grow: 1;
        }

        .custom-alert-close {
            background: transparent;
            border: none;
            color: inherit;
            opacity: 0.7;
            padding: 0 4px;
            cursor: pointer;
        }

        .custom-alert-close:hover {
            opacity: 1;
        }

        /* Alert Types */
        .custom-alert-success {
            background-color: #d1e7dd;
            color: #0f5132;
        }

        .custom-alert-danger {
            background-color: #f8d7da;
            color: #842029;
        }

        .custom-alert-warning {
            background-color: #fff3cd;
            color: #664d03;
        }

        .custom-alert-info {
            background-color: #cff4fc;
            color: #055160;
        }

        .custom-alert-success .progress-bar {
            background-color: #198754;
        }

        .custom-alert-danger .progress-bar {
            background-color: #dc3545;
        }

        .custom-alert-warning .progress-bar {
            background-color: #ffc107;
        }

        .custom-alert-info .progress-bar {
            background-color: #0dcaf0;
        }

        /* Dark Mode Alerts */
        @media (prefers-color-scheme: dark) {
            .custom-alert-success {
                background-color: #12281e;
                color: #7ee2b8;
            }
            .custom-alert-danger {
                background-color: #2e0a12;
                color: #fda4af;
            }
            .custom-alert-warning {
                background-color: #2e2a0e;
                color: #fde047;
            }
            .custom-alert-info {
                background-color: #092c42;
                color: #7dd3fc;
            }
        }

        /* Add glow animation to header and form containers only */
        .editor-header, .editor-form {
            position: relative;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            transform: translateZ(0);
            will-change: transform;
            border: 1px solid transparent;
        }

        .editor-header::before,
        .editor-header::after,
        .editor-form::before,
        .editor-form::after {
            content: '';
            position: absolute;
            left: 0;
            width: 100%;
            height: 2px;
            background: rgba(9, 105, 218, 0.3);
            transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 1;
            opacity: 0;
        }

        .editor-header::before,
        .editor-form::before {
            top: 0;
            transform: translateX(-105%);
            box-shadow: 0 0 15px rgba(9, 105, 218, 0.3);
        }

        .editor-header::after,
        .editor-form::after {
            bottom: 0;
            transform: translateX(105%);
            box-shadow: 0 0 15px rgba(9, 105, 218, 0.3);
        }

        .editor-header:hover::before,
        .editor-header:hover::after,
        .editor-form:hover::before,
        .editor-form:hover::after {
            transform: translateX(0);
            opacity: 1;
        }

        .editor-header:hover,
        .editor-form:hover {
            transform: translateY(-2px);
            box-shadow: 0 0 25px 5px rgba(9, 105, 218, 0.2),
                        0 4px 20px rgba(0, 0, 0, 0.3);
            border-color: rgba(9, 105, 218, 0.3);
        }

        /* Dark mode adjustments */
        @media (prefers-color-scheme: dark) {
            .editor-header::before,
            .editor-header::after,
            .editor-form::before,
            .editor-form::after {
                background: rgba(88, 166, 255, 0.3);
                box-shadow: 0 0 15px rgba(88, 166, 255, 0.3);
            }
            
            .editor-header:hover,
            .editor-form:hover {
                box-shadow: 0 0 25px 5px rgba(88, 166, 255, 0.2),
                            0 4px 20px rgba(0, 0, 0, 0.3);
                border-color: rgba(88, 166, 255, 0.3);
            }
        }

        @media (max-width: 768px) {
            .two-column {
                grid-template-columns: 1fr;
            }
            
            .editor-container {
                padding: 16px;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .user-meta {
                flex-direction: column;
                gap: 8px;
            }
        }
    </style>
</head>
<body>
    <?php include('../header.php'); ?>
    
    <!-- Alert Container -->
    <div id="alertContainer" class="alert-container"></div>

    <div class="editor-container">
        <!-- Header Section -->
        <div class="editor-header">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <h1 class="editor-title">Edit User</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="manage_users.php">Manage Users</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Edit User</li>
                        </ol>
                    </nav>
                </div>
            </div>
            
            <div class="user-meta">
                <span><i class="bi bi-person me-1"></i>Username: <?php echo htmlspecialchars($username); ?></span>
                <span><i class="bi bi-envelope me-1"></i>Email: <?php echo htmlspecialchars($email); ?></span>
                <span><i class="bi bi-hash me-1"></i>ID: <?php echo $user_id; ?></span>
            </div>
        </div>

        <!-- Editor Form -->
        <form action="edit_user.php?id=<?php echo $edit_user_id; ?>" method="POST" class="editor-form">
            <!-- User Info Section -->
            <div class="form-section">
                <div class="section-title">
                    <i class="bi bi-person-badge"></i>
                    User Information
                </div>
                <div class="two-column">
                    <div>
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
                    </div>
                    <div>
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                    </div>
                </div>
            </div>

            <!-- Role and Status -->
            <div class="form-section">
                <div class="section-title">
                    <i class="bi bi-gear"></i>
                    User Settings
                </div>
                <div class="two-column">
                    <div>
                        <label for="role" class="form-label">Role</label>
                        <select class="form-select" id="role" name="role">
                            <option value="user" <?php echo $role === 'user' ? 'selected' : ''; ?>>User</option>
                            <option value="admin" <?php echo $role === 'admin' ? 'selected' : ''; ?>>Admin</option>
                        </select>
                    </div>
                    <div>
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="suspended" <?php echo $status === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <!-- Status reason field (initially hidden, shows when status changes) -->
            <div class="form-section status-reason" id="status_reason_container">
                <div class="section-title">
                    <i class="bi bi-info-circle"></i>
                    Status Change Reason
                </div>
                <label for="status_reason" class="form-label">Reason for Status Change</label>
                <textarea class="form-control" id="status_reason" name="status_reason" rows="3" placeholder="Provide a reason for changing the user's status"></textarea>
                <div class="form-text text-warning">This reason will be included in the email notification sent to the user.</div>
            </div>
            
            <!-- Form Actions -->
            <div class="form-actions">
                <a href="manage_users.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Cancel
                </a>
                <button type="submit" class="btn btn-primary ms-auto">
                    <i class="bi bi-check-lg me-1"></i>Save Changes
                </button>
            </div>
        </form>
    </div>

    <script>
        // Alert Functions
        function showAlert(message, type = 'info') {
            const alertContainer = document.getElementById('alertContainer');
            const alertElement = document.createElement('div');
            alertElement.className = `custom-alert custom-alert-${type}`;
            
            let iconClass = 'bi-info-circle';
            if (type === 'success') iconClass = 'bi-check-circle';
            if (type === 'danger') iconClass = 'bi-exclamation-triangle';
            if (type === 'warning') iconClass = 'bi-exclamation-circle';
            
            alertElement.innerHTML = `
                <div class="custom-alert-content">
                    <div class="custom-alert-icon"><i class="bi ${iconClass}"></i></div>
                    <div class="custom-alert-message">${message}</div>
                    <button type="button" class="custom-alert-close"><i class="bi bi-x"></i></button>
                </div>
                <div class="progress">
                    <div class="progress-bar"></div>
                </div>
            `;
            
            alertContainer.appendChild(alertElement);
            
            requestAnimationFrame(() => alertElement.classList.add('show'));
            
            const progressBar = alertElement.querySelector('.progress-bar');
            progressBar.style.transition = 'width linear 5000ms';
            progressBar.style.width = '100%';
            setTimeout(() => { progressBar.style.width = '0%'; }, 50);
            
            const dismissTimeout = setTimeout(() => {
                dismissAlert(alertElement);
            }, 5050);
            
            alertElement.querySelector('.custom-alert-close').addEventListener('click', () => {
                clearTimeout(dismissTimeout);
                dismissAlert(alertElement);
            });
        }

        function dismissAlert(alertElement) {
            if (!alertElement || alertElement.classList.contains('hiding')) return;
            alertElement.classList.add('hiding');
            alertElement.classList.remove('show');
            setTimeout(() => { alertElement.remove(); }, 300);
        }

        // Show alerts if messages exist
        <?php if (isset($_SESSION['success_message'])): ?>
            showAlert(<?= json_encode($_SESSION['success_message']) ?>, "success");
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            showAlert(<?= json_encode($_SESSION['error_message']) ?>, "danger");
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        // JavaScript for showing/hiding the reason field
        document.addEventListener('DOMContentLoaded', function() {
            const statusSelect = document.getElementById('status');
            const currentStatus = "<?php echo $status; ?>";
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