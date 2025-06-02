<?php
// Start session
session_start();

$type = isset($_GET['type']) ? $_GET['type'] : '';

// Set base URL
$baseUrl = '/moonarrowstudios/';

// Check if user is logged in
require_once '../db_connect.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: " . $baseUrl . "../sign_in/sign_in_html.php");
    exit();
}

// Initialize comment ID
$comment_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

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

// Initialize variables
$content = '';
$status = '';
$error_message = '';
$success_message = '';
$comment_type = ''; // To track whether the comment is from `comments` or `comments_asset`

// Check if comment exists
if ($comment_id > 0) {
    $comment = null;
    $comment_type = '';
    
    // Get type parameter from URL
    $type = isset($_GET['type']) ? $_GET['type'] : '';

    // Check comments table if type is post or not specified
    if ($type === 'post' || $type === '') {
        $query = "SELECT c.*, u.username, p.title as post_title 
                FROM comments c 
                JOIN users u ON c.user_id = u.user_id 
                JOIN posts p ON c.post_id = p.id 
                WHERE c.id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $comment_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $comment = $result->fetch_assoc();
            $comment_type = 'post';
        }
        $stmt->close();
    }
    
    // Check comments_asset table if type is asset or not found in posts
    if ($type === 'asset' || ($type === '' && !$comment)) {
        $query = "SELECT c.*, u.username, a.title as asset_title 
                FROM comments_asset c 
                JOIN users u ON c.user_id = u.user_id 
                JOIN assets a ON c.asset_id = a.id 
                WHERE c.id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $comment_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $comment = $result->fetch_assoc();
            $comment_type = 'asset';
        }
        $stmt->close();
    }
    
    // If comment not found in either table
    if (!$comment) {
        if ($is_admin) {
            header("Location: manage_comments.php");
        } else {
            header("Location: " . $baseUrl);
        }
        exit();
    }
    
    // For non-admins: Must own the comment
    if (!$is_admin && $comment['user_id'] !== $user_id) {
        header("Location: " . $baseUrl);
        exit();
    }
    
    // Populate variables with comment data
    $content = $comment['content'];
    $status = $comment['status'];
    $author = $comment['username'];
    $post_title = $comment['post_title'] ?? $comment['asset_title'] ?? 'Unknown'; // Handle both post and asset titles
    $created_at = $comment['created_at'];
    $upvotes = $comment['upvotes'];
    $downvotes = $comment['downvotes'];
    $reported_count = $comment['reported_count'];
    $current_user_id = $comment['user_id'];
} else {
    // No comment ID provided
    if ($is_admin) {
        header("Location: manage_comments.php");
    } else {
        header("Location: " . $baseUrl);
    }
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $content = trim($_POST['content']);
    $status = $_POST['status'];
    
    // Validate input
    if (empty($content)) {
        $error_message = "Content is required.";
    } else {
        // Update comment in the appropriate table
        if ($comment_type === 'post') {
            $updateQuery = "UPDATE comments SET 
                            content = ?, 
                            status = ?, 
                            updated_at = NOW()
                            WHERE id = ?";
        } else {
            $updateQuery = "UPDATE comments_asset SET 
                            content = ?, 
                            status = ?, 
                            updated_at = NOW()
                            WHERE id = ?";
        }
        
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("ssi", $content, $status, $comment_id);
        
        if ($stmt->execute()) {
        // Add edited_at timestamp
        $updateTimestamp = $conn->prepare(
            "UPDATE " . ($comment_type === 'post' ? 'comments' : 'comments_asset') . " 
            SET edited_at = NOW() 
            WHERE id = ?"
        );
        $updateTimestamp->bind_param("i", $comment_id);
        $updateTimestamp->execute();
        
        // Redirect back to post with anchor
        if ($comment_type === 'post') {
            $anchor = $comment['parent_id'] ? 'reply-'.$comment_id : 'comment-'.$comment_id;
            header("Location: ".$baseUrl."php/view_post.php?id=".$comment['post_id']."#".$anchor);
        } else {
            $anchor = $comment['parent_id'] ? 'reply-'.$comment_id : 'comment-'.$comment_id;
            header("Location: ".$baseUrl."php/view_asset.php?id=".$comment['asset_id']."#".$anchor);
        }
        exit;
    }
}
}
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MoonArrow Studios - Edit Comment</title>
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

        .comment-meta {
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
            min-height: 200px;
            color: var(--color-fg-default) !important;
            font-size: 14px !important;
            line-height: 1.5 !important;
        }

        .ql-editor.ql-blank::before {
            color: var(--color-fg-muted);
            font-style: normal;
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
            
            .comment-meta {
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
        <!-- Header Section (Admin Only) -->
        <div class="editor-header">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <h1 class="editor-title">Edit Comment</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="manage_comments.php">Manage Comments</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Edit Comment</li>
                        </ol>
                    </nav>
                </div>
            </div>
            
            <div class="comment-meta">
                <span><i class="bi bi-person me-1"></i>Author: <?php echo htmlspecialchars($author); ?></span>
                <span><i class="bi bi-calendar me-1"></i>Created: <?php echo date('M d, Y', strtotime($created_at)); ?></span>
                <span><i class="bi bi-hash me-1"></i>ID: <?php echo $comment_id; ?></span>
            </div>
        </div>
        <?php endif; ?>

        <!-- Editor Form -->
        <form method="POST" action="" class="editor-form">
            <!-- Post/Asset Title Section -->
            <div class="form-section">
                <div class="section-title">
                    <i class="bi bi-file-text"></i>
                    <?php echo $comment_type === 'post' ? 'Post Title' : 'Asset Title'; ?>
                </div>
                <input type="text" 
                       class="form-control" 
                       value="<?php echo htmlspecialchars($post_title); ?>" 
                       readonly>
            </div>

            <?php if ($is_admin): ?>
            <!-- Status Section (Admin Only) -->
            <div class="form-section">
                <div class="section-title">
                    <i class="bi bi-gear"></i>
                    Comment Status
                </div>
                <select class="form-select" name="status" required>
                    <option value="published" <?php echo $status === 'published' ? 'selected' : ''; ?>>Published</option>
                    <option value="hidden" <?php echo $status === 'hidden' ? 'selected' : ''; ?>>Hidden</option>
                </select>
            </div>
            <?php else: ?>
            <!-- Hidden status field for regular users -->
            <input type="hidden" name="status" value="<?php echo htmlspecialchars($status); ?>">
            <?php endif; ?>

            <!-- Content Section -->
            <div class="form-section">
                <div class="section-title">
                    <i class="bi bi-chat-text"></i>
                    Comment Content
                </div>
                <div id="editor"></div>
                <textarea name="content" id="content" style="display: none;"><?php echo htmlspecialchars($content); ?></textarea>
            </div>

            <!-- Form Actions -->
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i>Update Comment
                </button>
                <?php if ($is_admin): ?>
                <button type="button" class="btn btn-danger ms-2" onclick="confirmDelete()">
                    <i class="bi bi-trash me-1"></i>Delete Comment
                </button>
                <?php endif; ?>
            </div>
        </form>

        <!-- Delete Form (hidden, Admin Only) -->
        <?php if ($is_admin): ?>
        <form id="deleteForm" method="POST" action="delete_comment.php" style="display: none;">
            <input type="hidden" name="comment_id" value="<?php echo $comment_id; ?>">
            <input type="hidden" name="comment_type" value="<?php echo $comment_type; ?>">
        </form>
        <?php endif; ?>

        <script>
        // Initialize Quill editor
        var quill = new Quill('#editor', {
            placeholder: 'Write your comment here...',
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

        <?php if ($is_admin): ?>
        // Delete confirmation function (Admin Only)
        function confirmDelete() {
            if (confirm('Are you sure you want to delete this comment? This action cannot be undone.')) {
                let form = document.getElementById('deleteForm');
                if (!form) {
                    form = document.createElement('form');
                    form.id = 'deleteForm';
                    form.method = 'POST';
                    form.action = 'delete_comment.php';
                    form.style.display = 'none';
                    
                    const commentIdInput = document.createElement('input');
                    commentIdInput.type = 'hidden';
                    commentIdInput.name = 'comment_id';
                    commentIdInput.value = <?php echo $comment_id; ?>;
                    
                    const commentTypeInput = document.createElement('input');
                    commentTypeInput.type = 'hidden';
                    commentTypeInput.name = 'comment_type';
                    commentTypeInput.value = '<?php echo $comment_type; ?>';
                    
                    form.appendChild(commentIdInput);
                    form.appendChild(commentTypeInput);
                    document.body.appendChild(form);
                }
                form.submit();
            }
        }
        <?php endif; ?>
    </script>
</body>
</html>