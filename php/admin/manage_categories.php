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

// Handle category deletion
if (isset($_POST['delete_category'])) {
    $category_id = $_POST['category_id'];
    $category_type = $_POST['category_type'];

    if ($category_type === 'forum') {
        // Check if category is in use
        $check_query = "SELECT COUNT(*) as count FROM posts WHERE category_id = ?";
        $stmt = $conn->prepare($check_query);
        $stmt->bind_param("i", $category_id);
        $stmt->execute();
        $stmt->bind_result($post_count);
        $stmt->fetch();
        $stmt->close();

        if ($post_count > 0) {
            $_SESSION['error_message'] = "Cannot delete category. It is currently in use by $post_count posts.";
        } else {
            // Delete forum category
            $delete_query = "DELETE FROM categories WHERE id = ?";
            $stmt = $conn->prepare($delete_query);
            $stmt->bind_param("i", $category_id);
            $stmt->execute();
            $stmt->close();

            $_SESSION['success_message'] = "Forum category deleted successfully.";
        }
    } elseif ($category_type === 'asset') {
        // Check if category is in use
        $check_query = "SELECT COUNT(*) as count FROM assets WHERE category_id = ?";
        $stmt = $conn->prepare($check_query);
        $stmt->bind_param("i", $category_id);
        $stmt->execute();
        $stmt->bind_result($asset_count);
        $stmt->fetch();
        $stmt->close();

        if ($asset_count > 0) {
            $_SESSION['error_message'] = "Cannot delete category. It is currently in use by $asset_count assets.";
        } else {
            // Delete asset category
            $delete_query = "DELETE FROM asset_categories WHERE id = ?";
            $stmt = $conn->prepare($delete_query);
            $stmt->bind_param("i", $category_id);
            $stmt->execute();
            $stmt->close();

            $_SESSION['success_message'] = "Asset category deleted successfully.";
        }
    }

    header("Location: manage_categories.php");
    exit();
}

// Handle category creation
if (isset($_POST['create_category'])) {
    $category_name = trim($_POST['category_name']);
    $category_type = $_POST['category_type'];

    if (empty($category_name)) {
        $_SESSION['error_message'] = "Category name cannot be empty.";
    } else {
        if ($category_type === 'forum') {
            // Check if category already exists
            $check_query = "SELECT COUNT(*) as count FROM categories WHERE name = ?";
            $stmt = $conn->prepare($check_query);
            $stmt->bind_param("s", $category_name);
            $stmt->execute();
            $stmt->bind_result($category_count);
            $stmt->fetch();
            $stmt->close();

            if ($category_count > 0) {
                $_SESSION['error_message'] = "A forum category with this name already exists.";
            } else {
                // Create forum category
                $insert_query = "INSERT INTO categories (name) VALUES (?)";
                $stmt = $conn->prepare($insert_query);
                $stmt->bind_param("s", $category_name);
                $stmt->execute();
                $stmt->close();

                $_SESSION['success_message'] = "Forum category created successfully.";
            }
        } elseif ($category_type === 'asset') {
            // Check if category already exists
            $check_query = "SELECT COUNT(*) as count FROM asset_categories WHERE name = ?";
            $stmt = $conn->prepare($check_query);
            $stmt->bind_param("s", $category_name);
            $stmt->execute();
            $stmt->bind_result($category_count);
            $stmt->fetch();
            $stmt->close();

            if ($category_count > 0) {
                $_SESSION['error_message'] = "An asset category with this name already exists.";
            } else {
                // Create asset category
                $insert_query = "INSERT INTO asset_categories (name) VALUES (?)";
                $stmt = $conn->prepare($insert_query);
                $stmt->bind_param("s", $category_name);
                $stmt->execute();
                $stmt->close();

                $_SESSION['success_message'] = "Asset category created successfully.";
            }
        }
    }

    header("Location: manage_categories.php");
    exit();
}

// Fetch forum categories
$forum_categories_query = "SELECT id, name FROM categories ORDER BY name";
$forum_categories_result = $conn->query($forum_categories_query);
$forum_categories = [];
while ($row = $forum_categories_result->fetch_assoc()) {
    $forum_categories[] = $row;
}

// Fetch asset categories
$asset_categories_query = "SELECT id, name FROM asset_categories ORDER BY name";
$asset_categories_result = $conn->query($asset_categories_query);
$asset_categories = [];
while ($row = $asset_categories_result->fetch_assoc()) {
    $asset_categories[] = $row;
}
// Count forum categories
$forum_category_count_query = "SELECT COUNT(*) as count FROM categories";
$forum_category_count_result = $conn->query($forum_category_count_query);
$forum_category_count = $forum_category_count_result->fetch_assoc()['count'];

// Count asset categories
$asset_category_count_query = "SELECT COUNT(*) as count FROM asset_categories";
$asset_category_count_result = $conn->query($asset_category_count_query);
$asset_category_count = $asset_category_count_result->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories - Moon Arrow Studios</title>
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="icon" href="<?php echo $baseUrl; ?>media/moon.ico" type="image/x-icon" />
    <style>
        .category-list .list-group-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .scrollable-categories {
            max-height: 400px;
            overflow-y: auto;
        }
        .card-compact {
            max-height: 500px;
            display: flex;
            flex-direction: column;
        }
        .card-compact .card-body {
            overflow-y: auto;
        }
        .dashboard-card .stats-icon {
            font-size: 3rem;
            opacity: 0.7;
            margin-bottom: 10px;
        }
        .dashboard-card {
            transition: transform 0.3s ease-in-out;
        }
        .dashboard-card:hover {
            transform: translateY(-5px);
        }
    </style>
</head>

<body>
    <?php include('../header.php'); ?>

    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0">Manage Categories</h1>
            <div>
                <span class="badge bg-primary fs-6">Admin: <?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></span>
            </div>
        </div>

        <!-- Stats Overview Cards -->
        <div class="row mb-4 justify-content-center">
            <div class="col-md-3 mb-3">
                <div class="card dashboard-card bg-primary bg-gradient text-white">
                    <div class="card-body text-center">
                        <i class="bi bi-chat-left-text-fill stats-icon"></i>
                        <h3 class="fs-2 mb-0"><?php echo $forum_category_count; ?></h3>
                        <p class="mb-0">Forum Categories</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card dashboard-card bg-info bg-gradient text-white">
                    <div class="card-body text-center">
                        <i class="bi bi-bag-fill stats-icon"></i>
                        <h3 class="fs-2 mb-0"><?php echo $asset_category_count; ?></h3>
                        <p class="mb-0">Asset Categories</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Success/Error Messages -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php 
                echo htmlspecialchars($_SESSION['success_message']); 
                unset($_SESSION['success_message']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php 
                echo htmlspecialchars($_SESSION['error_message']); 
                unset($_SESSION['error_message']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Create Category -->
            <div class="col-md-4 mb-4">
                <div class="card card-compact h-100">
                    <div class="card-header bg-success bg-gradient text-white">
                        <h5 class="mb-0"><i class="bi bi-plus-circle me-2"></i>Create New Category</h5>
                    </div>
                    <div class="card-body">
                        <form action="manage_categories.php" method="POST">
                            <div class="mb-3">
                                <label for="category_name" class="form-label">Category Name</label>
                                <input type="text" class="form-control" id="category_name" name="category_name" required>
                            </div>
                            <div class="mb-3">
                                <label for="category_type" class="form-label">Category Type</label>
                                <select class="form-select" id="category_type" name="category_type" required>
                                    <option value="forum">Forum Category</option>
                                    <option value="asset">Asset Category</option>
                                </select>
                            </div>
                            <button type="submit" name="create_category" class="btn btn-success w-100">
                                <i class="bi bi-plus me-2"></i>Create Category
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Forum Categories -->
            <div class="col-md-4 mb-4">
                <div class="card card-compact h-100">
                    <div class="card-header bg-primary bg-gradient text-white">
                        <h5 class="mb-0"><i class="bi bi-chat-left-text me-2"></i>Forum Categories</h5>
                    </div>
                    <div class="card-body p-0 scrollable-categories">
                        <?php if (empty($forum_categories)): ?>
                            <div class="p-3 text-center text-muted">
                                No forum categories found.
                            </div>
                        <?php else: ?>
                            <ul class="list-group category-list">
                                <?php foreach ($forum_categories as $category): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <?php echo htmlspecialchars($category['name']); ?>
                                        <button class="btn btn-sm btn-danger" data-bs-toggle="modal" 
                                                data-bs-target="#deleteCategoryModal" 
                                                data-category-id="<?php echo $category['id']; ?>" 
                                                data-category-name="<?php echo htmlspecialchars($category['name']); ?>"
                                                data-category-type="forum">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Asset Categories -->
            <div class="col-md-4 mb-4">
                <div class="card card-compact h-100">
                    <div class="card-header bg-info bg-gradient text-white">
                        <h5 class="mb-0"><i class="bi bi-bag me-2"></i>Asset Categories</h5>
                    </div>
                    <div class="card-body p-0 scrollable-categories">
                        <?php if (empty($asset_categories)): ?>
                            <div class="p-3 text-center text-muted">
                                No asset categories found.
                            </div>
                        <?php else: ?>
                            <ul class="list-group category-list">
                                <?php foreach ($asset_categories as $category): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <?php echo htmlspecialchars($category['name']); ?>
                                        <button class="btn btn-sm btn-danger" data-bs-toggle="modal" 
                                                data-bs-target="#deleteCategoryModal" 
                                                data-category-id="<?php echo $category['id']; ?>" 
                                                data-category-name="<?php echo htmlspecialchars($category['name']); ?>"
                                                data-category-type="asset">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Category Modal -->
    <div class="modal fade" id="deleteCategoryModal" tabindex="-1" aria-labelledby="deleteCategoryModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteCategoryModalLabel">Delete Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the category "<span id="categoryNameDisplay"></span>"?</p>
                    <p class="fw-bold">WARNING: This action can only be performed if no posts or assets are using this category.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form action="manage_categories.php" method="POST">
                        <input type="hidden" name="category_id" id="deleteCategoryId">
                        <input type="hidden" name="category_type" id="deleteCategoryType">
                        <button type="submit" name="delete_category" class="btn btn-danger">Delete Category</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    
    <script>
        // Set the correct category details when opening the delete modal
        document.addEventListener('DOMContentLoaded', function() {
        const deleteCategoryModal = document.getElementById('deleteCategoryModal');
        
        deleteCategoryModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const categoryId = button.getAttribute('data-category-id');
            const categoryName = button.getAttribute('data-category-name');
            const categoryType = button.getAttribute('data-category-type');

            document.getElementById('deleteCategoryId').value = categoryId;
            document.getElementById('deleteCategoryType').value = categoryType;
            document.getElementById('categoryNameDisplay').textContent = categoryName;
        });
    });
    </script>