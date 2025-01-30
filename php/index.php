<?php
// Start the session at the top of the page
session_start();

// Database connection (update with your database credentials)
require 'db_connect.php';
// Handle post creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_post'])) {
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['error'] = "You must be logged in to create a post.";
        header("Location: index.php");
        exit;
    }

    $title = $_POST['title'];
    $content = $_POST['content'];

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

    header("Location: index.php");
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

$sql = "SELECT posts.*, categories.name AS category_name, 
               users.username, users.user_id,
               posts.upvotes, posts.downvotes, 
               (posts.upvotes - posts.downvotes) AS score 
        FROM posts 
        JOIN categories ON posts.category_id = categories.id
        JOIN users ON posts.user_id = users.user_id
        WHERE 1";

if ($search) {
    $sql .= " AND (posts.title LIKE '%$search%' OR posts.content LIKE '%$search%' OR posts.hashtags LIKE '%$search%')";
}
if ($category_filter) {
    $sql .= " AND category_id = $category_filter";
}

// Append the dynamic ORDER BY clause
$sql .= " ORDER BY $order_by";

$result = $conn->query($sql);

// Fetch categories for the filter
$categories = $conn->query("SELECT * FROM categories");
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">

<head> <?php require 'header.php'; ?>
	<link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
	<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
	
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
    --color-alert-error-bg: #FFEBE9;
    --color-alert-error-border: rgba(255, 129, 130, 0.4);
    --color-alert-error-fg: #cf222e;
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
        --color-alert-error-bg: #ff000015;
        --color-alert-error-border: rgba(248, 81, 73, 0.4);
        --color-alert-error-fg: #f85149;
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
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 8px;
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

/* Alerts */
.alert {
    padding: 12px 16px;
    margin-bottom: 16px;
    border-radius: 6px;
    border: 1px solid transparent;
}

.alert-danger {
    background-color: var(--color-alert-error-bg);
    border-color: var(--color-alert-error-border);
    color: var(--color-alert-error-fg);
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
    color: var(--color-accent-fg);
    font-size: 12px;
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
    height: 80px;
    width: 100%;
    object-fit: cover;
    border-top-left-radius: 6px;
    border-top-right-radius: 6px;
    background-color: var(--color-canvas-subtle);
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
	</style>
	<script>
	document.addEventListener("DOMContentLoaded", function() {
		// Initialize Quill editor
		var quill = new Quill('#editor', {
			placeholder: 'Your post content goes here',
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
		// Submit handler to sync Quill content with hidden textarea
		const form = document.querySelector('#createPostForm');
    
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
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
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
    
    form.addEventListener('submit', function(e) {
        const contentInput = document.querySelector('input[name="content"]');
        const quillContent = quill.root.innerHTML.trim();
        if(quillContent === '' || quillContent === '<p><br></p>') {
            e.preventDefault();
            showBootstrapAlert('Post content is required!');
        } else {
            contentInput.value = quillContent;
        }
    });
});
// Add this JavaScript to handle the hover card functionality
document.addEventListener('DOMContentLoaded', function() {
    let hoverCard = document.createElement('div');
    hoverCard.className = 'profile-hover-card';
    document.body.appendChild(hoverCard);
    
    let hoverTimeout;
    let currentUsername;
    
    // Add hover listeners to all username links
    document.querySelectorAll('a[href^="profile.php"]').forEach(link => {
        link.addEventListener('mouseenter', async (e) => {
            clearTimeout(hoverTimeout);
            const userId = new URLSearchParams(link.href.split('?')[1]).get('id');
            currentUsername = link;
            
            // Position the hover card near the username
            const rect = link.getBoundingClientRect();
            hoverCard.style.left = `${rect.left}px`;
            hoverCard.style.top = `${rect.bottom + 8}px`;
            
            // Fetch and display user data
            try {
                const response = await fetch(`fetch_user_preview.php?user_id=${userId}`);
                const userData = await response.json();
                
                // Create avatar content based on whether there's a profile picture
                const avatarContent = userData.profile_picture 
                    ? `<img class="hover-card-avatar" src="${userData.profile_picture}" alt="${userData.username}'s avatar">` 
                    : `<div class="hover-card-avatar d-flex align-items-center justify-content-center bg-dark">
                         <i class="bi bi-person-fill text-light" style="font-size: 1.5rem;"></i>
                       </div>`;
                
                hoverCard.innerHTML = `
                    <div class="hover-card-banner" style="background-image: url('${userData.banner || ''}')"></div>
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
    
    // Handle hover on the card itself
    hoverCard.addEventListener('mouseenter', () => {
        clearTimeout(hoverTimeout);
    });
    
    hoverCard.addEventListener('mouseleave', () => {
        hoverCard.classList.remove('visible');
    });
});
	</script>
</head>

<body class="">
	<div class="container"> <?php if (isset($_SESSION['error'])): ?> <div class="alert alert-danger alert-dismissible fade show" role="alert"> <?= htmlspecialchars($_SESSION['error']) ?> <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
		</div> <?php unset($_SESSION['error']); ?> <?php endif; ?>
		<!-- Search, Filter, and Create Post Section -->
		<div class="d-flex justify-content-between align-items-center my-4">
			<form method="GET" class="d-flex align-items-center">
				<input type="text" name="search" class="form-control me-2 bg-dark" placeholder="Search" value="<?= htmlspecialchars($search) ?>">
				<select name="category" class="form-select me-2 bg-dark text-light">
					<option value="">All Categories</option> <?php while ($row = $categories->fetch_assoc()): ?> <option value="<?= $row['id'] ?>" <?= $category_filter == $row['id'] ? 'selected' : '' ?>><?= $row['name'] ?></option> <?php endwhile; ?>
				</select>
				<select name="filter" class="form-select me-2 bg-dark text-light">
					<option value="newest" <?= isset($_GET['filter']) && $_GET['filter'] == 'newest' ? 'selected' : '' ?>>Newest</option>
					<option value="oldest" <?= isset($_GET['filter']) && $_GET['filter'] == 'oldest' ? 'selected' : '' ?>>Oldest</option>
					<option value="highest_score" <?= isset($_GET['filter']) && $_GET['filter'] == 'highest_score' ? 'selected' : '' ?>>Highest Score</option>
				</select>
				<button type="submit" class="btn btn-primary">Search</button>
			</form> <?php if (isset($_SESSION['user_id'])): ?> <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createPostModal">Create Post</button> <?php endif; ?>
		</div>
		<hr>
		<!-- Modal for Creating Post -->
		<div class="modal fade" id="createPostModal" tabindex="-1" aria-labelledby="createPostModalLabel" aria-hidden="true">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title" id="createPostModalLabel">Create Post</h5>
						<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
					</div>
					<div class="modal-body">
						<form id="createPostForm" method="POST" enctype="multipart/form-data">
							<div class="mb-3">
								<label for="title" class="form-label">Title</label>
								<input type="text" name="title" id="title" class="form-control bg-dark" placeholder="Your post title goes here" required>
							</div>
							<div class="mb-3">
								<label for="content" class="form-label">Content</label>
								<div id="editor" style="height: 200px; border: 1px solid #ccc;"></div>
								<input type="hidden" name="content">
							</div>
							<div class="mb-3">
								<label for="category" class="form-label">Category</label>
								<select name="category" id="category" class="form-select bg-dark text-light" required>
									<option value="" class="">Select Category</option> <?php $categories->data_seek(0); while ($row = $categories->fetch_assoc()): ?> <option value="<?= $row['id'] ?>"><?= $row['name'] ?></option> <?php endwhile; ?>
								</select>
							</div>
							<div class="mb-3">
								<label for="hashtags" class="form-label">Hashtags</label>
								<input type="text" name="hashtags" id="hashtags" class="form-control bg-dark" placeholder="e.g., #2025, #unity, #unrealengine" required>
							</div>
							<div class="mb-3">
								<label for="images" class="form-label">Images</label>
								<input type="file" name="images[]" id="images" class="form-control" accept="image/*" multiple>
							</div>
							<div class="mb-3">
								<label for="videos" class="form-label">Videos</label>
								<input type="file" name="videos[]" id="videos" class="form-control" accept="video/*" multiple>
							</div>
							<script>
							</script>
							<button type="submit" name="create_post" class="btn btn-primary">Post</button>
						</form>
					</div>
				</div>
			</div>
		</div>
		<!-- Display Posts -->
		<h2 class="mt-4">Posts</h2> <?php while ($post = $result->fetch_assoc()): ?> <div class="card mb-3">
			<div class="card-body">
				<h3 class="card-title">
					<a href="view_post.php?id=<?= $post['id'] ?>" class="text-decoration-none"> <?= htmlspecialchars($post['title'] ?? 'No Title') ?> </a>
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
				<!-- Simplified Rating Display -->
				<p class="card-text">
					<strong>Rating:</strong> <i class="bi bi-caret-up-fill"></i><?= $post['upvotes'] ?? 0 ?> <i class="bi bi-caret-down-fill"></i><?= $post['downvotes'] ?? 0 ?> Score: <?= $post['score'] ?? 0 ?></p>
			</div>
		</div> <?php endwhile; ?>
</body>

</html> <?php $conn->close(); ?>
