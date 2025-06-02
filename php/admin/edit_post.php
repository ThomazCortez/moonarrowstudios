<?php
// Start session
session_start();

// Set base URL
$baseUrl = '/moonarrowstudios/';

// Check if user is logged in
require_once '../db_connect.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: " . $baseUrl . "../sign_in/sign_in_html.php");
    exit();
}

// Initialize post ID
$post_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Verify user role
$user_id = $_SESSION['user_id'];
$is_admin = false;
$current_user_id = null;

$query = "SELECT role, user_id FROM users WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($user_role, $db_user_id);
$stmt->fetch();
$stmt->close();

// Set admin flag
if ($user_role === 'admin') {
    $is_admin = true;
}

// For non-admins: Must have post ID and must own the post
if (!$is_admin) {
    if ($post_id <= 0) {
        header("Location: " . $baseUrl);
        exit();
    }
    
    // Verify post ownership
    $checkQuery = "SELECT user_id FROM posts WHERE id = ?";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bind_param("i", $post_id);
    $checkStmt->execute();
    $checkStmt->bind_result($post_owner_id);
    $checkStmt->fetch();
    $checkStmt->close();
    
    if ($post_owner_id !== $user_id) {
        header("Location: " . $baseUrl);
        exit();
    }
}

// Initialize variables
$post_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$title = '';
$content = '';
$category_id = 0;
$status = '';
$error_message = '';
$success_message = '';

// Get categories for dropdown
$categoriesQuery = "SELECT id, name FROM categories ORDER BY name";
$categoriesResult = $conn->query($categoriesQuery);
$categories = [];
while ($row = $categoriesResult->fetch_assoc()) {
    $categories[] = $row;
}

// Initialize variables for attachments and hashtags
$images = [];
$videos = [];
$hashtags = '';

// Check if post exists and belongs to user or admin
if ($post_id > 0) {
    $query = "SELECT p.*, u.username 
              FROM posts p 
              JOIN users u ON p.user_id = u.user_id 
              WHERE p.id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $post_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // Post not found
        header("Location: manage_posts.php");
        exit();
    }
    
    $post = $result->fetch_assoc();
    $stmt->close();
    
    // Populate variables with post data
    $title = $post['title'];
    $content = $post['content'];
    $category_id = $post['category_id'];
    $status = $post['status'];
    $author = $post['username'];
    $created_at = $post['created_at'];
    $current_user_id = $post['user_id'];
    
    // Fetch existing attachments and hashtags
    $images = json_decode($post['images'], true) ?? [];
    $videos = json_decode($post['videos'], true) ?? [];
    $hashtags = $post['hashtags'];
} else {
    // No post ID provided
    header("Location: manage_posts.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $category_id = (int)$_POST['category_id'];
    $status = $_POST['status'];
    $hashtags = trim($_POST['hashtags']);
    
    // Handle image uploads
    $new_images = [];
    if (isset($_FILES['images']['name'][0]) && $_FILES['images']['error'][0] === UPLOAD_ERR_OK) {
        // Fixed path - going back one directory from admin/ to php/, then to uploads/images/
        $upload_dir = '../uploads/images/';
        $full_upload_path = realpath(__DIR__ . '/../uploads/images/');
        
        // Create directory if it doesn't exist
        if (!is_dir($full_upload_path)) {
            mkdir($full_upload_path, 0777, true);
        }
        
        foreach ($_FILES['images']['name'] as $key => $name) {
            if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                // Generate unique filename to prevent conflicts
                $file_extension = pathinfo($name, PATHINFO_EXTENSION);
                $unique_name = uniqid() . '_' . time() . '.' . $file_extension;
                $image_path = $upload_dir . $unique_name;
                $full_image_path = $full_upload_path . '/' . $unique_name;
                
                if (move_uploaded_file($_FILES['images']['tmp_name'][$key], $full_image_path)) {
                    // Store relative path for database (without leading ../)
                    $new_images[] = 'php/uploads/images/' . $unique_name;
                }
            }
        }
    }
    
    // Handle video uploads
    $new_videos = [];
    if (isset($_FILES['videos']['name'][0]) && $_FILES['videos']['error'][0] === UPLOAD_ERR_OK) {
        // Fixed path - going back one directory from admin/ to php/, then to uploads/videos/
        $upload_dir = '../uploads/videos/';
        $full_upload_path = realpath(__DIR__ . '/../uploads/videos/');
        
        // Create directory if it doesn't exist
        if (!is_dir($full_upload_path)) {
            mkdir($full_upload_path, 0777, true);
        }
        
        foreach ($_FILES['videos']['name'] as $key => $name) {
            if ($_FILES['videos']['error'][$key] === UPLOAD_ERR_OK) {
                // Generate unique filename to prevent conflicts
                $file_extension = pathinfo($name, PATHINFO_EXTENSION);
                $unique_name = uniqid() . '_' . time() . '.' . $file_extension;
                $video_path = $upload_dir . $unique_name;
                $full_video_path = $full_upload_path . '/' . $unique_name;
                
                if (move_uploaded_file($_FILES['videos']['tmp_name'][$key], $full_video_path)) {
                    // Store relative path for database (without leading ../)
                    $new_videos[] = 'php/uploads/videos/' . $unique_name;
                }
            }
        }
    }
    
    // Merge existing and new attachments
    $images = array_merge($images, $new_images);
    $videos = array_merge($videos, $new_videos);
    
    // Handle file deletions
    if (isset($_POST['delete_images']) && is_array($_POST['delete_images'])) {
        foreach ($_POST['delete_images'] as $index) {
            if (isset($images[$index])) {
                // Delete the physical file
                $file_path = '../../' . $images[$index]; // Go back to root, then to the file
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
                
                // Remove from the array
                unset($images[$index]);
            }
        }
        // Re-index array
        $images = array_values($images);
    }

    if (isset($_POST['delete_videos']) && is_array($_POST['delete_videos'])) {
        foreach ($_POST['delete_videos'] as $index) {
            if (isset($videos[$index])) {
                // Delete the physical file
                $file_path = '../../' . $videos[$index]; // Go back to root, then to the file
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
                
                // Remove from the array
                unset($videos[$index]);
            }
        }
        // Re-index array
        $videos = array_values($videos);
    }

    // Validate input
    if (empty($title)) {
        $error_message = "Title is required.";
    } elseif (empty($content)) {
        $error_message = "Content is required.";
    } else {
        // Update post in database
        $updateQuery = "UPDATE posts SET 
                        title = ?, 
                        content = ?, 
                        category_id = ?, 
                        status = ?,
                        images = ?,
                        videos = ?,
                        hashtags = ?,
                        updated_at = NOW()
                        WHERE id = ?";
        
        // Store the JSON-encoded strings in variables
        $images_json = json_encode($images);
        $videos_json = json_encode($videos);
        
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("ssissssi", $title, $content, $category_id, $status, $images_json, $videos_json, $hashtags, $post_id);
        
        if ($stmt->execute()) {
            // Set success message for JavaScript
            $success_message = "Post successfully updated!";
        } else {
            // Set error message for JavaScript
            $error_message = "Error updating post: " . $conn->error;
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
    <title>MoonArrow Studios - Edit Post</title>
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="icon" href="<?php echo $baseUrl; ?>media/moon.ico" type="image/x-icon" />
    <!-- Quill Editor -->
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
    
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

        .post-meta {
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

        .btn-sm {
            padding: 4px 8px;
            font-size: 12px;
        }

        /* Quill Editor Customization */
        .ql-container {
            border-radius: 0 0 6px 6px !important;
            background-color: var(--color-input-bg) !important;
            border-color: var(--color-border-default) !important;
            font-size: 14px;
        }

        .ql-toolbar {
            border-radius: 6px 6px 0 0 !important;
            background-color: var(--color-header-bg) !important;
            border-color: var(--color-border-default) !important;
        }

        .ql-editor {
            min-height: 300px;
            color: var(--color-fg-default) !important;
            font-size: 14px !important;
            line-height: 1.5 !important;
        }

        .ql-editor.ql-blank::before {
            color: var(--color-fg-muted);
            font-style: normal;
        }

        /* File Upload Areas */
        .file-upload-area {
            border: 2px dashed var(--color-border-default);
            border-radius: 6px;
            padding: 20px;
            text-align: center;
            background-color: var(--color-canvas-subtle);
            transition: border-color 0.2s ease;
        }

        .file-upload-area:hover {
            border-color: var(--color-accent-fg);
        }

        .file-upload-area input[type="file"] {
            margin-bottom: 8px;
        }

        .file-preview {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 12px;
        }

        .file-item {
            position: relative;
            border: 1px solid var(--color-border-default);
            border-radius: 6px;
            padding: 8px;
            background-color: var(--color-canvas-subtle);
            max-width: 150px;
        }

        .file-item img, .file-item video {
            width: 100%;
            height: 80px;
            object-fit: cover;
            border-radius: 4px;
            margin-bottom: 4px;
        }

        .file-item-name {
            font-size: 12px;
            color: var(--color-fg-muted);
            word-break: break-all;
        }

        .file-remove {
            position: absolute;
            top: 4px;
            right: 4px;
            background: rgba(218, 54, 51, 0.9);
            color: white;
            border: none;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 12px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .hashtag-input {
            position: relative;
        }

        .hashtag-help {
            font-size: 12px;
            color: var(--color-fg-muted);
            margin-top: 4px;
        }

        .form-actions {
            display: flex;
            gap: 12px;
            padding-top: 20px;
            border-top: 1px solid var(--color-border-muted);
            margin-top: 24px;
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

        .two-column {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
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
            
            .post-meta {
                flex-direction: column;
                gap: 8px;
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

    </style>
</head>

<body>
    <?php include('../header.php'); ?>

     <!-- Alert Container -->
    <div id="alertContainer" class="alert-container"></div>

    <div class="editor-container">
        <?php if ($is_admin): ?>
        <!-- Header Section -->
        <div class="editor-header">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <h1 class="editor-title">Edit Post</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="manage_posts.php">Manage Posts</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Edit Post</li>
                        </ol>
                    </nav>
                </div>
                <a href="<?php echo $baseUrl; ?>php/view_post.php?id=<?php echo $post_id; ?>" class="btn btn-outline-secondary" target="_blank">
                    <i class="bi bi-eye me-1"></i>View
                </a>
            </div>
            
            <div class="post-meta">
                <span><i class="bi bi-person me-1"></i>Author: <?php echo htmlspecialchars($author); ?></span>
                <span><i class="bi bi-calendar me-1"></i>Created: <?php echo date('M d, Y', strtotime($created_at)); ?></span>
                <span><i class="bi bi-hash me-1"></i>ID: <?php echo $post_id; ?></span>
            </div>
        </div>
        <?php endif; ?>

        <!-- Editor Form -->
        <form method="POST" enctype="multipart/form-data" class="editor-form">
            <!-- Title Section -->
            <div class="form-section">
                <div class="section-title">
                    <i class="bi bi-type"></i>
                    Post Title
                </div>
                <input type="text" 
                       class="form-control" 
                       name="title" 
                       value="<?php echo htmlspecialchars($title); ?>" 
                       placeholder="Enter your post title..."
                       required>
            </div>

            <!-- Category and Status -->
            <div class="form-section">
                <div class="section-title">
                    <i class="bi bi-gear"></i>
                    Post Settings
                </div>
                <div class="two-column">
                    <div>
                        <label for="category" class="form-label">Category</label>
                        <select class="form-select" name="category_id" id="category" required>
                            <option value="">Select a category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" 
                                        <?php echo ($category['id'] == $category_id) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" name="status" id="status" required>
                            <option value="hidden" <?php echo ($status === 'hidden') ? 'selected' : ''; ?>>Hidden</option>
                            <option value="published" <?php echo ($status === 'published') ? 'selected' : ''; ?>>Published</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Content Section -->
            <div class="form-section">
                <div class="section-title">
                    <i class="bi bi-file-text"></i>
                    Content
                </div>
                <div id="editor"></div>
                <textarea name="content" id="content" style="display: none;"><?php echo htmlspecialchars($content); ?></textarea>
            </div>

            <!-- Hashtags Section -->
            <div class="form-section">
                <div class="section-title">
                    <i class="bi bi-hash"></i>
                    Hashtags
                </div>
                <div class="hashtag-input">
                    <input type="text" 
                           class="form-control" 
                           name="hashtags" 
                           value="<?php echo htmlspecialchars($hashtags); ?>" 
                           placeholder="Enter hashtags separated by commas...">
                    <div class="hashtag-help">Separate multiple hashtags with commas (e.g., design, web, tutorial)</div>
                </div>
            </div>

            <!-- Images Section -->
            <div class="form-section">
                <div class="section-title">
                    <i class="bi bi-image"></i>
                    Images
                </div>
                    <input type="file" name="images[]" accept="image/*" multiple class="form-control">

                <?php if (!empty($images)): ?>
                    <div class="file-preview">
                        <?php foreach ($images as $index => $image): ?>
                            <div class="file-item">
                                <img src="<?php echo $baseUrl; ?><?php echo htmlspecialchars($image); ?>" alt="Image <?php echo $index + 1; ?>">
                                <div class="file-item-name"><?php echo basename($image); ?></div>
                                <button type="button" class="file-remove" onclick="markForDeletion('images', <?php echo $index; ?>, this)">
                                    <i class="bi bi-x"></i>
                                </button>
                                <input type="hidden" name="delete_images[]" value="<?php echo $index; ?>" disabled>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Videos Section -->
            <div class="form-section">
                <div class="section-title">
                    <i class="bi bi-camera-video"></i>
                    Videos
                </div>
                    <input type="file" name="videos[]" accept="video/*" multiple class="form-control">
                
                <?php if (!empty($videos)): ?>
                    <div class="file-preview">
                        <?php foreach ($videos as $index => $video): ?>
                            <div class="file-item">
                                <video src="<?php echo $baseUrl; ?><?php echo htmlspecialchars($video); ?>" controls></video>
                                <div class="file-item-name"><?php echo basename($video); ?></div>
                                <button type="button" class="file-remove" onclick="markForDeletion('videos', <?php echo $index; ?>, this)">
                                    <i class="bi bi-x"></i>
                                </button>
                                <input type="hidden" name="delete_videos[]" value="<?php echo $index; ?>" disabled>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Form Actions -->
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i>Update Post
                </button>
            </div>
        </form>
    </div>

    <script>
        // Initialize Quill editor
        var quill = new Quill('#editor', {
            placeholder: 'Write your post content here...',
            theme: 'snow',
            modules: {
                toolbar: [
                    [{ header: [3, 4, false] }],
                    ['bold', 'italic', 'underline'],
                    ['blockquote', 'code-block'],
                    [{ list: 'ordered' }, { list: 'bullet' }],
                    ['link', 'clean']
                ]
            }
        });

        // Set initial content
        quill.root.innerHTML = document.getElementById('content').value;

        // Update hidden textarea when form is submitted
        document.querySelector('form').addEventListener('submit', function() {
            document.getElementById('content').value = quill.root.innerHTML;
        });

        // File deletion functionality
        function markForDeletion(type, index, button) {
            var fileItem = button.parentElement;
            var hiddenInput = fileItem.querySelector('input[type="hidden"]');
            
            if (fileItem.style.opacity === '0.5') {
                // Restore file
                fileItem.style.opacity = '1';
                button.innerHTML = '<i class="bi bi-x"></i>';
                button.style.backgroundColor = 'rgba(218, 54, 51, 0.9)';
                hiddenInput.disabled = true;
            } else {
                // Mark for deletion
                fileItem.style.opacity = '0.5';
                button.innerHTML = '<i class="bi bi-arrow-clockwise"></i>';
                button.style.backgroundColor = 'rgba(40, 167, 69, 0.9)';
                hiddenInput.disabled = false;
            }
        }

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
<?php if (!empty($success_message)): ?>
    showAlert(<?= json_encode($success_message) ?>, "success");
<?php endif; ?>

<?php if (!empty($error_message)): ?>
    showAlert(<?= json_encode($error_message) ?>, "danger");
<?php endif; ?>
    </script>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>