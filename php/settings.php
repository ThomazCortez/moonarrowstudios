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
    
    // Handle Security Updates
    elseif (isset($_POST['action']) && $_POST['action'] === 'update_password') {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Verify current password
        $stmt = $conn->prepare("SELECT password FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $user_data = $stmt->get_result()->fetch_assoc();
        
        if (password_verify($current_password, $user_data['password'])) {
            if ($new_password === $confirm_password) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                $stmt->bind_param("si", $hashed_password, $user_id);
                $stmt->execute();
                
                $success_message = "Password updated successfully!";
            } else {
                $error_message = "New passwords do not match!";
            }
        } else {
            $error_message = "Current password is incorrect!";
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
            background-color: #212529;
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
            padding: 0 15px;
            background-color: rgba(0,0,0,0.05);
        }

        .nav-tabs .nav-link {
            border: none;
            color: #adb5bd;
            font-weight: 500;
            padding: 15px 20px;
            border-bottom: 3px solid transparent;
            transition: all 0.2s ease;
        }

        .nav-tabs .nav-link:hover {
            border-color: rgba(255,255,255,0.2);
            background-color: rgba(255,255,255,0.05);
            color: #fff;
        }

        .nav-tabs .nav-link.active {
            background-color: transparent;
            border-bottom: 3px solid #0d6efd;
            color: #fff;
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
    </style>
</head>
<body>
    <div class="container mt-4 mb-5">
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show animate__animated animate__fadeIn" role="alert">
                <?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show animate__animated animate__fadeIn" role="alert">
                <?php echo $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

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
                            <h4 class="mb-4"><i class="fas fa-lock me-2"></i> Change Password</h4>
                            <form method="POST" id="securityForm">
                                <input type="hidden" name="action" value="update_password">
                                
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
                                        <i class="fas fa-key me-2"></i> Update Password
                                    </button>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Notifications Tab -->
                        <div class="tab-pane fade <?php echo ($active_tab == 'notifications') ? 'show active' : ''; ?> animated-tab animate__animated animate__fadeIn" id="notifications" role="tabpanel" aria-labelledby="notifications-tab">
                            <h4 class="mb-4"><i class="fas fa-bell me-2"></i> Notification Preferences</h4>
                            <form method="POST" id="notificationsForm">
                                <input type="hidden" name="action" value="update_notifications">
                                
                                <div class="card mb-3 bg-dark border-0">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <div>
                                                <h5 class="mb-1"><i class="fas fa-envelope me-2"></i> Email Updates</h5>
                                                <p class="text-muted mb-0">Receive news, updates, and promotional emails</p>
                                            </div>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="email_updates" name="email_updates" <?php echo (isset($user['email_updates']) && $user['email_updates']) ? 'checked' : ''; ?>>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="card mb-3 bg-dark border-0">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <div>
                                                <h5 class="mb-1"><i class="fas fa-comment me-2"></i> Comment Notifications</h5>
                                                <p class="text-muted mb-0">Get notified when someone comments on your posts</p>
                                            </div>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="comment_notifications" name="comment_notifications" <?php echo (isset($user['comment_notifications']) && $user['comment_notifications']) ? 'checked' : ''; ?>>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="card mb-4 bg-dark border-0">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <div>
                                                <h5 class="mb-1"><i class="fas fa-paper-plane me-2"></i> Message Notifications</h5>
                                                <p class="text-muted mb-0">Get notified when you receive new messages</p>
                                            </div>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="message_notifications" name="message_notifications" <?php echo (isset($user['message_notifications']) && $user['message_notifications']) ? 'checked' : ''; ?>>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="text-end">
                                    <button type="submit" class="btn btn-primary btn-save">
                                        <i class="fas fa-save me-2"></i> Save Notification Preferences
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
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
                alert('Passwords do not match!');
            }
            
            if (newPassword.length < 8) {
                e.preventDefault();
                alert('Password must be at least 8 characters long!');
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
    </script>
</body>
</html>