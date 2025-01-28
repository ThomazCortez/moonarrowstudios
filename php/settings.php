<?php
// settings.php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: sign_in/sign_in_html.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $description = $_POST['description'];
    
    // Handle profile picture upload
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/profile_pictures/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION));
        $new_filename = 'profile_' . $user_id . '.' . $file_extension;
        $profile_picture_path = $upload_dir . $new_filename;
        
        move_uploaded_file($_FILES['profile_picture']['tmp_name'], $profile_picture_path);
    }
    
    // Handle banner upload
    if (isset($_FILES['banner']) && $_FILES['banner']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/banners/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES['banner']['name'], PATHINFO_EXTENSION));
        $new_filename = 'banner_' . $user_id . '.' . $file_extension;
        $banner_path = $upload_dir . $new_filename;
        
        move_uploaded_file($_FILES['banner']['tmp_name'], $banner_path);
    }
    
    // Update user profile in database
    $stmt = $conn->prepare("UPDATE users SET description = ? WHERE user_id = ?");
    $stmt->bind_param("si", $description, $user_id);
    $stmt->execute();
    
    if (isset($profile_picture_path)) {
        $stmt = $conn->prepare("UPDATE users SET profile_picture = ? WHERE user_id = ?");
        $stmt->bind_param("si", $profile_picture_path, $user_id);
        $stmt->execute();
    }
    
    if (isset($banner_path)) {
        $stmt = $conn->prepare("UPDATE users SET banner = ? WHERE user_id = ?");
        $stmt->bind_param("si", $banner_path, $user_id);
        $stmt->execute();
    }
    
    $_SESSION['success'] = "Profile updated successfully!";
    header("Location: settings.php");
    exit;
}

// Fetch current user data
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <?php require 'header.php'; ?>
    <title>Settings - Profile</title>
    <style>
        .profile-picture-preview,
        .banner-preview {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            margin-top: 10px;
        }
        
        .profile-picture-preview {
            max-height: 200px;
        }
        
        .banner-preview {
            max-height: 300px;
        }
        
        .preview-container {
            position: relative;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= $_SESSION['success'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h2>Profile Settings</h2>
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="profile_picture" class="form-label">Profile Picture</label>
                        <input type="file" class="form-control" id="profile_picture" name="profile_picture" accept="image/*">
                        <?php if ($user['profile_picture']): ?>
                            <div class="preview-container">
                                <img src="<?= $user['profile_picture'] ?>" alt="Current profile picture" class="profile-picture-preview">
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="banner" class="form-label">Profile Banner</label>
                        <input type="file" class="form-control" id="banner" name="banner" accept="image/*">
                        <?php if ($user['banner']): ?>
                            <div class="preview-container">
                                <img src="<?= $user['banner'] ?>" alt="Current banner" class="banner-preview">
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">About Me</label>
                        <textarea class="form-control" id="description" name="description" rows="4"><?= htmlspecialchars($user['description'] ?? '') ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Preview uploaded images
        function previewImage(input, previewClass) {
            const file = input.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.querySelector(previewClass);
                    if (preview) {
                        preview.src = e.target.result;
                    } else {
                        const newPreview = document.createElement('img');
                        newPreview.src = e.target.result;
                        newPreview.className = previewClass.substring(1);
                        input.parentElement.appendChild(newPreview);
                    }
                }
                reader.readAsDataURL(file);
            }
        }

        document.getElementById('profile_picture').addEventListener('change', function() {
            previewImage(this, '.profile-picture-preview');
        });

        document.getElementById('banner').addEventListener('change', function() {
            previewImage(this, '.banner-preview');
        });
    </script>
</body>
</html>
