<?php
// Start the session at the top of the page
session_start();

// Database connection (update with your database credentials)
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "moonarrowstudios";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

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

$sql = "SELECT posts.*, categories.name AS category_name, users.username, 
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
<html lang="en">

<head> <?php include 'header.php'; ?>
	<link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
	<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
	<link rel="stylesheet" href="https://bootswatch.com/5/darkly/bootstrap.min.css">
	<link rel="stylesheet" href="css.css">
	<style>
	/* Style for Quill placeholder */
	.ql-editor.ql-blank::before {
		color: #6c757d;
		/* This is the Bootstrap 'text-body-tertiary' color */
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
	</script>
</head>

<body>
	<div class="container"> <?php if (isset($_SESSION['error'])): ?> <div class="alert alert-danger alert-dismissible fade show" role="alert"> <?= htmlspecialchars($_SESSION['error']) ?> <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
		</div> <?php unset($_SESSION['error']); ?> <?php endif; ?>
		<!-- Search, Filter, and Create Post Section -->
		<div class="d-flex justify-content-between align-items-center my-4">
			<form method="GET" class="d-flex align-items-center">
				<input type="text" name="search" class="form-control me-2 bg-dark text-body" placeholder="Search" value="<?= htmlspecialchars($search) ?>">
				<select name="category" class="form-select me-2 bg-dark text-body-tertiary">
					<option value="">All Categories</option> <?php while ($row = $categories->fetch_assoc()): ?> <option value="<?= $row['id'] ?>" <?= $category_filter == $row['id'] ? 'selected' : '' ?>><?= $row['name'] ?></option> <?php endwhile; ?>
				</select>
				<select name="filter" class="form-select me-2 bg-dark text-body-tertiary">
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
								<input type="text" name="title" id="title" class="form-control bg-dark text-body" placeholder="Your post title goes here" required>
							</div>
							<div class="mb-3">
								<label for="content" class="form-label">Content</label>
								<div id="editor" style="height: 200px; border: 1px solid #ccc;"></div>
								<input type="hidden" name="content">
							</div>
							<div class="mb-3">
								<label for="category" class="form-label">Category</label>
								<select name="category" id="category" class="form-select bg-dark text-body-tertiary" required>
									<option value="" class="">Select Category</option> <?php $categories->data_seek(0); while ($row = $categories->fetch_assoc()): ?> <option value="<?= $row['id'] ?>"><?= $row['name'] ?></option> <?php endwhile; ?>
								</select>
							</div>
							<div class="mb-3">
								<label for="hashtags" class="form-label">Hashtags</label>
								<input type="text" name="hashtags" id="hashtags" class="form-control bg-dark text-body" placeholder="e.g., #2025, #unity, #unrealengine" required>
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
				<p class="card-text text-muted">
					<em>Posted on <?= $post['created_at'] ?> by <?= htmlspecialchars($post['username']) ?></em>
				</p>
				<p class="card-text"><strong>Category:</strong> <?= htmlspecialchars($post['category_name'] ?? 'Uncategorized') ?></p>
				<p class="card-text"><strong>Hashtags:</strong> <?= htmlspecialchars($post['hashtags'] ?? '') ?></p>
				<!-- Simplified Rating Display -->
				<p class="card-text">
					<strong>Rating:</strong> Upvotes <i class="bi bi-caret-up-fill"></i><?= $post['upvotes'] ?? 0 ?> ; Downvotes <i class="bi bi-caret-down-fill"></i><?= $post['downvotes'] ?? 0 ?> ; Score : <?= $post['score'] ?? 0 ?>; </p>
			</div>
		</div> <?php endwhile; ?>
	</div> <?php include 'footer.php'; ?>
</body>

</html> <?php $conn->close(); ?>