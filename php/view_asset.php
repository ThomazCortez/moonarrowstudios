<?php
session_start();

// Database connection
require 'db_connect.php';

// Fetch the asset by ID
$asset_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$stmt = $conn->prepare("SELECT assets.*, asset_categories.name AS category_name, users.username, users.profile_picture 
                        FROM assets 
                        JOIN asset_categories ON assets.category_id = asset_categories.id 
                        JOIN users ON assets.user_id = users.user_id 
                        WHERE assets.id = ? AND assets.status != 'hidden'");
$stmt->bind_param("i", $asset_id);
$stmt->execute();
$result = $stmt->get_result();
$asset = $result->fetch_assoc();

if (!$asset || $asset['status'] === 'hidden') {
    echo "<h1>Asset not found</h1>";
    exit;
}

// In view_asset.php asset retrieval
$model_files = [];
if (!empty($asset['asset_file']) && is_dir($asset['asset_file'])) {
    $model_files = array_diff(scandir($asset['asset_file']), ['.', '..']);
    $model_files = array_map(function($file) use ($asset) {
        return $asset['asset_file'] . $file;
    }, $model_files);
}


// Increment the view count - ADD THIS SECTION
$update_views_stmt = $conn->prepare("UPDATE assets SET views = views + 1 WHERE id = ?");
$update_views_stmt->bind_param("i", $asset_id);
$update_views_stmt->execute();

// Fetch comments and their replies based on the selected filter
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'highest_score';

$order_by = 'c1.created_at DESC'; // Default order
switch ($filter) {
    case 'newest':
        $order_by = 'c1.created_at DESC';
        break;
    case 'most_replies':
        $order_by = '(SELECT COUNT(*) FROM comments_asset c2 WHERE c2.parent_id = c1.id AND c2.status != "hidden") DESC';
        break;
    case 'highest_score':
    default:
        $order_by = '(c1.upvotes - c1.downvotes) DESC';
        break;
}

// Fetch top-level comments for the specific asset
$comments_stmt = $conn->prepare("
    SELECT c1.*, users.username, users.profile_picture,
        (SELECT COUNT(*) FROM comments_asset c2 WHERE c2.parent_id = c1.id AND c2.status != 'hidden') AS reply_count 
    FROM comments_asset c1 
    JOIN users ON c1.user_id = users.user_id 
    WHERE c1.asset_id = ? AND c1.parent_id IS NULL AND c1.status != 'hidden'  
    ORDER BY $order_by
");
$comments_stmt->bind_param("i", $asset_id);
$comments_stmt->execute();
$comments_result = $comments_stmt->get_result();

$comments = [];
while ($row = $comments_result->fetch_assoc()) {
    $row['replies'] = [];
    $reply_stmt = $conn->prepare("
    SELECT replies.*, users.username, users.profile_picture 
    FROM comments_asset replies 
    JOIN users ON replies.user_id = users.user_id 
    WHERE replies.parent_id = ? 
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

<head> 
    <?php include 'header.php'; ?>
    <!-- Include Highlight.js -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/styles/default.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/highlight.min.js"></script>
    <link rel="stylesheet" href="https://cdn.quilljs.com/1.3.7/quill.snow.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
    <title>MoonArrow Studios - Asset</title>
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
    /* Additional colors for asset view */
    --color-vote-active: #238636;
    --color-downvote-active: #cf222e;
    --color-comment-bg: #ffffff;
    --color-comment-border: #d0d7de;
    --color-reply-bg: #f6f8fa;
    --color-code-bg: #f6f8fa;
    --color-media-overlay: rgba(0, 0, 0, 0.5);
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
        --color-card-bg: #161b22;
        --color-card-border: #30363d;
        --color-accent-fg: #58a6ff;
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

/* asset Title and Metadata */
.card-title {
    font-size: 32px;
    font-weight: 600;
    margin-bottom: 8px;
    color: var(--color-fg-default);
}

.asset-metadata {
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
    background-color: var(--color-code-bg) !important;
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
.texture-viewer-container {
    width: 100%;
    max-width: 900px;
    margin: 20px auto;
    border-radius: 16px;
    overflow: hidden;
    background: linear-gradient(145deg, #1a1a1a, #2d2d2d);
    box-shadow: 
        0 20px 40px rgba(0, 0, 0, 0.4),
        0 10px 20px rgba(0, 0, 0, 0.2),
        inset 0 1px 0 rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.1);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.texture-viewer-container:hover {
    box-shadow: 
        0 25px 50px rgba(0, 0, 0, 0.5),
        0 15px 30px rgba(0, 0, 0, 0.3),
        inset 0 1px 0 rgba(255, 255, 255, 0.15);
}

.texture-viewer-container .model-viewer {
    height: 500px;
    position: relative;
    background: radial-gradient(circle at center, rgba(255, 255, 255, 0.05) 0%, transparent 70%);
}

.texture-viewer-container canvas {
    display: block;
    width: 100% !important;
    height: 100% !important;
    border-radius: 16px 16px 0 0;
}

.texture-viewer-container .model-loading {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    background: rgba(0, 0, 0, 0.9);
    backdrop-filter: blur(10px);
    border-radius: 12px;
    padding: 30px;
    min-width: 200px;
}

.texture-viewer-container .loading-spinner {
    width: 40px;
    height: 40px;
    border: 3px solid rgba(255, 255, 255, 0.1);
    border-top: 3px solid #4f46e5;
    border-radius: 50%;
    animation: spin 1s cubic-bezier(0.68, -0.55, 0.265, 1.55) infinite;
    margin-bottom: 15px;
}

.texture-viewer-container .loading-text {
    color: rgba(255, 255, 255, 0.9);
    font-size: 14px;
    font-weight: 500;
    margin: 0;
    text-align: center;
}

.texture-viewer-container .error-message {
    color: #ef4444;
    background: rgba(239, 68, 68, 0.1);
    padding: 15px;
    border-radius: 8px;
    border: 1px solid rgba(239, 68, 68, 0.3);
    text-align: center;
}

.texture-viewer-container .model-controls {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 8px;
    padding: 16px 20px;
    background: rgba(0, 0, 0, 0.8);
    backdrop-filter: blur(20px);
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    flex-wrap: wrap;
}

.texture-viewer-container .control-btn {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 8px 12px;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 8px;
    color: rgba(255, 255, 255, 0.9);
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    backdrop-filter: blur(10px);
}

.texture-viewer-container .control-btn:hover {
    background: rgba(255, 255, 255, 0.1);
    border-color: rgba(255, 255, 255, 0.2);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
}

.texture-viewer-container .model-info {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px 20px;
    background: rgba(0, 0, 0, 0.6);
    color: rgba(255, 255, 255, 0.7);
    font-size: 12px;
    text-align: center;
    border-top: 1px solid rgba(255, 255, 255, 0.05);
}

/* Responsive Design */
@media (max-width: 768px) {
    .texture-viewer-container {
        width: 100%;
        margin: 0;
        border-radius: 12px;
    }
    
    .texture-viewer-container .model-viewer {
        height: 350px;
    }
    
    .texture-viewer-container .model-controls {
        padding: 12px 16px;
        gap: 6px;
    }
    
    .texture-viewer-container .control-btn {
        padding: 6px 10px;
        font-size: 12px;
    }
    
    .texture-viewer-container .control-btn span {
        display: none;
    }
    
    .texture-viewer-container .model-info {
        padding: 10px 16px;
        font-size: 11px;
    }
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Add these theme variations */
.texture-viewer-container.light-theme {
    background: linear-gradient(145deg, #ffffff, #f8fafc);
    border-color: rgba(0, 0, 0, 0.1);
}

.texture-viewer-container.light-theme .model-controls {
    background: rgba(255, 255, 255, 0.9);
    border-top-color: rgba(0, 0, 0, 0.1);
}

.texture-viewer-container.light-theme .control-btn {
    background: rgba(0, 0, 0, 0.05);
    border-color: rgba(0, 0, 0, 0.1);
    color: rgba(0, 0, 0, 0.8);
}

.texture-viewer-container.light-theme .control-btn:hover {
    background: rgba(0, 0, 0, 0.1);
    border-color: rgba(0, 0, 0, 0.2);
}

.texture-viewer-container.blue-theme {
    background: linear-gradient(145deg, #1e3a8a, #3b82f6);
}

.texture-viewer-container.purple-theme {
    background: linear-gradient(145deg, #581c87, #a855f7);
}

.texture-viewer-container.green-theme {
    background: linear-gradient(145deg, #166534, #22c55e);
}

.texture-viewer-container.gradient-theme {
    background: linear-gradient(145deg, #667eea, #764ba2);
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
                <!-- Asset Header -->
                <div class="post-header mb-4">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div class="d-flex align-items-center animate__animated animate__fadeInLeft">
            <div class="profile-pic-container me-3">
                <?php if (!empty($asset['profile_picture'])): ?>
                    <img src="<?= htmlspecialchars($asset['profile_picture']) ?>" alt="Profile" class="rounded-circle" width="40" height="40">
                <?php else: ?>
                    <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                        <i class="bi bi-person-fill text-white"></i>
                    </div>
                <?php endif; ?>
            </div>
            <div>
                <a href="profile.php?id=<?= htmlspecialchars($asset['user_id']) ?>" class="text-decoration-none">
                    <?= htmlspecialchars($asset['username'] ?? 'Anonymous') ?>
                </a>
                <div class="text-muted small">
                    <i class="bi bi-clock"></i> <?= date('F j, Y, g:i A', strtotime($asset['created_at'])) ?>
                </div>
            </div>
        </div>
        <div class="d-flex align-items-center animate__animated animate__fadeInRight">
            <span class="badge bg-dark me-2">
                <i class="bi bi-tag"></i> <?= htmlspecialchars($asset['category_name'] ?? 'Uncategorized') ?>
            </span>
            <span class="badge bg-dark">
                <i class="bi bi-eye"></i> <?= htmlspecialchars($asset['views'] ?? '0') ?>
            </span>
        </div>
    </div>
    
    <h1 class="card-title mb-3 animate__animated animate__fadeInDown"><?= htmlspecialchars($asset['title'] ?? 'No Title') ?></h1>
    
    <?php if (!empty($asset['hashtags'])): ?>
        <div class="hashtag-container mb-3 animate__animated animate__fadeIn">
            <?php 
            preg_match_all('/#([^\s#]+)/', $asset['hashtags'], $matches);
            foreach ($matches[1] as $tag) {
                echo '<span class="badge bg-dark me-1"><i class="bi bi-hash"></i>' . htmlspecialchars($tag) . '</span>';
            }
            ?>
        </div>
    <?php endif; ?>
</div>
                <hr>
                <!-- Asset Content -->
<div class="animate__animated animate__fadeIn"> 
    <?= $asset['content'] ?> 
</div>
<?php
// After decoding the JSON, clean up the paths
$images = !empty($asset['images']) ? json_decode($asset['images'], true) : [];
$videos = !empty($asset['videos']) ? json_decode($asset['videos'], true) : [];

// Clean up the paths by replacing escaped slashes
$images = array_map(function($path) {
    return str_replace('\\/', '/', $path);
}, $images);

$videos = array_map(function($path) {
    return str_replace('\\/', '/', $path);
}, $videos);

// Check if there are any attachments (images, videos, or asset_file)
if (!empty($images) || !empty($videos) || !empty($asset['asset_file'])): ?>
    <hr>
    <!-- Display Images, Videos, and Asset File -->
    <h6 class='text-center animate__animated animate__fadeIn'><i class="bi bi-paperclip"></i>Attachments<i class="bi bi-paperclip"></i></h6>
    <div class="media-container animate__animated animate__fadeIn">
        <?php 
        $delay = 0.3;
        if (!empty($images)): ?>
            <?php foreach ($images as $image): 
                $delay += 0.2; ?>
                <div class="media-item animate__animated animate__zoomIn">
                    <img src="<?= htmlspecialchars($image) ?>" alt="Asset Image">
                    <button class="fullscreen-btn" onclick="toggleFullscreen(event)">⛶</button>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php if (!empty($videos)): ?>
            <?php foreach ($videos as $video_path): 
            $delay += 0.2; ?>
            <div class="media-item animate__animated animate__zoomIn" style="animation-delay: <?= $delay ?>s">
                    <video>
                        <source src="<?= htmlspecialchars($video_path) ?>" type="video/mp4"> Your browser does not support the video tag.
                    </video>
                    <button class="fullscreen-btn" onclick="toggleFullscreen(event)">⛶</button>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <?php if (!empty($asset['asset_file'])): ?>
        <?php
        // Construct the file path
        $filePath = __DIR__ . '/' . $asset['asset_file'];
        $filePath = str_replace('\\', '/', realpath($filePath));

        if (!file_exists($filePath)) {
            echo "<div class='alert alert-danger'>3D model file not found at: $filePath</div>";
        }
        
        // Normalize slashes for Windows compatibility
        $filePath = str_replace('\\', '/', $filePath);

        // Get file extension to determine file type
        $fileExtension = pathinfo($asset['asset_file'], PATHINFO_EXTENSION);
        $isAudioFile = in_array(strtolower($fileExtension), ['mp3', 'wav', 'ogg', 'm4a', 'flac', 'aac']);
        $is3DModelFile = in_array(strtolower($fileExtension), ['obj', 'fbx', 'gltf', 'glb', 'dae', 'ply', 'stl', '3ds']);
        
        // Check if the file exists
        if (file_exists($filePath)) {
            $fileHash = hash_file('sha256', $filePath);
            $fileSize = filesize($filePath);
        } else {
            $fileHash = 'File not found';
            $fileSize = 0;
        }
        ?>
        
<?php if ($asset['category_id'] == 5 && $is3DModelFile && file_exists($filePath)): ?>
    <!-- 3D Model Viewer -->
    <div class="model-viewer-container mt-3 mb-3">
        <div id="model-viewer-<?= $asset_id ?>" class="model-viewer">
            <div class="model-loading" style="position: absolute; z-index: 1; top: 50%; left: 50%; transform: translate(-50%, -50%);">
                <div class="loading-spinner"></div>
                <p class="loading-text">Loading 3D model...</p>
            </div>
        </div>
        <div class="model-controls">
            <button class="control-btn" onclick="resetCamera<?= $asset_id ?>()" title="Reset camera view">
                <i class="bi bi-arrow-clockwise"></i>
                <span>Reset View</span>
            </button>
            <button class="control-btn" onclick="toggleWireframe<?= $asset_id ?>()" title="Toggle wireframe mode">
                <i class="bi bi-grid-3x3"></i>
                <span>Wireframe</span>
            </button>
            <button class="control-btn autorotate-btn" onclick="toggleAutoRotate<?= $asset_id ?>()" title="Toggle auto-rotation">
                <i class="bi bi-play-circle"></i>
                <span>Auto-Rotate</span>
            </button>
            <button class="control-btn background-btn" onclick="changeBackground<?= $asset_id ?>()" title="Change background">
                <i class="bi bi-palette"></i>
                <span class="bg-label">Dark</span>
            </button>
            <button class="control-btn shadow-btn" onclick="window['changeShadowMode<?= $asset_id ?>']?.()" title="Change shadow settings">
                <i class="bi bi-brightness-high"></i>
                <span class="shadow-label">Normal</span>
            </button>
        </div>
        <div class="model-info">
            <i class="bi bi-info-circle"></i>
            <span>Mouse: rotate • Scroll: zoom • Right-click: pan</span>
        </div>
    </div>

    <!-- Three.js Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/three@0.128.0/build/three.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/controls/OrbitControls.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/loaders/GLTFLoader.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/loaders/OBJLoader.js"></script>

    <script>
// Modern 3D viewer with enhanced lighting and shadows
(function() {
    const assetId = <?= $asset_id ?>;
    let scene, camera, renderer, controls, model;
    let autoRotate = false;
    let wireframe = false;
    let mainLight, fillLight, rimLight, ground;
    
    // Modern background options
    const backgroundColors = [
        { name: 'Dark', color: 0x0f0f0f, class: 'dark' },
        { name: 'Light', color: 0xf5f5f5, class: 'light' },
        { name: 'Blue', color: 0x1e3a8a, class: 'blue' },
        { name: 'Purple', color: 0x581c87, class: 'purple' },
        { name: 'Green', color: 0x166534, class: 'green' },
        { name: 'Gradient', color: null, class: 'gradient' }
    ];
    let currentBackgroundIndex = 0;
    
    // Shadow mode options
    const shadowModes = [
        { 
            name: 'Normal', 
            config: { 
                enabled: true, 
                intensity: 0.3, 
                bias: -0.0005, 
                normalBias: 0.02,
                mapSize: 2048,
                showGround: true
            } 
        },
        { 
            name: 'Soft', 
            config: { 
                enabled: true, 
                intensity: 0.2, 
                bias: -0.001, 
                normalBias: 0.05,
                mapSize: 1024,
                showGround: true
            } 
        },
        { 
            name: 'Sharp', 
            config: { 
                enabled: true, 
                intensity: 0.5, 
                bias: 0, 
                normalBias: 0.01,
                mapSize: 4096,
                showGround: true
            } 
        },
        { 
            name: 'Minimal', 
            config: { 
                enabled: true, 
                intensity: 0.1, 
                bias: -0.002, 
                normalBias: 0.1,
                mapSize: 512,
                showGround: false
            } 
        },
        { 
            name: 'Off', 
            config: { 
                enabled: false, 
                intensity: 0, 
                bias: 0, 
                normalBias: 0,
                mapSize: 512,
                showGround: false
            } 
        }
    ];
    let currentShadowIndex = 0;

    function init3DViewer() {
        const container = document.getElementById(`model-viewer-${assetId}`);

        // Scene setup
        scene = new THREE.Scene();
        setupBackground();

        // Camera setup with better field of view
        camera = new THREE.PerspectiveCamera(
            45, // Reduced FOV for less distortion
            container.clientWidth / container.clientHeight,
            0.1,
            1000
        );
        camera.position.set(8, 6, 8);

        // Enhanced renderer setup
        renderer = new THREE.WebGLRenderer({ 
            antialias: true,
            alpha: true,
            powerPreference: "high-performance"
        });
        renderer.setSize(container.clientWidth, container.clientHeight);
        renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
        
        // Enhanced shadow settings
        renderer.shadowMap.enabled = true;
        renderer.shadowMap.type = THREE.PCFSoftShadowMap;
        renderer.shadowMap.autoUpdate = true;
        
        // Better color management
        renderer.outputEncoding = THREE.sRGBEncoding;
        renderer.toneMapping = THREE.ACESFilmicToneMapping;
        renderer.toneMappingExposure = 1.2;
        
        container.appendChild(renderer.domElement);

        // Enhanced OrbitControls
        controls = new THREE.OrbitControls(camera, renderer.domElement);
        controls.enableDamping = true;
        controls.dampingFactor = 0.05;
        controls.autoRotate = autoRotate;
        controls.autoRotateSpeed = 3.0; // Faster rotation speed
        controls.enablePan = true;
        controls.enableZoom = true;
        controls.maxDistance = 50;
        controls.minDistance = 1;

        setupLighting();
        loadModel();
        animate();
        
        window.addEventListener('resize', onWindowResize);
    }

    function setupBackground() {
        const current = backgroundColors[currentBackgroundIndex];
        if (current.name === 'Gradient') {
            // Create gradient background
            const canvas = document.createElement('canvas');
            canvas.width = 512;
            canvas.height = 512;
            const ctx = canvas.getContext('2d');
            
            const gradient = ctx.createLinearGradient(0, 0, 0, 512);
            gradient.addColorStop(0, '#1e293b');
            gradient.addColorStop(1, '#0f172a');
            
            ctx.fillStyle = gradient;
            ctx.fillRect(0, 0, 512, 512);
            
            const texture = new THREE.CanvasTexture(canvas);
            scene.background = texture;
        } else {
            scene.background = new THREE.Color(current.color);
        }
    }

    function setupLighting() {
        // Clear existing lights and ground
        scene.children = scene.children.filter(child => !child.isLight && child.material?.type !== 'ShadowMaterial');
        
        const shadowConfig = shadowModes[currentShadowIndex].config;
        
        // Ambient light for overall illumination
        const ambientLight = new THREE.AmbientLight(0xffffff, 0.3);
        scene.add(ambientLight);
        
        // Main directional light with configurable shadows
        mainLight = new THREE.DirectionalLight(0xffffff, 1.2);
        mainLight.position.set(10, 10, 5);
        mainLight.castShadow = shadowConfig.enabled;
        
        if (shadowConfig.enabled) {
            // Enhanced shadow settings based on current mode
            mainLight.shadow.mapSize.width = shadowConfig.mapSize;
            mainLight.shadow.mapSize.height = shadowConfig.mapSize;
            mainLight.shadow.camera.near = 0.1;
            mainLight.shadow.camera.far = 50;
            mainLight.shadow.camera.left = -10;
            mainLight.shadow.camera.right = 10;
            mainLight.shadow.camera.top = 10;
            mainLight.shadow.camera.bottom = -10;
            mainLight.shadow.bias = shadowConfig.bias;
            mainLight.shadow.normalBias = shadowConfig.normalBias;
        }
        
        scene.add(mainLight);
        
        // Fill light from opposite side
        fillLight = new THREE.DirectionalLight(0xffffff, 0.4);
        fillLight.position.set(-5, 3, -5);
        scene.add(fillLight);
        
        // Rim light for edge definition
        rimLight = new THREE.DirectionalLight(0xffffff, 0.6);
        rimLight.position.set(0, 5, -10);
        scene.add(rimLight);
        
        // Add ground plane for shadows (configurable)
        if (shadowConfig.showGround && shadowConfig.enabled) {
            const groundGeometry = new THREE.PlaneGeometry(20, 20);
            const groundMaterial = new THREE.ShadowMaterial({ 
                opacity: shadowConfig.intensity,
                transparent: true 
            });
            ground = new THREE.Mesh(groundGeometry, groundMaterial);
            ground.rotation.x = -Math.PI / 2;
            ground.position.y = -2;
            ground.receiveShadow = true;
            scene.add(ground);
        }
        
        // Update renderer shadow settings
        renderer.shadowMap.enabled = shadowConfig.enabled;
    }

    // Global functions for controls
    window[`changeBackground${assetId}`] = function() {
        currentBackgroundIndex = (currentBackgroundIndex + 1) % backgroundColors.length;
        const currentBg = backgroundColors[currentBackgroundIndex];
        
        setupBackground();
        
        // Update button label
        const bgLabel = document.querySelector(`#model-viewer-${assetId}`).parentElement.querySelector('.bg-label');
        bgLabel.textContent = currentBg.name;
        
        // Update container theme
        const container = document.querySelector(`#model-viewer-${assetId}`).parentElement;
        container.className = container.className.replace(/\s(dark|light|blue|purple|green|gradient)-theme/g, '');
        container.classList.add(`${currentBg.class}-theme`);
    };

    window[`changeShadowMode${assetId}`] = function() {
        currentShadowIndex = (currentShadowIndex + 1) % shadowModes.length;
        const currentShadow = shadowModes[currentShadowIndex];
        
        console.log('Changing shadow mode to:', currentShadow.name); // Debug log
        
        setupLighting();
        
        // Update button label
        const container = document.querySelector(`#model-viewer-${assetId}`).parentElement;
        const shadowLabel = container.querySelector('.shadow-label');
        if (shadowLabel) {
            shadowLabel.textContent = currentShadow.name;
        }
        
        // Update shadow icon based on mode
        const shadowBtn = container.querySelector('.shadow-btn i');
        if (shadowBtn) {
            const iconMap = {
                'Normal': 'bi-brightness-high',
                'Soft': 'bi-brightness-alt-high',
                'Sharp': 'bi-sun',
                'Minimal': 'bi-brightness-low',
                'Off': 'bi-eye-slash'
            };
            shadowBtn.className = `bi ${iconMap[currentShadow.name]}`;
        }
        
        // Re-apply shadows to model if it exists
        if (model) {
            const shadowConfig = shadowModes[currentShadowIndex].config;
            model.traverse((child) => {
                if (child.isMesh) {
                    child.castShadow = shadowConfig.enabled;
                    child.receiveShadow = shadowConfig.enabled;
                }
            });
        }
        
        // Force a render update
        if (renderer && scene && camera) {
            renderer.render(scene, camera);
        }
    };

    window[`resetCamera${assetId}`] = function() {
        controls.reset();
        camera.position.set(8, 6, 8);
        camera.lookAt(0, 0, 0);
        controls.update();
    };

    window[`toggleWireframe${assetId}`] = function() {
        wireframe = !wireframe;
        scene.traverse(child => {
            if (child.isMesh && child.material && child !== scene.children.find(c => c.material && c.material.type === 'ShadowMaterial')) {
                if (Array.isArray(child.material)) {
                    child.material.forEach(mat => mat.wireframe = wireframe);
                } else {
                    child.material.wireframe = wireframe;
                }
            }
        });
        
        const button = event.target.closest('button');
        const span = button.querySelector('span');
        span.textContent = wireframe ? 'Solid' : 'Wireframe';
    };

    window[`toggleAutoRotate${assetId}`] = function() {
        autoRotate = !autoRotate;
        controls.autoRotate = autoRotate;
        
        const button = document.querySelector(`#model-viewer-${assetId}`).parentElement.querySelector('.autorotate-btn');
        const icon = button.querySelector('i');
        const span = button.querySelector('span');
        
        icon.className = `bi ${autoRotate ? 'bi-pause-circle' : 'bi-play-circle'}`;
        span.textContent = autoRotate ? 'Stop Rotate' : 'Auto-Rotate';
    };

    function onWindowResize() {
        const container = document.getElementById(`model-viewer-${assetId}`);
        camera.aspect = container.clientWidth / container.clientHeight;
        camera.updateProjectionMatrix();
        renderer.setSize(container.clientWidth, container.clientHeight);
    }

    function loadModel() {
        const modelPath = '<?= htmlspecialchars($asset['asset_file']) ?>';
        const extension = modelPath.split('.').pop().toLowerCase();
        const loadingElement = document.querySelector(`#model-viewer-<?= $asset_id ?> .model-loading`);
        
        let loader;
        switch(extension) {
            case 'gltf':
            case 'glb':
                loader = new THREE.GLTFLoader();
                break;
            case 'obj':
                loader = new THREE.OBJLoader();
                break;
            default:
                loadingElement.innerHTML = `<div class="error-message">Unsupported format: .${extension}</div>`;
                return;
        }

        loader.load(
            modelPath,
            (object) => {
                loadingElement.style.display = 'none';
                
                let loadedModel = (extension === 'gltf' || extension === 'glb') ? object.scene : object;

                // Enhanced shadow and material setup
                const shadowConfig = shadowModes[currentShadowIndex].config;
                loadedModel.traverse((child) => {
                    if (child.isMesh) {
                        child.castShadow = shadowConfig.enabled;
                        child.receiveShadow = shadowConfig.enabled;
                        
                        // Enhance materials
                        if (child.material) {
                            if (Array.isArray(child.material)) {
                                child.material.forEach(mat => {
                                    mat.shadowSide = THREE.DoubleSide;
                                });
                            } else {
                                child.material.shadowSide = THREE.DoubleSide;
                            }
                        }
                    }
                });

                // Center and scale model
                const box = new THREE.Box3().setFromObject(loadedModel);
                const center = box.getCenter(new THREE.Vector3());
                const size = box.getSize(new THREE.Vector3());
                
                loadedModel.position.sub(center);
                
                // Scale model to fit nicely in view
                const maxDim = Math.max(size.x, size.y, size.z);
                if (maxDim > 5) {
                    const scale = 5 / maxDim;
                    loadedModel.scale.setScalar(scale);
                }

                scene.add(loadedModel);
                model = loadedModel;
                
                // Adjust camera
                const scaledSize = size.clone().multiplyScalar(loadedModel.scale.x);
                const maxScaledDim = Math.max(scaledSize.x, scaledSize.y, scaledSize.z);
                const distance = maxScaledDim * 2.5;
                camera.position.set(distance, distance * 0.75, distance);
                camera.lookAt(0, 0, 0);
                controls.update();
            },
            (progress) => {
                if (progress.lengthComputable) {
                    const percent = Math.round((progress.loaded / progress.total) * 100);
                    loadingElement.querySelector('.loading-text').textContent = `Loading... ${percent}%`;
                }
            },
            (error) => {
                console.error('Model loading error:', error);
                loadingElement.innerHTML = `<div class="error-message">Failed to load model</div>`;
            }
        );
    }

    function animate() {
        requestAnimationFrame(animate);
        controls.update();
        renderer.render(scene, camera);
    }

    // Initialize
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init3DViewer);
    } else {
        init3DViewer();
    }
})();
    </script>

    <style>
        .model-viewer-container {
            width: 100%;
            max-width: 900px;
            margin: 0 auto;
            border-radius: 16px;
            overflow: hidden;
            background: linear-gradient(145deg, #1a1a1a, #2d2d2d);
            box-shadow: 
                0 20px 40px rgba(0, 0, 0, 0.4),
                0 10px 20px rgba(0, 0, 0, 0.2),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .model-viewer-container:hover {
            box-shadow: 
                0 25px 50px rgba(0, 0, 0, 0.5),
                0 15px 30px rgba(0, 0, 0, 0.3),
                inset 0 1px 0 rgba(255, 255, 255, 0.15);
        }
        
        .model-viewer {
            height: 500px;
            position: relative;
            background: radial-gradient(circle at center, rgba(255, 255, 255, 0.05) 0%, transparent 70%);
        }
        
        .model-viewer canvas {
            display: block;
            width: 100% !important;
            height: 100% !important;
            border-radius: 16px 16px 0 0;
        }
        
        .model-loading {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: rgba(0, 0, 0, 0.9);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            padding: 30px;
            min-width: 200px;
        }
        
        .loading-spinner {
            width: 40px;
            height: 40px;
            border: 3px solid rgba(255, 255, 255, 0.1);
            border-top: 3px solid #4f46e5;
            border-radius: 50%;
            animation: spin 1s cubic-bezier(0.68, -0.55, 0.265, 1.55) infinite;
            margin-bottom: 15px;
        }
        
        .loading-text {
            color: rgba(255, 255, 255, 0.9);
            font-size: 14px;
            font-weight: 500;
            margin: 0;
            text-align: center;
        }
        
        .error-message {
            color: #ef4444;
            background: rgba(239, 68, 68, 0.1);
            padding: 15px;
            border-radius: 8px;
            border: 1px solid rgba(239, 68, 68, 0.3);
            text-align: center;
        }
        
        .model-controls {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            padding: 16px 20px;
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(20px);
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            flex-wrap: wrap;
        }
        
        .control-btn {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 8px 12px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            color: rgba(255, 255, 255, 0.9);
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            backdrop-filter: blur(10px);
        }
        
        .control-btn:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.2);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }
        
        .control-btn:active {
            transform: translateY(0);
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
        }
        
        .control-btn i {
            font-size: 14px;
            opacity: 0.9;
        }
        
        .model-info {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 20px;
            background: rgba(0, 0, 0, 0.6);
            color: rgba(255, 255, 255, 0.7);
            font-size: 12px;
            text-align: center;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .model-info i {
            opacity: 0.8;
        }
        
        /* Theme variations */
        .model-viewer-container.light-theme {
            background: linear-gradient(145deg, #ffffff, #f8fafc);
            border-color: rgba(0, 0, 0, 0.1);
        }
        
        .model-viewer-container.light-theme .model-controls {
            background: rgba(255, 255, 255, 0.9);
            border-top-color: rgba(0, 0, 0, 0.1);
        }
        
        .model-viewer-container.light-theme .control-btn {
            background: rgba(0, 0, 0, 0.05);
            border-color: rgba(0, 0, 0, 0.1);
            color: rgba(0, 0, 0, 0.8);
        }
        
        .model-viewer-container.light-theme .control-btn:hover {
            background: rgba(0, 0, 0, 0.1);
            border-color: rgba(0, 0, 0, 0.2);
        }
        
        .model-viewer-container.light-theme .model-info {
            background: rgba(255, 255, 255, 0.8);
            color: rgba(0, 0, 0, 0.6);
            border-top-color: rgba(0, 0, 0, 0.05);
        }
        
        .model-viewer-container.blue-theme {
            background: linear-gradient(145deg, #1e3a8a, #3b82f6);
        }
        
        .model-viewer-container.purple-theme {
            background: linear-gradient(145deg, #581c87, #a855f7);
        }
        
        .model-viewer-container.green-theme {
            background: linear-gradient(145deg, #166534, #22c55e);
        }
        
        .model-viewer-container.gradient-theme {
            background: linear-gradient(145deg, #667eea, #764ba2);
        }
        
        /* Responsive design */
        @media (max-width: 768px) {
            .model-viewer-container {
                width: 100%;
                margin: 0;
                border-radius: 12px;
            }
            
            .model-viewer {
                height: 350px;
            }
            
            .model-controls {
                padding: 12px 16px;
                gap: 6px;
            }
            
            .control-btn {
                padding: 6px 10px;
                font-size: 12px;
            }
            
            .control-btn span {
                display: none;
            }
            
            .model-info {
                padding: 10px 16px;
                font-size: 11px;
            }
        }
        
        @media (max-width: 480px) {
            .model-controls {
                justify-content: space-between;
            }
            
            .control-btn {
                flex: 1;
                justify-content: center;
                min-width: 0;
            }
        }
        
        /* Animations */
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Focus styles for accessibility */
        .control-btn:focus {
            outline: 2px solid #4f46e5;
            outline-offset: 2px;
        }
        
        /* Smooth transitions */
        * {
            box-sizing: border-box;
        }
    </style>
<?php elseif ($asset['category_id'] == 6 && in_array(strtolower($fileExtension), ['jpg', 'jpeg', 'png', 'bmp', 'gif', 'tga', 'hdr'])): ?>
    <!-- Texture Viewer -->
    <div class="texture-viewer-container mt-3 mb-3">
        <div id="texture-viewer-<?= $asset_id ?>" class="model-viewer">
            <div class="model-loading" style="position: absolute; z-index: 1; top: 50%; left: 50%; transform: translate(-50%, -50%);">
                <div class="loading-spinner"></div>
                <p class="loading-text">Loading texture...</p>
            </div>
        </div>
        <div class="model-controls">
            <button class="control-btn" onclick="resetCamera<?= $asset_id ?>()" title="Reset camera view">
                <i class="bi bi-arrow-clockwise"></i>
                <span>Reset View</span>
            </button>
            <button class="control-btn" onclick="toggleWireframe<?= $asset_id ?>()" title="Toggle wireframe mode">
                <i class="bi bi-grid-3x3"></i>
                <span>Wireframe</span>
            </button>
            <button class="control-btn autorotate-btn" onclick="toggleAutoRotate<?= $asset_id ?>()" title="Toggle auto-rotation">
                <i class="bi bi-play-circle"></i>
                <span>Auto-Rotate</span>
            </button>
            <!-- Add this new button -->
            <button class="control-btn background-btn" onclick="changeBackground<?= $asset_id ?>()" title="Change background">
                <i class="bi bi-palette"></i>
                <span class="bg-label">Dark</span>
            </button>
        </div>
        <div class="model-info">
            <i class="bi bi-info-circle"></i>
            <span>Mouse: rotate • Scroll: zoom • Right-click: pan</span>
        </div>
    </div>

    <!-- Add THREE.js dependency -->
    <script src="https://cdn.jsdelivr.net/npm/three@0.128.0/build/three.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/controls/OrbitControls.js"></script>

    <script>
    (function() {
        // Add these color options at the top
        const backgroundColors = [
            { name: 'Dark', color: 0x1a1a1a, class: 'dark' },
            { name: 'Light', color: 0xf5f5f5, class: 'light' },
            { name: 'Blue', color: 0x1e3a8a, class: 'blue' },
            { name: 'Purple', color: 0x581c87, class: 'purple' },
            { name: 'Green', color: 0x166534, class: 'green' },
            { name: 'Gradient', color: null, class: 'gradient' }
        ];
        let currentBackgroundIndex = 0;
        const assetId = <?= $asset_id ?>;
        let scene, camera, renderer, controls, sphere;
        let autoRotate = false;
        let wireframe = false;
        
        function initTextureViewer() {
            const container = document.getElementById(`texture-viewer-${assetId}`);
            if (!container) return;
            
            // Scene setup
            scene = new THREE.Scene();
            scene.background = new THREE.Color(0x1a1a1a);
            
            // Camera setup
            camera = new THREE.PerspectiveCamera(
                45,
                container.clientWidth / container.clientHeight,
                0.1,
                1000
            );
            camera.position.set(5, 5, 5);
            
            // Renderer setup
            renderer = new THREE.WebGLRenderer({ antialias: true });
            renderer.setSize(container.clientWidth, container.clientHeight);
            container.appendChild(renderer.domElement);
            
            // Controls
            controls = new THREE.OrbitControls(camera, renderer.domElement);
            controls.enableDamping = true;
            controls.dampingFactor = 0.05;
            
            // Lighting
            const ambientLight = new THREE.AmbientLight(0xffffff, 0.5);
            scene.add(ambientLight);

            // Set initial background
            scene.background = new THREE.Color(backgroundColors[currentBackgroundIndex].color);
            
            const directionalLight = new THREE.DirectionalLight(0xffffff, 1);
            directionalLight.position.set(5, 5, 5);
            scene.add(directionalLight);
            
            // Load texture
            const textureLoader = new THREE.TextureLoader();
            textureLoader.load(
                '<?= htmlspecialchars($asset['asset_file']) ?>',
                (texture) => {
                    const loadingElement = document.querySelector(`#texture-viewer-${assetId} .model-loading`);
                    if (loadingElement) loadingElement.style.display = 'none';
                    
                    // Create sphere
                    const geometry = new THREE.SphereGeometry(3, 64, 64);
                    const material = new THREE.MeshStandardMaterial({
                        map: texture,
                        metalness: 0.3,
                        roughness: 0.4
                    });
                    
                    sphere = new THREE.Mesh(geometry, material);
                    scene.add(sphere);
                },
                undefined,
                (err) => {
                    console.error('Error loading texture:', err);
                    const loadingElement = document.querySelector(`#texture-viewer-${assetId} .model-loading`);
                    if (loadingElement) loadingElement.innerHTML = 
                        '<div class="error-message">Failed to load texture</div>';
                }
            );
            
            // Handle window resize
            window.addEventListener('resize', () => {
                camera.aspect = container.clientWidth / container.clientHeight;
                camera.updateProjectionMatrix();
                renderer.setSize(container.clientWidth, container.clientHeight);
            });
            
            animate();
        }
        
        function animate() {
            requestAnimationFrame(animate);
            if (controls) controls.update();
            if (renderer && scene && camera) renderer.render(scene, camera);
        }
        
        // Control functions
        window[`resetCamera${assetId}`] = function() {
            if (controls && camera) {
                controls.reset();
                camera.position.set(5, 5, 5);
                controls.update();
            }
        };
        
        window[`toggleWireframe${assetId}`] = function() {
            wireframe = !wireframe;
            if (sphere) {
                sphere.material.wireframe = wireframe;
            }
        };
        
       window[`toggleAutoRotate${assetId}`] = function() {
            autoRotate = !autoRotate;
            if (controls) controls.autoRotate = autoRotate;
            
            const button = document.querySelector(`#texture-viewer-${assetId}`).parentElement.querySelector('.autorotate-btn');
            const icon = button.querySelector('i');
            const span = button.querySelector('span');
            
            icon.className = `bi ${autoRotate ? 'bi-pause-circle' : 'bi-play-circle'}`;
            span.textContent = autoRotate ? 'Stop Rotate' : 'Auto-Rotate';
        };

        // Add this new function for background changing
        window[`changeBackground${assetId}`] = function() {
            currentBackgroundIndex = (currentBackgroundIndex + 1) % backgroundColors.length;
            const currentBg = backgroundColors[currentBackgroundIndex];
            
            if (currentBg.name === 'Gradient') {
                // Create gradient texture
                const canvas = document.createElement('canvas');
                canvas.width = 512;
                canvas.height = 512;
                const ctx = canvas.getContext('2d');
                const gradient = ctx.createLinearGradient(0, 0, 0, 512);
                gradient.addColorStop(0, '#1e293b');
                gradient.addColorStop(1, '#0f172a');
                ctx.fillStyle = gradient;
                ctx.fillRect(0, 0, 512, 512);
                scene.background = new THREE.CanvasTexture(canvas);
            } else {
                scene.background = new THREE.Color(currentBg.color);
            }
            
            // Update button label
            const bgLabel = document.querySelector(`#texture-viewer-${assetId}`).parentElement.querySelector('.bg-label');
            bgLabel.textContent = currentBg.name;
            
            // Update container theme
            const container = document.querySelector(`.texture-viewer-container`);
            container.className = container.className.replace(/\s(dark|light|blue|purple|green|gradient)-theme/g, '');
            container.classList.add(`${currentBg.class}-theme`);
        };
        
        // Initialize when document is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initTextureViewer);
        } else {
            initTextureViewer();
        }
    })();
    </script>
<?php elseif ($asset['category_id'] == 8): ?>
<div class="texture-viewer-container mt-3 mb-3 dark-theme">
    <div id="animation-viewer-<?= $asset_id ?>" class="model-viewer">
        <div class="model-loading" style="position: absolute; z-index: 1; top: 50%; left: 50%; transform: translate(-50%, -50%);">
            <div class="loading-spinner"></div>
            <p class="loading-text">Loading animation...</p>
        </div>
    </div>
    <div class="model-controls">
        <button class="control-btn" onclick="resetCameraAnim<?= $asset_id ?>()" title="Reset camera view">
            <i class="bi bi-arrow-clockwise"></i>
            <span>Reset View</span>
        </button>
        <button class="control-btn autorotate-btn" onclick="toggleAutoRotateAnim<?= $asset_id ?>()" title="Toggle auto-rotation">
            <i class="bi bi-play-circle"></i>
            <span>Auto-Rotate</span>
        </button>
        <button class="control-btn anim-playpause-btn" onclick="toggleAnimation<?= $asset_id ?>()" title="Play/Pause Animation">
            <i class="bi bi-pause-circle"></i>
            <span>Pause</span>
        </button>
        <button class="control-btn background-btn" onclick="changeBackground<?= $asset_id ?>()" title="Change background">
            <i class="bi bi-palette"></i>
            <span class="bg-label">Dark</span>
        </button>
    </div>
    <div class="model-controls">
        <input id="anim-timeline-<?= $asset_id ?>" type="range" min="0" max="1" step="0.001" value="0" style="width: 100%;" title="Animation Timeline" />
    </div>
    <div class="model-info">
        <i class="bi bi-info-circle"></i>
        <span>Mouse: rotate • Scroll: zoom • Right-click: pan</span>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/three@0.128.0/build/three.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/controls/OrbitControls.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fflate@0.7.4/umd/index.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/loaders/FBXLoader.js"></script>

<script>
(function() {
    const assetId = <?= $asset_id ?>;
    let scene, camera, renderer, controls, mixer, clock;
    let animationAction = null;
    let autoRotate = false;
    let isPlaying = true;
    let currentModel = null;

    const backgroundColors = [
        { name: 'Dark', color: 0x1a1a1a, class: 'dark' },
        { name: 'Light', color: 0xf5f5f5, class: 'light' },
        { name: 'Blue', color: 0x1e3a8a, class: 'blue' },
        { name: 'Purple', color: 0x581c87, class: 'purple' },
        { name: 'Green', color: 0x166534, class: 'green' },
        { name: 'Gradient', color: null, class: 'gradient' }
    ];
    let currentBackgroundIndex = 0;

    function initAnimationViewer() {
        const container = document.getElementById(`animation-viewer-${assetId}`);
        if (!container) return;

        clock = new THREE.Clock();

        scene = new THREE.Scene();
        scene.background = new THREE.Color(backgroundColors[0].color);

        camera = new THREE.PerspectiveCamera(45, container.clientWidth / container.clientHeight, 0.1, 1000);
        camera.position.set(5, 5, 5);

        renderer = new THREE.WebGLRenderer({ antialias: true });
        renderer.setSize(container.clientWidth, container.clientHeight);
        container.appendChild(renderer.domElement);

        controls = new THREE.OrbitControls(camera, renderer.domElement);
        controls.enableDamping = true;
        controls.dampingFactor = 0.05;

        scene.add(new THREE.AmbientLight(0xffffff, 0.6));
        const directionalLight = new THREE.DirectionalLight(0xffffff, 1);
        directionalLight.position.set(5, 10, 7.5);
        scene.add(directionalLight);

        const loader = new THREE.FBXLoader();
        loader.load(
            '<?= htmlspecialchars($asset['asset_file']) ?>',
            function(object) {
                const loading = container.querySelector('.model-loading');
                if (loading) loading.style.display = 'none';

                currentModel = object;

                const box = new THREE.Box3().setFromObject(object);
                const size = box.getSize(new THREE.Vector3()).length();
                const center = box.getCenter(new THREE.Vector3());
                controls.target.copy(center);
                camera.position.copy(center).add(new THREE.Vector3(size / 2, size / 2, size / 2));
                camera.lookAt(center);
                controls.update();

                mixer = new THREE.AnimationMixer(object);
                if (object.animations.length > 0) {
                    animationAction = mixer.clipAction(object.animations[0]);
                    animationAction.play();
                    isPlaying = true;
                }

                scene.add(object);
            },
            undefined,
            function(error) {
                console.error('Error loading FBX animation:', error);
                container.querySelector('.model-loading').innerHTML = '<div class="error-message">Failed to load animation</div>';
            }
        );

        window.addEventListener('resize', () => {
            camera.aspect = container.clientWidth / container.clientHeight;
            camera.updateProjectionMatrix();
            renderer.setSize(container.clientWidth, container.clientHeight);
        });

        animate();
    }

    function animate() {
        requestAnimationFrame(animate);
        const delta = clock.getDelta();
        if (mixer && isPlaying) {
            mixer.update(delta);
            updateTimeline();
        }
        controls.autoRotate = autoRotate;
        controls.update();
        renderer.render(scene, camera);
    }

    function updateTimeline() {
        const input = document.getElementById(`anim-timeline-${assetId}`);
        if (animationAction && input && animationAction._clip) {
            const duration = animationAction._clip.duration;
            const time = animationAction.time % duration;
            input.value = time / duration;
        }
    }

    const scrubInput = document.getElementById(`anim-timeline-${assetId}`);
    if (scrubInput) {
        scrubInput.addEventListener('input', function() {
            if (animationAction && animationAction._clip) {
                const duration = animationAction._clip.duration;
                const newTime = parseFloat(this.value) * duration;
                animationAction.time = newTime;
                animationAction.paused = true;
                isPlaying = false;
                const btn = document.querySelector(`#animation-viewer-${assetId}`).parentElement.querySelector('.anim-playpause-btn');
                btn.querySelector('i').className = 'bi bi-play-circle';
                btn.querySelector('span').textContent = 'Play';
            }
        });
    }

    window[`resetCameraAnim${assetId}`] = function() {
        if (!currentModel) return;
        const box = new THREE.Box3().setFromObject(currentModel);
        const size = box.getSize(new THREE.Vector3()).length();
        const center = box.getCenter(new THREE.Vector3());
        controls.target.copy(center);
        camera.position.copy(center).add(new THREE.Vector3(size / 2, size / 2, size / 2));
        camera.lookAt(center);
        controls.update();
    };

    window[`toggleAutoRotateAnim${assetId}`] = function() {
        autoRotate = !autoRotate;
        const btn = document.querySelector(`#animation-viewer-${assetId}`).parentElement.querySelector('.autorotate-btn');
        btn.querySelector('i').className = `bi ${autoRotate ? 'bi-pause-circle' : 'bi-play-circle'}`;
        btn.querySelector('span').textContent = autoRotate ? 'Stop Rotate' : 'Auto-Rotate';
    };

    window[`toggleAnimation${assetId}`] = function() {
        if (!animationAction) return;
        if (isPlaying) {
            animationAction.paused = true;
        } else {
            animationAction.paused = false;
            animationAction.play();
        }
        isPlaying = !isPlaying;

        const btn = document.querySelector(`#animation-viewer-${assetId}`).parentElement.querySelector('.anim-playpause-btn');
        btn.querySelector('i').className = `bi ${isPlaying ? 'bi-pause-circle' : 'bi-play-circle'}`;
        btn.querySelector('span').textContent = isPlaying ? 'Pause' : 'Play';
    };

    window[`changeBackground${assetId}`] = function() {
        currentBackgroundIndex = (currentBackgroundIndex + 1) % backgroundColors.length;
        const currentBg = backgroundColors[currentBackgroundIndex];

        if (currentBg.name === 'Gradient') {
            const canvas = document.createElement('canvas');
            canvas.width = 512;
            canvas.height = 512;
            const ctx = canvas.getContext('2d');
            const gradient = ctx.createLinearGradient(0, 0, 0, 512);
            gradient.addColorStop(0, '#1e293b');
            gradient.addColorStop(1, '#0f172a');
            ctx.fillStyle = gradient;
            ctx.fillRect(0, 0, 512, 512);
            scene.background = new THREE.CanvasTexture(canvas);
        } else {
            scene.background = new THREE.Color(currentBg.color);
        }

        const container = document.querySelector(`#animation-viewer-${assetId}`).closest('.texture-viewer-container');
        container.className = container.className.replace(/\b(dark|light|blue|purple|green|gradient)-theme\b/, '');
        container.classList.add(`${currentBg.class}-theme`);

        const label = container.querySelector('.bg-label');
        if (label) label.textContent = currentBg.name;
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAnimationViewer);
    } else {
        initAnimationViewer();
    }
})();
</script>
<?php elseif (in_array($asset['category_id'], [10, 11, 12])): ?>
    <!-- Audio Player with Visualizer -->
    <div class="texture-viewer-container audio-player-container mt-3 mb-3 dark-theme">
        <div id="audio-viewer-<?= $asset_id ?>" class="model-viewer audio-viewer">
            <canvas id="audio-canvas-<?= $asset_id ?>" class="audio-canvas"></canvas>
            <div class="audio-loading" style="position: absolute; z-index: 1; top: 50%; left: 50%; transform: translate(-50%, -50%);">
                <div class="loading-spinner"></div>
                <p class="loading-text">Loading audio...</p>
            </div>
        </div>
        
        <!-- Audio Controls -->
        <div class="model-controls audio-controls">
            <button class="control-btn play-pause-btn" onclick="togglePlayPause<?= $asset_id ?>()" title="Play/Pause">
                <i class="bi bi-play-circle"></i>
                <span>Play</span>
            </button>
            <button class="control-btn" onclick="stopAudio<?= $asset_id ?>()" title="Stop">
                <i class="bi bi-stop-circle"></i>
                <span>Stop</span>
            </button>
            <button class="control-btn volume-btn" onclick="toggleMute<?= $asset_id ?>()" title="Mute/Unmute">
                <i class="bi bi-volume-up"></i>
                <span>Volume</span>
            </button>
            <button class="control-btn background-btn" onclick="changeBackgroundAudio<?= $asset_id ?>()" title="Change background">
                <i class="bi bi-palette"></i>
                <span class="bg-label">Dark</span>
            </button>
        </div>
        
        <!-- Progress and Volume Controls -->
        <div class="model-controls audio-progress-controls">
            <div class="progress-container">
                <span class="time-display current-time">0:00</span>
                <input id="audio-progress-<?= $asset_id ?>" type="range" min="0" max="100" value="0" class="progress-slider" title="Audio Progress" />
                <span class="time-display total-time">0:00</span>
            </div>
            <div class="volume-container">
                <i class="bi bi-volume-down"></i>
                <input id="audio-volume-<?= $asset_id ?>" type="range" min="0" max="100" value="70" class="volume-slider" title="Volume" />
                <i class="bi bi-volume-up"></i>
            </div>
        </div>
        
        <div class="model-info">
            <i class="bi bi-music-note"></i>
            <span id="audio-info-<?= $asset_id ?>">Audio Player • Click play to start</span>
        </div>
    </div>

    <script>
    (function() {
        const assetId = <?= $asset_id ?>;
        const audioUrl = '<?= htmlspecialchars($asset['asset_file']) ?>';
        
        let audio, audioContext, analyser, dataArray, source;
        let canvas, canvasContext;
        let isPlaying = false;
        let isMuted = false;
        let animationId;
        
        const backgroundColors = [
            { name: 'Dark', color: '#1a1a1a', class: 'dark' },
            { name: 'Light', color: '#f5f5f5', class: 'light' },
            { name: 'Blue', color: '#1e3a8a', class: 'blue' },
            { name: 'Purple', color: '#581c87', class: 'purple' },
            { name: 'Green', color: '#166534', class: 'green' },
            { name: 'Gradient', color: 'linear-gradient(45deg, #667eea, #764ba2)', class: 'gradient' }
        ];
        let currentBackgroundIndex = 0;
        
        function initAudioPlayer() {
            const container = document.getElementById(`audio-viewer-${assetId}`);
            if (!container) return;
            
            canvas = document.getElementById(`audio-canvas-${assetId}`);
            canvasContext = canvas.getContext('2d');
            
            // Set canvas size
            canvas.width = container.clientWidth;
            canvas.height = container.clientHeight;
            
            // Create audio element
            audio = new Audio();
            audio.crossOrigin = "anonymous";
            audio.src = audioUrl;
            audio.volume = 0.7;
            
            // Setup Web Audio API
            try {
                audioContext = new (window.AudioContext || window.webkitAudioContext)();
                analyser = audioContext.createAnalyser();
                analyser.fftSize = 512;
                analyser.smoothingTimeConstant = 0.8;
                
                source = audioContext.createMediaElementSource(audio);
                source.connect(analyser);
                analyser.connect(audioContext.destination);
                
                dataArray = new Uint8Array(analyser.frequencyBinCount);
                
                console.log('Web Audio API initialized successfully');
            } catch (e) {
                console.warn('Web Audio API not supported:', e);
            }
            
            // Audio event listeners
            audio.addEventListener('loadedmetadata', () => {
                const loading = container.querySelector('.audio-loading');
                if (loading) loading.style.display = 'none';
                
                updateTimeDisplay();
                updateAudioInfo();
            });
            
            audio.addEventListener('timeupdate', updateProgress);
            audio.addEventListener('ended', () => {
                isPlaying = false;
                updatePlayButton();
                if (animationId) {
                    cancelAnimationFrame(animationId);
                    animationId = null;
                }
            });
            
            audio.addEventListener('error', (e) => {
                console.error('Audio loading error:', e);
                const loading = container.querySelector('.audio-loading');
                if (loading) loading.innerHTML = '<div class="error-message">Failed to load audio</div>';
            });
            
            // Progress slider
            const progressSlider = document.getElementById(`audio-progress-${assetId}`);
            progressSlider.addEventListener('input', () => {
                if (audio.duration) {
                    audio.currentTime = (progressSlider.value / 100) * audio.duration;
                }
            });
            
            // Volume slider
            const volumeSlider = document.getElementById(`audio-volume-${assetId}`);
            volumeSlider.addEventListener('input', () => {
                audio.volume = volumeSlider.value / 100;
                updateVolumeButton();
            });
            
            // Start visualization
            visualize();
            
            // Handle window resize
            window.addEventListener('resize', () => {
                canvas.width = container.clientWidth;
                canvas.height = container.clientHeight;
            });
        }
        
        function visualize() {
            animationId = requestAnimationFrame(visualize);
            
            // Clear canvas with background color
            canvasContext.fillStyle = getCanvasBackground();
            canvasContext.fillRect(0, 0, canvas.width, canvas.height);
            
            if (!analyser || !dataArray) {
                return;
            }
            
            analyser.getByteFrequencyData(dataArray);
            
            const bufferLength = analyser.frequencyBinCount;
            const barWidth = (canvas.width / bufferLength) * 2.5;
            let barHeight;
            let x = 0;
            
            for (let i = 0; i < bufferLength; i++) {
                barHeight = (dataArray[i] / 255) * canvas.height * 0.7;
                
                // Create gradient for bars
                const gradient = canvasContext.createLinearGradient(0, canvas.height, 0, canvas.height - barHeight);
                gradient.addColorStop(0, getCurrentThemeColor());
                gradient.addColorStop(1, getCurrentThemeColorSecondary());
                
                canvasContext.fillStyle = gradient;
                canvasContext.fillRect(x, canvas.height - barHeight, barWidth, barHeight);
                
                x += barWidth + 1;
            }
            
            // Add subtle glow effect
            canvasContext.shadowColor = getCurrentThemeColor();
            canvasContext.shadowBlur = 10;
            canvasContext.strokeStyle = getCurrentThemeColor();
            canvasContext.lineWidth = 1;
            canvasContext.beginPath();
            canvasContext.moveTo(0, canvas.height - 2);
            canvasContext.lineTo(canvas.width, canvas.height - 2);
            canvasContext.stroke();
            canvasContext.shadowBlur = 0;
        }
        
        function getCanvasBackground() {
            const theme = backgroundColors[currentBackgroundIndex];
            if (theme.class === 'gradient') {
                return '#2d3748';
            }
            return theme.color;
        }
        
        function getCurrentThemeColor() {
            const theme = backgroundColors[currentBackgroundIndex];
            switch (theme.class) {
                case 'light': return '#3b82f6';
                case 'blue': return '#60a5fa';
                case 'purple': return '#c084fc';
                case 'green': return '#4ade80';
                case 'gradient': return '#667eea';
                default: return '#06b6d4';
            }
        }
        
        function getCurrentThemeColorSecondary() {
            const theme = backgroundColors[currentBackgroundIndex];
            switch (theme.class) {
                case 'light': return '#1e40af';
                case 'blue': return '#3b82f6';
                case 'purple': return '#a855f7';
                case 'green': return '#22c55e';
                case 'gradient': return '#764ba2';
                default: return '#0891b2';
            }
        }
        
        function updateProgress() {
            if (audio.duration) {
                const progress = (audio.currentTime / audio.duration) * 100;
                document.getElementById(`audio-progress-${assetId}`).value = progress;
                updateTimeDisplay();
            }
        }
        
        function updateTimeDisplay() {
            const currentTime = formatTime(audio.currentTime || 0);
            const totalTime = formatTime(audio.duration || 0);
            
            const container = document.getElementById(`audio-viewer-${assetId}`).parentElement;
            container.querySelector('.current-time').textContent = currentTime;
            container.querySelector('.total-time').textContent = totalTime;
        }
        
        function updateAudioInfo() {
            const info = document.getElementById(`audio-info-${assetId}`);
            const fileName = audioUrl.split('/').pop();
            info.textContent = `${fileName} • ${formatTime(audio.duration || 0)}`;
        }
        
        function formatTime(seconds) {
            const mins = Math.floor(seconds / 60);
            const secs = Math.floor(seconds % 60);
            return `${mins}:${secs.toString().padStart(2, '0')}`;
        }
        
        function updatePlayButton() {
            const btn = document.querySelector(`#audio-viewer-${assetId}`).parentElement.querySelector('.play-pause-btn');
            const icon = btn.querySelector('i');
            const span = btn.querySelector('span');
            
            icon.className = `bi ${isPlaying ? 'bi-pause-circle' : 'bi-play-circle'}`;
            span.textContent = isPlaying ? 'Pause' : 'Play';
        }
        
        function updateVolumeButton() {
            const btn = document.querySelector(`#audio-viewer-${assetId}`).parentElement.querySelector('.volume-btn');
            const icon = btn.querySelector('i');
            
            if (audio.volume === 0 || isMuted) {
                icon.className = 'bi bi-volume-mute';
            } else if (audio.volume < 0.5) {
                icon.className = 'bi bi-volume-down';
            } else {
                icon.className = 'bi bi-volume-up';
            }
        }
        
        // Control functions
        window[`togglePlayPause${assetId}`] = function() {
            if (audioContext && audioContext.state === 'suspended') {
                audioContext.resume().then(() => {
                    console.log('Audio context resumed');
                });
            }
            
            if (isPlaying) {
                audio.pause();
            } else {
                audio.play().then(() => {
                    console.log('Audio playing');
                }).catch(e => {
                    console.error('Failed to play audio:', e);
                });
            }
            isPlaying = !isPlaying;
            updatePlayButton();
        };
        
        window[`stopAudio${assetId}`] = function() {
            audio.pause();
            audio.currentTime = 0;
            isPlaying = false;
            updatePlayButton();
            if (animationId) {
                cancelAnimationFrame(animationId);
                animationId = null;
            }
            // Restart the visualization loop to keep it running
            visualize();
        };
        
        window[`toggleMute${assetId}`] = function() {
            isMuted = !isMuted;
            audio.muted = isMuted;
            updateVolumeButton();
        };
        
        window[`changeBackgroundAudio${assetId}`] = function() {
            currentBackgroundIndex = (currentBackgroundIndex + 1) % backgroundColors.length;
            const currentBg = backgroundColors[currentBackgroundIndex];
            
            const container = document.querySelector(`#audio-viewer-${assetId}`).closest('.texture-viewer-container');
            container.className = container.className.replace(/\b(dark|light|blue|purple|green|gradient)-theme\b/, '');
            container.classList.add(`${currentBg.class}-theme`);
            
            const label = container.querySelector('.bg-label');
            if (label) label.textContent = currentBg.name;
        };
        
        // Initialize when document is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initAudioPlayer);
        } else {
            initAudioPlayer();
        }
    })();
    </script>

    <style>
    .audio-viewer {
        position: relative;
        overflow: hidden;
    }
    
    .audio-canvas {
        width: 100%;
        height: 100%;
        display: block;
    }
    
    .audio-progress-controls {
        display: flex;
        align-items: center;
        gap: 20px;
        padding: 10px 15px;
        background: rgba(255, 255, 255, 0.05);
        border-top: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    .progress-container {
        display: flex;
        align-items: center;
        gap: 10px;
        flex: 1;
    }
    
    .volume-container {
        display: flex;
        align-items: center;
        gap: 8px;
        min-width: 120px;
    }
    
    .progress-slider, .volume-slider {
        -webkit-appearance: none;
        appearance: none;
        height: 4px;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 2px;
        outline: none;
        cursor: pointer;
    }
    
    .progress-slider {
        flex: 1;
    }
    
    .volume-slider {
        width: 80px;
    }
    
    .progress-slider::-webkit-slider-thumb,
    .volume-slider::-webkit-slider-thumb {
        -webkit-appearance: none;
        appearance: none;
        width: 16px;
        height: 16px;
        background: #06b6d4;
        border-radius: 50%;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    
    .progress-slider::-webkit-slider-thumb:hover,
    .volume-slider::-webkit-slider-thumb:hover {
        transform: scale(1.2);
        background: #0891b2;
    }
    
    .time-display {
        font-size: 12px;
        color: rgba(255, 255, 255, 0.8);
        font-family: monospace;
        min-width: 35px;
        text-align: center;
    }
    
    .volume-container i {
        color: rgba(255, 255, 255, 0.6);
        font-size: 14px;
    }
    
    /* Theme variations for audio player */
    .texture-viewer-container.light-theme .audio-progress-controls {
        background: rgba(0, 0, 0, 0.05);
        border-top-color: rgba(0, 0, 0, 0.1);
    }
    
    .texture-viewer-container.light-theme .time-display,
    .texture-viewer-container.light-theme .volume-container i {
        color: rgba(0, 0, 0, 0.7);
    }
    
    .texture-viewer-container.light-theme .progress-slider,
    .texture-viewer-container.light-theme .volume-slider {
        background: rgba(0, 0, 0, 0.2);
    }
    
    .texture-viewer-container.light-theme .progress-slider::-webkit-slider-thumb,
    .texture-viewer-container.light-theme .volume-slider::-webkit-slider-thumb {
        background: #3b82f6;
    }
    
    .texture-viewer-container.blue-theme .progress-slider::-webkit-slider-thumb,
    .texture-viewer-container.blue-theme .volume-slider::-webkit-slider-thumb {
        background: #60a5fa;
    }
    
    .texture-viewer-container.purple-theme .progress-slider::-webkit-slider-thumb,
    .texture-viewer-container.purple-theme .volume-slider::-webkit-slider-thumb {
        background: #c084fc;
    }
    
    .texture-viewer-container.green-theme .progress-slider::-webkit-slider-thumb,
    .texture-viewer-container.green-theme .volume-slider::-webkit-slider-thumb {
        background: #4ade80;
    }
    
    .texture-viewer-container.gradient-theme .progress-slider::-webkit-slider-thumb,
    .texture-viewer-container.gradient-theme .volume-slider::-webkit-slider-thumb {
        background: #667eea;
    }
    </style>
<?php elseif ($asset['category_id'] == 9): ?>
    <!-- Video Player -->
    <div class="texture-viewer-container video-player-container mt-3 mb-3 dark-theme">
        <div id="video-viewer-<?= $asset_id ?>" class="model-viewer video-viewer">
            <video id="video-element-<?= $asset_id ?>" class="video-element" preload="metadata">
                <source src="<?= htmlspecialchars($asset['asset_file']) ?>" type="video/mp4">
                <source src="<?= htmlspecialchars($asset['asset_file']) ?>" type="video/webm">
                <source src="<?= htmlspecialchars($asset['asset_file']) ?>" type="video/ogg">
                Your browser does not support the video tag.
            </video>
            <div class="video-loading" style="position: absolute; z-index: 1; top: 50%; left: 50%; transform: translate(-50%, -50%);">
                <div class="loading-spinner"></div>
                <p class="loading-text">Loading video...</p>
            </div>
        </div>
        
        <!-- Video Controls -->
        <div class="model-controls video-controls">
            <button class="control-btn play-pause-btn" onclick="togglePlayPauseVideo<?= $asset_id ?>()" title="Play/Pause">
                <i class="bi bi-play-circle"></i>
                <span>Play</span>
            </button>
            <button class="control-btn" onclick="stopVideo<?= $asset_id ?>()" title="Stop">
                <i class="bi bi-stop-circle"></i>
                <span>Stop</span>
            </button>
            <button class="control-btn volume-btn" onclick="toggleMuteVideo<?= $asset_id ?>()" title="Mute/Unmute">
                <i class="bi bi-volume-up"></i>
                <span>Volume</span>
            </button>
            <button class="control-btn fullscreen-btn" onclick="toggleFullscreen<?= $asset_id ?>()" title="Fullscreen">
                <i class="bi bi-fullscreen"></i>
                <span>Fullscreen</span>
            </button>
            <button class="control-btn background-btn" onclick="changeBackgroundVideo<?= $asset_id ?>()" title="Change background">
                <i class="bi bi-palette"></i>
                <span class="bg-label">Dark</span>
            </button>
        </div>
        
        <!-- Progress and Volume Controls -->
        <div class="model-controls video-progress-controls">
            <div class="progress-container">
                <span class="time-display current-time">0:00</span>
                <input id="video-progress-<?= $asset_id ?>" type="range" min="0" max="100" value="0" class="progress-slider" title="Video Progress" />
                <span class="time-display total-time">0:00</span>
            </div>
            <div class="volume-container">
                <i class="bi bi-volume-down"></i>
                <input id="video-volume-<?= $asset_id ?>" type="range" min="0" max="100" value="70" class="volume-slider" title="Volume" />
                <i class="bi bi-volume-up"></i>
            </div>
        </div>
        
        <div class="model-info">
            <i class="bi bi-camera-video"></i>
            <span id="video-info-<?= $asset_id ?>">Video Player • Click play to start</span>
        </div>
    </div>

    <script>
    (function() {
        const assetId = <?= $asset_id ?>;
        const videoUrl = '<?= htmlspecialchars($asset['asset_file']) ?>';
        
        let video;
        let isPlaying = false;
        let isMuted = false;
        
        const backgroundColors = [
            { name: 'Dark', color: '#1a1a1a', class: 'dark' },
            { name: 'Light', color: '#f5f5f5', class: 'light' },
            { name: 'Blue', color: '#1e3a8a', class: 'blue' },
            { name: 'Purple', color: '#581c87', class: 'purple' },
            { name: 'Green', color: '#166534', class: 'green' },
            { name: 'Gradient', color: 'linear-gradient(45deg, #667eea, #764ba2)', class: 'gradient' }
        ];
        let currentBackgroundIndex = 0;
        
        function initVideoPlayer() {
            const container = document.getElementById(`video-viewer-${assetId}`);
            if (!container) return;
            
            video = document.getElementById(`video-element-${assetId}`);
            video.volume = 0.7;
            
            // Video event listeners
            video.addEventListener('loadedmetadata', () => {
                const loading = container.querySelector('.video-loading');
                if (loading) loading.style.display = 'none';
                
                updateTimeDisplay();
                updateVideoInfo();
            });
            
            video.addEventListener('timeupdate', updateProgress);
            video.addEventListener('ended', () => {
                isPlaying = false;
                updatePlayButton();
            });
            
            video.addEventListener('play', () => {
                isPlaying = true;
                updatePlayButton();
            });
            
            video.addEventListener('pause', () => {
                isPlaying = false;
                updatePlayButton();
            });
            
            video.addEventListener('error', (e) => {
                console.error('Video loading error:', e);
                const loading = container.querySelector('.video-loading');
                if (loading) loading.innerHTML = '<div class="error-message">Failed to load video</div>';
            });
            
            // Progress slider
            const progressSlider = document.getElementById(`video-progress-${assetId}`);
            progressSlider.addEventListener('input', () => {
                if (video.duration) {
                    video.currentTime = (progressSlider.value / 100) * video.duration;
                }
            });
            
            // Volume slider
            const volumeSlider = document.getElementById(`video-volume-${assetId}`);
            volumeSlider.addEventListener('input', () => {
                video.volume = volumeSlider.value / 100;
                updateVolumeButton();
            });
            
            // Fullscreen event listeners
            document.addEventListener('fullscreenchange', updateFullscreenButton);
            document.addEventListener('webkitfullscreenchange', updateFullscreenButton);
            document.addEventListener('mozfullscreenchange', updateFullscreenButton);
            document.addEventListener('MSFullscreenChange', updateFullscreenButton);
        }
        
        function updateProgress() {
            if (video.duration) {
                const progress = (video.currentTime / video.duration) * 100;
                document.getElementById(`video-progress-${assetId}`).value = progress;
                updateTimeDisplay();
            }
        }
        
        function updateTimeDisplay() {
            const currentTime = formatTime(video.currentTime || 0);
            const totalTime = formatTime(video.duration || 0);
            
            const container = document.getElementById(`video-viewer-${assetId}`).parentElement;
            container.querySelector('.current-time').textContent = currentTime;
            container.querySelector('.total-time').textContent = totalTime;
        }
        
        function updateVideoInfo() {
            const info = document.getElementById(`video-info-${assetId}`);
            const fileName = videoUrl.split('/').pop();
            const resolution = video.videoWidth && video.videoHeight ? ` • ${video.videoWidth}x${video.videoHeight}` : '';
            info.textContent = `${fileName} • ${formatTime(video.duration || 0)}${resolution}`;
        }
        
        function formatTime(seconds) {
            const mins = Math.floor(seconds / 60);
            const secs = Math.floor(seconds % 60);
            return `${mins}:${secs.toString().padStart(2, '0')}`;
        }
        
        function updatePlayButton() {
            const btn = document.querySelector(`#video-viewer-${assetId}`).parentElement.querySelector('.play-pause-btn');
            const icon = btn.querySelector('i');
            const span = btn.querySelector('span');
            
            icon.className = `bi ${isPlaying ? 'bi-pause-circle' : 'bi-play-circle'}`;
            span.textContent = isPlaying ? 'Pause' : 'Play';
        }
        
        function updateVolumeButton() {
            const btn = document.querySelector(`#video-viewer-${assetId}`).parentElement.querySelector('.volume-btn');
            const icon = btn.querySelector('i');
            
            if (video.volume === 0 || isMuted) {
                icon.className = 'bi bi-volume-mute';
            } else if (video.volume < 0.5) {
                icon.className = 'bi bi-volume-down';
            } else {
                icon.className = 'bi bi-volume-up';
            }
        }
        
        function updateFullscreenButton() {
            const btn = document.querySelector(`#video-viewer-${assetId}`).parentElement.querySelector('.fullscreen-btn');
            const icon = btn.querySelector('i');
            const span = btn.querySelector('span');
            
            const isFullscreen = document.fullscreenElement || document.webkitFullscreenElement || 
                                document.mozFullScreenElement || document.msFullscreenElement;
            
            icon.className = `bi ${isFullscreen ? 'bi-fullscreen-exit' : 'bi-fullscreen'}`;
            span.textContent = isFullscreen ? 'Exit FS' : 'Fullscreen';
        }
        
        // Control functions
        window[`togglePlayPauseVideo${assetId}`] = function() {
            if (isPlaying) {
                video.pause();
            } else {
                video.play().then(() => {
                    console.log('Video playing');
                }).catch(e => {
                    console.error('Failed to play video:', e);
                });
            }
        };
        
        window[`stopVideo${assetId}`] = function() {
            video.pause();
            video.currentTime = 0;
            isPlaying = false;
            updatePlayButton();
        };
        
        window[`toggleMuteVideo${assetId}`] = function() {
            isMuted = !isMuted;
            video.muted = isMuted;
            updateVolumeButton();
        };
        
        window[`toggleFullscreen${assetId}`] = function() {
            const container = document.getElementById(`video-viewer-${assetId}`);
            
            if (!document.fullscreenElement && !document.webkitFullscreenElement && 
                !document.mozFullScreenElement && !document.msFullscreenElement) {
                // Enter fullscreen
                if (container.requestFullscreen) {
                    container.requestFullscreen();
                } else if (container.webkitRequestFullscreen) {
                    container.webkitRequestFullscreen();
                } else if (container.mozRequestFullScreen) {
                    container.mozRequestFullScreen();
                } else if (container.msRequestFullscreen) {
                    container.msRequestFullscreen();
                }
            } else {
                // Exit fullscreen
                if (document.exitFullscreen) {
                    document.exitFullscreen();
                } else if (document.webkitExitFullscreen) {
                    document.webkitExitFullscreen();
                } else if (document.mozCancelFullScreen) {
                    document.mozCancelFullScreen();
                } else if (document.msExitFullscreen) {
                    document.msExitFullscreen();
                }
            }
        };
        
        window[`changeBackgroundVideo${assetId}`] = function() {
            currentBackgroundIndex = (currentBackgroundIndex + 1) % backgroundColors.length;
            const currentBg = backgroundColors[currentBackgroundIndex];
            
            const container = document.querySelector(`#video-viewer-${assetId}`).closest('.texture-viewer-container');
            container.className = container.className.replace(/\b(dark|light|blue|purple|green|gradient)-theme\b/, '');
            container.classList.add(`${currentBg.class}-theme`);
            
            const label = container.querySelector('.bg-label');
            if (label) label.textContent = currentBg.name;
        };
        
        // Initialize when document is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initVideoPlayer);
        } else {
            initVideoPlayer();
        }
    })();
    </script>

    <style>
    .video-viewer {
        position: relative;
        overflow: hidden;
        background: #000;
    }
    
    .video-element {
        width: 100%;
        height: 100%;
        display: block;
        object-fit: cover;
    }
    
    .video-progress-controls {
        display: flex;
        align-items: center;
        gap: 20px;
        padding: 10px 15px;
        background: rgba(255, 255, 255, 0.05);
        border-top: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    .progress-container {
        display: flex;
        align-items: center;
        gap: 10px;
        flex: 1;
    }
    
    .volume-container {
        display: flex;
        align-items: center;
        gap: 8px;
        min-width: 120px;
    }
    
    .progress-slider, .volume-slider {
        -webkit-appearance: none;
        appearance: none;
        height: 4px;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 2px;
        outline: none;
        cursor: pointer;
    }
    
    .progress-slider {
        flex: 1;
    }
    
    .volume-slider {
        width: 80px;
    }
    
    .progress-slider::-webkit-slider-thumb,
    .volume-slider::-webkit-slider-thumb {
        -webkit-appearance: none;
        appearance: none;
        width: 16px;
        height: 16px;
        background: #06b6d4;
        border-radius: 50%;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    
    .progress-slider::-webkit-slider-thumb:hover,
    .volume-slider::-webkit-slider-thumb:hover {
        transform: scale(1.2);
        background: #0891b2;
    }
    
    .time-display {
        font-size: 12px;
        color: rgba(255, 255, 255, 0.8);
        font-family: monospace;
        min-width: 35px;
        text-align: center;
    }
    
    .volume-container i {
        color: rgba(255, 255, 255, 0.6);
        font-size: 14px;
    }
    
    /* Fullscreen styles */
    .video-viewer:-webkit-full-screen {
        width: 100vw;
        height: 100vh;
    }
    
    .video-viewer:-moz-full-screen {
        width: 100vw;
        height: 100vh;
    }
    
    .video-viewer:fullscreen {
        width: 100vw;
        height: 100vh;
    }
    
    .video-viewer:-webkit-full-screen .video-element,
    .video-viewer:-moz-full-screen .video-element,
    .video-viewer:fullscreen .video-element {
        object-fit: contain;
        width: 100%;
        height: 100%;
    }
    
    /* Theme variations for video player */
    .texture-viewer-container.light-theme .video-progress-controls {
        background: rgba(0, 0, 0, 0.05);
        border-top-color: rgba(0, 0, 0, 0.1);
    }
    
    .texture-viewer-container.light-theme .time-display,
    .texture-viewer-container.light-theme .volume-container i {
        color: rgba(0, 0, 0, 0.7);
    }
    
    .texture-viewer-container.light-theme .progress-slider,
    .texture-viewer-container.light-theme .volume-slider {
        background: rgba(0, 0, 0, 0.2);
    }
    
    .texture-viewer-container.light-theme .progress-slider::-webkit-slider-thumb,
    .texture-viewer-container.light-theme .volume-slider::-webkit-slider-thumb {
        background: #3b82f6;
    }
    
    .texture-viewer-container.blue-theme .progress-slider::-webkit-slider-thumb,
    .texture-viewer-container.blue-theme .volume-slider::-webkit-slider-thumb {
        background: #60a5fa;
    }
    
    .texture-viewer-container.purple-theme .progress-slider::-webkit-slider-thumb,
    .texture-viewer-container.purple-theme .volume-slider::-webkit-slider-thumb {
        background: #c084fc;
    }
    
    .texture-viewer-container.green-theme .progress-slider::-webkit-slider-thumb,
    .texture-viewer-container.green-theme .volume-slider::-webkit-slider-thumb {
        background: #4ade80;
    }
    
    .texture-viewer-container.gradient-theme .progress-slider::-webkit-slider-thumb,
    .texture-viewer-container.gradient-theme .volume-slider::-webkit-slider-thumb {
        background: #667eea;
    }
    
    /* Video loading styles */
    .video-loading {
        color: white;
        text-align: center;
    }
    
    .video-loading .loading-spinner {
        width: 40px;
        height: 40px;
        border: 4px solid rgba(255, 255, 255, 0.3);
        border-top: 4px solid #06b6d4;
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin: 0 auto 10px;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    .error-message {
        color: #ef4444;
        padding: 20px;
        text-align: center;
    }
    </style>
<?php elseif ($asset['category_id'] == 15): ?>
    <!-- GIF Player -->
    <div class="texture-viewer-container gif-player-container mt-3 mb-3 dark-theme">
        <div id="gif-viewer-<?= $asset_id ?>" class="model-viewer gif-viewer">
            <img id="gif-element-<?= $asset_id ?>" class="gif-element" src="<?= htmlspecialchars($asset['asset_file']) ?>" alt="GIF Animation">
            <div class="gif-loading" style="position: absolute; z-index: 1; top: 50%; left: 50%; transform: translate(-50%, -50%);">
                <div class="loading-spinner"></div>
                <p class="loading-text">Loading GIF...</p>
            </div>
        </div>
        
        <!-- GIF Controls -->
        <div class="model-controls gif-controls">
            <button class="control-btn play-pause-btn" onclick="togglePlayPauseGif<?= $asset_id ?>()" title="Play/Pause">
                <i class="bi bi-pause-circle"></i>
                <span>Pause</span>
            </button>
            <button class="control-btn" onclick="restartGif<?= $asset_id ?>()" title="Restart">
                <i class="bi bi-arrow-clockwise"></i>
                <span>Restart</span>
            </button>
            <button class="control-btn speed-btn" onclick="changeSpeed<?= $asset_id ?>()" title="Change Speed">
                <i class="bi bi-speedometer2"></i>
                <span class="speed-label">1x</span>
            </button>
            <button class="control-btn fullscreen-btn" onclick="toggleFullscreen<?= $asset_id ?>()" title="Fullscreen">
                <i class="bi bi-fullscreen"></i>
                <span>Fullscreen</span>
            </button>
            <button class="control-btn background-btn" onclick="changeBackgroundGif<?= $asset_id ?>()" title="Change background">
                <i class="bi bi-palette"></i>
                <span class="bg-label">Dark</span>
            </button>
        </div>
        
        <div class="model-info">
            <i class="bi bi-file-image"></i>
            <span id="gif-info-<?= $asset_id ?>">GIF Animation • Loading...</span>
        </div>
    </div>

    <script>
    (function() {
        const assetId = <?= $asset_id ?>;
        const gifUrl = '<?= htmlspecialchars($asset['asset_file']) ?>';
        
        let gifElement;
        let isPlaying = true;  // GIFs auto-play by default
        let currentSpeed = 1;
        let originalSrc = gifUrl;
        
        const speeds = [0.25, 0.5, 1, 1.5, 2];
        let currentSpeedIndex = 2; // Default to 1x speed
        
        const backgroundColors = [
            { name: 'Dark', color: '#1a1a1a', class: 'dark' },
            { name: 'Light', color: '#f5f5f5', class: 'light' },
            { name: 'Blue', color: '#1e3a8a', class: 'blue' },
            { name: 'Purple', color: '#581c87', class: 'purple' },
            { name: 'Green', color: '#166534', class: 'green' },
            { name: 'Gradient', color: 'linear-gradient(45deg, #667eea, #764ba2)', class: 'gradient' }
        ];
        let currentBackgroundIndex = 0;
        
        function initGifPlayer() {
            const container = document.getElementById(`gif-viewer-${assetId}`);
            if (!container) return;
            
            gifElement = document.getElementById(`gif-element-${assetId}`);
            
            // GIF event listeners
            gifElement.addEventListener('load', () => {
                const loading = container.querySelector('.gif-loading');
                if (loading) loading.style.display = 'none';
                
                updateGifInfo();
            });
            
            gifElement.addEventListener('error', (e) => {
                console.error('GIF loading error:', e);
                const loading = container.querySelector('.gif-loading');
                if (loading) loading.innerHTML = '<div class="error-message">Failed to load GIF</div>';
            });
            
            // Fullscreen event listeners
            document.addEventListener('fullscreenchange', updateFullscreenButton);
            document.addEventListener('webkitfullscreenchange', updateFullscreenButton);
            document.addEventListener('mozfullscreenchange', updateFullscreenButton);
            document.addEventListener('MSFullscreenChange', updateFullscreenButton);
        }
        
        function updateGifInfo() {
            const info = document.getElementById(`gif-info-${assetId}`);
            const fileName = gifUrl.split('/').pop();
            const dimensions = gifElement.naturalWidth && gifElement.naturalHeight ? 
                ` • ${gifElement.naturalWidth}x${gifElement.naturalHeight}` : '';
            info.textContent = `${fileName}${dimensions} • Animated GIF`;
        }
        
        function updatePlayButton() {
            const btn = document.querySelector(`#gif-viewer-${assetId}`).parentElement.querySelector('.play-pause-btn');
            const icon = btn.querySelector('i');
            const span = btn.querySelector('span');
            
            icon.className = `bi ${isPlaying ? 'bi-pause-circle' : 'bi-play-circle'}`;
            span.textContent = isPlaying ? 'Pause' : 'Play';
        }
        
        function updateSpeedButton() {
            const btn = document.querySelector(`#gif-viewer-${assetId}`).parentElement.querySelector('.speed-btn');
            const span = btn.querySelector('.speed-label');
            span.textContent = `${speeds[currentSpeedIndex]}x`;
        }
        
        function updateFullscreenButton() {
            const btn = document.querySelector(`#gif-viewer-${assetId}`).parentElement.querySelector('.fullscreen-btn');
            const icon = btn.querySelector('i');
            const span = btn.querySelector('span');
            
            const isFullscreen = document.fullscreenElement || document.webkitFullscreenElement || 
                                document.mozFullScreenElement || document.msFullscreenElement;
            
            icon.className = `bi ${isFullscreen ? 'bi-fullscreen-exit' : 'bi-fullscreen'}`;
            span.textContent = isFullscreen ? 'Exit FS' : 'Fullscreen';
        }
        
        // Control functions
        window[`togglePlayPauseGif${assetId}`] = function() {
            if (isPlaying) {
                // To "pause" a GIF, we replace it with a static image (first frame)
                // This is a workaround since GIFs don't have native pause functionality
                const canvas = document.createElement('canvas');
                const ctx = canvas.getContext('2d');
                canvas.width = gifElement.naturalWidth || gifElement.width;
                canvas.height = gifElement.naturalHeight || gifElement.height;
                ctx.drawImage(gifElement, 0, 0);
                
                gifElement.src = canvas.toDataURL();
                isPlaying = false;
            } else {
                // Resume by reloading the original GIF
                gifElement.src = originalSrc + '?t=' + Date.now(); // Cache bust
                isPlaying = true;
            }
            updatePlayButton();
        };
        
        window[`restartGif${assetId}`] = function() {
            // Restart GIF by reloading it with cache busting
            gifElement.src = originalSrc + '?t=' + Date.now();
            isPlaying = true;
            updatePlayButton();
        };
        
        window[`changeSpeed${assetId}`] = function() {
            currentSpeedIndex = (currentSpeedIndex + 1) % speeds.length;
            currentSpeed = speeds[currentSpeedIndex];
            
            // Note: Changing GIF speed requires CSS animation manipulation
            // This is a simplified version - real speed control would need more complex implementation
            const speedMultiplier = 1 / currentSpeed;
            gifElement.style.animationDuration = speedMultiplier + 's';
            gifElement.style.animationTimingFunction = 'steps(1, end)';
            
            updateSpeedButton();
        };
        
        window[`toggleFullscreen${assetId}`] = function() {
            const container = document.getElementById(`gif-viewer-${assetId}`);
            
            if (!document.fullscreenElement && !document.webkitFullscreenElement && 
                !document.mozFullScreenElement && !document.msFullscreenElement) {
                // Enter fullscreen
                if (container.requestFullscreen) {
                    container.requestFullscreen();
                } else if (container.webkitRequestFullscreen) {
                    container.webkitRequestFullscreen();
                } else if (container.mozRequestFullScreen) {
                    container.mozRequestFullScreen();
                } else if (container.msRequestFullscreen) {
                    container.msRequestFullscreen();
                }
            } else {
                // Exit fullscreen
                if (document.exitFullscreen) {
                    document.exitFullscreen();
                } else if (document.webkitExitFullscreen) {
                    document.webkitExitFullscreen();
                } else if (document.mozCancelFullScreen) {
                    document.mozCancelFullScreen();
                } else if (document.msExitFullscreen) {
                    document.msExitFullscreen();
                }
            }
        };
        
        window[`changeBackgroundGif${assetId}`] = function() {
            currentBackgroundIndex = (currentBackgroundIndex + 1) % backgroundColors.length;
            const currentBg = backgroundColors[currentBackgroundIndex];
            
            const container = document.querySelector(`#gif-viewer-${assetId}`).closest('.texture-viewer-container');
            container.className = container.className.replace(/\b(dark|light|blue|purple|green|gradient)-theme\b/, '');
            container.classList.add(`${currentBg.class}-theme`);
            
            const label = container.querySelector('.bg-label');
            if (label) label.textContent = currentBg.name;
        };
        
        // Initialize when document is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initGifPlayer);
        } else {
            initGifPlayer();
        }
    })();
    </script>

    <style>
    .gif-viewer {
        position: relative;
        overflow: hidden;
        background: #000;
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 300px;
    }
    
    .gif-element {
        max-width: 100%;
        max-height: 100%;
        display: block;
        object-fit: contain;
    }
    
    /* Fullscreen styles */
    .gif-viewer:-webkit-full-screen {
        width: 100vw;
        height: 100vh;
    }
    
    .gif-viewer:-moz-full-screen {
        width: 100vw;
        height: 100vh;
    }
    
    .gif-viewer:fullscreen {
        width: 100vw;
        height: 100vh;
    }
    
    .gif-viewer:-webkit-full-screen .gif-element,
    .gif-viewer:-moz-full-screen .gif-element,
    .gif-viewer:fullscreen .gif-element {
        max-width: 100vw;
        max-height: 100vh;
        width: auto;
        height: auto;
    }
    
    /* GIF loading styles */
    .gif-loading {
        color: white;
        text-align: center;
    }
    
    .gif-loading .loading-spinner {
        width: 40px;
        height: 40px;
        border: 4px solid rgba(255, 255, 255, 0.3);
        border-top: 4px solid #06b6d4;
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin: 0 auto 10px;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    .error-message {
        color: #ef4444;
        padding: 20px;
        text-align: center;
    }
    
    /* Theme variations for GIF player */
    .texture-viewer-container.light-theme .gif-viewer {
        background: #f5f5f5;
    }
    
    .texture-viewer-container.blue-theme .gif-viewer {
        background: #1e3a8a;
    }
    
    .texture-viewer-container.purple-theme .gif-viewer {
        background: #581c87;
    }
    
    .texture-viewer-container.green-theme .gif-viewer {
        background: #166534;
    }
    
    .texture-viewer-container.gradient-theme .gif-viewer {
        background: linear-gradient(45deg, #667eea, #764ba2);
    }
    
    /* Speed control visual feedback */
    .gif-element.speed-adjusted {
        filter: hue-rotate(10deg);
        transition: filter 0.3s ease;
    }
    </style>
<?php elseif (in_array($asset['category_id'], [1, 7, 18])): ?>
    <!-- Image Viewer -->
    <div class="texture-viewer-container image-viewer-container mt-3 mb-3 dark-theme">
        <div id="image-viewer-<?= $asset_id ?>" class="model-viewer image-viewer">
            <img id="image-element-<?= $asset_id ?>" class="image-element" src="<?= htmlspecialchars($asset['asset_file']) ?>" alt="Image">
            <div class="image-loading" style="position: absolute; z-index: 1; top: 50%; left: 50%; transform: translate(-50%, -50%);">
                <div class="loading-spinner"></div>
                <p class="loading-text">Loading image...</p>
            </div>
        </div>
        
        <!-- Image Controls -->
        <div class="model-controls image-controls">
            <button class="control-btn zoom-in-btn" onclick="zoomIn<?= $asset_id ?>()" title="Zoom In">
                <i class="bi bi-zoom-in"></i>
                <span>Zoom In</span>
            </button>
            <button class="control-btn zoom-out-btn" onclick="zoomOut<?= $asset_id ?>()" title="Zoom Out">
                <i class="bi bi-zoom-out"></i>
                <span>Zoom Out</span>
            </button>
            <button class="control-btn reset-zoom-btn" onclick="resetZoom<?= $asset_id ?>()" title="Reset Zoom">
                <i class="bi bi-aspect-ratio"></i>
                <span>Fit</span>
            </button>
            <button class="control-btn fullscreen-btn" onclick="toggleFullscreen<?= $asset_id ?>()" title="Fullscreen">
                <i class="bi bi-fullscreen"></i>
                <span>Fullscreen</span>
            </button>
            <button class="control-btn background-btn" onclick="changeBackgroundImage<?= $asset_id ?>()" title="Change background">
                <i class="bi bi-palette"></i>
                <span class="bg-label">Dark</span>
            </button>
        </div>
        
        <div class="model-info">
            <i class="bi bi-image"></i>
            <span id="image-info-<?= $asset_id ?>">Image • Loading...</span>
        </div>
    </div>

    <script>
    (function() {
        const assetId = <?= $asset_id ?>;
        const imageUrl = '<?= htmlspecialchars($asset['asset_file']) ?>';
        
        let imageElement;
        let currentZoom = 1;
        let isDragging = false;
        let startX, startY, translateX = 0, translateY = 0;
        
        const backgroundColors = [
            { name: 'Dark', color: '#1a1a1a', class: 'dark' },
            { name: 'Light', color: '#f5f5f5', class: 'light' },
            { name: 'Blue', color: '#1e3a8a', class: 'blue' },
            { name: 'Purple', color: '#581c87', class: 'purple' },
            { name: 'Green', color: '#166534', class: 'green' },
            { name: 'Gradient', color: 'linear-gradient(45deg, #667eea, #764ba2)', class: 'gradient' }
        ];
        let currentBackgroundIndex = 0;
        
        function initImageViewer() {
            const container = document.getElementById(`image-viewer-${assetId}`);
            if (!container) return;
            
            imageElement = document.getElementById(`image-element-${assetId}`);
            
            // Check if image is already loaded (cached)
            const loading = container.querySelector('.image-loading');
            if (imageElement.complete && imageElement.naturalHeight !== 0) {
                if (loading) loading.style.display = 'none';
                updateImageInfo();
            }
            
            // Image event listeners
            imageElement.addEventListener('load', () => {
                const loading = container.querySelector('.image-loading');
                if (loading) loading.style.display = 'none';
                
                updateImageInfo();
            });
            
            imageElement.addEventListener('error', (e) => {
                console.error('Image loading error:', e);
                const loading = container.querySelector('.image-loading');
                if (loading) loading.innerHTML = '<div class="error-message">Failed to load image</div>';
            });
            
            // Mouse wheel zoom
            container.addEventListener('wheel', (e) => {
                e.preventDefault();
                const delta = e.deltaY > 0 ? 0.9 : 1.1;
                zoom(delta, e.clientX, e.clientY);
            });
            
            // Drag functionality
            imageElement.addEventListener('mousedown', startDrag);
            document.addEventListener('mousemove', drag);
            document.addEventListener('mouseup', endDrag);
            
            // Touch support for mobile
            imageElement.addEventListener('touchstart', startDrag);
            document.addEventListener('touchmove', drag);
            document.addEventListener('touchend', endDrag);
            
            // Fullscreen event listeners
            document.addEventListener('fullscreenchange', updateFullscreenButton);
            document.addEventListener('webkitfullscreenchange', updateFullscreenButton);
            document.addEventListener('mozfullscreenchange', updateFullscreenButton);
            document.addEventListener('MSFullscreenChange', updateFullscreenButton);
        }
        
        function updateImageInfo() {
            const info = document.getElementById(`image-info-${assetId}`);
            const fileName = imageUrl.split('/').pop();
            const dimensions = imageElement.naturalWidth && imageElement.naturalHeight ? 
                ` • ${imageElement.naturalWidth}x${imageElement.naturalHeight}` : '';
            const fileSize = imageElement.complete ? ' • Image' : '';
            info.textContent = `${fileName}${dimensions}${fileSize}`;
        }
        
        function updateFullscreenButton() {
            const btn = document.querySelector(`#image-viewer-${assetId}`).parentElement.querySelector('.fullscreen-btn');
            const icon = btn.querySelector('i');
            const span = btn.querySelector('span');
            
            const isFullscreen = document.fullscreenElement || document.webkitFullscreenElement || 
                                document.mozFullScreenElement || document.msFullscreenElement;
            
            icon.className = `bi ${isFullscreen ? 'bi-fullscreen-exit' : 'bi-fullscreen'}`;
            span.textContent = isFullscreen ? 'Exit FS' : 'Fullscreen';
        }
        
        function zoom(factor, centerX, centerY) {
            const newZoom = Math.max(0.1, Math.min(10, currentZoom * factor));
            
            if (centerX && centerY) {
                const rect = imageElement.getBoundingClientRect();
                const offsetX = centerX - rect.left - rect.width / 2;
                const offsetY = centerY - rect.top - rect.height / 2;
                
                translateX -= offsetX * (factor - 1);
                translateY -= offsetY * (factor - 1);
            }
            
            currentZoom = newZoom;
            updateImageTransform();
        }
        
        function updateImageTransform() {
            imageElement.style.transform = `scale(${currentZoom}) translate(${translateX}px, ${translateY}px)`;
            imageElement.style.cursor = currentZoom > 1 ? 'grab' : 'default';
        }
        
        function startDrag(e) {
            if (currentZoom <= 1) return;
            
            isDragging = true;
            const clientX = e.clientX || (e.touches && e.touches[0].clientX);
            const clientY = e.clientY || (e.touches && e.touches[0].clientY);
            
            startX = clientX - translateX;
            startY = clientY - translateY;
            
            imageElement.style.cursor = 'grabbing';
            e.preventDefault();
        }
        
        function drag(e) {
            if (!isDragging) return;
            
            const clientX = e.clientX || (e.touches && e.touches[0].clientX);
            const clientY = e.clientY || (e.touches && e.touches[0].clientY);
            
            translateX = clientX - startX;
            translateY = clientY - startY;
            
            updateImageTransform();
            e.preventDefault();
        }
        
        function endDrag() {
            isDragging = false;
            imageElement.style.cursor = currentZoom > 1 ? 'grab' : 'default';
        }
        
        // Control functions
        window[`zoomIn${assetId}`] = function() {
            zoom(1.25);
        };
        
        window[`zoomOut${assetId}`] = function() {
            zoom(0.8);
        };
        
        window[`resetZoom${assetId}`] = function() {
            currentZoom = 1;
            translateX = 0;
            translateY = 0;
            updateImageTransform();
        };
        
        window[`toggleFullscreen${assetId}`] = function() {
            const container = document.getElementById(`image-viewer-${assetId}`);
            
            if (!document.fullscreenElement && !document.webkitFullscreenElement && 
                !document.mozFullScreenElement && !document.msFullscreenElement) {
                // Enter fullscreen
                if (container.requestFullscreen) {
                    container.requestFullscreen();
                } else if (container.webkitRequestFullscreen) {
                    container.webkitRequestFullscreen();
                } else if (container.mozRequestFullScreen) {
                    container.mozRequestFullScreen();
                } else if (container.msRequestFullscreen) {
                    container.msRequestFullscreen();
                }
            } else {
                // Exit fullscreen
                if (document.exitFullscreen) {
                    document.exitFullscreen();
                } else if (document.webkitExitFullscreen) {
                    document.webkitExitFullscreen();
                } else if (document.mozCancelFullScreen) {
                    document.mozCancelFullScreen();
                } else if (document.msExitFullscreen) {
                    document.msExitFullscreen();
                }
            }
        };
        
        window[`changeBackgroundImage${assetId}`] = function() {
            currentBackgroundIndex = (currentBackgroundIndex + 1) % backgroundColors.length;
            const currentBg = backgroundColors[currentBackgroundIndex];
            
            const container = document.querySelector(`#image-viewer-${assetId}`).closest('.texture-viewer-container');
            container.className = container.className.replace(/\b(dark|light|blue|purple|green|gradient)-theme\b/, '');
            container.classList.add(`${currentBg.class}-theme`);
            
            const label = container.querySelector('.bg-label');
            if (label) label.textContent = currentBg.name;
        };
        
        // Initialize when document is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initImageViewer);
        } else {
            initImageViewer();
        }
    })();
    </script>

    <style>
    .image-viewer {
        position: relative;
        overflow: hidden;
        background: #000;
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 400px;
        user-select: none;
    }
    
    .image-element {
        max-width: 100%;
        max-height: 100%;
        display: block;
        object-fit: contain;
        transition: transform 0.2s ease;
        cursor: default;
    }
    
    .image-element:active {
        cursor: grabbing !important;
    }
    
    /* Fullscreen styles */
    .image-viewer:-webkit-full-screen {
        width: 100vw;
        height: 100vh;
    }
    
    .image-viewer:-moz-full-screen {
        width: 100vw;
        height: 100vh;
    }
    
    .image-viewer:fullscreen {
        width: 100vw;
        height: 100vh;
    }
    
    .image-viewer:-webkit-full-screen .image-element,
    .image-viewer:-moz-full-screen .image-element,
    .image-viewer:fullscreen .image-element {
        max-width: 100vw;
        max-height: 100vh;
    }
    
    /* Image loading styles */
    .image-loading {
        color: white;
        text-align: center;
    }
    
    .image-loading .loading-spinner {
        width: 40px;
        height: 40px;
        border: 4px solid rgba(255, 255, 255, 0.3);
        border-top: 4px solid #06b6d4;
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin: 0 auto 10px;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    .error-message {
        color: #ef4444;
        padding: 20px;
        text-align: center;
    }
    
    /* Theme variations for image viewer */
    .texture-viewer-container.light-theme .image-viewer {
        background: #f5f5f5;
    }
    
    .texture-viewer-container.blue-theme .image-viewer {
        background: #1e3a8a;
    }
    
    .texture-viewer-container.purple-theme .image-viewer {
        background: #581c87;
    }
    
    .texture-viewer-container.green-theme .image-viewer {
        background: #166534;
    }
    
    .texture-viewer-container.gradient-theme .image-viewer {
        background: linear-gradient(45deg, #667eea, #764ba2);
    }
    
    /* Zoom controls styling */
    .zoom-in-btn:hover,
    .zoom-out-btn:hover,
    .reset-zoom-btn:hover {
        background: rgba(6, 182, 212, 0.2);
    }
    
    /* Mobile touch improvements */
    @media (max-width: 768px) {
        .image-viewer {
            min-height: 300px;
        }
        
        .image-element {
            touch-action: none;
        }
    }
    </style>
<?php elseif ($asset['category_id'] == 17): ?>
    <!-- Font Viewer -->
    <div class="texture-viewer-container font-viewer-container mt-3 mb-3 dark-theme">
        <div id="font-viewer-<?= $asset_id ?>" class="model-viewer font-viewer">
            <div class="font-loading" style="position: absolute; z-index: 1; top: 50%; left: 50%; transform: translate(-50%, -50%);">
                <div class="loading-spinner"></div>
                <p class="loading-text">Loading font...</p>
            </div>
            
            <div class="font-preview-container" style="display: none;">
                <!-- Sample Text Display -->
                <div class="font-sample-large" id="font-sample-large-<?= $asset_id ?>">
                    The Quick Brown Fox Jumps Over The Lazy Dog
                </div>
                
                <!-- Alphabet Display -->
                <div class="font-sample-alphabet" id="font-sample-alphabet-<?= $asset_id ?>">
                    ABCDEFGHIJKLMNOPQRSTUVWXYZ<br>
                    abcdefghijklmnopqrstuvwxyz<br>
                    1234567890 !@#$%^&*()
                </div>
                
                <!-- Custom Text Input -->
                <div class="font-custom-text">
                    <textarea id="font-custom-input-<?= $asset_id ?>" 
                              class="custom-text-input" 
                              placeholder="Type your custom text here..."
                              rows="3">Your custom text preview</textarea>
                    <div class="font-sample-custom" id="font-sample-custom-<?= $asset_id ?>">
                        Your custom text preview
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Font Controls -->
        <div class="model-controls font-controls">
            <button class="control-btn size-down-btn" onclick="decreaseFontSize<?= $asset_id ?>()" title="Decrease Size">
                <i class="bi bi-dash-circle"></i>
                <span>A-</span>
            </button>
            <button class="control-btn size-up-btn" onclick="increaseFontSize<?= $asset_id ?>()" title="Increase Size">
                <i class="bi bi-plus-circle"></i>
                <span>A+</span>
            </button>
            <button class="control-btn sample-btn" onclick="changeSampleText<?= $asset_id ?>()" title="Change Sample">
                <i class="bi bi-text-paragraph"></i>
                <span class="sample-label">Sample 1</span>
            </button>
            <button class="control-btn weight-btn" onclick="changeFontWeight<?= $asset_id ?>()" title="Font Weight">
                <i class="bi bi-type-bold"></i>
                <span class="weight-label">Normal</span>
            </button>
            <button class="control-btn background-btn" onclick="changeBackgroundFont<?= $asset_id ?>()" title="Change background">
                <i class="bi bi-palette"></i>
                <span class="bg-label">Dark</span>
            </button>
        </div>
        
        <div class="model-info">
            <i class="bi bi-fonts"></i>
            <span id="font-info-<?= $asset_id ?>">Font Preview • Loading...</span>
        </div>
    </div>

    <script>
    (function() {
        const assetId = <?= $asset_id ?>;
        const fontUrl = '<?= htmlspecialchars($asset['asset_file']) ?>';
        
        let currentFontSize = 24;
        let currentWeight = 'normal';
        let currentSample = 0;
        
        const fontWeights = ['normal', 'bold', '100', '300', '400', '500', '600', '700', '800', '900'];
        let currentWeightIndex = 0;
        
        const sampleTexts = [
            'The Quick Brown Fox Jumps Over The Lazy Dog',
            'Pack my box with five dozen liquor jugs',
            'How vexingly quick daft zebras jump!',
            'Waltz, bad nymph, for quick jigs vex',
            'Typography & Design Elements',
            'Lorem ipsum dolor sit amet consectetur'
        ];
        
        const backgroundColors = [
            { name: 'Dark', color: '#1a1a1a', class: 'dark' },
            { name: 'Light', color: '#f5f5f5', class: 'light' },
            { name: 'Blue', color: '#1e3a8a', class: 'blue' },
            { name: 'Purple', color: '#581c87', class: 'purple' },
            { name: 'Green', color: '#166534', class: 'green' },
            { name: 'Gradient', color: 'linear-gradient(45deg, #667eea, #764ba2)', class: 'gradient' }
        ];
        let currentBackgroundIndex = 0;
        
        function initFontViewer() {
            const container = document.getElementById(`font-viewer-${assetId}`);
            if (!container) return;
            
            loadFont();
            
            // Custom text input listener
            const customInput = document.getElementById(`font-custom-input-${assetId}`);
            customInput.addEventListener('input', updateCustomText);
        }
        
        function loadFont() {
            const fontName = `CustomFont${assetId}`;
            const fontFace = new FontFace(fontName, `url(${fontUrl})`);
            
            fontFace.load().then((loadedFont) => {
                document.fonts.add(loadedFont);
                
                // Apply font to preview elements
                const elements = [
                    document.getElementById(`font-sample-large-${assetId}`),
                    document.getElementById(`font-sample-alphabet-${assetId}`),
                    document.getElementById(`font-sample-custom-${assetId}`)
                ];
                
                elements.forEach(el => {
                    if (el) {
                        el.style.fontFamily = fontName;
                        el.style.fontSize = currentFontSize + 'px';
                        el.style.fontWeight = currentWeight;
                    }
                });
                
                // Hide loading, show preview
                const loading = document.querySelector(`#font-viewer-${assetId} .font-loading`);
                const preview = document.querySelector(`#font-viewer-${assetId} .font-preview-container`);
                if (loading) loading.style.display = 'none';
                if (preview) preview.style.display = 'block';
                
                updateFontInfo();
                
            }).catch((error) => {
                console.error('Font loading failed:', error);
                const loading = document.querySelector(`#font-viewer-${assetId} .font-loading`);
                if (loading) loading.innerHTML = '<div class="error-message">Failed to load font</div>';
            });
        }
        
        function updateFontInfo() {
            const info = document.getElementById(`font-info-${assetId}`);
            const fileName = fontUrl.split('/').pop();
            const fileExt = fileName.split('.').pop().toUpperCase();
            info.textContent = `${fileName} • ${fileExt} Font • ${currentFontSize}px`;
        }
        
        function updateFontStyles() {
            const elements = [
                document.getElementById(`font-sample-large-${assetId}`),
                document.getElementById(`font-sample-alphabet-${assetId}`),
                document.getElementById(`font-sample-custom-${assetId}`)
            ];
            
            elements.forEach(el => {
                if (el) {
                    el.style.fontSize = currentFontSize + 'px';
                    el.style.fontWeight = currentWeight;
                }
            });
            
            updateFontInfo();
        }
        
        function updateCustomText() {
            const input = document.getElementById(`font-custom-input-${assetId}`);
            const preview = document.getElementById(`font-sample-custom-${assetId}`);
            if (input && preview) {
                preview.textContent = input.value || 'Your custom text preview';
            }
        }
        
        function updateWeightButton() {
            const btn = document.querySelector(`#font-viewer-${assetId}`).parentElement.querySelector('.weight-btn');
            const span = btn.querySelector('.weight-label');
            const weightName = currentWeight === 'normal' ? 'Normal' : 
                              currentWeight === 'bold' ? 'Bold' : currentWeight;
            span.textContent = weightName;
        }
        
        function updateSampleButton() {
            const btn = document.querySelector(`#font-viewer-${assetId}`).parentElement.querySelector('.sample-btn');
            const span = btn.querySelector('.sample-label');
            span.textContent = `Sample ${currentSample + 1}`;
        }
        
        // Control functions
        window[`increaseFontSize${assetId}`] = function() {
            currentFontSize = Math.min(72, currentFontSize + 4);
            updateFontStyles();
        };
        
        window[`decreaseFontSize${assetId}`] = function() {
            currentFontSize = Math.max(8, currentFontSize - 4);
            updateFontStyles();
        };
        
        window[`changeSampleText${assetId}`] = function() {
            currentSample = (currentSample + 1) % sampleTexts.length;
            const largeElement = document.getElementById(`font-sample-large-${assetId}`);
            if (largeElement) {
                largeElement.textContent = sampleTexts[currentSample];
            }
            updateSampleButton();
        };
        
        window[`changeFontWeight${assetId}`] = function() {
            currentWeightIndex = (currentWeightIndex + 1) % fontWeights.length;
            currentWeight = fontWeights[currentWeightIndex];
            updateFontStyles();
            updateWeightButton();
        };
        
        window[`changeBackgroundFont${assetId}`] = function() {
            currentBackgroundIndex = (currentBackgroundIndex + 1) % backgroundColors.length;
            const currentBg = backgroundColors[currentBackgroundIndex];
            
            const container = document.querySelector(`#font-viewer-${assetId}`).closest('.texture-viewer-container');
            container.className = container.className.replace(/\b(dark|light|blue|purple|green|gradient)-theme\b/, '');
            container.classList.add(`${currentBg.class}-theme`);
            
            const label = container.querySelector('.bg-label');
            if (label) label.textContent = currentBg.name;
        };
        
        // Initialize when document is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initFontViewer);
        } else {
            initFontViewer();
        }
    })();
    </script>

    <style>
    .font-viewer {
        position: relative;
        background: #000;
        min-height: 400px;
        padding: 20px;
        overflow-y: auto;
    }
    
    .font-preview-container {
        display: flex;
        flex-direction: column;
        gap: 30px;
        color: white;
    }
    
    .font-sample-large {
        font-size: 24px;
        line-height: 1.2;
        text-align: center;
        padding: 20px 0;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    .font-sample-alphabet {
        font-size: 18px;
        line-height: 1.4;
        text-align: center;
        font-family: monospace;
        padding: 15px 0;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    .font-custom-text {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }
    
    .custom-text-input {
        width: 100%;
        padding: 10px;
        background: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 4px;
        color: white;
        font-size: 14px;
        font-family: inherit;
        resize: vertical;
    }
    
    .custom-text-input::placeholder {
        color: rgba(255, 255, 255, 0.5);
    }
    
    .custom-text-input:focus {
        outline: none;
        border-color: #06b6d4;
        background: rgba(255, 255, 255, 0.15);
    }
    
    .font-sample-custom {
        font-size: 20px;
        line-height: 1.3;
        padding: 15px;
        background: rgba(255, 255, 255, 0.05);
        border-radius: 4px;
        min-height: 60px;
        word-wrap: break-word;
    }
    
    /* Font loading styles */
    .font-loading {
        color: white;
        text-align: center;
    }
    
    .font-loading .loading-spinner {
        width: 40px;
        height: 40px;
        border: 4px solid rgba(255, 255, 255, 0.3);
        border-top: 4px solid #06b6d4;
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin: 0 auto 10px;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    .error-message {
        color: #ef4444;
        padding: 20px;
        text-align: center;
    }
    
    /* Theme variations for font viewer */
    .texture-viewer-container.light-theme .font-viewer {
        background: #f5f5f5;
    }
    
    .texture-viewer-container.light-theme .font-preview-container {
        color: #1a1a1a;
    }
    
    .texture-viewer-container.light-theme .font-sample-large,
    .texture-viewer-container.light-theme .font-sample-alphabet {
        border-bottom-color: rgba(0, 0, 0, 0.1);
    }
    
    .texture-viewer-container.light-theme .custom-text-input {
        background: rgba(0, 0, 0, 0.05);
        border-color: rgba(0, 0, 0, 0.2);
        color: #1a1a1a;
    }
    
    .texture-viewer-container.light-theme .custom-text-input::placeholder {
        color: rgba(0, 0, 0, 0.5);
    }
    
    .texture-viewer-container.light-theme .custom-text-input:focus {
        background: rgba(0, 0, 0, 0.1);
        border-color: #3b82f6;
    }
    
    .texture-viewer-container.light-theme .font-sample-custom {
        background: rgba(0, 0, 0, 0.05);
    }
    
    .texture-viewer-container.blue-theme .font-viewer {
        background: #1e3a8a;
    }
    
    .texture-viewer-container.purple-theme .font-viewer {
        background: #581c87;
    }
    
    .texture-viewer-container.green-theme .font-viewer {
        background: #166534;
    }
    
    .texture-viewer-container.gradient-theme .font-viewer {
        background: linear-gradient(45deg, #667eea, #764ba2);
    }
    
    /* Control button styling */
    .size-up-btn:hover,
    .size-down-btn:hover {
        background: rgba(6, 182, 212, 0.2);
    }
    
    /* Responsive design */
    @media (max-width: 768px) {
        .font-viewer {
            padding: 15px;
        }
        
        .font-sample-large {
            font-size: 20px;
        }
        
        .font-sample-alphabet {
            font-size: 14px;
        }
        
        .font-sample-custom {
            font-size: 16px;
        }
    }
    </style>
<?php endif; ?>
        
        <div class="text-center mt-3">
            <a href="<?= htmlspecialchars($asset['asset_file']) ?>" class="btn btn-primary btn-sm me-2" download>
                <i class="bi bi-download"></i> Download <?= basename($asset['asset_file']) ?>
            </a>
            <button class="btn btn-outline-secondary btn-sm" type="button" 
                    data-bs-toggle="collapse" data-bs-target="#securityInfo">
                <i class="bi bi-shield-check"></i> Security Info
            </button>
        </div>
        <div class="collapse mt-2" id="securityInfo">
            <div class="card card-body">
                <?php if (file_exists($filePath)): ?>
                    <small>File Hash (SHA-256): <code><?= $fileHash ?></code></small>
                    <small>File Size: <?= number_format($fileSize / 1024, 2) ?> KB</small>
                    <p class="mb-0 mt-1"><small>You can verify this hash online at <a href="https://www.virustotal.com/gui/home/search" target="_blank">VirusTotal</a> by searching for this hash.</small></p>
                <?php else: ?>
                    <small>File not found.</small>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
<?php endif; ?>
</div>
</div>
</div>
    <div class="text-center mt-3 animate__animated animate__fadeIn animate__delay-1s">
    <button class="btn btn-outline-success me-2 upvote-btn <?= isset($_SESSION['user_id']) ? '' : 'disabled' ?>" data-asset-id="<?= $asset_id ?>" <?= isset($_SESSION['user_id']) ? '' : 'disabled' ?>>
        <i class="bi bi-caret-up-fill"></i> <span id="upvote-count"><?= $asset['upvotes'] ?></span>
    </button>
    <button class="btn btn-outline-danger downvote-btn <?= isset($_SESSION['user_id']) ? '' : 'disabled' ?>" data-asset-id="<?= $asset_id ?>" <?= isset($_SESSION['user_id']) ? '' : 'disabled' ?>>
        <i class="bi bi-caret-down-fill"></i> <span id="downvote-count"><?= $asset['downvotes'] ?></span>
    </button>
    <p class="mt-2">Score: <span id="score"><?= $asset['upvotes'] - $asset['downvotes'] ?></span></p>
    <button class="btn text-danger report-btn mb-3" data-content-type="asset" data-content-id="<?= $asset_id ?>">
        <i class="bi bi-flag"></i> Report asset
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
                <button class="btn btn-primary animate__animated animate__fadeIn animate__delay-1s" id="submit-comment" data-asset-id="<?= $asset_id ?>">Submit Comment</button> 
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
                            <!-- Comment Content -->
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
    <?php $conn->close(); ?>
    <script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
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
				const assetId = button.dataset.assetId;
				const voteType = button.classList.contains('upvote-btn') ? 'upvote' : 'downvote';
				fetch('asset/vote.php', {
					method: 'POST',
					headers: {
						'Content-Type': 'application/x-www-form-urlencoded'
					},
					body: `asset_id=${assetId}&vote_type=${voteType}`
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

    // Get asset ID from submit button
    const assetId = document.getElementById('submit-comment').getAttribute('data-asset-id');

    document.getElementById('submit-comment').addEventListener('click', () => {
        const content = quill.root.innerHTML;
        const plainText = quill.getText().trim();

        if (!plainText) {
            showAlert('Comment cannot be empty.', 'warning');
            return;
        }

        fetch('asset/submit_comment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: `asset_id=${assetId}&content=${encodeURIComponent(content)}`
        }).then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                showAlert(data.error || 'An error occurred.', 'danger');
            }
        }).catch(err => console.error('Error:', err));
    });

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
                submitReplyButton.textContent = 'Submit Reply';
                submitReplyButton.classList.add('btn', 'btn-primary', 'mt-2');
                e.target.parentElement.appendChild(submitReplyButton);

                submitReplyButton.addEventListener('click', () => {
                    const content = replyQuill.root.innerHTML;
                    const plainText = replyQuill.getText().trim();

                    if (!plainText) {
                        showAlert('Reply cannot be empty.', 'warning');
                        return;
                    }

                    fetch('asset/submit_comment.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: `asset_id=${assetId}&parent_id=${parentCommentId}&content=${encodeURIComponent(content)}`
                    }).then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        } else {
                            showAlert(data.error || 'An error occurred.', 'danger');
                        }
                    }).catch(err => console.error('Error:', err));
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
					showAlert('You must log in to vote.', 'danger');
					return;
				}
				const commentId = button.dataset.commentId;
				const voteType = button.classList.contains('upvote-comment-btn') ? 'upvote' : 'downvote';
				fetch('asset/comment_vote.php', {
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
				fetch('asset/comment_vote.php', {
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
            showAlert('Please login to report content', 'danger');
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
            // Show success message with Bootstrap alert
            showAlert('Report submitted successfully!');
            
            // Close modal
            bootstrap.Modal.getInstance(document.getElementById('reportModal')).hide();
        } else {
            throw new Error(data.error || 'Unknown error occurred');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        // Show error message with Bootstrap alert
        showAlert('Error submitting report: ' + error.message, 'danger');
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
	</script>
</body>