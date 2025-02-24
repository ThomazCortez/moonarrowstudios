<?php
// Start the session at the top of the page
session_start();

// Database connection (update with your database credentials)
require 'db_connect.php';
// Include the Composer autoload file
require '../vendor/autoload.php';

// Use the ProfanityFilter\Check class
use Mofodojodino\ProfanityFilter\Check;

// Handle asset creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_asset'])) {
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['error'] = "You must be logged in to create a asset.";
        header("Location: marketplace.php");
        exit;
    }

    $title = $_POST['title'];
    $content = $_POST['content'];

    // Initialize the profanity filter
    $profanityFilter = new Check();

    // Check for profanity in the title and content
    if ($profanityFilter->hasProfanity($title)) {
        $_SESSION['error'] = "Your asset title contains inappropriate language.";
        header("Location: marketplace.php");
        exit;
    }

    if ($profanityFilter->hasProfanity($content)) {
        $_SESSION['error'] = "Your asset content contains inappropriate language.";
        header("Location: marketplace.php");
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

    // Handle preview image upload
    $preview_image_path = '';
    if (isset($_FILES['preview_image']['name']) && $_FILES['preview_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/previews/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $preview_image_path = $upload_dir . basename($_FILES['preview_image']['name']);
        if (move_uploaded_file($_FILES['preview_image']['tmp_name'], $preview_image_path)) {
            // Image uploaded successfully
        }
    }

    // Update the INSERT query to include preview_image
    $stmt = $conn->prepare("INSERT INTO assets (title, content, category_id, hashtags, images, videos, user_id, preview_image) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssisssis", $title, $content, $category_id, $hashtags, json_encode($image_paths), json_encode($video_paths), $user_id, $preview_image_path);
    $stmt->execute();
    $stmt->close();

    header("Location: marketplace.php");
    exit;
}



// Fetch assets
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
$assets_per_page = 5;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $assets_per_page;

// First, get total number of assets for pagination
$count_sql = "SELECT COUNT(DISTINCT assets.id) as total_count 
              FROM assets 
              JOIN asset_categories ON assets.category_id = asset_categories.id
              JOIN users ON assets.user_id = users.user_id
              WHERE 1";

if ($search) {
    $count_sql .= " AND (assets.title LIKE '%$search%' OR assets.content LIKE '%$search%' OR assets.hashtags LIKE '%$search%')";
}
if ($category_filter) {
    $count_sql .= " AND category_id = $category_filter";
}

$total_result = $conn->query($count_sql);
$total_row = $total_result->fetch_assoc();
$total_assets = $total_row['total_count'];
$total_pages = max(1, ceil($total_assets / $assets_per_page));

// Ensure page number is within valid range
if ($page > $total_pages) {
    $page = $total_pages;
}

// Recalculate offset with validated page number
$offset = ($page - 1) * $assets_per_page;

// Main query for assets with LIMIT and OFFSET
$sql = "SELECT assets.*, asset_categories.name AS category_name, 
               users.username, users.user_id,
               assets.upvotes, assets.downvotes, 
               (assets.upvotes - assets.downvotes) AS score 
        FROM assets 
        JOIN asset_categories ON assets.category_id = asset_categories.id
        JOIN users ON assets.user_id = users.user_id
        WHERE 1";

if ($search) {
    $sql .= " AND (assets.title LIKE '%$search%' OR assets.content LIKE '%$search%' OR assets.hashtags LIKE '%$search%')";
}
if ($category_filter) {
    $sql .= " AND category_id = $category_filter";
}

// Add the ORDER BY and LIMIT clauses
$sql .= " ORDER BY $order_by LIMIT $assets_per_page OFFSET $offset";

$result = $conn->query($sql);

// Fetch asset_categories for the filter
$asset_categories = $conn->query("SELECT * FROM asset_categories");
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">

<head> <?php require 'header.php'; ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
	<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
	<title>MoonArrow Studios - Forum</title>
	<style>
	/* Style for Quill placeholder */
	.ql-editor.ql-blank::before {
		color: #6c757d;
		/* This is the Bootstrap 'text-body-tertiry' color */
	}

	/* Zoom effect on hover for asset cards */
	.card {
		transition: transform 0.2s;
		/* Smooth transition */
	}

	.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.card-image-container {
    flex: 0 0 150px; /* Fixed width for the image container */
    height: auto; /* Allow height to adjust */
    overflow: hidden;
    border-radius: 6px 0 0 6px; /* Rounded corners on the left */
    display: flex; /* Make the image container a flex container */
    align-items: stretch; /* Stretch the image to fill the container */
}

.card-image {
    width: 100%;
    height: 100%; /* Ensure the image fills the container */
    object-fit: cover; /* Ensure the image covers the container */
}

.card-content {
    flex: 1; /* Take up remaining space */
    padding: 16px; /* Add padding for spacing */
    display: flex;
    flex-direction: column;
    justify-content: space-between; /* Distribute content evenly */
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

/* Cards for Assets */
.card {
    background-color: var(--color-card-bg);
    border: 1px solid var(--color-card-border);
    border-radius: 6px;
    margin-bottom: 16px;
    transition: transform 0.2s, box-shadow 0.2s;
    display: flex; /* Ensure the card itself is a flex container */
}

.card-flex-container {
    display: flex; /* Flexbox to align image and content side by side */
    align-items: stretch; /* Stretch children to match height */
    width: 100%; /* Ensure the container takes full width */
}

.card:hover {
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

/* Asset Metadata */
.asset-metadata {
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

.asset-list {
    width: 100%;
    min-width: 0; /* Allows content to shrink below minimum content size */
}

.asset-list .card {
    width: 100%;
    margin: 0;
    border: 1px solid var(--color-border-default);
    border-radius: 8px;
    transition: transform 0.2s, box-shadow 0.2s;
}


.asset-list .card:hover {
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
    margin: 20px 0;
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
    
    .asset-list {
        order: 1;
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
    display: flex;
    justify-content: center;
    margin-top: 2rem;
}



.assets-container {
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
    
    /* Make create asset button full width */
    .btn-success {
        width: 100%;
    }
}
.card-img-top {
    width: 100%;
    height: 200px;
    object-fit: cover;
    border-top-left-radius: 6px;
    border-top-right-radius: 6px;
}
	</style>
	<script>
	document.addEventListener("DOMContentLoaded", function() {
    // Initialize Quill editor
    var quill = new Quill('#editor', {
        placeholder: 'Your asset content goes here',
        theme: 'snow',
        modules: {
            toolbar: [
                [{
                    header: [3, 4, false]
                }],
                ['bold', 'italic', 'underline'],
                ['blockquote', 'code-block'],
                [{
                    list: 'ordered'
                }, {
                    list: 'bullet'
                }],
                ['link']
            ]
        }
    });

    // Create alert container
    const alertContainer = document.createElement('div');
    alertContainer.style.position = 'fixed';
    alertContainer.style.top = '20px';
    alertContainer.style.left = '50%';
    alertContainer.style.transform = 'translateX(-50%)';
    alertContainer.style.zIndex = '9999';
    document.body.appendChild(alertContainer);
    
    function showBootstrapAlert(message) {
        const alert = `
            <div class="alert alert-danger alert-dismissible fade show">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>`;
        alertContainer.innerHTML = alert;
        
        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            const alertElement = document.querySelector('.alert');
            if (alertElement) {
                const bsAlert = new bootstrap.Alert(alertElement);
                bsAlert.close();
            }
        }, 5000);
    }

    let hashtags = new Set(); // Move hashtags Set to global scope

    // Submit handler
    const form = document.querySelector('#createAssetForm');
    form.addEventListener('submit', function(e) {
        const contentInput = document.querySelector('input[name="content"]');
        const quillContent = quill.root.innerHTML.trim();
        
        let hasError = false;
        
        // Check content
        if(quillContent === '' || quillContent === '<p><br></p>') {
            e.preventDefault();
            showBootstrapAlert('Asset content is required!');
            hasError = true;
        }
        
        // Check for hashtag badges
        if(hashtags.size === 0) {
            e.preventDefault();
            if (!hasError) { // Only show if no content error
                showBootstrapAlert('At least one hashtag is required!');
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

// Add this JavaScript after your existing scripts
document.addEventListener('DOMContentLoaded', function() {
    const hashtagInput = document.querySelector('#hashtags');
    const hashtagContainer = document.createElement('div');
    hashtagContainer.className = 'hashtag-container d-flex flex-wrap gap-2 mb-2';
    const hiddenHashtagInput = document.createElement('input');
    hiddenHashtagInput.type = 'hidden';
    hiddenHashtagInput.name = 'hashtags';
    
    // Insert container before the input
    hashtagInput.parentNode.insertBefore(hashtagContainer, hashtagInput);
    hashtagInput.parentNode.appendChild(hiddenHashtagInput);
    
    // Style the input
    hashtagInput.style.paddingLeft = '20px';
    
    // Create a '#' prefix that stays before the input
    const hashPrefix = document.createElement('div');
    hashPrefix.textContent = '#';
    hashPrefix.style.position = 'absolute';
    hashPrefix.style.left = '8px';
    hashPrefix.style.top = '50%';
    hashPrefix.style.transform = 'translateY(-50%)';
    hashPrefix.style.color = '#6c757d';
    hashPrefix.style.pointerEvents = 'none';
    
    // Wrap input in relative container
    const inputWrapper = document.createElement('div');
    inputWrapper.style.position = 'relative';
    hashtagInput.parentNode.insertBefore(inputWrapper, hashtagInput);
    inputWrapper.appendChild(hashPrefix);
    inputWrapper.appendChild(hashtagInput);
    
    let hashtags = new Set();
    
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
    
    // Handle paste events
    hashtagInput.addEventListener('paste', function(e) {
        e.preventDefault();
        const paste = (e.clipboardData || window.clipboardData).getData('text');
        const tags = paste.split(/[\s,]+/);
        tags.forEach(tag => {
            tag = tag.trim().replace(/^#/, '');
            if (tag) addHashtag(tag);
        });
    });
});
// Modify the asset display section to show hashtags as badges
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
	</script>
</head>

<body class="">
    <div class="container">
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_SESSION['error']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <!-- Search, Filter, and Create Asset Section -->
        <div class="d-flex justify-content-between align-items-center my-4">
            <form method="GET" class="d-flex align-items-center">
                <input type="text" name="search" class="form-control me-2 bg-dark" placeholder="Search" value="<?= htmlspecialchars($search) ?>">
                <select name="category" class="form-select me-2 bg-dark text-light">
                    <option value="">All Categories</option>
                    <?php while ($row = $asset_categories->fetch_assoc()): ?>
                        <option value="<?= $row['id'] ?>" <?= $category_filter == $row['id'] ? 'selected' : '' ?>><?= $row['name'] ?></option>
                    <?php endwhile; ?>
                </select>
                <select name="filter" class="form-select me-2 bg-dark text-light">
                    <option value="newest" <?= isset($_GET['filter']) && $_GET['filter'] == 'newest' ? 'selected' : '' ?>>Newest</option>
                    <option value="oldest" <?= isset($_GET['filter']) && $_GET['filter'] == 'oldest' ? 'selected' : '' ?>>Oldest</option>
                    <option value="highest_score" <?= isset($_GET['filter']) && $_GET['filter'] == 'highest_score' ? 'selected' : '' ?>>Highest Score</option>
                </select>
                <button type="submit" class="btn btn-primary">Search</button>
            </form>
            <?php if (isset($_SESSION['user_id'])): ?>
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createAssetModal">Create Asset</button>
            <?php endif; ?>
        </div>
        <hr>

        <!-- Main Content -->
        <div class="main-container">
            <!-- Sidebar with Leaderboard -->
            <div class="sidebar">
                <div class="leaderboard-card">
                    <h2 class="leaderboard-title">Leaderboard</h2>
                    
                    <!-- Top Assets Section -->
                    <div class="leaderboard-section">
                        <h3 class="leaderboard-section-title">Top Assets Today</h3>
                        <?php
                        $top_assets = $conn->query("SELECT * FROM assets ORDER BY upvotes DESC LIMIT 5");
                        $rank = 1;
                        while ($asset = $top_assets->fetch_assoc()): ?>
                            <a href="view_asset.php?id=<?= $asset['id'] ?>" class="leaderboard-item">
                                <span class="leaderboard-rank">#<?= $rank++ ?></span>
                                <span class="leaderboard-content"><?= htmlspecialchars($asset['title']) ?></span>
                            </a>
                        <?php endwhile; ?>
                    </div>

                    <!-- Top Users Section -->
                    <div class="leaderboard-section">
                        <h3 class="leaderboard-section-title">Most Followed Users</h3>
                        <?php
                        $top_followed = $conn->query("SELECT users.user_id, users.username, COUNT(follows.follower_id) AS followers 
                                                    FROM users 
                                                    LEFT JOIN follows ON users.user_id = follows.following_id 
                                                    GROUP BY users.user_id 
                                                    ORDER BY followers DESC 
                                                    LIMIT 5");
                        while ($user = $top_followed->fetch_assoc()): ?>
                            <a href="profile.php?id=<?= $user['user_id'] ?>" class="leaderboard-item">
                                <div class="leaderboard-content">
                                    <?= htmlspecialchars($user['username']) ?>
                                    <span class="leaderboard-stat"><?= $user['followers'] ?> followers</span>
                                </div>
                            </a>
                        <?php endwhile; ?>
                    </div>

                    <!-- Trending Tags Section -->
                    <div class="leaderboard-section">
                        <h3 class="leaderboard-section-title">Trending Tags</h3>
                        <div class="trending-tags">
                            <?php
                            $top_hashtags = $conn->query("
                                WITH RECURSIVE split_hashtags AS (
                                    SELECT 
                                        SUBSTRING_INDEX(SUBSTRING_INDEX(hashtags, ' ', 1), ' ', -1) AS hashtag,
                                        SUBSTRING(hashtags, LENGTH(SUBSTRING_INDEX(hashtags, ' ', 1)) + 2) AS remaining_string,
                                        created_at
                                    FROM assets
                                    WHERE hashtags != '' AND created_at >= NOW() - INTERVAL 7 DAY
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
                            while ($hashtag = $top_hashtags->fetch_assoc()): ?>
                                <a href="?search=<?= urlencode($hashtag['hashtag']) ?>" class="tag-badge">
                                    <?= htmlspecialchars($hashtag['hashtag']) ?>
                                </a>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Asset List -->
            <div class="asset-list">
                <h2 class="mt-4">Assets</h2>
                <div class="assets-container">
                    <?php while ($asset = $result->fetch_assoc()): ?>
                        <div class="card">
    <div class="card-flex-container">
        <!-- Image on the left -->
        <div class="card-image-container">
            <?php if (!empty($asset['preview_image'])): ?>
                <img src="<?= htmlspecialchars($asset['preview_image']) ?>" class="card-image" alt="Preview Image">
            <?php else: ?>
                <img src="../media/default_image_for_category_<?= htmlspecialchars($asset['category_id']) ?>.jpg" class="card-image default-image" alt="Default Image">
            <?php endif; ?>
        </div>

        <!-- Asset information on the right -->
        <div class="card-content">
            <h3 class="card-title">
                <a href="view_asset.php?id=<?= $asset['id'] ?>" class="text-decoration-none">
                    <?= htmlspecialchars($asset['title'] ?? 'No Title') ?>
                </a>
            </h3>
            <p class="card-text text-light">
                <em>Published on <?= $asset['created_at'] ?> by 
                    <a href="profile.php?id=<?= htmlspecialchars($asset['user_id']) ?>" class="text-decoration-none">
                        <?= htmlspecialchars($asset['username']) ?>
                    </a>
                </em>
            </p>
            <p class="card-text"><strong>Category:</strong> <?= htmlspecialchars($asset['category_name'] ?? 'Uncategorized') ?></p>
            <p class="card-text"><strong>Hashtags:</strong> <?= htmlspecialchars($asset['hashtags'] ?? '') ?></p>
            <p class="card-text">
                <strong>Rating:</strong> 
                <i class="bi bi-caret-up-fill"></i><?= $asset['upvotes'] ?? 0 ?> 
                <i class="bi bi-caret-down-fill"></i><?= $asset['downvotes'] ?? 0 ?> 
                Score: <?= $asset['score'] ?? 0 ?>
            </p>
        </div>
    </div>
</div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>

        <!-- Modal for Creating Asset -->
        <div class="modal fade" id="createAssetModal" tabindex="-1" aria-labelledby="createAssetModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="createAssetModalLabel">Create Asset</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="createAssetForm" method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="title" class="form-label">Title</label>
                                <input type="text" name="title" id="title" class="form-control bg-dark" placeholder="Your asset title goes here" required>
                            </div>
                            <div class="mb-3">
                                <label for="content" class="form-label">Content</label>
                                <div id="editor" style="height: 200px; border: 1px solid #ccc;"></div>
                                <input type="hidden" name="content">
                            </div>
                            <div class="mb-3">
                                <label for="category" class="form-label">Category</label>
                                <select name="category" id="category" class="form-select bg-dark text-light" required>
                                    <option value="" class="">Select Category</option>
                                    <?php $asset_categories->data_seek(0); while ($row = $asset_categories->fetch_assoc()): ?>
                                        <option value="<?= $row['id'] ?>"><?= $row['name'] ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="hashtags" class="form-label">Hashtags</label>
                                <input type="text" name="hashtags" id="hashtags" class="form-control bg-dark" placeholder="e.g., #2025, #unity, #unrealengine">
                            </div>
                            <div class="mb-3">
                                <label for="preview_image" class="form-label">Preview Image</label>
                                <input type="file" name="preview_image" id="preview_image" class="form-control" accept="image/*">
                            </div>
                            <div class="mb-3">
                                <label for="images" class="form-label">Images</label>
                                <input type="file" name="images[]" id="images" class="form-control" accept="image/*" multiple>
                            </div>
                            <div class="mb-3">
                                <label for="videos" class="form-label">Videos</label>
                                <input type="file" name="videos[]" id="videos" class="form-control" accept="video/*" multiple>
                            </div>
                            <button type="submit" name="create_asset" class="btn btn-primary">Publish Asset</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pagination -->
        <div class="pagination-wrapper">
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
                        <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
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
</body>

</html> <?php $conn->close(); ?>
