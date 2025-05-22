<?php
// Start the session at the top of the page
session_start();

// Database connection (update with your database credentials)
require 'db_connect.php';
// Include the Composer autoload file
require '../vendor/autoload.php';

// Use the ProfanityFilter\Check class
use Mofodojodino\ProfanityFilter\Check;

// Handle post creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_post'])) {
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['error'] = "You must be logged in to create a post.";
        header("Location: forum.php");
        exit;
    }

    $title = $_POST['title'];
    $content = $_POST['content'];

    // Initialize the profanity filter
    $profanityFilter = new Check();

    // Check for profanity in the title and content
    if ($profanityFilter->hasProfanity($title)) {
        $_SESSION['error'] = "Your post title contains inappropriate language.";
        header("Location: forum.php");
        exit;
    }

    if ($profanityFilter->hasProfanity($content)) {
        $_SESSION['error'] = "Your post content contains inappropriate language.";
        header("Location: forum.php");
        exit;
    }

    // Sanitize content to ensure code blocks are wrapped properly
    $content = preg_replace('/<code>(.*?)<\/code>/', '<pre><code>$1</code></pre>', $content);

    $category_id = $_POST['category'];
    $hashtags = isset($_POST['hashtags']) ? $_POST['hashtags'] : '';
    $user_id = $_SESSION['user_id']; // Get the logged-in user's ID

    // Handle image uploads
    $image_paths = [];
    if (isset($_FILES['images']['name'][0]) && $_FILES['images']['error'][0] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/images/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        foreach ($_FILES['images']['name'] as $key => $name) {
            $image_path = $upload_dir . basename($name);
            if (move_uploaded_file($_FILES['images']['tmp_name'][$key], $image_path)) {
                $image_paths[] = $image_path;
            }
        }
    }

    // Handle video uploads
    $video_paths = [];
    if (isset($_FILES['videos']['name'][0]) && $_FILES['videos']['error'][0] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/videos/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        foreach ($_FILES['videos']['name'] as $key => $name) {
            $video_path = $upload_dir . basename($name);
            if (move_uploaded_file($_FILES['videos']['tmp_name'][$key], $video_path)) {
                $video_paths[] = $video_path;
            }
        }
    }

    // Update the INSERT query to include user_id
    $stmt = $conn->prepare("INSERT INTO posts (title, content, category_id, hashtags, images, videos, user_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssisssi", $title, $content, $category_id, $hashtags, json_encode($image_paths), json_encode($video_paths), $user_id);
    $stmt->execute();
    $stmt->close();

    header("Location: forum.php");
    exit;
}



// Fetch posts
$search = isset($_GET['search']) ? $_GET['search'] : '';
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'newest';
$order_by = "created_at DESC"; // Default sorting

// Determine the sorting logic based on the filter
if ($filter === 'oldest') {
    $order_by = "created_at ASC";
} elseif ($filter === 'highest_score') {
    $order_by = "score DESC";
}

// At the top of your file, after the database connection
$posts_per_page = 5;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $posts_per_page;

// First, get total number of posts for pagination
$count_sql = "SELECT COUNT(DISTINCT posts.id) as total_count 
              FROM posts 
              JOIN categories ON posts.category_id = categories.id
              JOIN users ON posts.user_id = users.user_id
              WHERE posts.status != 'hidden'";  // Add this condition

if ($search) {
    $count_sql .= " AND (posts.title LIKE '%$search%' OR posts.content LIKE '%$search%' OR posts.hashtags LIKE '%$search%')";
}
if ($category_filter) {
    $count_sql .= " AND category_id = $category_filter";
}

$total_result = $conn->query($count_sql);
$total_row = $total_result->fetch_assoc();
$total_posts = $total_row['total_count'];
$total_pages = max(1, ceil($total_posts / $posts_per_page));

// Ensure page number is within valid range
if ($page > $total_pages) {
    $page = $total_pages;
}

// Recalculate offset with validated page number
$offset = ($page - 1) * $posts_per_page;

// Main query for posts with LIMIT and OFFSET
$sql = "SELECT posts.*, categories.name AS category_name, 
               users.username, users.user_id,
               posts.upvotes, posts.downvotes, posts.views,
               (posts.upvotes - posts.downvotes) AS score 
        FROM posts 
        JOIN categories ON posts.category_id = categories.id
        JOIN users ON posts.user_id = users.user_id
        WHERE posts.status != 'hidden'";

if ($search) {
    $sql .= " AND (posts.title LIKE '%$search%' OR posts.content LIKE '%$search%' OR posts.hashtags LIKE '%$search%')";
}
if ($category_filter) {
    $sql .= " AND category_id = $category_filter";
}

// Add the ORDER BY and LIMIT clauses
$sql .= " ORDER BY $order_by LIMIT $posts_per_page OFFSET $offset";

$result = $conn->query($sql);

// Fetch categories for the filter
$categories = $conn->query("SELECT * FROM categories");
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">

<head> <?php require 'header.php'; ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
	<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
	<title>MoonArrow Studios - Forum</title>
	<style>
	/* Style for Quill placeholder */
	.ql-editor.ql-blank::before {
		color: #6c757d;
		/* This is the Bootstrap 'text-body-tertiry' color */
	}

	/* Zoom effect on hover for post cards */
	.card {
		transition: transform 0.2s;
		/* Smooth transition */
	}

	.card:hover {
		transform: scale(1.05);
		/* Slightly zoom in */
	}
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
}

body {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", "Noto Sans", Helvetica, Arial, sans-serif;
    background-color: var(--color-canvas-default);
    color: var(--color-fg-default);
    line-height: 1.5;
    font-size: 14px;
}

/* Form Controls */
.form-control, .form-select {
    padding: 5px 12px;
    font-size: 14px;
    line-height: 20px;
    color: var(--color-fg-default);
    background-color: var(--color-input-bg);
    border: 1px solid var(--color-border-default);
    border-radius: 6px;
    box-shadow: var(--color-primer-shadow-inset);
    transition: border-color .15s ease-in-out, box-shadow .15s ease-in-out;
}

.form-control:focus, .form-select:focus {
    border-color: var(--color-accent-fg);
    outline: none;
    box-shadow: 0 0 0 3px rgba(9, 105, 218, 0.3);
}

/* Labels */
.form-label {
    font-weight: 500;
    font-size: 14px;
    color: var(--color-fg-default);
    margin-bottom: 8px;
}

/* Buttons */
.btn {
    border-radius: 6px;
    padding: 5px 16px;
    font-size: 14px;
    font-weight: 500;
    line-height: 20px;
    transition: .2s cubic-bezier(0.3, 0, 0.5, 1);
}

.btn-primary {
    color: #ffffff;
    background-color: var(--color-btn-primary-bg);
    border: 1px solid rgba(27, 31, 36, 0.15);
    box-shadow: 0 1px 0 rgba(27, 31, 36, 0.1);
}

.btn-primary:hover {
    background-color: var(--color-btn-primary-hover-bg);
}

/* Cards for Posts */
.card {
    background-color: var(--color-card-bg);
    border: 1px solid var(--color-card-border);
    border-radius: 6px;
    margin-bottom: 16px;
    transition: border-color 0.2s ease;
}

.card:hover {
    border-color: var(--color-accent-fg);
    transform: translateY(-2px);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.card-title {
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.card-text {
    font-size: 0.9rem;
    color: var(--color-fg-muted);
    margin-bottom: 0.5rem;
}

.card-body {
    padding: 16px;
}

/* Modal Styling */
.modal-content {
    background-color: var(--color-modal-bg);
    border: 1px solid var(--color-border-default);
    border-radius: 6px;
}

.modal-header {
    background-color: var(--color-header-bg);
    border-bottom: 1px solid var(--color-border-muted);
    padding: 16px;
}

.modal-title {
    font-size: 20px;
    font-weight: 600;
}

.modal-body {
    padding: 16px;
}

/* Quill Editor Customization */
.ql-container {
    border-radius: 0 0 6px 6px !important;
    background-color: var(--color-input-bg) !important;
    border-color: var(--color-border-default) !important;
}

.ql-toolbar {
    border-radius: 6px 6px 0 0 !important;
    background-color: var(--color-header-bg) !important;
    border-color: var(--color-border-default) !important;
}

.ql-editor {
    min-height: 200px;
    color: var(--color-fg-default) !important;
}

/* Search and Filter Section */
.search-filter-section {
    background-color: var(--color-canvas-subtle);
    padding: 16px;
    border-radius: 6px;
    margin-bottom: 24px;
}

/* Post Metadata */
.post-metadata {
    font-size: 12px;
    color: var(--color-fg-muted);
    margin-bottom: 8px;
}

/* Rating Section */
.rating-section {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 8px 0;
    border-top: 1px solid var(--color-border-muted);
    margin-top: 16px;
}

.rating-item {
    display: flex;
    align-items: center;
    gap: 4px;
}

/* Hashtags */
.hashtags {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-top: 0.5rem;
}

.hashtags .badge {
    background-color: var(--color-canvas-subtle);
    color: var(--color-fg-default);
    border: 1px solid var(--color-border-default);
    font-size: 0.8rem;
    padding: 0.25rem 0.5rem;
}

.hashtag {
    background-color: var(--color-canvas-subtle);
    padding: 2px 8px;
    border-radius: 12px;
    margin-right: 4px;
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
.main-container {
    display: grid;
    grid-template-columns: 300px minmax(0, 1fr);
    gap: 2rem;
    padding: 0 20px;
    max-width: 1400px;
    margin: 0 auto;
    min-height: 100vh;
}

.sidebar {
    width: 300px;
    position: sticky;
    top: 1rem;
    height: fit-content;
}

.sidebar h2 {
    text-align: left;
    margin-bottom: 20px;
}

.sidebar-lists {
    display: flex;
    gap: 15px;
    justify-content: flex-start;
}

.sidebar-row {
    flex: 1;
    width: 170px;
}

.list-group {
    margin-bottom: 20px;
    width: 170px;
    min-height: 200px;
}

.list-group-item {
    font-size: 0.9rem;
    padding: 8px 12px;
    word-break: break-word;
    height: 40px;
    display: flex;
    align-items: center;
}

.post-list {
    display: flex;
    flex-direction: column;
    flex: 1;
}

.post-list .card {
    width: 100%;
    margin: 0;
    border: 1px solid var(--color-border-default);
    border-radius: 8px;
    transition: transform 0.2s, box-shadow 0.2s;
}


.post-list .card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}


.list-group-item {
            background-color: var(--color-card-bg);
            border: 1px solid var(--color-card-border);
            color: var(--color-fg-default);
        }

hr {
    border: 0;
    height: 1px;
    background-color: var(--color-card-border);
    margin: 10px 0;
}
.pagination {
    margin: 0;
    display: flex;
    justify-content: center;
    gap: 5px;
}

.pagination .page-item .page-link {
    color: var(--color-fg-default);
    background-color: var(--color-card-bg);
    border: 1px solid var(--color-border-default);
    padding: 0.5rem 0.75rem;
    margin: 0 0.25rem;
    border-radius: 6px;
    transition: all 0.2s ease;
}

.pagination .page-item .page-link:hover {
    background-color: var(--color-canvas-subtle);
}

.pagination .page-item.active .page-link {
    background-color: var(--color-accent-fg);
    color: #ffffff;
    border-color: var(--color-accent-fg);
}

.pagination .page-item.disabled .page-link {
    color: var(--color-fg-muted);
    pointer-events: none;
    background-color: var(--color-canvas-subtle);
}
/* Add these styles to your existing CSS */
.leaderboard-card {
    padding: 1rem;
}

        @media (max-width: 992px) {
            .leaderboard-card {
                position: static;
                max-height: none;
                overflow-y: visible;
            }
        }

        .leaderboard-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: var(--color-fg-default);
        }

        .leaderboard-section {
            margin-bottom: 1.5rem;
        }

        .leaderboard-section-title {
    font-size: 0.875rem;
    color: var(--color-fg-muted);
    margin-bottom: 1rem;
    font-weight: 500;
    text-transform: uppercase;
}

.leaderboard-item {
    padding: 0.75rem 0;
    border-bottom: 1px solid var(--color-border-muted);
    display: flex;
    align-items: center;
    text-decoration: none;
    color: var(--color-fg-default);
}

.leaderboard-item:last-child {
    border-bottom: none;
}

.leaderboard-item:hover {
    background-color: var(--color-canvas-subtle);
}

.leaderboard-rank {
    width: 24px;
    font-size: 0.875rem;
    color: var(--color-fg-muted);
    font-weight: 500;
    margin-right: 0.5rem;
}

        .leaderboard-content {
            flex: 1;
            font-size: 0.875rem;
        }

        .leaderboard-stat {
            font-size: 0.75rem;
            color: var(--color-fg-muted);
            margin-left: 0.5rem;
        }

        .trending-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .tag-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            background-color: var(--color-canvas-subtle);
            color: var(--color-fg-muted);
            text-decoration: none;
            transition: all 0.2s ease;
            border: 1px solid var(--color-border-muted);
        }

        .tag-badge:hover {
            background-color: var(--color-canvas-subtle);
            color: var(--color-accent-fg);
            border-color: var(--color-accent-fg);
        }

        @media (max-width: 992px) {
    .main-container {
        grid-template-columns: 1fr;
        padding: 0 15px;
    }
    
    /* Move sidebar/leaderboard to bottom */
    .main-container {
        display: flex;
        flex-direction: column;
    }
    
    .sidebar {
        order: 2;
        width: 100%;
        position: static;
        margin-top: 2rem;
    }
    
    /* Adjust leaderboard card styles for mobile */
    .leaderboard-card {
        margin-bottom: 2rem;
    }
    
    /* Make cards full width on mobile */
    .card {
        width: 100%;
    }
}

@media (max-width: 576px) {
    .container {
        padding: 0 10px;
    }
    
    .card-body {
        padding: 1rem;
    }
    
    .leaderboard-title {
        font-size: 1.1rem;
    }
    
    .trending-tags {
        gap: 0.25rem;
    }
    
    .tag-badge {
        font-size: 0.7rem;
    }
}


.pagination-wrapper {
    margin-top: auto; /* Push to bottom */
    padding: 20px 0;
    display: flex;
    justify-content: center;
}



.posts-container {
    flex: 1; /* Take up available space */
    width: 100%;
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

/* Update search and filter section styles */
.d-flex.justify-content-between {
    flex-wrap: wrap;
    gap: 1rem;
}

@media (max-width: 768px) {
    /* Stack search form elements vertically */
    .d-flex.justify-content-between {
        flex-direction: column;
    }
    
    form.d-flex {
        flex-direction: column;
        width: 100%;
    }
    
    form.d-flex input,
    form.d-flex select,
    form.d-flex button {
        width: 100%;
        margin-right: 0 !important;
        margin-bottom: 0.5rem;
    }
    
    /* Make create post button full width */
    .btn-success {
        width: 100%;
    }
}
/* Add/Update these styles in your CSS */
.card {
    position: relative;
    overflow: hidden;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1) !important;
    transform: translateZ(0); /* Hardware acceleration */
    will-change: transform; /* Prepare browser for animation */
    border: 1px solid transparent; /* Add this line */
}

.card::before,
.card::after {
    content: '';
    position: absolute;
    left: 0;
    width: 100%;
    height: 2px;
    background: rgba(88, 166, 255, 0.3); /* Use your theme's blue color */
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

.card:hover {
    box-shadow: 0 0 25px 5px rgba(88, 166, 255, 0.2),
                0 4px 20px rgba(0, 0, 0, 0.3) !important;
    border-color: rgba(88, 166, 255, 0.3) !important;
}

.card:hover::before,
.card:hover::after {
    transform: translateX(0);
    opacity: 1;
}

.card-body {
    position: relative;
    z-index: 1; /* Ensure content stays above borders */
}

.card-body::before,
.card-body::after {
    content: '';
    position: absolute;
    top: 0;
    height: 100%;
    width: 2px;
    background: rgba(88, 166, 255, 0.3);
    transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1);
    z-index: 2;
    opacity: 0;
    box-shadow: 0 0 15px rgba(88, 166, 255, 0.3);
}

.card-body::before {
    left: 0;
    transform: translateY(105%);
}

.card-body::after {
    right: 0;
    transform: translateY(-105%);
}

.card:hover .card-body::before,
.card:hover .card-body::after {
    transform: translateY(0);
    opacity: 1;
}

/* Custom Alert Animation Styles */
.alert-container {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 1060; /* Increased from 1050 to be above Bootstrap modals (1055) */
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
    z-index: 1061; /* Added explicit z-index for the alert itself */
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
	</style>
<script>
document.addEventListener("DOMContentLoaded", function() {
    // Initialize Quill editor
    var quill = new Quill('#editor', {
        placeholder: 'Your post content goes here',
        theme: 'snow',
        modules: {
            toolbar: [
                [{ header: [3, 4, false] }],
                ['bold', 'italic', 'underline'],
                ['blockquote', 'code-block'],
                [{ list: 'ordered' }, { list: 'bullet' }],
                ['link']
            ]
        }
    });

    // Custom Alert Functions
    function showAlert(message, type = 'info') {
        const alertContainer = document.getElementById('alertContainer');
        const alertElement = document.createElement('div');
        alertElement.className = `custom-alert custom-alert-${type}`;
        let iconClass = 'bi-info-circle';
        if (type === 'success') iconClass = 'bi-check-circle';
        if (type === 'danger')  iconClass = 'bi-exclamation-triangle';
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

    // Handle URL parameters for alerts
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('alert')) {
        showAlert(urlParams.get('alert'), urlParams.get('type') || 'info');
    }

    // Handle session errors from PHP
    <?php if (isset($_SESSION['error'])): ?>
        showAlert('<?= addslashes($_SESSION['error']) ?>', 'danger');
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    let hashtags = new Set();

    // Submit handler
    const form = document.querySelector('#createPostForm');
    form.addEventListener('submit', function(e) {
        const contentInput = document.querySelector('input[name="content"]');
        const quillContent = quill.root.innerHTML.trim();
        
        let hasError = false;
        
        // Check content
        if(quillContent === '' || quillContent === '<p><br></p>') {
            e.preventDefault();
            showAlert('Post content is required!', 'danger');
            hasError = true;
        }
        
        // Check for hashtag badges
        if(hashtags.size === 0) {
            e.preventDefault();
            if (!hasError) {
                showAlert('At least one hashtag is required!', 'danger');
            }
            hasError = true;
        }

        if (!hasError) {
            contentInput.value = quillContent;
        }
    });

    // Hashtag handling
    const hashtagInput = document.querySelector('#hashtags');
    const hashtagContainer = document.createElement('div');
    hashtagContainer.className = 'hashtag-container d-flex flex-wrap gap-2 mb-2';
    const hiddenHashtagInput = document.createElement('input');
    hiddenHashtagInput.type = 'hidden';
    hiddenHashtagInput.name = 'hashtags';
    
    hashtagInput.parentNode.insertBefore(hashtagContainer, hashtagInput);
    hashtagInput.parentNode.appendChild(hiddenHashtagInput);
    
    hashtagInput.style.paddingLeft = '20px';
    
    const hashPrefix = document.createElement('div');
    hashPrefix.textContent = '#';
    hashPrefix.style.position = 'absolute';
    hashPrefix.style.left = '8px';
    hashPrefix.style.top = '50%';
    hashPrefix.style.transform = 'translateY(-50%)';
    hashPrefix.style.color = '#6c757d';
    hashPrefix.style.pointerEvents = 'none';
    
    const inputWrapper = document.createElement('div');
    inputWrapper.style.position = 'relative';
    hashtagInput.parentNode.insertBefore(inputWrapper, hashtagInput);
    inputWrapper.appendChild(hashPrefix);
    inputWrapper.appendChild(hashtagInput);
    
    function addHashtag(tag) {
        if (tag && !hashtags.has(tag)) {
            hashtags.add(tag);
            updateHashtagDisplay();
            updateHiddenInput();
        }
    }
    
    function removeHashtag(tag) {
        hashtags.delete(tag);
        updateHashtagDisplay();
        updateHiddenInput();
    }
    
    function updateHashtagDisplay() {
        hashtagContainer.innerHTML = '';
        hashtags.forEach(tag => {
            const badge = document.createElement('span');
            badge.className = 'badge bg-dark d-flex align-items-center gap-2';
            badge.innerHTML = `
                #${tag}
                <button type="button" class="btn-close btn-close-white" style="font-size: 0.5rem;"></button>
            `;
            badge.querySelector('.btn-close').addEventListener('click', () => removeHashtag(tag));
            hashtagContainer.appendChild(badge);
        });
    }
    
    function updateHiddenInput() {
        hiddenHashtagInput.value = Array.from(hashtags).map(tag => `#${tag}`).join(' ');
    }
    
    hashtagInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            const tag = this.value.trim().replace(/^#/, '');
            if (tag) {
                addHashtag(tag);
                this.value = '';
            }
        }
    });
    
    hashtagInput.addEventListener('paste', function(e) {
        e.preventDefault();
        const paste = (e.clipboardData || window.clipboardData).getData('text');
        const tags = paste.split(/[\s,]+/);
        tags.forEach(tag => {
            tag = tag.trim().replace(/^#/, '');
            if (tag) addHashtag(tag);
        });
    });

    // Hover card functionality
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
                
                const avatarContent = userData.profile_picture 
                    ? `<img class="hover-card-avatar" src="${userData.profile_picture}" alt="${userData.username}'s avatar">` 
                    : `<div class="hover-card-avatar d-flex align-items-center justify-content-center bg-dark">
                         <i class="bi bi-person-fill text-light" style="font-size: 1.5rem;"></i>
                       </div>`;
                
                let bannerContent = userData.banner 
                    ? `<img src="${userData.banner}" class="hover-card-banner" alt="User banner">`
                    : `<div class="hover-card-banner" style="background-color: rgb(108, 117, 125);"></div>`;
                
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

    // Animation enhancements
    const cards = document.querySelectorAll('.card');
    cards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.classList.add('animate__pulse');
        });
        card.addEventListener('mouseleave', function() {
            this.classList.remove('animate__pulse');
        });
    });

    const leaderboardItems = document.querySelectorAll('.leaderboard-item');
    leaderboardItems.forEach(item => {
        item.addEventListener('mouseenter', function() {
            this.classList.add('animate__pulse');
        });
        item.addEventListener('mouseleave', function() {
            this.classList.remove('animate__pulse');
        });
    });

    const tagBadges = document.querySelectorAll('.tag-badge');
    tagBadges.forEach(badge => {
        badge.addEventListener('mouseenter', function() {
            this.classList.add('animate__pulse');
        });
        badge.addEventListener('mouseleave', function() {
            this.classList.remove('animate__pulse');
        });
    });
});
</script>
</head>

<body class="">
    <div class="alert-container" id="alertContainer"></div>
<div class="container">
        <!-- Search, Filter, and Create Post Section -->
        <div class="d-flex justify-content-between align-items-center my-4 animate__animated animate__fadeIn">
            <form method="GET" class="d-flex align-items-center">
                <input type="text" name="search" class="form-control me-2 bg-dark animate__animated animate__fadeInLeft" placeholder="Search" value="<?= htmlspecialchars($search) ?>">
                <select name="category" class="form-select me-2 bg-dark text-light animate__animated animate__fadeInLeft">
                    <option value="">All Categories</option>
                    <?php while ($row = $categories->fetch_assoc()): ?>
                        <option value="<?= $row['id'] ?>" <?= $category_filter == $row['id'] ? 'selected' : '' ?>><?= $row['name'] ?></option>
                    <?php endwhile; ?>
                </select>
                <select name="filter" class="form-select me-2 bg-dark text-light animate__animated animate__fadeInLeft">
                    <option value="newest" <?= isset($_GET['filter']) && $_GET['filter'] == 'newest' ? 'selected' : '' ?>>Newest</option>
                    <option value="oldest" <?= isset($_GET['filter']) && $_GET['filter'] == 'oldest' ? 'selected' : '' ?>>Oldest</option>
                    <option value="highest_score" <?= isset($_GET['filter']) && $_GET['filter'] == 'highest_score' ? 'selected' : '' ?>>Highest Score</option>
                </select>
                <button type="submit" class="btn btn-primary animate__animated animate__fadeInLeft">Search</button>
            </form>
            <?php if (isset($_SESSION['user_id'])): ?>
                <button class="btn btn-success animate__animated animate__fadeInRight" data-bs-toggle="modal" data-bs-target="#createPostModal">Create Post</button>
            <?php endif; ?>
        </div>
        <hr class="animate__animated animate__fadeIn">

        <!-- Main Content -->
        <div class="main-container animate__animated animate__fadeIn">
            <!-- Sidebar with Leaderboard -->
            <div class="sidebar animate__animated animate__fadeInLeft">
                <div class="leaderboard-card">
                    <h2 class="leaderboard-title animate__animated animate__fadeIn">Leaderboard</h2>
                    
                    <!-- Top Posts Section -->
                    <div class="leaderboard-section animate__animated animate__fadeIn">
                        <h3 class="leaderboard-section-title">Top Posts Today</h3>
                        <?php
                        $top_posts = $conn->query("SELECT * FROM posts WHERE status != 'hidden' ORDER BY upvotes DESC LIMIT 5");
                        $rank = 1;
                        while ($post = $top_posts->fetch_assoc()): ?>
                            <a href="view_post.php?id=<?= $post['id'] ?>" class="leaderboard-item animate__animated animate__fadeInUp-<?= $rank ?>s">
                                <span class="leaderboard-rank">#<?= $rank++ ?></span>
                                <span class="leaderboard-content"><?= htmlspecialchars($post['title']) ?></span>
                            </a>
                        <?php endwhile; ?>
                    </div>

                    <!-- Top Users Section -->
                    <div class="leaderboard-section animate__animated animate__fadeIn">
                        <h3 class="leaderboard-section-title">Most Followed Users</h3>
                        <?php
                        $top_followed = $conn->query("SELECT users.user_id, users.username, COUNT(follows.follower_id) AS followers 
                                                    FROM users 
                                                    LEFT JOIN follows ON users.user_id = follows.following_id 
                                                    GROUP BY users.user_id 
                                                    ORDER BY followers DESC 
                                                    LIMIT 5");
                        $user_rank = 1;
                        while ($user = $top_followed->fetch_assoc()): ?>
                            <a href="profile.php?id=<?= $user['user_id'] ?>" class="leaderboard-item animate__animated animate__fadeInUp<?= $user_rank++ ?>s">
                                <div class="leaderboard-content">
                                    <?= htmlspecialchars($user['username']) ?>
                                    <span class="leaderboard-stat"><?= $user['followers'] ?> followers</span>
                                </div>
                            </a>
                        <?php endwhile; ?>
                    </div>

                    <!-- Trending Tags Section -->
                    <div class="leaderboard-section animate__animated animate__fadeIn">
                        <h3 class="leaderboard-section-title">Trending Tags</h3>
                        <div class="trending-tags">
                            <?php
                            $top_hashtags = $conn->query("
                            WITH RECURSIVE split_hashtags AS (
                                SELECT 
                                    SUBSTRING_INDEX(SUBSTRING_INDEX(hashtags, ' ', 1), ' ', -1) AS hashtag,
                                    SUBSTRING(hashtags, LENGTH(SUBSTRING_INDEX(hashtags, ' ', 1)) + 2) AS remaining_string,
                                    created_at
                                FROM posts
                                WHERE hashtags != '' AND created_at >= NOW() - INTERVAL 7 DAY AND status != 'hidden'
                                UNION ALL
                                SELECT 
                                    SUBSTRING_INDEX(SUBSTRING_INDEX(remaining_string, ' ', 1), ' ', -1),
                                    SUBSTRING(remaining_string, LENGTH(SUBSTRING_INDEX(remaining_string, ' ', 1)) + 2),
                                    created_at
                                FROM split_hashtags
                                WHERE remaining_string != ''
                            )
                            SELECT 
                                hashtag,
                                COUNT(*) as count
                            FROM split_hashtags
                            GROUP BY hashtag
                            ORDER BY count DESC, hashtag ASC
                            LIMIT 5
                        ");
                            $tag_index = 1;
                            while ($hashtag = $top_hashtags->fetch_assoc()): ?>
                                <a href="?search=<?= urlencode($hashtag['hashtag']) ?>" class="tag-badge animate__animated animate__fadeInUp animate__delay-<?= $tag_index++ ?>s">
                                    <?= htmlspecialchars($hashtag['hashtag']) ?>
                                </a>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Post List -->
            <div class="post-list animate__animated animate__fadeInRight">
                <h2 class="mt-4 animate__animated animate__fadeIn">Posts</h2>
                <div class="posts-container">
                    <?php 
                    $post_count = 0;
                    while ($post = $result->fetch_assoc()): 
                        $post_count++;
                        $animation_delay = min($post_count * 0.15, 2); // Cap the delay at 2 seconds
                    ?>
                        <div class="card animate__animated animate__fadeInUp" style="animation-delay: <?= $animation_delay ?>s;">
                            <div class="card-body">
                                <h3 class="card-title">
                                    <a href="view_post.php?id=<?= $post['id'] ?>" class="text-decoration-none">
                                        <?= htmlspecialchars($post['title'] ?? 'No Title') ?>
                                    </a>
                                </h3>
                                <p class="card-text text-light">
                                    <em>Posted on <?= $post['created_at'] ?> by 
                                        <a href="profile.php?id=<?= htmlspecialchars($post['user_id']) ?>" class="text-decoration-none">
                                            <?= htmlspecialchars($post['username']) ?>
                                        </a>
                                    </em>
                                </p>
                                <p class="card-text"><strong>Category:</strong> <?= htmlspecialchars($post['category_name'] ?? 'Uncategorized') ?></p>
                                <p class="card-text"><strong>Hashtags:</strong> <?= htmlspecialchars($post['hashtags'] ?? '') ?></p>
                                <p class="card-text">
                                    <strong>Rating:</strong> 
                                    <i class="bi bi-caret-up-fill"></i><?= $post['upvotes'] ?? 0 ?> 
                                    <i class="bi bi-caret-down-fill"></i><?= $post['downvotes'] ?? 0 ?> 
                                    Score: <?= $post['score'] ?? 0 ?>
                                    <span class="ms-3"><i class="bi bi-eye-fill"></i> <?= $post['views'] ?? 0 ?> views</span>
                                </p>
                            </div>
                        </div>
                    <?php endwhile; ?>
                    <!-- Pagination -->
        <div class="pagination-wrapper animate__animated animate__fadeIn animate__delay-1s">
            <nav aria-label="Page navigation">
                <ul class="pagination">
                    <!-- First Page -->
                    <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=1<?= isset($_GET['search']) ? '&search='.urlencode($_GET['search']) : '' ?><?= isset($_GET['category']) ? '&category='.urlencode($_GET['category']) : '' ?><?= isset($_GET['filter']) ? '&filter='.urlencode($_GET['filter']) : '' ?>">First</a>
                    </li>
                    
                    <!-- Previous Page -->
                    <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= max(1, $page - 1) ?><?= isset($_GET['search']) ? '&search='.urlencode($_GET['search']) : '' ?><?= isset($_GET['category']) ? '&category='.urlencode($_GET['category']) : '' ?><?= isset($_GET['filter']) ? '&filter='.urlencode($_GET['filter']) : '' ?>">Previous</a>
                    </li>
                    
                    <!-- Page Numbers -->
                    <?php
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);
                    
                    for ($i = $start_page; $i <= $end_page; $i++): 
                    ?>
                        <li class="page-item <?= ($i == $page) ? 'active animate__animated animate__pulse animate__infinite' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?><?= isset($_GET['search']) ? '&search='.urlencode($_GET['search']) : '' ?><?= isset($_GET['category']) ? '&category='.urlencode($_GET['category']) : '' ?><?= isset($_GET['filter']) ? '&filter='.urlencode($_GET['filter']) : '' ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    
                    <!-- Next Page -->
                    <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= min($total_pages, $page + 1) ?><?= isset($_GET['search']) ? '&search='.urlencode($_GET['search']) : '' ?><?= isset($_GET['category']) ? '&category='.urlencode($_GET['category']) : '' ?><?= isset($_GET['filter']) ? '&filter='.urlencode($_GET['filter']) : '' ?>">Next</a>
                    </li>
                    
                    <!-- Last Page -->
                    <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= $total_pages ?><?= isset($_GET['search']) ? '&search='.urlencode($_GET['search']) : '' ?><?= isset($_GET['category']) ? '&category='.urlencode($_GET['category']) : '' ?><?= isset($_GET['filter']) ? '&filter='.urlencode($_GET['filter']) : '' ?>">Last</a>
                    </li>
                </ul>
            </nav>
        </div>
    </div>
                </div>
            </div>
        </div>

        <!-- Modal for Creating Post -->
        <div class="modal fade" id="createPostModal" tabindex="-1" aria-labelledby="createPostModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content animate__animated animate__zoomIn">
                    <div class="modal-header">
                        <h5 class="modal-title" id="createPostModalLabel">Create Post</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="createPostForm" method="POST" enctype="multipart/form-data">
                            <div class="mb-3 animate__animated animate__fadeIn">
                                <label for="title" class="form-label">Title</label>
                                <input type="text" name="title" id="title" class="form-control bg-dark" placeholder="Your post title goes here" required>
                            </div>
                            <div class="mb-3 animate__animated animate__fadeIn">
                                <label for="content" class="form-label">Content</label>
                                <div id="editor" style="height: 200px; border: 1px solid #ccc;"></div>
                                <input type="hidden" name="content">
                            </div>
                            <div class="mb-3 animate__animated animate__fadeIn">
                                <label for="category" class="form-label">Category</label>
                                <select name="category" id="category" class="form-select bg-dark text-light" required>
                                    <option value="" class="">Select Category</option>
                                    <?php $categories->data_seek(0); while ($row = $categories->fetch_assoc()): ?>
                                        <option value="<?= $row['id'] ?>"><?= $row['name'] ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="mb-3 animate__animated animate__fadeIn ">
                                <label for="hashtags" class="form-label">Hashtags</label>
                                <input type="text" name="hashtags" id="hashtags" class="form-control bg-dark" placeholder="e.g., #2025, #unity, #unrealengine">
                            </div>
                            <div class="mb-3 animate__animated animate__fadeIn">
                                <label for="images" class="form-label">Images</label>
                                <input type="file" name="images[]" id="images" class="form-control" accept="image/*" multiple>
                            </div>
                            <div class="mb-3 animate__animated animate__fadeIn">
                                <label for="videos" class="form-label">Videos</label>
                                <input type="file" name="videos[]" id="videos" class="form-control" accept="video/*" multiple>
                            </div>
                            <div class="mt-3 animate__animated animate__fadeIn d-grid">
                        <button type="submit" name="create_post" class="btn btn-primary animate__animated animate__infinite w-100">Post</button>
                    </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    <!-- Add JavaScript for enhancing animations -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Add hover animations to cards
        const cards = document.querySelectorAll('.card');
        cards.forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.classList.add('animate__pulse');
            });
            card.addEventListener('mouseleave', function() {
                this.classList.remove('animate__pulse');
            });
        });

        // Add animations to leaderboard items on hover
        const leaderboardItems = document.querySelectorAll('.leaderboard-item');
        leaderboardItems.forEach(item => {
            item.addEventListener('mouseenter', function() {
                this.classList.add('animate__pulse');
            });
            item.addEventListener('mouseleave', function() {
                this.classList.remove('animate__pulse');
            });
        });

        // Add animations to tag badges on hover
        const tagBadges = document.querySelectorAll('.tag-badge');
        tagBadges.forEach(badge => {
            badge.addEventListener('mouseenter', function() {
                this.classList.add('animate__pulse');
            });
            badge.addEventListener('mouseleave', function() {
                this.classList.remove('animate__pulse');
            });
        });
    });
    </script>
</body>

</html> <?php $conn->close(); ?>