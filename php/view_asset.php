<?php
session_start();

// Database connection
require 'db_connect.php';

// Fetch the asset by ID
$asset_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$stmt = $conn->prepare("SELECT assets.*, categories.name AS category_name, users.username 
                        FROM assets 
                        JOIN categories ON assets.category_id = categories.id 
                        JOIN users ON assets.user_id = users.user_id 
                        WHERE assets.id = ?");
$stmt->bind_param("i", $asset_id);
$stmt->execute();
$result = $stmt->get_result();
$asset = $result->fetch_assoc();

if (!$asset) {
    echo "<h1>Asset not found</h1>";
    exit;
}

// Fetch comments and their replies based on the selected filter
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'highest_score';

$order_by = 'c1.created_at DESC'; // Default order
switch ($filter) {
    case 'newest':
        $order_by = 'c1.created_at DESC';
        break;
    case 'most_replies':
        $order_by = '(SELECT COUNT(*) FROM comments_asset c2 WHERE c2.parent_id = c1.id) DESC';
        break;
    case 'highest_score':
    default:
        $order_by = '(c1.upvotes - c1.downvotes) DESC';
        break;
}

// Fetch top-level comments for the specific asset
$comments_stmt = $conn->prepare("
    SELECT c1.*, users.username, 
        (SELECT COUNT(*) FROM comments_asset c2 WHERE c2.parent_id = c1.id) AS reply_count 
    FROM comments_asset c1 
    JOIN users ON c1.user_id = users.user_id 
    WHERE c1.asset_id = ? AND c1.parent_id IS NULL 
    ORDER BY $order_by
");
$comments_stmt->bind_param("i", $asset_id);
$comments_stmt->execute();
$comments_result = $comments_stmt->get_result();

$comments = [];
while ($row = $comments_result->fetch_assoc()) {
    $row['replies'] = [];
    $reply_stmt = $conn->prepare("
        SELECT replies.*, users.username 
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

<head> <?php include 'header.php'; ?>
	<!-- Include Highlight.js -->
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/styles/default.min.css">
	<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/highlight.min.js"></script>
	<link rel="stylesheet" href="https://cdn.quilljs.com/1.3.7/quill.snow.css">
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
    }
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
    background-color: var(--color-canvas-default);
    border: 1px solid var(--color-border-default);
    border-radius: 6px;
    margin-bottom: 24px;
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
    border: 1px solid var(--color-border-muted);
    border-radius: 6px;
    margin-bottom: 16px;
    background-color: var(--color-comment-bg);
}

.replies {
    margin-left: 24px;
    padding-left: 24px;
    border-left: 2px solid var(--color-border-muted);
}

.reply-card {
    background-color: var(--color-reply-bg);
    border: 1px solid var(--color-border-muted);
    border-radius: 6px;
    margin-bottom: 8px;
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

/* Alert Styling */
.alert {
    position: fixed;
    top: 20px;
    left: 50%;
    transform: translateX(-50%);
    padding: 12px 16px;
    border-radius: 6px;
    z-index: 1000;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.alert-danger {
    background-color: var(--color-downvote-active);
    color: white;
    border: none;
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
        if (mediaElement) {
            mediaElement.remove();
        }
        modal.classList.remove('active');
        document.body.style.overflow = ''; // Restore scrolling
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
	<div class="container mt-5">
		<div class="card">
			<div class="card-body">
				<!-- asset Header -->
				<div class="text-center mb-4">
					<h1 class="card-title mb-1"><?= htmlspecialchars($asset['title'] ?? 'No Title') ?></h1>
					<p class="mb-0"><em>Published on <?= date('F j, Y, g:i A', strtotime($asset['created_at'])) ?></em></p>
					<p class="mb-0">By <strong><a href="profile.php?id=<?= htmlspecialchars($asset['user_id']) ?>"><?= htmlspecialchars($asset['username'] ?? 'Anonymous') ?></a></strong></p>
					<p><strong>Category:</strong> <?= htmlspecialchars($asset['category_name'] ?? 'Uncategorized') ?></p>
					<p class="card-text"><strong>Hashtags:</strong> <?= htmlspecialchars($asset['hashtags'] ?? '') ?></p>
				</div>
				<hr>
				<!-- asset Content -->
				<div> <?= $asset['content'] ?> </div> <?php
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
    <h6 class='text-center'><i class="bi bi-paperclip"></i>Attachments<i class="bi bi-paperclip"></i></h6>
    <div class="media-container">
        <?php if (!empty($images)): ?>
            <?php foreach ($images as $image): ?>
                <div class="media-item">
                    <img src="<?= htmlspecialchars($image) ?>" alt="Asset Image">
                    <button class="fullscreen-btn" onclick="toggleFullscreen(event)">⛶</button>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php if (!empty($videos)): ?>
            <?php foreach ($videos as $video_path): ?>
                <div class="media-item">
                    <video>
                        <source src="<?= htmlspecialchars($video_path) ?>" type="video/mp4"> Your browser does not support the video tag.
                    </video>
                    <button class="fullscreen-btn" onclick="toggleFullscreen(event)">⛶</button>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Download Button for Asset File -->
    <?php if (!empty($asset['asset_file'])): ?>
        <div class="text-center mt-3">
            <a href="<?= htmlspecialchars($asset['asset_file']) ?>" class="btn btn-primary btn-sm" download>
                <i class="bi bi-download"></i> Download <?= basename($asset['asset_file']) ?>
            </a>
        </div>
    <?php endif; ?>
<?php endif; ?>
			</div>
		</div>
	</div>
	<div class="text-center mt-3">
		<button class="btn btn-outline-success me-2 upvote-btn <?= isset($_SESSION['user_id']) ? '' : 'disabled' ?>" data-asset-id="<?= $asset_id ?>" <?= isset($_SESSION['user_id']) ? '' : 'disabled' ?>>
			<i class="bi bi-caret-up-fill"></i> <span id="upvote-count"><?= $asset['upvotes'] ?></span>
		</button>
		<button class="btn btn-outline-danger downvote-btn <?= isset($_SESSION['user_id']) ? '' : 'disabled' ?>" data-asset-id="<?= $asset_id ?>" <?= isset($_SESSION['user_id']) ? '' : 'disabled' ?>>
			<i class="bi bi-caret-down-fill"></i> <span id="downvote-count"><?= $asset['downvotes'] ?></span>
		</button>
		<p class="mt-2">Score: <span id="score"><?= $asset['upvotes'] - $asset['downvotes'] ?></span></p>
	</div> <?php if (!isset($_SESSION['user_id'])): ?> <p class="text-center">You must <a href="sign_in/sign_in_html.php" class="text-decoration-none">sign in</a> to vote.</p> <?php endif; ?>
	<!-- Comment Section -->
	<div class="card container">
		<div class="card-body">
			<h4>Comments</h4> <?php if (isset($_SESSION['user_id'])): ?> <div id="comment-editor" class="mb-3"></div>
			<button class="btn btn-primary" id="submit-comment" data-asset-id="<?= $asset_id ?>">Submit Comment</button> <?php else: ?> <p class="">You must <a href="sign_in/sign_in_html.php" class="text-decoration-none">sign in</a> to comment and reply.</p> <?php endif; ?>
			<hr>
            <div class="mb-3">
                <label for="filter" class="form-label">Filter Comments:</label>
                <select name="filter" id="filter" class="form-select me-2 bg-dark text-light">
                    <option value="highest_score" <?= isset($_GET['filter']) && $_GET['filter'] == 'highest_score' ? 'selected' : '' ?>>Highest Score</option>
                    <option value="newest" <?= isset($_GET['filter']) && $_GET['filter'] == 'newest' ? 'selected' : '' ?>>Newest</option>
                    <option value="most_replies" <?= isset($_GET['filter']) && $_GET['filter'] == 'most_replies' ? 'selected' : '' ?>>Most Replies</option>
                </select>
            </div>
			<div id="comments-container"> <?php foreach ($comments as $comment): ?> <div class="card mb-3" style="max-width: 100%;">
					<div class="card-body">
						<!-- Comment Content -->
						<h6 class="card-subtitle mb-2"><a href="profile.php?id=<?= htmlspecialchars($comment['user_id']) ?>"><?= htmlspecialchars($comment['username']) ?></a> - <?= date('F j, Y, g:i A', strtotime($comment['created_at'])) ?></h6>
						<p class="card-text"><?= $comment['content'] ?></p>
						<!-- Upvote and Downvote Buttons for Comments -->
						<button class="btn btn-outline-success me-2 upvote-comment-btn <?= isset($_SESSION['user_id']) ? '' : 'disabled' ?>" data-comment-id="<?= $comment['id'] ?>">
							<i class="bi bi-caret-up-fill"></i> <span class="upvote-count"><?= $comment['upvotes'] ?? 0 ?></span>
						</button>
						<button class="btn btn-outline-danger downvote-comment-btn <?= isset($_SESSION['user_id']) ? '' : 'disabled' ?>" data-comment-id="<?= $comment['id'] ?>">
							<i class="bi bi-caret-down-fill"></i> <span class="downvote-count"><?= $comment['downvotes'] ?? 0 ?></span>
						</button>
						<a class="btn btn-link text-decoration-none reply-btn" data-comment-id="<?= $comment['id'] ?>">Reply</a>
						<!-- Hide/Show Replies Button -->
						<a class="btn btn-link text-decoration-none toggle-replies-btn" data-comment-id="<?= $comment['id'] ?>" data-reply-count="<?= $comment['reply_count'] ?>"> Show Replies (<?= $comment['reply_count'] ?>) </a>
						<!-- Replies Section -->
						<div class="replies ms-4 mt-3" style="display: none;"> <?php foreach ($comment['replies'] as $reply): ?> <div class="card mb-2" style="max-width: 100%;">
								<div class="card-body">
									<!-- Reply Content -->
									<h6 class="card-subtitle mb-2 text-muted"><a href="profile.php?id=<?= htmlspecialchars($reply['user_id']) ?>"><?= htmlspecialchars($reply['username']) ?></a> - <?= date('F j, Y, g:i A', strtotime($reply['created_at'])) ?></h6>
									<p class="card-text"><?= $reply['content'] ?></p>
									<!-- Upvote and Downvote Buttons for Replies -->
									<button class="btn btn-outline-success me-3 upvote-reply-btn <?= isset($_SESSION['user_id']) ? '' : 'disabled' ?>" data-comment-id="<?= $reply['id'] ?>">
										<i class="bi bi-caret-up-fill"></i> <span class="upvote-count"><?= $reply['upvotes'] ?? 0 ?></span>
									</button>
									<button class="btn btn-outline-danger me-3 downvote-reply-btn <?= isset($_SESSION['user_id']) ? '' : 'disabled' ?>" data-comment-id="<?= $reply['id'] ?>">
										<i class="bi bi-caret-down-fill"></i> <span class="downvote-count"><?= $reply['downvotes'] ?? 0 ?></span>
									</button>
								</div>
							</div> <?php endforeach; ?> </div>
					</div>
				</div> <?php endforeach; ?> </div>
		</div>
	</div>
	<br> <?php $conn->close(); ?> <script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
	<script>
	function toggleFullscreen(event) {
		const media = event.target.parentElement.querySelector('img, video');
		if(media.requestFullscreen) {
			media.requestFullscreen();
		} else if(media.webkitRequestFullscreen) { // Safari
			media.webkitRequestFullscreen();
		} else if(media.msRequestFullscreen) { // IE11
			media.msRequestFullscreen();
		}
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
						alert(data.error || 'An error occurred');
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

    function showAlert(message) {
        alertDiv.innerHTML = `
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
        alertDiv.style.display = 'block';
        setTimeout(() => {
            alertDiv.style.display = 'none';
        }, 3000);
    }

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
            showAlert('Comment cannot be empty.');
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
                showAlert(data.error || 'An error occurred.');
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
                        showAlert('Reply cannot be empty.');
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
                            showAlert(data.error || 'An error occurred.');
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
					alert('You must log in to vote.');
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
						alert(data.error || 'An error occurred');
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
						alert(data.error || 'An error occurred');
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
	</script>
</body>