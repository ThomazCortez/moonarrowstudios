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
    
    // Update user description
    $stmt = $conn->prepare("UPDATE users SET description = ? WHERE user_id = ?");
    $stmt->bind_param("si", $description, $user_id);
    $stmt->execute();
    
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
    <title>MoonArrow Studios - Settings</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css">
    <style>
        .profile-picture-preview,
        .banner-preview {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            margin-top: 10px;
        }
        
        .profile-picture-preview {
            max-height: 150px; /* Set max height for profile picture */
            width: 150px; /* Set width for profile picture */
            object-fit: cover; /* Ensure the image covers the area */
        }
        
        .banner-preview {
            max-height: 200px; /* Set max height for banner */
            width: 669px; /* Set width for banner */
            object-fit: cover; /* Ensure the image covers the area */
        }
        
        .preview-container {
            position: relative;
            margin-bottom: 20px;
        }

        .modal-content {
            background-color: #2b3035;
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
                <form method="POST" enctype="multipart/form-data" id="profileForm">
                    <div class="mb-3">
                        <label for="profile_picture" class="form-label">Profile Picture</label>
                        <input type="file" class="form-control" id="profile_picture" accept="image/*">
                        <input type="hidden" name="profile_picture_data" id="profile_picture_data">
                        <?php if ($user['profile_picture']): ?>
                            <div class="preview-container">
                                <img src="<?= $user['profile_picture'] ?>" alt="Current profile picture" class="profile-picture-preview">
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="banner" class="form-label">Profile Banner</label>
                        <input type="file" class="form-control" id="banner" accept="image/*">
                        <input type="hidden" name="banner_data" id="banner_data">
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

    <!-- Cropping Modal -->
    <div class="modal fade" id="cropModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Crop Image</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="cropper-container">
                        <img id="cropImage" src="" alt="Image to crop">
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary" id="cropButton">Crop</button>
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
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
            const preview = document.querySelector(previewClass);
            
            if (preview) {
                preview.src = croppedImage;
            } else {
                const newPreview = document.createElement('img');
                newPreview.src = croppedImage;
                newPreview.className = previewClass.substring(1);
                currentInput.parentElement.appendChild(newPreview);
            }
            
            // Update hidden input
            const hiddenInput = document.getElementById(currentInput.id + '_data');
            hiddenInput.value = croppedImage;
            
            modal.hide();
        });
    </script>
</body>
</html>