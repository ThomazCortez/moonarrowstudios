<?php
// Start session
session_start();

// Set base URL
$baseUrl = '/moonarrowstudios/';

// Check if user is logged in and is an admin
require_once '../db_connect.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: " . $baseUrl . "../sign_in/sign_in_html.php");
    exit();
}

// Verify admin status
$user_id = $_SESSION['user_id'];
$query = "SELECT role FROM users WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($user_role);
$stmt->fetch();
$stmt->close();

// Redirect if not admin
if ($user_role !== 'admin') {
    header("Location: " . $baseUrl);
    exit();
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
        $upload_dir = 'uploads/images/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        foreach ($_FILES['images']['name'] as $key => $name) {
            $image_path = $upload_dir . basename($name);
            if (move_uploaded_file($_FILES['images']['tmp_name'][$key], $image_path)) {
                $new_images[] = $image_path;
            }
        }
    }
    
    // Handle video uploads
    $new_videos = [];
    if (isset($_FILES['videos']['name'][0]) && $_FILES['videos']['error'][0] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/videos/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        foreach ($_FILES['videos']['name'] as $key => $name) {
            $video_path = $upload_dir . basename($name);
            if (move_uploaded_file($_FILES['videos']['tmp_name'][$key], $video_path)) {
                $new_videos[] = $video_path;
            }
        }
    }
    
    // Merge existing and new attachments
    $images = array_merge($images, $new_images);
    $videos = array_merge($videos, $new_videos);
    
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
        
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("ssissssi", $title, $content, $category_id, $status, json_encode($images), json_encode($videos), $hashtags, $post_id);
        
        if ($stmt->execute()) {
            $success_message = "Post successfully updated.";
        } else {
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
    <title>Edit Post - Moon Arrow Studios</title>
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="icon" href="<?php echo $baseUrl; ?>media/moon.ico" type="image/x-icon" />
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <!-- Quill Editor -->
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
    <style>
        .admin-header {
            background-color: rgba(var(--bs-dark-rgb), 0.8);
            backdrop-filter: blur(10px);
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            border-radius: 0.375rem;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        .post-form {
            background-color: var(--bs-dark-bg-subtle);
            border-radius: 0.375rem;
            padding: 1.5rem;
        }
        #editor-container {
            height: 400px;
            margin-bottom: 1rem;
            border-radius: 0.375rem;
            overflow: hidden;
        }
        .ql-toolbar.ql-snow {
            border-top-left-radius: 0.375rem;
            border-top-right-radius: 0.375rem;
            background-color: var(--bs-body-bg);
        }
        .ql-container.ql-snow {
            border-bottom-left-radius: 0.375rem;
            border-bottom-right-radius: 0.375rem;
            background-color: var(--bs-body-bg);
            height: 350px;
        }
        .post-meta {
            font-size: 0.875rem;
            color: var(--bs-secondary-color);
        }
    </style>
</head>

<body>
    <?php include('../header.php'); ?>

    <div class="container py-4">
        <div class="admin-header">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h1 class="mb-0"><i class="bi bi-pencil-square me-2"></i>Edit Post</h1>
                <div>
                    <a href="manage_posts.php" class="btn btn-outline-light me-2">
                        <i class="bi bi-arrow-left me-1"></i>Back to Posts
                    </a>
                    <a href="<?php echo $baseUrl; ?>php/view_post.php?id=<?php echo $post_id; ?>" class="btn btn-primary" target="_blank">
                        <i class="bi bi-eye me-1"></i>View Post
                    </a>
                </div>
            </div>
            <div class="post-meta">
                <span class="me-3"><i class="bi bi-person me-1"></i>Author: <?php echo htmlspecialchars($author); ?></span>
                <span class="me-3"><i class="bi bi-calendar me-1"></i>Created: <?php echo date('M d, Y', strtotime($created_at)); ?></span>
                <span><i class="bi bi-hash me-1"></i>ID: <?php echo $post_id; ?></span>
            </div>
        </div>
        
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i><?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <div class="card">
    <div class="card-body post-form">
        <form method="POST" action="" id="postForm" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="title" class="form-label">Post Title</label>
                <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($title); ?>" required>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="category_id" class="form-label">Category</label>
                    <select class="form-select" id="category_id" name="category_id">
                        <option value="0">-- Select Category --</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>" <?php echo $category_id == $category['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status" required>
                        <option value="published" <?php echo $status === 'published' ? 'selected' : ''; ?>>Published</option>
                        <option value="draft" <?php echo $status === 'draft' ? 'selected' : ''; ?>>Draft</option>
                        <option value="hidden" <?php echo $status === 'hidden' ? 'selected' : ''; ?>>Hidden</option>
                    </select>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="hashtags" class="form-label">Hashtags</label>
                <input type="text" class="form-control" id="hashtags" name="hashtags" value="<?php echo htmlspecialchars($hashtags); ?>" placeholder="e.g., #2025, #unity, #unrealengine">
            </div>
            
            <div class="mb-3">
                <label for="images" class="form-label">Images</label>
                <input type="file" class="form-control" id="images" name="images[]" multiple>
                <?php if (!empty($images)): ?>
                    <div class="mt-2">
                        <strong>Existing Images:</strong>
                        <div class="d-flex flex-wrap gap-2">
                            <?php foreach ($images as $image): ?>
                                <img src="<?php echo $image; ?>" alt="Post Image" class="img-thumbnail" style="max-width: 100px;">
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="mb-3">
                <label for="videos" class="form-label">Videos</label>
                <input type="file" class="form-control" id="videos" name="videos[]" multiple>
                <?php if (!empty($videos)): ?>
                    <div class="mt-2">
                        <strong>Existing Videos:</strong>
                        <div class="d-flex flex-wrap gap-2">
                            <?php foreach ($videos as $video): ?>
                                <video src="<?php echo $video; ?>" controls class="img-thumbnail" style="max-width: 100px;"></video>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="mb-3">
                <label for="editor-container" class="form-label">Content</label>
                <div id="editor-container"></div>
                <input type="hidden" name="content" id="content">
            </div>
            
            <div class="d-flex justify-content-between">
                <button type="submit" class="btn btn-primary" id="saveButton">
                    <i class="bi bi-save me-1"></i>Save Changes
                </button>
                <div>
                    <a href="manage_posts.php" class="btn btn-secondary me-2">Cancel</a>
                    <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deletePostModal">
                        <i class="bi bi-trash me-1"></i>Delete Post
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
    
    <!-- Delete Post Modal -->
    <div class="modal fade" id="deletePostModal" tabindex="-1" aria-labelledby="deletePostModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deletePostModalLabel">Delete Post</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the post: <strong><?php echo htmlspecialchars($title); ?></strong>?</p>
                    <p class="text-danger">This action cannot be undone. All comments associated with this post will also be deleted.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form action="delete_post.php" method="POST">
                        <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
                        <button type="submit" class="btn btn-danger">Delete Post</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
    // Initialize Quill editor
    var quill = new Quill('#editor-container', {
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
        },
        theme: 'snow'
    });
    
    // Set initial content
    quill.root.innerHTML = <?php echo json_encode($content); ?>;
    
    // Update hidden input with quill content before form submission
    document.getElementById('postForm').addEventListener('submit', function() {
        document.getElementById('content').value = quill.root.innerHTML;
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
    
    let hashtags = new Set(<?php echo json_encode(explode(' ', $hashtags)); ?>);
    
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
    
    // Initialize hashtag display
    updateHashtagDisplay();
});
    </script>
</body>
</html>