<?php
// settings.php
session_start();

include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: sign_in/sign_in_html.php");
    exit;
}

// Initialize empty success message
$success_message = '';

// Load PHPMailer if needed
$phpmailer_loaded = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && 
    (isset($_POST['action']) && 
    ($_POST['action'] === 'change_password' || $_POST['action'] === 'change_email'))) {
    require '../vendor/autoload.php';
    $phpmailer_loaded = true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    
    // Handle Profile Updates
    if (isset($_POST['action']) && $_POST['action'] === 'update_profile') {
        $description = $_POST['description'];
        
        // Handle social links
        $twitter = isset($_POST['twitter']) ? $_POST['twitter'] : '';
        $instagram = isset($_POST['instagram']) ? $_POST['instagram'] : '';
        $github = isset($_POST['github']) ? $_POST['github'] : '';
        $portfolio = isset($_POST['portfolio']) ? $_POST['portfolio'] : '';
        $youtube = isset($_POST['youtube']) ? $_POST['youtube'] : '';
        $linkedin = isset($_POST['linkedin']) ? $_POST['linkedin'] : '';
        
        // Handle cropped profile picture upload
        if (!empty($_POST['profile_picture_data']) && strpos($_POST['profile_picture_data'], 'data:image/png;base64,') === 0) {
            $upload_dir = '../uploads/profile_pictures/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $img = $_POST['profile_picture_data'];
            $img = str_replace('data:image/png;base64,', '', $img);
            $img = str_replace(' ', '+', $img);
            $data = base64_decode($img);
            
            // Add timestamp to filename for cache busting
            $timestamp = time();
            $new_filename = 'profile_' . $user_id . '_' . $timestamp . '.png';
            
            // Local path for file saving
            $local_path = $upload_dir . $new_filename;
            // Database path for storage
            $db_path = '\moonarrowstudios\uploads\profile_pictures\\' . $new_filename;
            
            // Delete old profile picture if it exists
            $stmt = $conn->prepare("SELECT profile_picture FROM users WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $old_profile = $stmt->get_result()->fetch_assoc();
            
            if ($old_profile && $old_profile['profile_picture']) {
                $old_file = str_replace('\moonarrowstudios', '..', $old_profile['profile_picture']);
                if (file_exists($old_file)) {
                    unlink($old_file);
                }
            }
            
            file_put_contents($local_path, $data);
            
            $stmt = $conn->prepare("UPDATE users SET profile_picture = ? WHERE user_id = ?");
            $stmt->bind_param("si", $db_path, $user_id);
            $stmt->execute();
        }
        
        // Handle cropped banner upload
        if (!empty($_POST['banner_data']) && strpos($_POST['banner_data'], 'data:image/png;base64,') === 0) {
            $upload_dir = '../uploads/banners/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $img = $_POST['banner_data'];
            $img = str_replace('data:image/png;base64,', '', $img);
            $img = str_replace(' ', '+', $img);
            $data = base64_decode($img);
            
            // Add timestamp to filename for cache busting
            $timestamp = time();
            $new_filename = 'banner_' . $user_id . '_' . $timestamp . '.png';
            
            // Local path for file saving
            $local_path = $upload_dir . $new_filename;
            // Database path for storage
            $db_path = '\moonarrowstudios\uploads\banners\\' . $new_filename;
            
            // Delete old banner if it exists
            $stmt = $conn->prepare("SELECT banner FROM users WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $old_banner = $stmt->get_result()->fetch_assoc();
            
            if ($old_banner && $old_banner['banner']) {
                $old_file = str_replace('\moonarrowstudios', '..', $old_banner['banner']);
                if (file_exists($old_file)) {
                    unlink($old_file);
                }
            }
            
            file_put_contents($local_path, $data);
            
            $stmt = $conn->prepare("UPDATE users SET banner = ? WHERE user_id = ?");
            $stmt->bind_param("si", $db_path, $user_id);
            $stmt->execute();
        }
        
        // Update user description and social links
        $stmt = $conn->prepare("UPDATE users SET description = ?, twitter = ?, instagram = ?, github = ?, portfolio = ?, youtube = ?, linkedin = ? WHERE user_id = ?");
        $stmt->bind_param("sssssssi", $description, $twitter, $instagram, $github, $portfolio, $youtube, $linkedin, $user_id);
        $stmt->execute();
        
        $success_message = "Profile updated successfully!";
    }

    // Handle Password Change Request
elseif (isset($_POST['action']) && $_POST['action'] === 'change_password') {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Verify current password
    $stmt = $conn->prepare("SELECT password, email FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user_data = $stmt->get_result()->fetch_assoc();
    
    if (password_verify($current_password, $user_data['password'])) {
        if ($new_password === $confirm_password) {
            // Generate token
            $token = bin2hex(random_bytes(32));
            $email = $user_data['email'];
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            // Validate email exists
            if (empty($email)) {
                $error_message = "No email address associated with your account!";
            } else {
                // Store token and new password
                $stmt = $conn->prepare("INSERT INTO password_resets (email, token, new_password, purpose) VALUES (?, ?, ?, 'change')");
                $stmt->bind_param("sss", $email, $token, $hashed_password);
                $stmt->execute();
                
                // Send verification email
                if ($phpmailer_loaded) {
                    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                    try {
                        $mail->isSMTP();
                        $mail->Host       = 'smtp.gmail.com';
                        $mail->SMTPAuth   = true;
                        $mail->Username   = 'moonarrowstudios@gmail.com';
                        $mail->Password   = 'jbws akjv bxvr xxac';
                        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
                        $mail->Port       = 465;

                        $mail->setFrom('noreply@moonnarrowstudios.com', 'MoonArrow Studios');
                        
                        // Validate recipient before adding
                        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                            $mail->addAddress($email);
                        } else {
                            throw new Exception("Invalid email address: $email");
                        }

                        $mail->isHTML(true);
                        $mail->Subject = 'Verify Password Change';
                        $mail->Body    = '
                        <div style="font-family: Arial, sans-serif; text-align: center; background-color: #18141D; padding: 20px; color: #FFFFFF;">
                            <div style="max-width: 500px; margin: auto; background-color: #24222A; border: 1px solid #333; border-radius: 8px; padding: 30px; box-shadow: 0 2px 5px rgba(0, 0, 0, 0.5);">
                                <div style="margin-bottom: 20px;">
                                    <img src="https://i.ibb.co/q0Y1L5q/horizontal-logo.png" alt="MoonArrow Studios Logo" style="width: 120px; height: auto;">
                                </div>
                                <h2 style="font-size: 20px; color: #FFFFFF; margin-bottom: 10px;">Confirm Password Change</h2>
                                <p style="font-size: 14px; color: #CCCCCC; line-height: 1.6;">
                                    Please verify your password change by clicking the button below.
                                </p>
                                <a href="http://localhost/moonarrowstudios/php/verify_password_change.php?token=' . $token . '" 
                                    style="display: inline-block; margin-top: 20px; padding: 12px 25px; font-size: 14px; color: #FFFFFF; text-decoration: none; background-color: #007BFF; border-radius: 4px;">
                                    Verify Password Change
                                </a>
                                <p style="font-size: 12px; color: #777777; margin-top: 20px;">
                                    If you didn\'t request this change, please contact our support team immediately.
                                </p>
                                <hr style="border-top: 1px solid #444; margin: 20px 0;">
                                <p style="font-size: 12px; color: #555555;">
                                    &copy; 2024 MoonArrow Studios. All rights reserved.
                                </p>
                            </div>
                        </div>';

                        $mail->send();
                        $success_message = "A verification email has been sent. Please check your inbox to complete the password change.";
                    } catch (Exception $e) {
                        error_log("Email error: " . $e->getMessage());
                        $error_message = "Failed to send email. Please try again later.";
                    }
                } else {
                    $error_message = "Email service not available. Please try again later.";
                }
            }
        } else {
            $error_message = "New passwords do not match!";
        }
    } else {
        $error_message = "Current password is incorrect!";
    }
}

    // Handle Email Change Request
    elseif (isset($_POST['action']) && $_POST['action'] === 'change_email') {
        $new_email = filter_var($_POST['new_email'], FILTER_SANITIZE_EMAIL);
        $current_password = $_POST['password'];

        if (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
            $error_message = "Invalid email address.";
        } else {
            // Verify password
            $stmt = $conn->prepare("SELECT password, email FROM users WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $user_data = $stmt->get_result()->fetch_assoc();
            
            if (password_verify($current_password, $user_data['password'])) {
                // Generate token
                $token = bin2hex(random_bytes(32));
                
                // Store token and new email
                $stmt = $conn->prepare("INSERT INTO email_changes (user_id, new_email, token) VALUES (?, ?, ?)");
                $stmt->bind_param("iss", $user_id, $new_email, $token);
                $stmt->execute();
                
                // Send verification email to new address
                if ($phpmailer_loaded) {
                    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                    try {
                        $mail->isSMTP();
                        $mail->Host       = 'smtp.gmail.com';
                        $mail->SMTPAuth   = true;
                        $mail->Username   = 'moonarrowstudios@gmail.com';
                        $mail->Password   = 'jbws akjv bxvr xxac';
                        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
                        $mail->Port       = 465;
                        
                        $mail->setFrom('noreply@moonnarrowstudios.com', 'MoonArrow Studios');
                        $mail->addAddress($new_email);

                        $mail->isHTML(true);
                        $mail->Subject = 'Verify Email Change';
                        $mail->Body    = '
                        <div style="font-family: Arial, sans-serif; text-align: center; background-color: #18141D; padding: 20px; color: #FFFFFF;">
                            <div style="max-width: 500px; margin: auto; background-color: #24222A; border: 1px solid #333; border-radius: 8px; padding: 30px; box-shadow: 0 2px 5px rgba(0, 0, 0, 0.5);">
                                <div style="margin-bottom: 20px;">
                                    <img src="https://i.ibb.co/q0Y1L5q/horizontal-logo.png" alt="MoonArrow Studios Logo" style="width: 120px; height: auto;">
                                </div>
                                <h2 style="font-size: 20px; color: #FFFFFF; margin-bottom: 10px;">Confirm Email Change</h2>
                                <p style="font-size: 14px; color: #CCCCCC; line-height: 1.6;">
                                    Please verify your email change by clicking the button below.
                                </p>
                                <a href="http://localhost/moonarrowstudios/php/verify_email_change.php?token=' . $token . '" 
                                    style="display: inline-block; margin-top: 20px; padding: 12px 25px; font-size: 14px; color: #FFFFFF; text-decoration: none; background-color: #007BFF; border-radius: 4px;">
                                    Verify Email Change
                                </a>
                                <p style="font-size: 12px; color: #777777; margin-top: 20px;">
                                    If you didn\'t request this change, please contact our support team immediately.
                                </p>
                                <hr style="border-top: 1px solid #444; margin: 20px 0;">
                                <p style="font-size: 12px; color: #555555;">
                                    &copy; 2024 MoonArrow Studios. All rights reserved.
                                </p>
                            </div>
                        </div>';

                        $mail->send();
                        
                        // Also send notification to old email
                        $notification_mail = new PHPMailer\PHPMailer\PHPMailer(true);
                        try {
                            $notification_mail->isSMTP();
                            $notification_mail->Host       = 'smtp.gmail.com';
                            $notification_mail->SMTPAuth   = true;
                            $notification_mail->Username   = 'moonarrowstudios@gmail.com';
                            $notification_mail->Password   = 'jbws akjv bxvr xxac';
                            $notification_mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
                            $notification_mail->Port       = 465;
                            
                            $notification_mail->setFrom('noreply@moonnarrowstudios.com', 'MoonArrow Studios');
                            $notification_mail->addAddress($user_data['email']);

                            $notification_mail->isHTML(true);
                            $notification_mail->Subject = 'Email Change Requested';
                            $notification_mail->Body    = '
                            <div style="font-family: Arial, sans-serif; text-align: center; background-color: #18141D; padding: 20px; color: #FFFFFF;">
                                <div style="max-width: 500px; margin: auto; background-color: #24222A; border: 1px solid #333; border-radius: 8px; padding: 30px; box-shadow: 0 2px 5px rgba(0, 0, 0, 0.5);">
                                    <div style="margin-bottom: 20px;">
                                        <img src="https://i.ibb.co/q0Y1L5q/horizontal-logo.png" alt="MoonArrow Studios Logo" style="width: 120px; height: auto;">
                                    </div>
                                    <h2 style="font-size: 20px; color: #FFFFFF; margin-bottom: 10px;">Email Change Requested</h2>
                                    <p style="font-size: 14px; color: #CCCCCC; line-height: 1.6;">
                                        We received a request to change your email address to: ' . $new_email . '.
                                    </p>
                                    <p style="font-size: 14px; color: #CCCCCC; line-height: 1.6;">
                                        If this wasn\'t you, please contact our support team immediately.
                                    </p>
                                    <hr style="border-top: 1px solid #444; margin: 20px 0;">
                                    <p style="font-size: 12px; color: #555555;">
                                        &copy; 2024 MoonArrow Studios. All rights reserved.
                                    </p>
                                </div>
                            </div>';

                            $notification_mail->send();
                        } catch (Exception $e) {
                            // Notification failed, but proceed
                        }
                        
                        $success_message = "A verification email has been sent to your new email address.";
                    } catch (Exception $e) {
                        $error_message = "Failed to send email: {$mail->ErrorInfo}";
                    }
                } else {
                    $error_message = "Email service not available. Please try again later.";
                }
            } else {
                $error_message = "Password is incorrect!";
            }
        }
    }
    
    // Handle Account Deletion
    elseif (isset($_POST['action']) && $_POST['action'] === 'delete_account') {
        $password = trim($_POST['password'] ?? '');
    $error_message = '';
    
    // Validate input
    if (empty($password)) {
        $error_message = "Password is required for account deletion.";
    } else {
        try {
            // Begin transaction for data integrity
            $conn->begin_transaction();
            
            // Verify password
            $stmt = $conn->prepare("SELECT password FROM users WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                throw new Exception("User not found.");
            }
            
            $user_data = $result->fetch_assoc();
            
            if (!password_verify($password, $user_data['password'])) {
                throw new Exception("Incorrect password. Account deletion cancelled.");
            }
            
            // Get user email before deletion for cleanup
            $stmt = $conn->prepare("SELECT email FROM users WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $email_result = $stmt->get_result()->fetch_assoc();
            $user_email = $email_result['email'];
            
            // Delete related data first (foreign key constraints)
            $cleanup_queries = [
                "DELETE FROM password_resets WHERE email = ?",
                "DELETE FROM email_changes WHERE user_id = ?",
                // Add other related tables as needed
                // "DELETE FROM user_sessions WHERE user_id = ?",
                // "DELETE FROM user_preferences WHERE user_id = ?",
            ];
            
            foreach ($cleanup_queries as $query) {
                $stmt = $conn->prepare($query);
                if (strpos($query, 'email') !== false) {
                    $stmt->bind_param("s", $user_email);
                } else {
                    $stmt->bind_param("i", $user_id);
                }
                $stmt->execute();
            }
            
            // Finally, delete the user account
            $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            
            if ($stmt->affected_rows === 0) {
                throw new Exception("Failed to delete user account.");
            }
            
            // Commit transaction
            $conn->commit();
            
            // Destroy session and redirect
            session_destroy();
            
            // Clear any session cookies
            if (isset($_COOKIE[session_name()])) {
                setcookie(session_name(), '', time() - 3600, '/');
            }
            
            header("Location: ../index.php?alert=" . urlencode("Your account has been successfully deleted."));
            exit();
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            $error_message = $e->getMessage();
            
            // Log the error for debugging (optional)
            error_log("Account deletion failed for user_id $user_id: " . $e->getMessage());
        }
    }
}
    
    // Handle Notification Settings Updates
    elseif (isset($_POST['action']) && $_POST['action'] === 'update_notifications') {
        $email_updates = isset($_POST['email_updates']) ? 1 : 0;
        $comment_notifications = isset($_POST['comment_notifications']) ? 1 : 0;
        $message_notifications = isset($_POST['message_notifications']) ? 1 : 0;
        
        $stmt = $conn->prepare("UPDATE users SET email_updates = ?, comment_notifications = ?, message_notifications = ? WHERE user_id = ?");
        $stmt->bind_param("iiii", $email_updates, $comment_notifications, $message_notifications, $user_id);
        $stmt->execute();
        
        $success_message = "Notification preferences updated successfully!";
    }
}

// Fetch current user data
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Determine which tab to show
$active_tab = 'profile';
if (isset($_GET['tab'])) {
    $active_tab = $_GET['tab'];
}
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <?php require 'header.php'; ?>
    <title>MoonArrow Studios - Settings</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .profile-picture-preview,
        .banner-preview {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            margin-top: 10px;
            transition: all 0.3s ease;
        }
        
        .profile-picture-preview {
            max-height: 150px;
            width: 150px;
            object-fit: cover;
        }
        
        .banner-preview {
            max-height: 200px;
            width: 100%;
            object-fit: cover;
        }
        
        .preview-container {
            position: relative;
            margin-bottom: 20px;
        }

        .modal-content {
            background-color: #212529;
            color: #fff;
            height: 90vh;
        }

        .modal-xl {
            max-width: 95vw !important;
            margin: 15px auto;
        }

        .modal-body {
            padding: 0;
            height: calc(90vh - 130px);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .cropper-container {
            height: 100% !important;
            width: 100% !important;
        }

        .cropper-view-box,
        .cropper-face {
            border-radius: 0;
        }

        #cropImage {
            max-width: none;
            max-height: none;
        }

        .modal-header {
            padding: 1rem;
            border-bottom: 1px solid #444;
        }

        .modal-footer {
            padding: 1rem;
            border-top: 1px solid #444;
        }

        .cropper-line {
            background-color: #fff !important;
            opacity: 0.8 !important;
        }

        .cropper-point {
            background-color: #fff !important;
            opacity: 0.8 !important;
        }

        /* Improved styles for unified layout */
        .settings-container {
            margin-top: 30px;
        }

        .settings-card {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            transition: all 0.3s ease;
            background-color: #161b22;
        }

        .settings-card:hover {
            box-shadow: 0 8px 30px rgba(0,0,0,0.25);
        }

        .settings-header {
            padding: 20px 25px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            background-color: rgba(0,0,0,0.1);
        }

        .settings-title {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 500;
            color: #fff;
        }

        .settings-body {
            padding: 25px;
        }

        .settings-tabs-container {
            padding: 0;
        }

        .settings-tabs {
            border-bottom: 1px solid rgba(255,255,255,0.1);
            padding-bottom: 10px; /* Add this */
            background-color: rgba(0,0,0,0.05);
            padding-left: 15px;
            padding-right: 15px;
        }


        .nav-tabs .nav-link {
            border: none;
            color: #adb5bd;
            font-weight: 500;
            padding: 15px 20px;
            transition: all 0.2s ease;
        }

        .nav-tabs {
            border-bottom: none !important; /* Force-remove any browser default */
        }



        .tab-icon {
            margin-right: 8px;
        }

        .form-label {
            font-weight: 500;
            margin-bottom: 8px;
            color: #e9ecef;
        }

        .btn-save {
            padding: 10px 24px;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(13, 110, 253, 0.3);
        }

        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(13, 110, 253, 0.4);
        }

        .tab-pane {
            padding: 25px;
        }

        .form-switch .form-check-input {
            width: 3em;
            height: 1.5em;
        }

        .form-switch .form-check-input:checked {
            background-color: #0d6efd;
            border-color: #0d6efd;
        }

        /* Social links styling */
        .social-input-group {
            position: relative;
        }

        .social-input-group .form-control {
            padding-left: 40px;
        }

        .social-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #adb5bd;
            font-size: 1.2rem;
            z-index: 10;
        }

        /* Image upload improvements */
        .image-upload-container {
            background-color: rgba(0,0,0,0.1);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .upload-row {
            display: flex;
            align-items: flex-start;
            margin-bottom: 20px;
        }

        .upload-preview {
            flex: 0 0 auto;
            margin-right: 20px;
        }

        .upload-controls {
            flex: 1;
        }

        .upload-title {
            font-size: 1.1rem;
            font-weight: 500;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
        }

        .upload-title i {
            margin-right: 8px;
        }

        .upload-description {
            font-size: 0.9rem;
            color: #adb5bd;
            margin-bottom: 15px;
        }

        /* Add animation for tab transitions */
        .animated-tab {
            animation-duration: 0.5s;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .upload-row {
                flex-direction: column;
            }
            
            .upload-preview {
                margin-right: 0;
                margin-bottom: 15px;
                text-align: center;
            }
        }
        /* Alert Container */
.alert-container {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 1050;
  pointer-events: none;
}

/* Base Alert Styles */
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

/* Progress Bar */
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

/* Alert Content */
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

/* Alert Types - Light Mode */
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

/* Progress Bar Colors */
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

/* Dark Mode Styles */
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
    </style>
</head>
<body>
    <div id="alertContainer" class="alert-container"></div>
    <div class="container mt-4 mb-5">
    <script>
        <?php if (!empty($success_message)): ?>
            document.addEventListener('DOMContentLoaded', function() {
                showAlert(<?= json_encode($success_message) ?>, 'success');
            });
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            document.addEventListener('DOMContentLoaded', function() {
                showAlert(<?= json_encode($error_message) ?>, 'danger');
            });
        <?php endif; ?>
    </script>

        <h1 class="mb-4 animate__animated animate__fadeIn">
            <i class="fas fa-cog me-2"></i> Account Settings
        </h1>
        
        <div class="settings-container animate__animated animate__fadeIn">
            <div class="settings-card">
                <div class="settings-tabs-container">
                    <div class="settings-tabs">
                        <ul class="nav nav-tabs" id="settingsTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <a class="nav-link <?php echo ($active_tab == 'profile') ? 'active' : ''; ?>" id="profile-tab" data-bs-toggle="tab" href="#profile" role="tab" aria-controls="profile" aria-selected="<?php echo ($active_tab == 'profile') ? 'true' : 'false'; ?>">
                                    <i class="fas fa-user tab-icon"></i> Profile
                                </a>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a class="nav-link <?php echo ($active_tab == 'security') ? 'active' : ''; ?>" id="security-tab" data-bs-toggle="tab" href="#security" role="tab" aria-controls="security" aria-selected="<?php echo ($active_tab == 'security') ? 'true' : 'false'; ?>">
                                    <i class="fas fa-shield-alt tab-icon"></i> Security
                                </a>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a class="nav-link <?php echo ($active_tab == 'notifications') ? 'active' : ''; ?>" id="notifications-tab" data-bs-toggle="tab" href="#notifications" role="tab" aria-controls="notifications" aria-selected="<?php echo ($active_tab == 'notifications') ? 'true' : 'false'; ?>">
                                    <i class="fas fa-bell tab-icon"></i> Notifications
                                </a>
                            </li>
                        </ul>
                    </div>
                    
                    <div class="tab-content" id="settingsTabContent">
                        <!-- Profile Tab -->
                        <div class="tab-pane fade <?php echo ($active_tab == 'profile') ? 'show active' : ''; ?> animated-tab animate__animated animate__fadeIn" id="profile" role="tabpanel" aria-labelledby="profile-tab">
                            <form method="POST" enctype="multipart/form-data" id="profileForm">
                                <input type="hidden" name="action" value="update_profile">
                                
                                <h4 class="mb-4"><i class="fas fa-image me-2"></i> Profile Media</h4>
                                <div class="image-upload-container">
                                    <!-- Banner Upload Section -->
                                    <div class="upload-row">
                                        <div class="upload-preview">
                                            <?php if ($user['banner']): ?>
                                                <img src="<?= $user['banner'] ?>" alt="Current banner" class="banner-preview">
                                            <?php else: ?>
                                                <div class="banner-preview bg-dark d-flex align-items-center justify-content-center" style="width: 300px; height: 90px;">
                                                    <i class="fas fa-image text-muted fa-2x"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="upload-controls">
                                            <div class="upload-title">
                                                <i class="fas fa-image"></i> Profile Banner
                                            </div>
                                            <div class="upload-description">
                                                Recommended size 1000x300px. Max file size 2MB.
                                            </div>
                                            <input type="file" class="form-control" id="banner" accept="image/*">
                                            <input type="hidden" name="banner_data" id="banner_data">
                                        </div>
                                    </div>
                                    
                                    <!-- Profile Picture Upload Section -->
                                    <div class="upload-row">
                                        <div class="upload-preview">
                                            <?php if ($user['profile_picture']): ?>
                                                <img src="<?= $user['profile_picture'] ?>" alt="Current profile picture" class="profile-picture-preview rounded-circle">
                                            <?php else: ?>
                                                <div class="profile-picture-preview bg-dark rounded-circle d-flex align-items-center justify-content-center" style="width: 150px; height: 150px;">
                                                    <i class="fas fa-user text-muted fa-3x"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="upload-controls">
                                            <div class="upload-title">
                                                <i class="fas fa-user-circle"></i> Profile Picture
                                            </div>
                                            <div class="upload-description">
                                                Square image recommended. Max file size 1MB.
                                            </div>
                                            <input type="file" class="form-control" id="profile_picture" accept="image/*">
                                            <input type="hidden" name="profile_picture_data" id="profile_picture_data">
                                        </div>
                                    </div>
                                </div>
                                
                                <h4 class="mb-4"><i class="fas fa-info-circle me-2"></i> About You</h4>
                                <div class="mb-4">
                                    <label for="description" class="form-label">About Me</label>
                                    <textarea class="form-control" id="description" name="description" rows="4" placeholder="Tell the community about yourself..."><?= htmlspecialchars($user['description'] ?? '') ?></textarea>
                                </div>
                                
                                <h4 class="mb-4"><i class="fas fa-link me-2"></i> Social Links</h4>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="mb-3 social-input-group">
                                            <label for="twitter" class="form-label">Twitter</label>
                                            <i class="fab fa-twitter social-icon"></i>
                                            <input type="text" class="form-control" id="twitter" name="twitter" placeholder="Your Twitter username" value="<?= htmlspecialchars($user['twitter'] ?? '') ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3 social-input-group">
                                            <label for="instagram" class="form-label">Instagram</label>
                                            <i class="fab fa-instagram social-icon"></i>
                                            <input type="text" class="form-control" id="instagram" name="instagram" placeholder="Your Instagram username" value="<?= htmlspecialchars($user['instagram'] ?? '') ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3 social-input-group">
                                            <label for="youtube" class="form-label">YouTube</label>
                                            <i class="fab fa-youtube social-icon"></i>
                                            <input type="text" class="form-control" id="youtube" name="youtube" 
                                                placeholder="Your YouTube channel" value="<?= htmlspecialchars($user['youtube'] ?? '') ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3 social-input-group">
                                            <label for="linkedin" class="form-label">LinkedIn</label>
                                            <i class="fab fa-linkedin social-icon"></i>
                                            <input type="text" class="form-control" id="linkedin" name="linkedin" 
                                                placeholder="Your LinkedIn profile" value="<?= htmlspecialchars($user['linkedin'] ?? '') ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3 social-input-group">
                                            <label for="github" class="form-label">GitHub</label>
                                            <i class="fab fa-github social-icon"></i>
                                            <input type="text" class="form-control" id="github" name="github" placeholder="Your GitHub username" value="<?= htmlspecialchars($user['github'] ?? '') ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3 social-input-group">
                                            <label for="portfolio" class="form-label">Portfolio/Website</label>
                                            <i class="fas fa-globe social-icon"></i>
                                            <input type="text" class="form-control" id="portfolio" name="portfolio" placeholder="https://your-website.com" value="<?= htmlspecialchars($user['portfolio'] ?? '') ?>">
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-4 text-end">
                                    <button type="submit" class="btn btn-primary btn-save">
                                        <i class="fas fa-save me-2"></i> Save Profile Changes
                                    </button>
                                </div>
                            </form>
                        </div>
                        
<!-- Security Tab -->
<div class="tab-pane fade <?php echo ($active_tab == 'security') ? 'show active' : ''; ?> animated-tab animate__animated animate__fadeIn" id="security" role="tabpanel" aria-labelledby="security-tab">
    <!-- Change Password Form -->
    <h4 class="mb-4"><i class="fas fa-lock me-2"></i> Change Password</h4>
    <form method="POST" id="passwordForm">
        <input type="hidden" name="action" value="change_password">
        
        <div class="mb-3">
            <label for="current_password" class="form-label">Current Password</label>
            <div class="input-group">
                <span class="input-group-text"><i class="fas fa-key"></i></span>
                <input type="password" class="form-control" id="current_password" name="current_password" required>
            </div>
        </div>
        
        <div class="mb-3">
            <label for="new_password" class="form-label">New Password</label>
            <div class="input-group">
                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                <input type="password" class="form-control" id="new_password" name="new_password" required>
            </div>
            <small class="text-muted">Password must be at least 8 characters long and include a mix of letters and numbers.</small>
        </div>
        
        <div class="mb-4">
            <label for="confirm_password" class="form-label">Confirm New Password</label>
            <div class="input-group">
                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
            </div>
        </div>
        
        <div class="text-end">
            <button type="submit" class="btn btn-primary btn-save">
                <i class="fas fa-key me-2"></i> Send Verification Email
            </button>
        </div>
    </form>
    
    <!-- Change Email Form -->
    <h4 class="mb-4 mt-5"><i class="fas fa-envelope me-2"></i> Change Email</h4>
    <form method="POST" id="emailForm">
        <input type="hidden" name="action" value="change_email">
        
        <div class="mb-3">
            <label for="new_email" class="form-label">New Email Address</label>
            <div class="input-group">
                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                <input type="email" class="form-control" id="new_email" name="new_email" required>
            </div>
        </div>
        
        <div class="mb-4">
            <label for="password" class="form-label">Confirm Password</label>
            <div class="input-group">
                <span class="input-group-text"><i class="fas fa-key"></i></span>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
        </div>
        
        <div class="text-end">
            <button type="submit" class="btn btn-primary btn-save">
                <i class="fas fa-paper-plane me-2"></i> Send Verification Email
            </button>
        </div>
    </form>
    
    <!-- Delete Account Section -->
<h4 class="mb-4 mt-5"><i class="fas fa-trash-alt me-2"></i> Delete Account</h4>
<div class="alert alert-danger">
    <strong>Warning:</strong> Deleting your account is permanent and cannot be undone. All your data will be removed.
</div>
<button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteAccountModal">
    <i class="fas fa-trash-alt me-2"></i> Delete Account
</button>

<!-- Delete Account Modal -->
<div class="modal fade" id="deleteAccountModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-danger">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Confirm Account Deletion
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="deleteAccountForm">
                <input type="hidden" name="action" value="delete_account">
                <div class="modal-body">
                    <div class="alert alert-warning mb-4">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>This action is irreversible!</strong> All your data will be permanently deleted.
                    </div>
                    
                    <div class="mb-4">
                        <h6 class="text-danger mb-3">What will be deleted:</h6>
                        <ul class="text-muted">
                            <li>Your account and profile information</li>
                            <li>All your saved data</li>
                            <li>Password reset tokens</li>
                            <li>Email change requests</li>
                        </ul>
                    </div>
                    
                    <div class="mb-4">
                        <label for="delete_password" class="form-label fw-bold">
                            Enter your password to confirm deletion:
                        </label>
                        <input type="password" 
                               class="form-control" 
                               id="delete_password" 
                               name="password" 
                               placeholder="Your current password"
                               required 
                               autocomplete="current-password">
                        <div class="invalid-feedback">
                            Please enter your password to confirm deletion.
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="confirmDeletion" required>
                            <label class="form-check-label text-danger fw-bold" for="confirmDeletion">
                                I understand this action cannot be undone
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-danger" id="deleteAccountButton" disabled>
                        <i class="fas fa-trash-alt me-2"></i>
                        <span id="countdown">10</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


    <!-- Cropping Modal -->
    <div class="modal fade" id="cropModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-crop me-2"></i> Crop Image</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="cropper-container">
                        <img id="cropImage" src="" alt="Image to crop">
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i> Cancel
                    </button>
                    <button class="btn btn-primary" id="cropButton">
                        <i class="fas fa-crop me-2"></i> Crop Image
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
    <script>
        let cropper;
        let currentInput;
        const modal = new bootstrap.Modal(document.getElementById('cropModal'));
        
        function initCropper(input, aspectRatio) {
            currentInput = input;
            const file = input.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const cropImage = document.getElementById('cropImage');
                    cropImage.src = e.target.result;
                    
                    if (cropper) {
                        cropper.destroy();
                    }
                    
                    modal.show();
                    
                    setTimeout(() => {
                        cropper = new Cropper(cropImage, {
                            aspectRatio: aspectRatio,
                            viewMode: 1,
                            background: true,
                            modal: true,
                            dragMode: 'move',
                            autoCropArea: 0.8,
                            responsive: true,
                            restore: false,
                            guides: true,
                            center: true,
                            highlight: false,
                            cropBoxMovable: true,
                            cropBoxResizable: true,
                            toggleDragModeOnDblclick: false
                        });
                    }, 200);
                }
                reader.readAsDataURL(file);
            }
        }

        document.getElementById('profile_picture').addEventListener('change', function() {
            initCropper(this, 1); // 1:1 aspect ratio for profile picture
        });

        document.getElementById('banner').addEventListener('change', function() {
            initCropper(this, 669 / 200); // 669:200 aspect ratio for banner
        });

        document.getElementById('cropButton').addEventListener('click', function() {
            const croppedCanvas = cropper.getCroppedCanvas({
                width: currentInput.id === 'profile_picture' ? 150 : 669, // Set width for profile picture and banner
                height: currentInput.id === 'profile_picture' ? 150 : 200 // Set height for profile picture and banner
            });
            const croppedImage = croppedCanvas.toDataURL('image/png');
            
            // Update preview
            const previewClass = currentInput.id === 'profile_picture' ? 
                '.profile-picture-preview' : '.banner-preview';
            
            let preview = document.querySelector(previewClass);
            
            if (preview) {
                preview.src = croppedImage;
                preview.classList.add('animate__animated', 'animate__fadeIn');
                
                // Remove placeholder if it exists
                if (preview.classList.contains('d-flex')) {
                    preview.classList.remove('d-flex', 'align-items-center', 'justify-content-center', 'bg-dark');
                    preview.innerHTML = '';
                }
            } else {
                const previewContainer = document.createElement('div');
                previewContainer.className = 'preview-container';
                
                const newPreview = document.createElement('img');
                newPreview.src = croppedImage;
                newPreview.className = previewClass.substring(1) + ' animate__animated animate__fadeIn';
                
                previewContainer.appendChild(newPreview);
                currentInput.parentElement.appendChild(previewContainer);
            }
            
            // Update hidden input
            const hiddenInput = document.getElementById(currentInput.id + '_data');
            hiddenInput.value = croppedImage;
            
            modal.hide();
        });

        // Handle tab navigation and history
        document.querySelectorAll('#settingsTabs .nav-link').forEach(tab => {
            tab.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Update URL with the tab parameter
                const tabId = this.getAttribute('href').substring(1);
                history.replaceState(null, null, `?tab=${tabId}`);
                
                // Make tab active
                document.querySelectorAll('#settingsTabs .nav-link').forEach(t => {
                    t.classList.remove('active');
                    t.setAttribute('aria-selected', 'false');
                });
                this.classList.add('active');
                this.setAttribute('aria-selected', 'true');
                
                // Show tab content
                document.querySelectorAll('.tab-pane').forEach(p => {
                    p.classList.remove('show', 'active');
                });
                
                const tabContent = document.querySelector(this.getAttribute('href'));
                tabContent.classList.add('show', 'active');
                
                // Add animation classes
                tabContent.classList.remove('animate__fadeIn');
                void tabContent.offsetWidth; // Trigger reflow
                tabContent.classList.add('animate__fadeIn');
            });
        });

        // Form validation for social links
        document.getElementById('profileForm').addEventListener('submit', function(e) {
            const twitterInput = document.getElementById('twitter');
            const instagramInput = document.getElementById('instagram');
            const githubInput = document.getElementById('github');
            const portfolioInput = document.getElementById('portfolio');
            
            // Twitter validation (remove @ if present)
            if (twitterInput.value.startsWith('@')) {
                twitterInput.value = twitterInput.value.substring(1);
            }
            
            // Instagram validation (remove @ if present)
            if (instagramInput.value.startsWith('@')) {
                instagramInput.value = instagramInput.value.substring(1);
            }
            
            // Portfolio URL validation
            if (portfolioInput.value && !portfolioInput.value.startsWith('http')) {
                portfolioInput.value = 'https://' + portfolioInput.value;
            }
        });
        
        // Password validation
        document.getElementById('securityForm').addEventListener('submit', function(e) {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (newPassword !== confirmPassword) {
                e.preventDefault();
                showAlert('Passwords do not match!', 'danger');
            }
            
            if (newPassword.length < 8) {
                e.preventDefault();
                showAlert('Password must be at least 8 characters long!', 'warning');
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
    function positionSocialIcons() {
        document.querySelectorAll('.social-icon').forEach(icon => {
            const input = icon.nextElementSibling;
            if (input && input.classList.contains('form-control')) {
                const group = icon.closest('.social-input-group');
                const label = group.querySelector('.form-label');
                const labelHeight = label.offsetHeight + parseInt(window.getComputedStyle(label).marginBottom);
                const inputHeight = input.offsetHeight;
                icon.style.top = `${labelHeight + (inputHeight / 2)}px`;
                icon.style.transform = 'translateY(-50%)';
            }
        });
    }

    // Initial positioning
    positionSocialIcons();
    
    // Reposition on window resize
    window.addEventListener('resize', positionSocialIcons);
});

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

// Enhanced Delete Account Modal Logic
document.addEventListener('DOMContentLoaded', function() {
    const deleteModal = document.getElementById('deleteAccountModal');
    const deleteButton = document.getElementById('deleteAccountButton');
    const countdownElement = document.getElementById('countdown');
    const passwordInput = document.getElementById('delete_password');
    const confirmCheckbox = document.getElementById('confirmDeletion');
    const deleteForm = document.getElementById('deleteAccountForm');
    
    let countdownInterval;
    let countdown = 10;
    
    // Reset modal when it's shown
    deleteModal.addEventListener('show.bs.modal', function() {
        resetModal();
        startCountdown();
    });
    
    // Clean up when modal is hidden
    deleteModal.addEventListener('hidden.bs.modal', function() {
        clearInterval(countdownInterval);
        resetModal();
    });
    
    // Check form validity when inputs change
    [passwordInput, confirmCheckbox].forEach(input => {
        input.addEventListener('change', checkFormValidity);
        input.addEventListener('input', checkFormValidity);
    });
    
    // Form submission with additional validation
    deleteForm.addEventListener('submit', function(e) {
        if (!validateForm()) {
            e.preventDefault();
            return false;
        }
        
        // Final confirmation
        if (!confirm('Are you absolutely sure you want to delete your account? This action cannot be undone!')) {
            e.preventDefault();
            return false;
        }
        
        // Show loading state
        deleteButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Deleting...';
        deleteButton.disabled = true;
    });
    
    function resetModal() {
        countdown = 10;
        deleteButton.disabled = true;
        countdownElement.textContent = countdown;
        passwordInput.value = '';
        confirmCheckbox.checked = false;
        passwordInput.classList.remove('is-invalid', 'is-valid');
        deleteButton.innerHTML = '<i class="fas fa-trash-alt me-2"></i><span id="countdown">10</span>';
    }
    
    function startCountdown() {
        countdownInterval = setInterval(function() {
            countdown--;
            countdownElement.textContent = countdown;
            
            if (countdown <= 0) {
                clearInterval(countdownInterval);
                countdownElement.textContent = 'Delete Account';
                checkFormValidity();
            } else {
                countdownElement.textContent = countdown + (countdown === 1 ? ' second' : ' seconds');
            }
        }, 1000);
    }
    
    function checkFormValidity() {
        const isPasswordValid = passwordInput.value.length > 0;
        const isCheckboxChecked = confirmCheckbox.checked;
        const isCountdownFinished = countdown <= 0;
        
        // Update password field validation styling
        if (passwordInput.value.length > 0) {
            passwordInput.classList.remove('is-invalid');
            passwordInput.classList.add('is-valid');
        } else if (passwordInput.value.length === 0 && passwordInput === document.activeElement) {
            passwordInput.classList.add('is-invalid');
            passwordInput.classList.remove('is-valid');
        }
        
        // Enable button only if all conditions are met
        deleteButton.disabled = !(isPasswordValid && isCheckboxChecked && isCountdownFinished);
    }
    
    function validateForm() {
        let isValid = true;
        
        // Validate password
        if (passwordInput.value.trim() === '') {
            passwordInput.classList.add('is-invalid');
            isValid = false;
        }
        
        // Validate checkbox
        if (!confirmCheckbox.checked) {
            isValid = false;
        }
        
        return isValid;
    }
});
    </script>
</body>
</html>