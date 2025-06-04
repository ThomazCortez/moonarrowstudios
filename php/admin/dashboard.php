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

// Get site statistics

// Total users
$query = "SELECT COUNT(*) as total_users FROM users";
$result = $conn->query($query);
$total_users = $result->fetch_assoc()['total_users'];

// Total posts
$query = "SELECT COUNT(*) as total_posts FROM posts";
$result = $conn->query($query);
$total_posts = $result->fetch_assoc()['total_posts'];

// Total assets
$query = "SELECT COUNT(*) as total_assets FROM assets";
$result = $conn->query($query);
$total_assets = $result->fetch_assoc()['total_assets'];

// Total comments
$query = "SELECT 
    (SELECT COUNT(*) FROM comments) +
    (SELECT COUNT(*) FROM comments_asset) as total_comments";
$result = $conn->query($query);
$total_comments = $result->fetch_assoc()['total_comments'];

// Recent users
$query = "SELECT user_id, username, email, created_at FROM users ORDER BY created_at DESC LIMIT 5";
$recent_users_result = $conn->query($query);
$recent_users = [];
while ($row = $recent_users_result->fetch_assoc()) {
    $recent_users[] = $row;
}

// Recent posts
$query = "SELECT p.id, p.title, u.username, p.created_at 
          FROM posts p 
          JOIN users u ON p.user_id = u.user_id 
          ORDER BY p.created_at DESC LIMIT 5";
$recent_posts_result = $conn->query($query);
$recent_posts = [];
while ($row = $recent_posts_result->fetch_assoc()) {
    $recent_posts[] = $row;
}

// Recent assets
$query = "SELECT a.id, a.title, u.username, a.created_at 
          FROM assets a 
          JOIN users u ON a.user_id = u.user_id 
          ORDER BY a.created_at DESC LIMIT 5";
$recent_assets_result = $conn->query($query);
$recent_assets = [];
while ($row = $recent_assets_result->fetch_assoc()) {
    $recent_assets[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MoonArrow Studios - Admin Dashboard</title>
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="icon" href="<?php echo $baseUrl; ?>media/moon.ico" type="image/x-icon" />
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <style>
        .dashboard-card {
            transition: transform 0.3s ease-in-out;
        }
        .dashboard-card:hover {
            transform: translateY(-5px);
        }
        .stats-icon {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }
        .card-body {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        .admin-actions a {
            margin-bottom: 0.5rem;
            width: 100%;
        }
        .table-responsive {
            border-radius: 0.375rem;
            overflow: hidden;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .stats-icon {
                font-size: 2rem;
            }
            
            .table {
                font-size: 0.85rem;
            }
            
            .table td, .table th {
                padding: 0.5rem 0.25rem;
                vertical-align: middle;
            }
            
            .btn-sm {
                padding: 0.25rem 0.4rem;
                font-size: 0.75rem;
            }
            
            .text-truncate-mobile {
                max-width: 80px;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }
            
            .d-none-mobile {
                display: none !important;
            }
            
            .admin-actions {
                padding: 0.5rem;
            }
            
            .admin-actions a {
                font-size: 0.85rem;
                padding: 0.5rem;
            }
        }
        
        @media (max-width: 576px) {
            .container {
                padding-left: 0.5rem;
                padding-right: 0.5rem;
            }
            
            .table {
                font-size: 0.75rem;
            }
            
            .table td, .table th {
                padding: 0.25rem 0.1rem;
            }
            
            .text-truncate-mobile {
                max-width: 60px;
            }
            
            .stats-icon {
                font-size: 1.5rem;
            }
            
            h3.fs-2 {
                font-size: 1.5rem !important;
            }
            
            .card-header h5 {
                font-size: 0.9rem;
            }
        }
        
        /* Card content overflow fix */
        .card {
            overflow: hidden;
        }
        
        .card-body {
            overflow-x: auto;
        }
        
        /* Mobile-friendly table styling */
        @media (max-width: 768px) {
            .mobile-stack {
                display: block !important;
                width: 100% !important;
                border: none !important;
                padding: 0.5rem !important;
                border-bottom: 1px solid var(--bs-border-color);
            }
            
            .mobile-stack:before {
                content: attr(data-label) ": ";
                font-weight: bold;
                display: inline-block;
                width: 80px;
            }
            
            .mobile-hide-header {
                display: none;
            }
        }
    </style>
</head>

<body>
    <?php include('../header.php'); ?>

    <div class="container py-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4">
            <h1 class="mb-2 mb-md-0">Admin Dashboard</h1>
            <div>
                <span class="badge bg-primary fs-6">Admin: <?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></span>
            </div>
        </div>

        <!-- Stats Overview Cards -->
        <div class="row mb-4">
            <div class="col-6 col-md-3 mb-3">
                <div class="card dashboard-card bg-primary bg-gradient text-white">
                    <div class="card-body text-center">
                        <i class="bi bi-people-fill stats-icon"></i>
                        <h3 class="fs-2 mb-0"><?php echo $total_users; ?></h3>
                        <p class="mb-0 small">Total Users</p>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3 mb-3">
                <div class="card dashboard-card bg-success bg-gradient text-white">
                    <div class="card-body text-center">
                        <i class="bi bi-chat-left-text-fill stats-icon"></i>
                        <h3 class="fs-2 mb-0"><?php echo $total_posts; ?></h3>
                        <p class="mb-0 small">Forum Posts</p>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3 mb-3">
                <div class="card dashboard-card bg-info bg-gradient text-white">
                    <div class="card-body text-center">
                        <i class="bi bi-bag-fill stats-icon"></i>
                        <h3 class="fs-2 mb-0"><?php echo $total_assets; ?></h3>
                        <p class="mb-0 small">Marketplace Assets</p>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3 mb-3">
                <div class="card dashboard-card bg-warning bg-gradient text-dark">
                    <div class="card-body text-center">
                        <i class="bi bi-chat-dots-fill stats-icon"></i>
                        <h3 class="fs-2 mb-0"><?php echo $total_comments; ?></h3>
                        <p class="mb-0 small">Total Comments</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Admin Actions -->
            <div class="col-12 col-lg-3 mb-4">
                <div class="card h-100">
                    <div class="card-header bg-secondary bg-gradient">
                        <h5 class="mb-0"><i class="bi bi-gear-fill me-2"></i>Admin Actions</h5>
                    </div>
                    <div class="card-body admin-actions">
                        <a href="manage_users.php" class="btn btn-outline-primary">
                            <i class="bi bi-people me-2"></i>Manage Users
                        </a>
                        <a href="manage_posts.php" class="btn btn-outline-success">
                            <i class="bi bi-chat-left-text me-2"></i>Manage Posts
                        </a>
                        <a href="manage_assets.php" class="btn btn-outline-info">
                            <i class="bi bi-box-seam me-2"></i>Manage Assets
                        </a>
                        <a href="manage_categories.php" class="btn btn-outline-warning">
                            <i class="bi bi-tags me-2"></i>Manage Categories
                        </a>
                        <a href="manage_comments.php" class="btn btn-outline-danger">
                            <i class="bi bi-chat-dots me-2"></i>Manage Comments
                        </a>
                    </div>
                </div>
            </div>

            <!-- Recent Users -->
            <div class="col-12 col-lg-9 mb-4">
                <div class="card h-100">
                    <div class="card-header bg-primary bg-gradient text-white">
                        <h5 class="mb-0"><i class="bi bi-people-fill me-2"></i>Recent Users</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover table-striped mb-0">
                                <thead class="mobile-hide-header">
                                    <tr>
                                        <th>ID</th>
                                        <th>Username</th>
                                        <th class="d-none d-md-table-cell">Email</th>
                                        <th class="d-none d-sm-table-cell">Joined</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_users as $user): ?>
                                    <tr>
                                        <td data-label="ID" class="mobile-stack"><?php echo $user['user_id']; ?></td>
                                        <td data-label="Username" class="mobile-stack">
                                            <span class="text-truncate-mobile d-inline-block"><?php echo htmlspecialchars($user['username']); ?></span>
                                        </td>
                                        <td data-label="Email" class="mobile-stack d-none d-md-table-cell">
                                            <span class="text-truncate-mobile d-inline-block"><?php echo htmlspecialchars($user['email']); ?></span>
                                        </td>
                                        <td data-label="Joined" class="mobile-stack d-none d-sm-table-cell"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                        <td data-label="Actions" class="mobile-stack">
                                            <div class="btn-group btn-group-sm">
                                                <a href="edit_user.php?id=<?php echo $user['user_id']; ?>" class="btn btn-sm btn-primary">
                                                    <i class="bi bi-pencil-fill"></i>
                                                </a>
                                                <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteUserModal" data-user-id="<?php echo $user['user_id']; ?>">
                                                    <i class="bi bi-trash-fill"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer text-end">
                        <a href="manage_users.php" class="btn btn-sm btn-primary">View All Users</a>
                    </div>
                </div>
            </div>

            <!-- Recent Posts -->
            <div class="col-12 col-lg-6 mb-4">
                <div class="card h-100">
                    <div class="card-header bg-success bg-gradient text-white">
                        <h5 class="mb-0"><i class="bi bi-chat-left-text-fill me-2"></i>Recent Forum Posts</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover table-striped mb-0">
                                <thead class="mobile-hide-header">
                                    <tr>
                                        <th>ID</th>
                                        <th>Title</th>
                                        <th class="d-none d-sm-table-cell">Author</th>
                                        <th class="d-none d-md-table-cell">Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_posts as $post): ?>
                                    <tr>
                                        <td data-label="ID" class="mobile-stack"><?php echo $post['id']; ?></td>
                                        <td data-label="Title" class="mobile-stack">
                                            <span class="text-truncate-mobile d-inline-block" title="<?php echo htmlspecialchars($post['title']); ?>">
                                                <?php echo htmlspecialchars($post['title']); ?>
                                            </span>
                                        </td>
                                        <td data-label="Author" class="mobile-stack d-none d-sm-table-cell"><?php echo htmlspecialchars($post['username']); ?></td>
                                        <td data-label="Date" class="mobile-stack d-none d-md-table-cell"><?php echo date('M d, Y', strtotime($post['created_at'])); ?></td>
                                        <td data-label="Actions" class="mobile-stack">
                                            <div class="btn-group btn-group-sm">
                                                <a href="edit_post.php?id=<?php echo $post['id']; ?>" class="btn btn-sm btn-primary">
                                                    <i class="bi bi-pencil-fill"></i>
                                                </a>
                                                <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deletePostModal" data-post-id="<?php echo $post['id']; ?>">
                                                    <i class="bi bi-trash-fill"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer text-end">
                        <a href="manage_posts.php" class="btn btn-sm btn-primary">View All Posts</a>
                    </div>
                </div>
            </div>

            <!-- Recent Assets -->
            <div class="col-12 col-lg-6 mb-4">
                <div class="card h-100">
                    <div class="card-header bg-info bg-gradient text-white">
                        <h5 class="mb-0"><i class="bi bi-bag-fill me-2"></i>Recent Assets</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover table-striped mb-0">
                                <thead class="mobile-hide-header">
                                    <tr>
                                        <th>ID</th>
                                        <th>Title</th>
                                        <th class="d-none d-sm-table-cell">Author</th>
                                        <th class="d-none d-md-table-cell">Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_assets as $asset): ?>
                                    <tr>
                                        <td data-label="ID" class="mobile-stack"><?php echo $asset['id']; ?></td>
                                        <td data-label="Title" class="mobile-stack">
                                            <span class="text-truncate-mobile d-inline-block" title="<?php echo htmlspecialchars($asset['title']); ?>">
                                                <?php echo htmlspecialchars($asset['title']); ?>
                                            </span>
                                        </td>
                                        <td data-label="Author" class="mobile-stack d-none d-sm-table-cell"><?php echo htmlspecialchars($asset['username']); ?></td>
                                        <td data-label="Date" class="mobile-stack d-none d-md-table-cell"><?php echo date('M d, Y', strtotime($asset['created_at'])); ?></td>
                                        <td data-label="Actions" class="mobile-stack">
                                            <div class="btn-group btn-group-sm">
                                                <a href="edit_asset.php?id=<?php echo $asset['id']; ?>" class="btn btn-sm btn-primary">
                                                    <i class="bi bi-pencil-fill"></i>
                                                </a>
                                                <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteAssetModal" data-asset-id="<?php echo $asset['id']; ?>">
                                                    <i class="bi bi-trash-fill"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer text-end">
                        <a href="manage_assets.php" class="btn btn-sm btn-primary">View All Assets</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete User Modal -->
    <div class="modal fade" id="deleteUserModal" tabindex="-1" aria-labelledby="deleteUserModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteUserModalLabel">Delete User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this user? This action cannot be undone.</p>
                    <p class="fw-bold">WARNING: Deleting a user will also delete all their posts, assets, and comments.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form action="delete_user.php" method="POST" class="d-inline">
                        <input type="hidden" name="user_id" id="deleteUserId">
                        <button type="submit" class="btn btn-danger">Delete User</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Post Modal -->
    <div class="modal fade" id="deletePostModal" tabindex="-1" aria-labelledby="deletePostModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deletePostModalLabel">Delete Post</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this post? This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form action="delete_post.php" method="POST" class="d-inline">
                        <input type="hidden" name="post_id" id="deletePostId">
                        <button type="submit" class="btn btn-danger">Delete Post</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Asset Modal -->
    <div class="modal fade" id="deleteAssetModal" tabindex="-1" aria-labelledby="deleteAssetModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteAssetModalLabel">Delete Asset</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this asset? This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form action="delete_asset.php" method="POST" class="d-inline">
                        <input type="hidden" name="asset_id" id="deleteAssetId">
                        <button type="submit" class="btn btn-danger">Delete Asset</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Set the correct ID when opening the delete modals
        document.addEventListener('DOMContentLoaded', function() {
            // User delete modal
            const deleteUserModal = document.getElementById('deleteUserModal');
            if (deleteUserModal) {
                deleteUserModal.addEventListener('show.bs.modal', function(event) {
                    const button = event.relatedTarget;
                    const userId = button.getAttribute('data-user-id');
                    document.getElementById('deleteUserId').value = userId;
                });
            }
            
            // Post delete modal
            const deletePostModal = document.getElementById('deletePostModal');
            if (deletePostModal) {
                deletePostModal.addEventListener('show.bs.modal', function(event) {
                    const button = event.relatedTarget;
                    const postId = button.getAttribute('data-post-id');
                    document.getElementById('deletePostId').value = postId;
                });
            }
            
            // Asset delete modal
            const deleteAssetModal = document.getElementById('deleteAssetModal');
            if (deleteAssetModal) {
                deleteAssetModal.addEventListener('show.bs.modal', function(event) {
                    const button = event.relatedTarget;
                    const assetId = button.getAttribute('data-asset-id');
                    document.getElementById('deleteAssetId').value = assetId;
                });
            }
        });
    </script>
</body>
</html>