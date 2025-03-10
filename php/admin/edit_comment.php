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
$comment_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$content = '';
$status = '';
$error_message = '';
$success_message = '';

// Check if comment exists
if ($comment_id > 0) {
    $query = "SELECT c.*, u.username, p.title as post_title 
              FROM comments c 
              JOIN users u ON c.user_id = u.user_id 
              JOIN posts p ON c.post_id = p.id 
              WHERE c.id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $comment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // Comment not found
        header("Location: manage_comments.php");
        exit();
    }
    
    $comment = $result->fetch_assoc();
    $stmt->close();
    
    // Populate variables with comment data
    $content = $comment['content'];
    $status = $comment['status'];
    $author = $comment['username'];
    $post_title = $comment['post_title'];
    $created_at = $comment['created_at'];
    $upvotes = $comment['upvotes'];
    $downvotes = $comment['downvotes'];
    $reported_count = $comment['reported_count'];
} else {
    // No comment ID provided
    header("Location: manage_comments.php");
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
        // Update comment in database
        $updateQuery = "UPDATE comments SET 
                        content = ?, 
                        status = ?, 
                        updated_at = NOW()
                        WHERE id = ?";
        
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("ssi", $content, $status, $comment_id);
        
        if ($stmt->execute()) {
            $success_message = "Comment successfully updated.";
        } else {
            $error_message = "Error updating comment: " . $conn->error;
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
    <title>MoonArrow Studios - Edit Comment</title>
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
        .comment-form {
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
        .comment-meta {
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
                <h1 class="mb-0"><i class="bi bi-pencil-square me-2"></i>Edit Comment</h1>
                <div>
                    <a href="manage_comments.php" class="btn btn-outline-light me-2">
                        <i class="bi bi-arrow-left me-1"></i>Back to Comments
                    </a>
                </div>
            </div>
            <div class="comment-meta">
                <span class="me-3"><i class="bi bi-person me-1"></i>Author: <?php echo htmlspecialchars($author); ?></span>
                <span class="me-3"><i class="bi bi-calendar me-1"></i>Created: <?php echo date('M d, Y', strtotime($created_at)); ?></span>
                <span><i class="bi bi-hash me-1"></i>ID: <?php echo $comment_id; ?></span>
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
            <div class="card-body comment-form">
                <form method="POST" action="" id="commentForm">
                    <div class="mb-3">
                        <label for="post_title" class="form-label">Post Title</label>
                        <input type="text" class="form-control" id="post_title" value="<?php echo htmlspecialchars($post_title); ?>" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="published" <?php echo $status === 'published' ? 'selected' : ''; ?>>Published</option>
                            <option value="draft" <?php echo $status === 'draft' ? 'selected' : ''; ?>>Draft</option>
                            <option value="hidden" <?php echo $status === 'hidden' ? 'selected' : ''; ?>>Hidden</option>
                        </select>
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
                            <a href="manage_comments.php" class="btn btn-secondary me-2">Cancel</a>
                            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteCommentModal">
                                <i class="bi bi-trash me-1"></i>Delete Comment
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Delete Comment Modal -->
    <div class="modal fade" id="deleteCommentModal" tabindex="-1" aria-labelledby="deleteCommentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteCommentModalLabel">Delete Comment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this comment?</p>
                    <p class="text-danger">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form action="delete_comment.php" method="POST">
                        <input type="hidden" name="comment_id" value="<?php echo $comment_id; ?>">
                        <button type="submit" class="btn btn-danger">Delete Comment</button>
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
            document.getElementById('commentForm').addEventListener('submit', function() {
                document.getElementById('content').value = quill.root.innerHTML;
            });
        });
    </script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>