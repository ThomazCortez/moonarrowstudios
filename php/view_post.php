<?php
session_start();

require_once 'notification_functions.php';

// Database connection
require 'db_connect.php';

// Fetch the post by ID
$post_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$stmt = $conn->prepare("SELECT posts.*, categories.name AS category_name, users.username, users.profile_picture 
                        FROM posts 
                        JOIN categories ON posts.category_id = categories.id 
                        JOIN users ON posts.user_id = users.user_id 
                        WHERE posts.id = ? AND posts.status = 'published'");
$stmt->bind_param("i", $post_id);
$stmt->execute();
$result = $stmt->get_result();
$post = $result->fetch_assoc();

if (!$post) {
    echo "<h1>Post not found or not published</h1>";
    exit;
}

// Increment the view count
$update_views_stmt = $conn->prepare("UPDATE posts SET views = views + 1 WHERE id = ?");
$update_views_stmt->bind_param("i", $post_id);
$update_views_stmt->execute();

// Fetch comments and their replies based on the selected filter
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'highest_score';

$order_by = 'c1.created_at DESC'; // Default order
switch ($filter) {
    case 'newest':
        $order_by = 'c1.created_at DESC';
        break;
    case 'most_replies':
        $order_by = '(SELECT COUNT(*) FROM comments c2 WHERE c2.parent_id = c1.id AND c2.status != "hidden") DESC';
        break;
    case 'highest_score':
    default:
        $order_by = '(c1.upvotes - c1.downvotes) DESC'; // Assuming you have upvotes and downvotes columns
        break;
}

$comments_stmt = $conn->prepare("
    SELECT c1.*, users.username, users.profile_picture,
        (SELECT COUNT(*) FROM comments c2 WHERE c2.parent_id = c1.id AND c2.status != 'hidden') AS reply_count 
    FROM comments c1 
    JOIN users ON c1.user_id = users.user_id 
    WHERE c1.post_id = ? AND c1.parent_id IS NULL AND c1.status != 'hidden' 
    ORDER BY $order_by
");
$comments_stmt->bind_param("i", $post_id);
$comments_stmt->execute();
$comments_result = $comments_stmt->get_result();

$comments = [];
while ($row = $comments_result->fetch_assoc()) {
    $row['replies'] = [];
    $reply_stmt = $conn->prepare("
        SELECT replies.*, users.username, users.profile_picture 
        FROM comments replies 
        JOIN users ON replies.user_id = users.user_id 
        WHERE replies.parent_id = ? AND replies.status != 'hidden' 
        ORDER BY replies.created_at ASC
    ");
    $reply_stmt->bind_param("i", $row['id']);
    $reply_stmt->execute();
    $reply_result = $reply_stmt->get_result();
    while ($reply = $reply_result->fetch_assoc()) {
        $row['replies'][] = $reply;
    }
    $comments[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">

<head> <?php include 'header.php'; ?>
	<!-- Include Highlight.js -->
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/styles/default.min.css">
	<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/highlight.min.js"></script>
	<link rel="stylesheet" href="https://cdn.quilljs.com/1.3.7/quill.snow.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
    <title>MoonArrow Studios - Post</title>
	<script>
	document.addEventListener('DOMContentLoaded', () => {
		hljs.highlightAll();
	});
	</script>
	<style>
	:root {
    /* Base colors from previous styles */
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
    /* Additional colors for post view */
    --color-vote-active: #238636;
    --color-downvote-active: #cf222e;
    --color-comment-bg: #ffffff;
    --color-comment-border: #d0d7de;
    --color-reply-bg: #f6f8fa;
    --color-code-bg: #f6f8fa;
    --color-media-overlay: rgba(0, 0, 0, 0.5);
    --color-card-bg: #161b22;
    --color-card-border: #30363d;
    --color-accent-fg: #58a6ff;
}

@media (prefers-color-scheme: dark) {
    :root {
        /* Base dark colors from previous styles */
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
        /* Additional dark colors */
        --color-vote-active: #238636;
        --color-downvote-active: #f85149;
        --color-comment-bg: #161b22;
        --color-comment-border: #30363d;
        --color-reply-bg: #1c2129;
        --color-code-bg: #161b22;
        --color-media-overlay: rgba(0, 0, 0, 0.7);
    }
}

* {
    transition: all 0.3s ease-in-out;
}

/* Hover effects for buttons with animation */
.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

/* Post Title and Metadata */
.card-title {
    font-size: 32px;
    font-weight: 600;
    margin-bottom: 8px;
    color: var(--color-fg-default);
}

.post-metadata {
    color: var(--color-fg-muted);
    font-size: 14px;
    margin-bottom: 16px;
}

/* Content Styling */
.card {
    background-color: var(--color-card-bg);
    border: 1px solid var(--color-card-border);
    border-radius: 8px;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    transform: translateZ(0);
    will-change: transform;
    margin-bottom: 1.5rem;
    position: relative; /* Add this */
    overflow: hidden; /* Prevent overflow of pseudo-elements */
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1),
                0 0 25px 5px rgba(88, 166, 255, 0.1);
    border-color: rgba(88, 166, 255, 0.3);
}

/* Add the glowing border animation */
.card::before,
.card::after {
    content: '';
    position: absolute;
    left: 0;
    width: 100%;
    height: 2px;
    background: rgba(88, 166, 255, 0.3);
    transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1);
    z-index: 2;
    opacity: 0;
}

.card::before {
    top: 0;
    transform: translateX(-105%);
    box-shadow: 0 0 15px rgba(88, 166, 255, 0.3);
}

.card::after {
    bottom: 0;
    transform: translateX(105%);
    box-shadow: 0 0 15px rgba(88, 166, 255, 0.3);
}

.card:hover::before,
.card:hover::after {
    transform: translateX(0);
    opacity: 1;
}

.card-body {
    padding: 24px;
}

/* Media Gallery */
.media-container {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    align-items: center;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 16px;
    padding: 16px;
    background-color: var(--color-canvas-subtle);
    border-radius: 6px;
}

.media-item {
    display: flex;
    justify-content: center;
    align-items: center;
    object-fit: contain;
    width: 150px;
    height: 100px;
    position: relative;
    aspect-ratio: 16/9;
    border-radius: 6px;
    overflow: hidden;
    border: 1px solid var(--color-border-muted);
    transition: transform 0.2s ease;
}

.media-item:hover {
    transform: scale(1.05);
}

.media-item img,
.media-item video {
    width: 100%;
    height: 100%;
    object-fit: cover;
    position: absolute;
    top: 0;
    left: 0;
}

.fullscreen-btn {
    position: absolute;
    bottom: 8px;
    right: 8px;
    background-color: var(--color-media-overlay);
    color: white;
    border: none;
    border-radius: 4px;
    padding: 4px 8px;
    font-size: 14px;
    opacity: 0;
    transition: opacity 0.2s ease;
    z-index: 2;
    cursor: pointer;
	display: none;
}

.media-item:hover .fullscreen-btn {
    opacity: 1;
}

/* Voting System */
.vote-section {
    display: flex;
    justify-content: center;
    gap: 8px;
    margin: 16px 0;
}

.upvote-btn, .downvote-btn {
    padding: 6px 12px;
    border-radius: 6px;
    border: 1px solid var(--color-border-default);
    background-color: var(--color-canvas-default);
    transition: all 0.2s ease;
}

.upvote-btn:hover, .downvote-btn:hover {
    background-color: var(--color-canvas-subtle);
}

.upvote-btn.active {
    background-color: var(--color-vote-active);
    color: white;
    border-color: transparent;
}

.downvote-btn.active {
    background-color: var(--color-downvote-active);
    color: white;
    border-color: transparent;
}

/* Comments Section */
.comments-section {
    margin-top: 32px;
}

.comment-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
}

/* Quill Editor Customization */
.ql-toolbar {
    background-color: var(--color-canvas-subtle) !important;
    border-color: var(--color-border-default) !important;
    border-top-left-radius: 6px;
    border-top-right-radius: 6px;
}

.ql-container {
    background-color: var(--color-canvas-default) !important;
    border-color: var(--color-border-default) !important;
    border-bottom-left-radius: 6px;
    border-bottom-right-radius: 6px;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", "Noto Sans", Helvetica, Arial, sans-serif;
}

.ql-editor {
    min-height: 120px;
    font-size: 14px;
    color: var(--color-fg-default) !important;
}

/* Comments and Replies */
.comment-card {
    border: 1px solid var(--color-card-border);
    background-color: var(--color-card-bg);
    border-radius: 8px;
    transition: all 0.2s ease;
}

.comment-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.replies {
    margin-left: 24px;
    padding-left: 24px;
    border-left: 2px solid var(--color-border-muted);
}

.reply-card {
    background-color: var(--color-canvas-subtle);
    border: 1px solid var(--color-card-border);
    border-radius: 6px;
}

.main-container {
    display: grid;
    grid-template-columns: minmax(0, 1fr);
    gap: 2rem;
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

.post-content-card {
    width: 100%;
    max-width: 800px;
    margin: 0 auto;
}

.comment-actions {
    display: flex;
    gap: 8px;
    margin-top: 8px;
}

.reply-btn, .toggle-replies-btn {
    color: var(--color-accent-fg);
    font-size: 14px;
    padding: 4px 8px;
    border-radius: 4px;
}

.reply-btn:hover, .toggle-replies-btn:hover {
    background-color: var(--color-canvas-subtle);
    text-decoration: none;
}

/* Code Blocks */
pre {
    background-color: var(--color-media-overlay) !important;
    border: 1px solid var(--color-border-muted);
    border-radius: 6px;
    padding: 16px;
    margin: 16px 0;
}

code {
    font-family: SFMono-Regular, Consolas, "Liberation Mono", Menlo, monospace;
    font-size: 85%;
    background-color: var(--color-code-bg);
    padding: 0.2em 0.4em;
    border-radius: 6px;
}

/* Filter Select */
.filter-select {
    padding: 6px 12px;
    border-radius: 6px;
    border: 1px solid var(--color-border-default);
    background-color: var(--color-canvas-default);
    color: var(--color-fg-default);
    font-size: 14px;
    margin-bottom: 16px;
}

/* Modal Styles */
.media-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.7);
    backdrop-filter: blur(8px);
    display: none;
    justify-content: center;
    align-items: center;
    z-index: 1000;
}

.media-modal.active {
    display: flex;
}

.modal-content {
    position: relative;
    max-width: 90%;
    max-height: 90vh;
    border-radius: 8px;
    overflow: hidden;
}

.modal-content img,
.modal-content video {
    max-width: 100%;
    max-height: 80vh;
    object-fit: contain;
    border-radius: 8px;
}

.close-modal {
    position: absolute;
    top: 16px;
    right: 16px;
    background-color: rgba(0, 0, 0, 0.5);
    color: white;
    border: none;
    border-radius: 50%;
    width: 36px;
    height: 36px;
    font-size: 20px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background-color 0.2s ease;
}

.close-modal:hover {
    background-color: rgba(0, 0, 0, 0.7);
}

/* Update existing media item styles */
.media-item img,
.media-item video {
    width: 100%;
    height: 100%;
    object-fit: cover;
    position: absolute;
    top: 0;
    left: 0;
    cursor: pointer;
}
/* Style for Quill placeholder */
.ql-editor.ql-blank::before {
	color: #6c757d;
	/* This is the Bootstrap 'text-body-tertiry' color */
}
/* Add this to your existing CSS */
.profile-hover-card {
    position: fixed;
    width: 300px;
    background-color: var(--color-card-bg);
    border: 1px solid var(--color-card-border);
    border-radius: 6px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.12);
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.2s ease, visibility 0.2s ease;
    z-index: 1000;
    pointer-events: none;
}

.profile-hover-card.visible {
    opacity: 1;
    visibility: visible;
}

.hover-card-banner {
    width: 100%;
    height: 80px;
    object-fit: cover;
    border-top-left-radius: 6px;
    border-top-right-radius: 6px;
    background-color: rgb(108, 117, 125);
}

.hover-card-content {
    padding: 12px;
    position: relative;
}

.hover-card-avatar {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    border: 3px solid var(--color-card-bg);
    position: absolute;
    top: -24px;
    left: 12px;
    background-color: var(--color-canvas-subtle);
}

.hover-card-info {
    margin-top: 28px;
}

.hover-card-username {
    font-size: 16px;
    font-weight: 600;
    color: var(--color-fg-default);
    margin-bottom: 4px;
}

.hover-card-meta {
    font-size: 12px;
    color: var(--color-fg-muted);
}
.card-banner {
    width: 100%;
    height: 200px; /* Set height for the banner */
    object-fit: cover; /* Ensure the image covers the area */
    border-top-left-radius: 6px;
    border-top-right-radius: 6px;
}

.hashtag-container .badge {
    font-size: 0.9em;
    padding: 0.5em 0.7em;
}

.hashtag-container .btn-close {
    padding: 0.5em;
    margin-left: 0.3em;
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
	<script>
document.addEventListener('DOMContentLoaded', () => {
    // Create modal container
    const modal = document.createElement('div');
    modal.className = 'media-modal';
    modal.innerHTML = `
        <div class="modal-content">
            <button class="close-modal">X</button>
        </div>
    `;
    document.body.appendChild(modal);

    const modalContent = modal.querySelector('.modal-content');
    const closeButton = modal.querySelector('.close-modal');

    // Function to open modal
    function openModal(mediaElement) {
        const clone = mediaElement.cloneNode(true);

         // Add zoom-in animation classes
        clone.classList.add('animate__animated', 'animate__zoomIn');
        
        // Reset any specific sizing from the thumbnail
        clone.style.position = 'static';
        clone.style.width = 'auto';
        clone.style.height = 'auto';
        
        // If it's a video, add controls
        if (clone.tagName.toLowerCase() === 'video') {
            clone.controls = true;
        }
        
        modalContent.insertBefore(clone, closeButton);
        modal.classList.add('active');
        document.body.style.overflow = 'hidden'; // Prevent scrolling
    }

    // Function to close modal
    function closeModal() {
        const mediaElement = modalContent.querySelector('img, video');
        
        // Add fade-out animation to both modal background and content
        modal.classList.add('animate__animated', 'animate__fadeOut');
        
        // Wait for either animation to complete
        modal.addEventListener('animationend', () => {
            // Cleanup after animations
            if (mediaElement) mediaElement.remove();
            modal.classList.remove('active', 'animate__animated', 'animate__fadeOut');
            document.body.style.overflow = '';
        }, { once: true });
    }

    // Event listeners for opening modal
    document.querySelectorAll('.media-item img, .media-item video').forEach(media => {
        media.addEventListener('click', () => openModal(media));
    });

    // Event listeners for closing modal
    closeButton.addEventListener('click', closeModal);
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            closeModal();
        }
    });

    // Close on escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modal.classList.contains('active')) {
            closeModal();
        }
    });
});
</script>
</head>

<body>
<div id="alertContainer" class="alert-container"></div>
<div class="container mt-5">
    <div class="card animate__animated animate__fadeIn">
        <div class="card-body">
            <!-- Post Header - Cleaner & Minimalist with Icons -->
            <div class="post-header mb-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="d-flex align-items-center animate__animated animate__fadeInLeft">
                        <div class="profile-pic-container me-3">
                            <?php if (!empty($post['profile_picture'])): ?>
                                <img src="<?= htmlspecialchars($post['profile_picture']) ?>" alt="Profile" class="rounded-circle" width="40" height="40">
                            <?php else: ?>
                                <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                    <i class="bi bi-person-fill text-white"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div>
                            <a href="profile.php?id=<?= htmlspecialchars($post['user_id']) ?>" class="text-decoration-none">
                                <?= htmlspecialchars($post['username'] ?? 'Anonymous') ?>
                            </a>
                            <div class="text-muted small">
                                <i class="bi bi-clock"></i> <?= date('F j, Y, g:i A', strtotime($post['created_at'])) ?>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center animate__animated animate__fadeInRight">
                        <span class="badge bg-dark me-2">
                            <i class="bi bi-tag"></i> <?= htmlspecialchars($post['category_name'] ?? 'Uncategorized') ?>
                        </span>
                        <span class="badge bg-dark">
                            <i class="bi bi-eye"></i> <?= htmlspecialchars($post['views'] ?? '0') ?>
                        </span>
                    </div>
                </div>
                
                <h1 class="card-title mb-3 animate__animated animate__fadeInDown"><?= htmlspecialchars($post['title'] ?? 'No Title') ?></h1>
                
                <?php if (!empty($post['hashtags'])): ?>
                    <div class="hashtag-container mb-3 animate__animated animate__fadeIn">
                        <?php 
                        // Extract all valid hashtags using regular expression
                        preg_match_all('/#([^\s#]+)/', $post['hashtags'], $matches);
                        foreach ($matches[1] as $tag) {
                            echo '<span class="badge bg-dark me-1"><i class="bi bi-hash"></i>' . htmlspecialchars($tag) . '</span>';
                        }
                        ?>
                    </div>
                <?php endif; ?>
            </div>

            <hr>
            <!-- Post Content -->
            <div class="animate__animated animate__fadeIn"> 
                <?= $post['content'] ?> 
            </div>
<?php
            // After decoding the JSON, clean up the paths
            $images = !empty($post['images']) ? json_decode($post['images'], true) : [];
            $videos = !empty($post['videos']) ? json_decode($post['videos'], true) : [];

            // Clean up the paths by replacing escaped slashes and fix relative paths
            $images = array_map(function($path) {
                $cleaned_path = str_replace('\\/', '/', $path);
                // If path starts with 'php/', go back one directory since view_post.php is in php/
                if (strpos($cleaned_path, 'php/') === 0) {
                    return '../' . $cleaned_path;
                }
                return $cleaned_path;
            }, $images);

            $videos = array_map(function($path) {
                $cleaned_path = str_replace('\\/', '/', $path);
                // If path starts with 'php/', go back one directory since view_post.php is in php/
                if (strpos($cleaned_path, 'php/') === 0) {
                    return '../' . $cleaned_path;
                }
                return $cleaned_path;
            }, $videos);

            // Check if there are any attachments
            if (!empty($images) || !empty($videos)): ?>
                <hr>
                <!-- Display Images and Videos -->
                <h6 class='text-center animate__animated animate__fadeIn'><i class="bi bi-paperclip"></i>Attachments<i class="bi bi-paperclip"></i></h6>
                <div class="media-container animate__animated animate__fadeIn">
                    <?php 
                    $delay = 0.3;
                    if (!empty($images)): ?>
                        <?php foreach ($images as $image): 
                            $delay += 0.2; ?>
                            <div class="media-item animate__animated animate__zoomIn">
                                <img src="<?= htmlspecialchars($image) ?>" alt="Post Image">
                                <button class="fullscreen-btn" onclick="toggleFullscreen(event)">⛶</button>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <?php if (!empty($videos)): ?>
                        <?php foreach ($videos as $video_path): 
                            $delay += 0.2; ?>
                            <div class="media-item animate__animated animate__zoomIn" style="animation-delay: <?= $delay ?>s">
                                <video controls>
                                    <source src="<?= htmlspecialchars($video_path) ?>" type="video/mp4"> Your browser does not support the video tag.
                                </video>
                                <button class="fullscreen-btn" onclick="toggleFullscreen(event)">⛶</button>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="text-center mt-3 animate__animated animate__fadeIn animate__delay-1s">
    <button class="btn btn-outline-success me-2 upvote-btn <?= isset($_SESSION['user_id']) ? '' : 'disabled' ?>" data-post-id="<?= $post_id ?>" <?= isset($_SESSION['user_id']) ? '' : 'disabled' ?>>
        <i class="bi bi-caret-up-fill"></i> <span id="upvote-count"><?= $post['upvotes'] ?></span>
    </button>
    <button class="btn btn-outline-danger downvote-btn <?= isset($_SESSION['user_id']) ? '' : 'disabled' ?>" data-post-id="<?= $post_id ?>" <?= isset($_SESSION['user_id']) ? '' : 'disabled' ?>>
        <i class="bi bi-caret-down-fill"></i> <span id="downvote-count"><?= $post['downvotes'] ?></span>
    </button>
    <p class="mt-2">Score: <span id="score"><?= $post['upvotes'] - $post['downvotes'] ?></span></p>
    <button class="btn text-danger report-btn mb-3" data-content-type="post" data-content-id="<?= $post_id ?>">
        <i class="bi bi-flag"></i> Report Post
    </button>
</div> 

<?php if (!isset($_SESSION['user_id'])): ?> 
    <p class="text-center animate__animated animate__fadeIn animate__delay-1s">You must <a href="sign_in/sign_in_html.php" class="text-decoration-none">sign in</a> to vote.</p> 
<?php endif; ?>

<!-- Comment Section -->
<div class="container mt-3">
    <div class="card animate__animated animate__fadeIn animate__delay-1s">
        <div class="card-body">
            <h4 class="animate__animated animate__fadeInLeft">Comments</h4> 
            
            <?php if (isset($_SESSION['user_id'])): ?> 
                <div id="comment-editor" class="mb-3 animate__animated animate__fadeIn animate__delay-1s"></div>
                <!-- Updated button with loader -->
                <button class="btn btn-primary animate__animated animate__fadeIn animate__delay-1s" id="submit-comment" data-post-id="<?= $post_id ?>">
                    <span class="button-text">Submit Comment</span>
                    <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                </button> 
            <?php else: ?> 
                <p class="animate__animated animate__fadeIn animate__delay-1s">You must <a href="sign_in/sign_in_html.php" class="text-decoration-none">sign in</a> to comment and reply.</p> 
            <?php endif; ?>
            
            <hr>
            <div class="mb-3 animate__animated animate__fadeIn animate__delay-1s">
                <label for="filter" class="form-label">Filter Comments:</label>
                <select name="filter" id="filter" class="form-select me-2 bg-dark text-light">
                    <option value="highest_score" <?= isset($_GET['filter']) && $_GET['filter'] == 'highest_score' ? 'selected' : '' ?>>Highest Score</option>
                    <option value="newest" <?= isset($_GET['filter']) && $_GET['filter'] == 'newest' ? 'selected' : '' ?>>Newest</option>
                    <option value="most_replies" <?= isset($_GET['filter']) && $_GET['filter'] == 'most_replies' ? 'selected' : '' ?>>Most Replies</option>
                </select>
            </div>
            
            <div id="comments-container">
                <?php 
                $commentDelay = 1;
                foreach ($comments as $comment): 
                $commentDelay += 0.2;
                ?>
                <div class="card mb-3 animate__animated animate__fadeInUp" id="comment-<?php echo $comment['id']; ?>" style="max-width: 100%; animation-delay: <?= $commentDelay ?>s;">
                    <div class="card-body">
                        <!-- Comment Header with Profile Picture -->
                        <div class="d-flex align-items-center mb-3">
                            <div class="me-2">
                                <?php if (!empty($comment['profile_picture'])): ?>
                                    <img src="<?= htmlspecialchars($comment['profile_picture']) ?>" alt="Profile" class="rounded-circle" width="32" height="32">
                                <?php else: ?>
                                    <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                        <i class="bi bi-person-fill text-white"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div>
                                <a href="profile.php?id=<?php echo htmlspecialchars($comment['user_id']); ?>" class="text-decoration-none fw-bold">
                                    <?php echo htmlspecialchars($comment['username']); ?>
                                </a>
                                <div class="text-muted small">
                                    <i class="bi bi-clock"></i> <?php echo date('F j, Y, g:i A', strtotime($comment['created_at'])); ?>
                                </div>
                            </div>
                        </div>
                        
                        <p class="card-text"><?php echo $comment['content']; ?></p>
                        
                        <!-- Upvote and Downvote Buttons for Comments -->
                        <button class="btn btn-outline-success me-2 upvote-comment-btn <?php echo isset($_SESSION['user_id']) ? '' : 'disabled'; ?>" data-comment-id="<?php echo $comment['id']; ?>">
                            <i class="bi bi-caret-up-fill"></i> <span class="upvote-count"><?php echo $comment['upvotes'] ?? 0; ?></span>
                        </button>
                        <button class="btn btn-outline-danger downvote-comment-btn <?php echo isset($_SESSION['user_id']) ? '' : 'disabled'; ?>" data-comment-id="<?php echo $comment['id']; ?>">
                            <i class="bi bi-caret-down-fill"></i> <span class="downvote-count"><?php echo $comment['downvotes'] ?? 0; ?></span>
                        </button>
                        
                        <!-- Add near comment vote buttons -->
                        <a class="btn btn-link text-decoration-none reply-btn" data-comment-id="<?php echo $comment['id']; ?>">Reply</a>
                        
                        <!-- Hide/Show Replies Button -->
                        <a class="btn btn-link text-decoration-none toggle-replies-btn" data-comment-id="<?php echo $comment['id']; ?>" data-reply-count="<?php echo $comment['reply_count']; ?>"> 
                            Show Replies (<?php echo $comment['reply_count']; ?>) 
                        </a>
                        
                        <button class="btn text-danger report-btn" data-content-type="comment" data-content-id="<?= $comment['id'] ?>">
                            <i class="bi bi-flag"></i> Report Comment
                        </button>
                        
                        <!-- Replies Section -->
                        <div class="replies ms-4 mt-3" style="display: none;">
                            <?php foreach ($comment['replies'] as $reply): ?>
                                <div class="card mb-2 animate__animated animate__fadeIn" id="reply-<?php echo $reply['id']; ?>" style="max-width: 100%;">
                                    <div class="card-body">
                                        <!-- Reply Header with Profile Picture -->
                                        <div class="d-flex align-items-center mb-2">
                                            <div class="me-2">
                                                <?php if (!empty($reply['profile_picture'])): ?>
                                                    <img src="<?= htmlspecialchars($reply['profile_picture']) ?>" alt="Profile" class="rounded-circle" width="32" height="32">
                                                <?php else: ?>
                                                    <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center" style="width: 24px; height: 24px;">
                                                        <i class="bi bi-person-fill text-white" style="font-size: 12px;"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div>
                                                <a href="profile.php?id=<?php echo htmlspecialchars($reply['user_id']); ?>" class="text-decoration-none fw-bold">
                                                    <?php echo htmlspecialchars($reply['username']); ?>
                                                </a>
                                                <div class="text-muted small">
                                                    <i class="bi bi-clock"></i> <?php echo date('F j, Y, g:i A', strtotime($reply['created_at'])); ?>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <p class="card-text"><?php echo $reply['content']; ?></p>
                                        
                                        <!-- Upvote and Downvote Buttons for Replies -->
                                        <button class="btn btn-outline-success me-3 upvote-reply-btn <?php echo isset($_SESSION['user_id']) ? '' : 'disabled'; ?>" data-comment-id="<?php echo $reply['id']; ?>">
                                            <i class="bi bi-caret-up-fill"></i> <span class="upvote-count"><?php echo $reply['upvotes'] ?? 0; ?></span>
                                        </button>
                                        <button class="btn btn-outline-danger me-3 downvote-reply-btn <?php echo isset($_SESSION['user_id']) ? '' : 'disabled'; ?>" data-comment-id="<?php echo $reply['id']; ?>">
                                            <i class="bi bi-caret-down-fill"></i> <span class="downvote-count"><?php echo $reply['downvotes'] ?? 0; ?></span>
                                        </button>
                                        
                                        <!-- Add near reply vote buttons -->
                                        <button class="btn text-danger report-btn" data-content-type="reply" data-content-id="<?= $reply['id'] ?>">
                                            <i class="bi bi-flag"></i> Report Reply
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?> 
            </div>
        </div>
    </div>
</div>

<!-- Report Modal -->
<div class="modal fade" id="reportModal" tabindex="-1" aria-labelledby="reportModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered animate__animated animate__zoomIn">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="reportModalLabel">Report Content</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="reportForm">
                <div class="modal-body">
                    <input type="hidden" id="reportContentType" name="content_type">
                    <input type="hidden" id="reportContentId" name="content_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Reason</label>
                        <select class="form-select" name="reason" required>
                            <option value="">Select a reason</option>
                            <option value="Swearing">Swearing</option>
                            <option value="NSFW">NSFW Content</option>
                            <option value="Harassment">Harassment</option>
                            <option value="Spam">Spam</option>
                            <option value="Malicious Link/File">Malicious Link/File</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Additional Details</label>
                        <textarea class="form-control" name="details" rows="3" placeholder="Please provide additional information..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Submit Report</button>
                </div>
            </form>
        </div>
    </div>
</div>
	<br> <?php $conn->close(); ?> <script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
	<script>
	function toggleFullscreen(event) {
    const mediaItem = event.target.closest('.media-item');
    const mediaElement = mediaItem.querySelector('img, video');
    
    if (!document.fullscreenElement) {
        if (mediaElement.requestFullscreen) {
            mediaElement.requestFullscreen();
        } else if (mediaElement.webkitRequestFullscreen) {
            mediaElement.webkitRequestFullscreen();
        } else if (mediaElement.msRequestFullscreen) {
            mediaElement.msRequestFullscreen();
        }
        
        // Add entrance animation when entering fullscreen
        mediaElement.classList.add('animate__animated', 'animate__zoomIn');
        setTimeout(() => {
            mediaElement.classList.remove('animate__animated', 'animate__zoomIn');
        }, 1000);
    } else {
        if (document.exitFullscreen) {
            document.exitFullscreen();
        } else if (document.webkitExitFullscreen) {
            document.webkitExitFullscreen();
        } else if (document.msExitFullscreen) {
            document.msExitFullscreen();
        }
    }
    
    event.preventDefault();
}
	document.addEventListener('DOMContentLoaded', () => {
		document.querySelectorAll('.upvote-btn, .downvote-btn').forEach(button => {
			button.addEventListener('click', () => {
				// Check if the button is disabled
				if(button.disabled) {
					alert('You must log in to vote.');
					return;
				}
				const postId = button.dataset.postId;
				const voteType = button.classList.contains('upvote-btn') ? 'upvote' : 'downvote';
				fetch('post/vote.php', {
					method: 'POST',
					headers: {
						'Content-Type': 'application/x-www-form-urlencoded'
					},
					body: `post_id=${postId}&vote_type=${voteType}`
				}).then(response => response.json()).then(data => {
					if(data.success) {
						document.getElementById('upvote-count').textContent = data.upvotes;
						document.getElementById('downvote-count').textContent = data.downvotes;
						document.getElementById('score').textContent = data.score;
						// Update button states
						document.querySelectorAll('.upvote-btn, .downvote-btn').forEach(btn => {
							btn.classList.remove('active');
						});
						if(voteType === 'upvote') {
							button.classList.toggle('active');
						} else {
							button.classList.toggle('active');
						}
					} else {
						showAlert(data.error || 'An error occurred', 'danger');
					}
				}).catch(err => console.error('Error:', err));
			});
		});
	});
	document.addEventListener('DOMContentLoaded', () => {
		// Define the toolbar options
		const alertDiv = document.createElement('div');
    alertDiv.style.position = 'fixed';
    alertDiv.style.top = '20px';
    alertDiv.style.left = '50%';
    alertDiv.style.transform = 'translateX(-50%)';
    alertDiv.style.zIndex = '1050';
    alertDiv.style.display = 'none';
    document.body.appendChild(alertDiv);

    const toolbarOptions = [
        [{ header: [1, 2, 3, false] }],
        ['bold', 'italic', 'underline'],
        ['blockquote', 'code-block'],
        [{ list: 'ordered' }, { list: 'bullet' }],
        ['link']
    ];

    const quill = new Quill('#comment-editor', {
        theme: 'snow',
        placeholder: 'Write your comment...',
        modules: {
            toolbar: toolbarOptions
        }
    });

    // Get post ID from submit button
    const postId = document.getElementById('submit-comment').getAttribute('data-post-id');
    const commentButton = document.getElementById('submit-comment');
    
    commentButton.addEventListener('click', () => {
        const content = quill.root.innerHTML;
        const plainText = quill.getText().trim();

        if (!plainText) {
            showAlert('Comment cannot be empty.', 'warning');
            return;
        }

        // Show loader
        const buttonText = commentButton.querySelector('.button-text');
        const spinner = commentButton.querySelector('.spinner-border');
        buttonText.textContent = 'Submitting...';
        spinner.classList.remove('d-none');

        fetch('post/submit_comment.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `post_id=${postId}&content=${encodeURIComponent(content)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success alert
            showAlert(data.message || 'Comment posted successfully!', 'success');
            
            // Clear editor and reset button
            quill.setText('');
            buttonText.textContent = 'Submit Comment';
            spinner.classList.add('d-none');

            sessionStorage.setItem('showCommentSuccess', 'true');
            location.reload();
            
            // Optional: Insert comment immediately without reload
            // You would need to implement this DOM insertion
        } else {
                // Reset button on error
                buttonText.textContent = 'Submit Comment';
                spinner.classList.add('d-none');
                showAlert(data.error || 'An error occurred.', 'danger');
            }
        }).catch(err => {
            console.error('Error:', err);
            // Reset button on error
            buttonText.textContent = 'Submit Comment';
            spinner.classList.add('d-none');
            showAlert('Network error. Please try again.', 'danger');
        });
    });

    // Reply handling
    document.getElementById('comments-container').addEventListener('click', (e) => {
        if (e.target.classList.contains('reply-btn')) {
            const parentCommentId = e.target.dataset.commentId;
            let replyEditor = document.getElementById(`reply-editor-${parentCommentId}`);
            let submitReplyButton = document.getElementById(`submit-reply-${parentCommentId}`);
            let replyQuill;

            if (!replyEditor) {
                replyEditor = document.createElement('div');
                replyEditor.id = `reply-editor-${parentCommentId}`;
                replyEditor.classList.add('mb-3');
                e.target.parentElement.appendChild(replyEditor);

                replyQuill = new Quill(replyEditor, {
                    theme: 'snow',
                    placeholder: 'Write your reply...',
                    modules: {
                        toolbar: toolbarOptions
                    }
                });

                submitReplyButton = document.createElement('button');
                submitReplyButton.id = `submit-reply-${parentCommentId}`;
                submitReplyButton.innerHTML = `
                    <span class="button-text">Submit Reply</span>
                    <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                `;
                submitReplyButton.classList.add('btn', 'btn-primary', 'mt-2');
                e.target.parentElement.appendChild(submitReplyButton);

                submitReplyButton.addEventListener('click', () => {
                    const content = replyQuill.root.innerHTML;
                    const plainText = replyQuill.getText().trim();

                    if (!plainText) {
                        showAlert('Reply cannot be empty.', 'warning');
                        return;
                    }

                    // Show loader
                    const buttonText = submitReplyButton.querySelector('.button-text');
                    const spinner = submitReplyButton.querySelector('.spinner-border');
                    buttonText.textContent = 'Submitting...';
                    spinner.classList.remove('d-none');
                    submitReplyButton.disabled = true;

                    fetch('post/submit_comment.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: `post_id=${postId}&parent_id=${parentCommentId}&content=${encodeURIComponent(content)}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showAlert('Reply posted successfully!', 'success');

                            // Clear editor and reset button
                            quill.setText('');
                            buttonText.textContent = 'Submit Reply';
                            spinner.classList.add('d-none');

                            sessionStorage.setItem('showReplySuccess', 'true');
                            location.reload();

                        } else {
                            // Reset button on error
                            buttonText.textContent = 'Submit Reply';
                            spinner.classList.add('d-none');
                            submitReplyButton.disabled = false;
                            showAlert(data.error || 'An error occurred.', 'danger');
                        }
                    }).catch(err => {
                        console.error('Error:', err);
                        // Reset button on error
                        buttonText.textContent = 'Submit Reply';
                        spinner.classList.add('d-none');
                        submitReplyButton.disabled = false;
                        showAlert('Network error. Please try again.', 'danger');
                    });
                });
            } else {
                replyQuill = Quill.find(replyEditor);
            }

            const isVisible = replyEditor.style.display === 'block';
            if (isVisible) {
                replyEditor.style.display = 'none';
                submitReplyButton.style.display = 'none';
                replyQuill.getModule('toolbar').container.style.display = 'none';
            } else {
                replyEditor.style.display = 'block';
                submitReplyButton.style.display = 'block';
                replyQuill.getModule('toolbar').container.style.display = 'block';
                replyQuill.getModule('toolbar').container.classList.add('mt-3');
            }
        }
    });
});
	document.addEventListener('DOMContentLoaded', () => {
		// Voting for comments
		document.querySelectorAll('.upvote-comment-btn, .downvote-comment-btn').forEach(button => {
			button.addEventListener('click', () => {
				if(button.disabled) {
					alert('You must log in to vote.');
					return;
				}
				const commentId = button.dataset.commentId;
				const voteType = button.classList.contains('upvote-comment-btn') ? 'upvote' : 'downvote';
				fetch('post/comment_vote.php', {
					method: 'POST',
					headers: {
						'Content-Type': 'application/x-www-form-urlencoded'
					},
					body: `comment_id=${commentId}&vote_type=${voteType}`
				}).then(response => response.json()).then(data => {
					if(data.success) {
						button.querySelector('.upvote-count, .downvote-count').textContent = voteType === 'upvote' ? data.upvotes : data.downvotes;
						// Update button states
						document.querySelectorAll('.upvote-comment-btn, .downvote-comment-btn').forEach(btn => {
							btn.classList.remove('active');
						});
						button.classList.toggle('active');
					} else {
						showAlert(data.error || 'An error occurred', 'danger');
					}
				}).catch(err => console.error('Error:', err));
			});
		});
		// Voting for replies (similar to comments)
		document.querySelectorAll('.upvote-reply-btn, .downvote-reply-btn').forEach(button => {
			button.addEventListener('click', () => {
				if(button.disabled) {
					alert('You must log in to vote.');
					return;
				}
				const replyId = button.dataset.commentId;
				const voteType = button.classList.contains('upvote-reply-btn') ? 'upvote' : 'downvote';
				fetch('post/comment_vote.php', {
					method: 'POST',
					headers: {
						'Content-Type': 'application/x-www-form-urlencoded'
					},
					body: `comment_id=${replyId}&vote_type=${voteType}`
				}).then(response => response.json()).then(data => {
					if(data.success) {
						button.querySelector('.upvote-count, .downvote-count').textContent = voteType === 'upvote' ? data.upvotes : data.downvotes;
						// Update button states
						document.querySelectorAll('.upvote-reply-btn, .downvote-reply-btn').forEach(btn => {
							btn.classList.remove('active');
						});
						button.classList.toggle('active');
					} else {
						showAlert(data.error || 'An error occurred', 'danger');
					}
				}).catch(err => console.error('Error:', err));
			});
		});
	});
	document.addEventListener('DOMContentLoaded', () => {
		// Existing code...
		// Toggle replies visibility
		document.querySelectorAll('.toggle-replies-btn').forEach(button => {
			button.addEventListener('click', () => {
				const commentId = button.dataset.commentId;
				const repliesContainer = button.closest('.card-body').querySelector('.replies');
				if(repliesContainer.style.display === 'none' || repliesContainer.style.display === '') {
					repliesContainer.style.display = 'block';
					button.textContent = `Hide Replies (${button.dataset.replyCount})`;
				} else {
					repliesContainer.style.display = 'none';
					button.textContent = `Show Replies (${button.dataset.replyCount})`;
				}
			});
		});
	});

    document.getElementById('filter').addEventListener('change', function() {
        const selectedFilter = this.value;
        const url = new URL(window.location.href);
        url.searchParams.set('filter', selectedFilter);
        window.location.href = url.toString();
    });
    // Add this JavaScript to handle the hover card functionality
    document.addEventListener('DOMContentLoaded', function() {
    let hoverCard = document.createElement('div');
    hoverCard.className = 'profile-hover-card';
    document.body.appendChild(hoverCard);
    
    let hoverTimeout;
    let currentUsername;
    
    document.querySelectorAll('a[href^="profile.php"]').forEach(link => {
        link.addEventListener('mouseenter', async (e) => {
            clearTimeout(hoverTimeout);
            const userId = new URLSearchParams(link.href.split('?')[1]).get('id');
            currentUsername = link;
            
            const rect = link.getBoundingClientRect();
            hoverCard.style.left = `${rect.left}px`;
            hoverCard.style.top = `${rect.bottom + 8}px`;
            
            try {
                const response = await fetch(`fetch_user_preview.php?user_id=${userId}`);
                const userData = await response.json();
                
                // Create avatar content
                const avatarContent = userData.profile_picture 
                    ? `<img class="hover-card-avatar" src="${userData.profile_picture}" alt="${userData.username}'s avatar">` 
                    : `<div class="hover-card-avatar d-flex align-items-center justify-content-center bg-dark">
                         <i class="bi bi-person-fill text-light" style="font-size: 1.5rem;"></i>
                       </div>`;
                
                // Create banner content
                let bannerContent;
                if (userData.banner) {
                    // Use img tag for banner instead of background-image
                    bannerContent = `<img src="${userData.banner}" class="hover-card-banner" alt="User banner">`;
                } else {
                    bannerContent = `<div class="hover-card-banner" style="background-color: rgb(108, 117, 125);"></div>`;
                }
                
                hoverCard.innerHTML = `
                    ${bannerContent}
                    <div class="hover-card-content">
                        ${avatarContent}
                        <div class="hover-card-info">
                            <div class="hover-card-username">${userData.username}</div>
                            <div class="hover-card-meta">
                                Joined ${userData.formatted_join_date}<br>
                                ${userData.follower_count} followers
                            </div>
                        </div>
                    </div>
                `;
                
                hoverCard.classList.add('visible');
            } catch (error) {
                console.error('Error fetching user data:', error);
            }
        });
        
        link.addEventListener('mouseleave', () => {
            hoverTimeout = setTimeout(() => {
                if (currentUsername === link) {
                    hoverCard.classList.remove('visible');
                }
            }, 200);
        });
    });
    
    hoverCard.addEventListener('mouseenter', () => {
        clearTimeout(hoverTimeout);
    });
    
    hoverCard.addEventListener('mouseleave', () => {
        hoverCard.classList.remove('visible');
    });
});

// Modify the post display section to show hashtags as badges
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.card-text').forEach(element => {
        if (element.innerHTML.includes('<strong>Hashtags:</strong>')) {
            const hashtags = element.innerHTML.split('<strong>Hashtags:</strong> ')[1].trim();
            if (hashtags) {
                const hashtagArray = hashtags.split(' ');
                const hashtagBadges = hashtagArray.map(tag => 
                    `<span class="badge bg-dark me-1">${tag}</span>`
                ).join('');
                element.innerHTML = `<strong>Hashtags:</strong> ${hashtagBadges}`;
            }
        }
    });
});
document.addEventListener('DOMContentLoaded', () => {
    // Parse URL parameters
    const urlParams = new URLSearchParams(window.location.search);
    const commentId = urlParams.get('comment');
    const replyId = urlParams.get('reply');

    if (commentId) {
        // Find the parent comment
        const parentComment = document.getElementById(`comment-${commentId}`);
        if (parentComment) {
            // Scroll to the parent comment
            parentComment.scrollIntoView({ behavior: 'smooth', block: 'center' });

            // If there's a reply ID, open the replies section and scroll to the reply
            if (replyId) {
                const toggleRepliesBtn = parentComment.querySelector('.toggle-replies-btn');
                const repliesSection = parentComment.querySelector('.replies');

                if (toggleRepliesBtn && repliesSection && repliesSection.style.display === 'none') {
                    toggleRepliesBtn.click(); // Simulate click to open replies
                }

                // Scroll to the specific reply
                const replyElement = document.getElementById(`reply-${replyId}`);
                if (replyElement) {
                    replyElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }
        }
    }
});

// Report button handling
document.querySelectorAll('.report-btn').forEach(btn => {
    btn.addEventListener('click', (e) => {
        if (!<?= isset($_SESSION['user_id']) ? 'true' : 'false' ?>) {
            showAlert('Please login to report content.', 'danger');
            return;
        }
        
        const modal = new bootstrap.Modal(document.getElementById('reportModal'));
        document.getElementById('reportContentType').value = btn.dataset.contentType;
        document.getElementById('reportContentId').value = btn.dataset.contentId;
        modal.show();
    });
});

// Handle report form submission
document.getElementById('reportForm').addEventListener('submit', (e) => {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    formData.append('reporter_id', <?= $_SESSION['user_id'] ?? 'null' ?>);
    
    fetch('report.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            showAlert('Report submitted successfully!', 'success');
            
            // Close modal
            bootstrap.Modal.getInstance(document.getElementById('reportModal')).hide();
        } else {
            throw new Error(data.error || 'Unknown error occurred');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        // Show error message with Bootstrap alert
        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-danger position-fixed top-0 start-50 translate-middle-x mt-3';
        showAlert('Error submitting report: ' + error.message, 'success');
    });
});

document.addEventListener('DOMContentLoaded', function() {
    
    // Animate vote count changes
    const voteButtons = document.querySelectorAll('.upvote-btn, .downvote-btn, .upvote-comment-btn, .downvote-comment-btn, .upvote-reply-btn, .downvote-reply-btn');
    
    voteButtons.forEach(button => {
        button.addEventListener('click', function() {
            const countSpan = this.querySelector('span');
            countSpan.classList.add('animate__animated', 'animate__bounceIn');
            
            setTimeout(() => {
                countSpan.classList.remove('animate__animated', 'animate__bounceIn');
            }, 1000);
        });
    });
    
    // Filter dropdown animation
    const filterSelect = document.getElementById('filter');
    if (filterSelect) {
        filterSelect.addEventListener('change', function() {
            document.getElementById('comments-container').classList.add('animate__animated', 'animate__fadeOut');
            
            setTimeout(() => {
                document.getElementById('comments-container').classList.remove('animate__fadeOut');
                document.getElementById('comments-container').classList.add('animate__fadeIn');
            }, 500);
        });
    }
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

document.addEventListener('DOMContentLoaded', function() {
    if (sessionStorage.getItem('showCommentSuccess')) {
        showAlert('Comment posted successfully!', 'success');
        sessionStorage.removeItem('showCommentSuccess');
    }
});

document.addEventListener('DOMContentLoaded', function() {
    if (sessionStorage.getItem('showReplySuccess')) {
        showAlert('Reply posted successfully!', 'success');
        sessionStorage.removeItem('showReplySuccess');
    }
});
	</script>
</body>