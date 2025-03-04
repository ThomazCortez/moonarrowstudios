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
$asset_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$title = '';
$content = '';
$category_id = 0;
$status = '';
$error_message = '';
$success_message = '';

// Get categories for dropdown
$categoriesQuery = "SELECT id, name FROM asset_categories ORDER BY name";
$categoriesResult = $conn->query($categoriesQuery);
$categories = [];
while ($row = $categoriesResult->fetch_assoc()) {
    $categories[] = $row;
}

// Initialize variables for attachments and hashtags
$images = [];
$videos = [];
$hashtags = '';

// Check if asset exists and belongs to user or admin
if ($asset_id > 0) {
    $query = "SELECT a.*, u.username 
              FROM assets a 
              JOIN users u ON a.user_id = u.user_id 
              WHERE a.id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $asset_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // Asset not found
        header("Location: manage_assets.php");
        exit();
    }
    
    $asset = $result->fetch_assoc();
    $stmt->close();
    
    // Populate variables with asset data
    $title = $asset['title'];
    $content = $asset['content'];
    $category_id = $asset['category_id'];
    $status = $asset['status'];
    $author = $asset['username'];
    $created_at = $asset['created_at'];
    $current_user_id = $asset['user_id'];
    
    // Fetch existing attachments and hashtags
    $images = json_decode($asset['images'], true) ?? [];
    $videos = json_decode($asset['videos'], true) ?? [];
    $hashtags = $asset['hashtags'];
} else {
    // No asset ID provided
    header("Location: manage_assets.php");
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
    
    // Handle file deletions
    if (isset($_POST['delete_images']) && is_array($_POST['delete_images'])) {
        foreach ($_POST['delete_images'] as $index) {
            // Optionally delete the physical file
            //unlink($images[$index]);
            
            // Remove from the array
            unset($images[$index]);
        }
        // Re-index array
        $images = array_values($images);
    }

    if (isset($_POST['delete_videos']) && is_array($_POST['delete_videos'])) {
        foreach ($_POST['delete_videos'] as $index) {
            // Optionally delete the physical file
            //unlink($videos[$index]);
            
            // Remove from the array
            unset($videos[$index]);
        }
        // Re-index array
        $videos = array_values($videos);
    }

    // Handle asset file upload
$asset_file = $asset['asset_file'] ?? '';
if (isset($_FILES['asset_file']['name']) && $_FILES['asset_file']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = 'uploads/asset_files/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    $asset_file_path = $upload_dir . basename($_FILES['asset_file']['name']);
    if (move_uploaded_file($_FILES['asset_file']['tmp_name'], $asset_file_path)) {
        $asset_file = $asset_file_path;
    }
}

// Handle asset file deletion
if (isset($_POST['delete_asset_file']) && $_POST['delete_asset_file'] === 'on') {
    // Optionally delete the physical file
    //unlink($asset_file);
    
    // Clear the asset file path
    $asset_file = '';
}

    // Validate input
    if (empty($title)) {
        $error_message = "Title is required.";
    } elseif (empty($content)) {
        $error_message = "Content is required.";
    } else {
        // Update the asset in the database
        $updateQuery = "UPDATE assets SET 
        title = ?, 
        content = ?, 
        category_id = ?, 
        status = ?,
        images = ?,
        videos = ?,
        hashtags = ?,
        asset_file = ?,
        updated_at = NOW()
        WHERE id = ?";

        // Store the JSON-encoded strings in variables
        $images_json = json_encode($images);
        $videos_json = json_encode($videos);

        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("ssisssssi", $title, $content, $category_id, $status, $images_json, $videos_json, $hashtags, $asset_file, $asset_id);

        if ($stmt->execute()) {
        $success_message = "Asset successfully updated.";
        } else {
        $error_message = "Error updating asset: " . $conn->error;
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
    <title>Edit Asset - Moon Arrow Studios</title>
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
        .asset-form {
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
        .asset-meta {
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
                <h1 class="mb-0"><i class="bi bi-pencil-square me-2"></i>Edit Asset</h1>
                <div>
                    <a href="manage_assets.php" class="btn btn-outline-light me-2">
                        <i class="bi bi-arrow-left me-1"></i>Back to Assets
                    </a>
                    <a href="<?php echo $baseUrl; ?>php/view_asset.php?id=<?php echo $asset_id; ?>" class="btn btn-primary" target="_blank">
                        <i class="bi bi-eye me-1"></i>View Asset
                    </a>
                </div>
            </div>
            <div class="asset-meta">
                <span class="me-3"><i class="bi bi-person me-1"></i>Author: <?php echo htmlspecialchars($author); ?></span>
                <span class="me-3"><i class="bi bi-calendar me-1"></i>Created: <?php echo date('M d, Y', strtotime($created_at)); ?></span>
                <span><i class="bi bi-hash me-1"></i>ID: <?php echo $asset_id; ?></span>
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
    <div class="card-body asset-form">
        <form method="POST" action="" id="assetForm" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="title" class="form-label">Asset Title</label>
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
            
            <!-- Add this before the Images section -->
            <div class="alert alert-info">
                <i class="bi bi-info-circle me-2"></i>To delete existing files, check the box in the top-right corner of each thumbnail.
            </div>

            <div class="mb-3">
                <label for="images" class="form-label">Images</label>
                <input type="file" class="form-control" id="images" name="images[]" multiple accept="image/*">
                <?php if (!empty($images)): ?>
                    <div class="mt-2">
                        <strong>Existing Images:</strong>
                        <div class="d-flex flex-wrap gap-2">
                            <?php foreach ($images as $index => $image): ?>
                                <div class="position-relative">
                                    <img src="<?php echo $image; ?>" alt="Asset Image" class="img-thumbnail" style="max-width: 100px;">
                                    <div class="form-check position-absolute top-0 end-0">
                                        <input class="form-check-input bg-danger" type="checkbox" name="delete_images[]" value="<?php echo $index; ?>" id="delete_image_<?php echo $index; ?>">
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="mb-3">
                <label for="videos" class="form-label">Videos</label>
                <input type="file" class="form-control" id="videos" name="videos[]" multiple accept="video/*">
                <?php if (!empty($videos)): ?>
                    <div class="mt-2">
                        <strong>Existing Videos:</strong>
                        <div class="d-flex flex-wrap gap-2">
                            <?php foreach ($videos as $index => $video): ?>
                                <div class="position-relative">
                                    <video src="<?php echo $video; ?>" controls class="img-thumbnail" style="max-width: 100px;"></video>
                                    <div class="form-check position-absolute top-0 end-0">
                                        <input class="form-check-input bg-danger" type="checkbox" name="delete_videos[]" value="<?php echo $index; ?>" id="delete_video_<?php echo $index; ?>">
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="mb-3">
    <label for="asset_file" class="form-label">Asset File</label>
    <input type="file" class="form-control" id="asset_file" name="asset_file">
    <?php if (!empty($asset['asset_file'])): ?>
        <div class="mt-2">
            <strong>Existing Asset File:</strong>
            <div class="d-flex flex-wrap gap-2">
                <div class="position-relative">
                    <a href="<?php echo $asset['asset_file']; ?>" target="_blank" class="btn btn-outline-light">
                        <i class="bi bi-file-earmark me-1"></i>Download Asset File
                    </a>
                    <div class="form-check position-absolute top-0 end-0">
                        <input class="form-check-input bg-danger" type="checkbox" name="delete_asset_file" id="delete_asset_file">
                    </div>
                </div>
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
                    <a href="manage_assets.php" class="btn btn-secondary me-2">Cancel</a>
                    <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteAssetModal">
                        <i class="bi bi-trash me-1"></i>Delete Asset
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
    
    <!-- Delete Post Modal -->
    <div class="modal fade" id="deleteAssetModal" tabindex="-1" aria-labelledby="deleteAssetModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteAssetModalLabel">Delete Asset</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the asset: <strong><?php echo htmlspecialchars($title); ?></strong>?</p>
                    <p class="text-danger">This action cannot be undone. All comments associated with this asset will also be deleted.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form action="delete_asset.php" method="POST">
                        <input type="hidden" name="asset_id" value="<?php echo $asset_id; ?>">
                        <button type="submit" class="btn btn-danger">Delete Asset</button>
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
    document.getElementById('assetForm').addEventListener('submit', function() {
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