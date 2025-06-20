<?php
// Function to output sort icons
function getSortIcon($column) {
    global $sortBy, $sortOrder;
    
    if ($sortBy !== $column) {
        return '<i class="bi bi-arrow-down-up text-muted"></i>';
    } else if ($sortOrder === 'ASC') {
        return '<i class="bi bi-sort-alpha-down"></i>';
    } else {
        return '<i class="bi bi-sort-alpha-down-alt"></i>';
    }
}

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

// Initialize variables for pagination and filtering
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$offset = ($page - 1) * $limit;
$search = isset($_GET['search']) ? $_GET['search'] : '';
$category = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$status = isset($_GET['status']) ? $_GET['status'] : '';
$sortBy = isset($_GET['sort']) ? $_GET['sort'] : 'created_at';
$sortOrder = isset($_GET['order']) ? $_GET['order'] : 'DESC';

// Validate sort parameters
$allowedSortFields = ['id', 'title', 'username', 'created_at', 'updated_at', 'category_name', 'views', 'comments_count', 'reported_count'];
if (!in_array($sortBy, $allowedSortFields)) {
    $sortBy = 'created_at';
}

$allowedSortOrders = ['ASC', 'DESC'];
if (!in_array(strtoupper($sortOrder), $allowedSortOrders)) {
    $sortOrder = 'DESC';
}

// Prepare base query
$query = "SELECT p.id, p.title, p.content, p.status, p.views, p.created_at, p.updated_at, p.reported_count,
                 u.user_id, u.username, c.id as category_id, c.name as category_name,
                 (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comments_count
          FROM posts p
          JOIN users u ON p.user_id = u.user_id
          LEFT JOIN categories c ON p.category_id = c.id
          WHERE 1=1";

// Add filters
$params = [];
$types = "";

if (!empty($search)) {
    $query .= " AND (p.title LIKE ? OR p.content LIKE ? OR u.username LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= "sss";
}

if ($category > 0) {
    $query .= " AND p.category_id = ?";
    $params[] = $category;
    $types .= "i";
}

if (!empty($status)) {
    $query .= " AND p.status = ?";
    $params[] = $status;
    $types .= "s";
}

// Count total records for pagination
$countQuery = "SELECT COUNT(*) as total 
               FROM posts p
               JOIN users u ON p.user_id = u.user_id
               LEFT JOIN categories c ON p.category_id = c.id
               WHERE 1=1";

// Add the same filters as the main query
if (!empty($search)) {
    $countQuery .= " AND (p.title LIKE ? OR p.content LIKE ? OR u.username LIKE ?)";
}

if ($category > 0) {
    $countQuery .= " AND p.category_id = ?";
}

if (!empty($status)) {
    $countQuery .= " AND p.status = ?";
}

$stmt = $conn->prepare($countQuery);
if (!empty($types)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$totalRecords = $result->fetch_assoc()['total'];
$totalPages = ceil($totalRecords / $limit);
$stmt->close();

// Add sort and pagination
$query .= " ORDER BY $sortBy $sortOrder LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

// Get posts
$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$posts = [];
while ($row = $result->fetch_assoc()) {
    $posts[] = $row;
}
$stmt->close();

// Get categories for filter dropdown
$categoriesQuery = "SELECT id, name FROM categories ORDER BY name";
$categoriesResult = $conn->query($categoriesQuery);
$categories = [];
while ($row = $categoriesResult->fetch_assoc()) {
    $categories[] = $row;
}

// Get post statistics
$statsQuery = "SELECT 
    COUNT(*) as total_posts,
    SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as published_count,
    SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft_count,
    SUM(CASE WHEN status = 'hidden' THEN 1 ELSE 0 END) as hidden_count,
    SUM(reported_count) as total_reports
    FROM posts";
$statsResult = $conn->query($statsQuery);
$post_stats = $statsResult->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MoonArrow Studios - Manage Posts</title>
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="icon" href="<?php echo $baseUrl; ?>media/moon.ico" type="image/x-icon" />
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <style>
        .stats-card {
            transition: transform 0.3s ease-in-out;
        }
        .stats-card:hover {
            transform: translateY(-5px);
        }
        .table-responsive {
            border-radius: 0.375rem;
            overflow: hidden;
        }
        .actions-column {
            width: 140px;
        }
        .cursor-pointer {
            cursor: pointer;
        }
        .truncate-text {
            max-width: 250px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
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
        @media (prefers-color-scheme: dark) {
        
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
    </style>
</head>

<body>
    <?php include('../header.php'); ?>

    <!-- Alert Container -->
    <div id="alertContainer" class="alert-container"></div>

    <div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="mb-0">Manage Posts</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Manage Posts</li>
            </ol>
        </nav>
    </div>
    <div>
        <button id="bulkDeleteBtn" class="btn btn-danger me-2" disabled>
            Delete Selected
        </button>
        <a href="../forum.php" class="btn btn-primary">
            <i class="bi bi-plus-circle me-2"></i>Create New Post
        </a>
    </div>
</div>

        <!-- Post Stats -->
        <div class="row mb-4 d-flex justify-content-center">
            <div class="col-md-2 mb-3">
                <div class="card stats-card bg-primary bg-gradient text-white">
                    <div class="card-body text-center">
                        <h3 class="fs-2 mb-0"><?php echo $post_stats['total_posts']; ?></h3>
                        <p class="mb-0">Total Posts</p>
                    </div>
                </div>
            </div>
            <div class="col-md-2 mb-3">
                <div class="card stats-card bg-success bg-gradient text-white">
                    <div class="card-body text-center">
                        <h3 class="fs-2 mb-0"><?php echo $post_stats['published_count']; ?></h3>
                        <p class="mb-0">Published</p>
                    </div>
                </div>
            </div>
            <div class="col-md-2 mb-3">
                <div class="card stats-card bg-warning bg-gradient text-dark">
                    <div class="card-body text-center">
                        <h3 class="fs-2 mb-0"><?php echo $post_stats['draft_count']; ?></h3>
                        <p class="mb-0">Drafts</p>
                    </div>
                </div>
            </div>
            <div class="col-md-2 mb-3">
                <div class="card stats-card bg-secondary bg-gradient text-white">
                    <div class="card-body text-center">
                        <h3 class="fs-2 mb-0"><?php echo $post_stats['hidden_count']; ?></h3>
                        <p class="mb-0">Hidden</p>
                    </div>
                </div>
            </div>
            <div class="col-md-2 mb-3">
                <div class="card stats-card bg-danger bg-gradient text-white">
                    <div class="card-body text-center">
                        <h3 class="fs-2 mb-0"><?php echo $post_stats['total_reports']; ?></h3>
                        <p class="mb-0">Reports</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search and Filter -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-6">
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" class="form-control" placeholder="Search posts..." name="search" value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <select class="form-select" name="category">
                            <option value="0">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" <?php echo $category == $cat['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select class="form-select" name="status">
                            <option value="">All Status</option>
                            <option value="published" <?php echo $status == 'published' ? 'selected' : ''; ?>>Published</option>
                            <option value="draft" <?php echo $status == 'draft' ? 'selected' : ''; ?>>Draft</option>
                            <option value="hidden" <?php echo $status == 'hidden' ? 'selected' : ''; ?>>Hidden</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">Filter</button>
                    </div>
                    <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sortBy); ?>">
                    <input type="hidden" name="order" value="<?php echo htmlspecialchars($sortOrder); ?>">
                </form>
            </div>
        </div>

        <!-- Post Table -->
        <div class="card mb-4">
            <div class="card-header bg-dark bg-gradient">
                <h5 class="mb-0"><i class="bi bi-chat-left-text-fill me-2"></i>All Posts</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead>
                        <tr>
                            <th width="40">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="selectAll">
                                </div>
                            </th>
                            <th class="cursor-pointer" onclick="changeSort('id')">
                                ID <?php echo getSortIcon('id'); ?>
                            </th>
                            <th class="cursor-pointer" onclick="changeSort('title')">
                                Title <?php echo getSortIcon('title'); ?>
                            </th>
                            <th class="cursor-pointer" onclick="changeSort('username')">
                                Author <?php echo getSortIcon('username'); ?>
                            </th>
                            <th class="cursor-pointer" onclick="changeSort('category_name')">
                                Category <?php echo getSortIcon('category_name'); ?>
                            </th>
                            <th class="cursor-pointer" onclick="changeSort('views')">
                                Views <?php echo getSortIcon('views'); ?>
                            </th>
                            <th class="cursor-pointer" onclick="changeSort('comments_count')">
                                Comments <?php echo getSortIcon('comments_count'); ?>
                            </th>
                            <th class="cursor-pointer" onclick="changeSort('created_at')">
                                Created At <?php echo getSortIcon('created_at'); ?>
                            </th>
                            <th class="cursor-pointer" onclick="changeSort('updated_at')">
                                Updated At <?php echo getSortIcon('updated_at'); ?>
                            </th>
                            <th class="cursor-pointer" onclick="changeSort('reported_count')">
                                Reports <?php echo getSortIcon('reported_count'); ?>
                            </th>
                            <th>Status</th>
                            <th class="actions-column">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($posts) > 0): ?>
                            <?php foreach ($posts as $post): ?>
                                <tr>
                                    <td>
                                        <div class="form-check">
                                            <input class="form-check-input post-select" type="checkbox" value="<?php echo $post['id']; ?>">
                                        </div>
                                    </td>
                                    <td><?php echo $post['id']; ?></td>
                                    <td class="truncate-text"><?php echo htmlspecialchars($post['title']); ?></td>
                                    <td><?php echo htmlspecialchars($post['username']); ?></td>
                                    <td><?php echo htmlspecialchars($post['category_name'] ?? 'Uncategorized'); ?></td>
                                    <td><?php echo $post['views']; ?></td>
                                    <td><?php echo $post['comments_count']; ?></td>
                                    <td><?php echo date('M d, Y', strtotime($post['created_at'])); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($post['updated_at'])); ?></td>
                                    <td><?php echo $post['reported_count']; ?></td>
                                    <td>
                                        <?php if ($post['status'] === 'published'): ?>
                                            <span class="badge bg-success">Visible</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Hidden</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="actions-column">
                                        <div class="btn-group btn-group-sm">
                                            <a href="<?php echo $baseUrl; ?>php/view_post.php?id=<?php echo $post['id']; ?>" class="btn btn-primary" title="View">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="edit_post.php?id=<?php echo $post['id']; ?>" class="btn btn-warning" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <button class="btn btn-danger delete-post" data-bs-toggle="modal" data-bs-target="#deletePostModal" data-post-id="<?php echo $post['id']; ?>" data-post-title="<?php echo htmlspecialchars($post['title']); ?>" title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="12" class="text-center py-4">No posts found matching your criteria.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                </div>
            </div>
            <div class="card-footer">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        Showing <?php echo min(($page - 1) * $limit + 1, $totalRecords); ?>-<?php echo min($page * $limit, $totalRecords); ?> of <?php echo $totalRecords; ?> posts
                    </div>
                    <?php if ($totalPages > 1): ?>
                        <nav aria-label="Posts pagination">
                            <ul class="pagination mb-0">
                                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&limit=<?php echo $limit; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo $category; ?>&status=<?php echo urlencode($status); ?>&sort=<?php echo $sortBy; ?>&order=<?php echo $sortOrder; ?>" aria-label="Previous">
                                        <span aria-hidden="true">&laquo;</span>
                                    </a>
                                </li>
                                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>&limit=<?php echo $limit; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo $category; ?>&status=<?php echo urlencode($status); ?>&sort=<?php echo $sortBy; ?>&order=<?php echo $sortOrder; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                                <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&limit=<?php echo $limit; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo $category; ?>&status=<?php echo urlencode($status); ?>&sort=<?php echo $sortBy; ?>&order=<?php echo $sortOrder; ?>" aria-label="Next">
                                        <span aria-hidden="true">&raquo;</span>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    <?php endif; ?>
                </div>
            </div>
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
                    <p>Are you sure you want to delete the post: <strong id="postTitleToDelete"></strong>?</p>
                    <p class="text-danger">This action cannot be undone. All comments associated with this post will also be deleted.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form action="delete_post.php" method="POST">
                        <input type="hidden" name="post_id" id="deletePostId">
                        <button type="submit" class="btn btn-danger">Delete Post</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bulk Delete Modal -->
<div class="modal fade" id="bulkDeleteModal" tabindex="-1" aria-labelledby="bulkDeleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="bulkDeleteModalLabel">Delete Multiple Posts</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the selected posts?</p>
                <p class="text-danger">This action cannot be undone. All comments associated with these posts will also be deleted.</p>
                <p>Number of posts selected: <strong id="selectedCount">0</strong></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form action="bulk_delete_posts.php" method="POST">
                    <input type="hidden" name="post_ids" id="bulkDeletePostIds">
                    <button type="submit" class="btn btn-danger">Delete Selected Posts</button>
                </form>
            </div>
        </div>
    </div>
</div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
    // Single delete modal functionality
    const deletePostModal = document.getElementById('deletePostModal');
    if (deletePostModal) {
        deletePostModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const postId = button.getAttribute('data-post-id');
            const postTitle = button.getAttribute('data-post-title');
            
            document.getElementById('deletePostId').value = postId;
            document.getElementById('postTitleToDelete').textContent = postTitle;
        });
    }
    
    // Select all checkbox functionality
    const selectAllCheckbox = document.getElementById('selectAll');
    const postCheckboxes = document.querySelectorAll('.post-select');
    const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
    
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            const isChecked = this.checked;
            
            postCheckboxes.forEach(checkbox => {
                checkbox.checked = isChecked;
            });
            
            updateBulkDeleteButton();
        });
    }
    
    // Individual checkbox functionality
    postCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateBulkDeleteButton();
            
            // Update "select all" checkbox state
            const allChecked = [...postCheckboxes].every(cb => cb.checked);
            const someChecked = [...postCheckboxes].some(cb => cb.checked);
            
            if (selectAllCheckbox) {
                selectAllCheckbox.checked = allChecked;
                selectAllCheckbox.indeterminate = someChecked && !allChecked;
            }
        });
    });
    
    // Bulk delete button functionality
    if (bulkDeleteBtn) {
        bulkDeleteBtn.addEventListener('click', function() {
            const selectedPosts = [...document.querySelectorAll('.post-select:checked')].map(cb => cb.value);
            document.getElementById('bulkDeletePostIds').value = JSON.stringify(selectedPosts);
            document.getElementById('selectedCount').textContent = selectedPosts.length;
            
            const bulkDeleteModal = new bootstrap.Modal(document.getElementById('bulkDeleteModal'));
            bulkDeleteModal.show();
        });
    }
    
    // Function to update bulk delete button state
    function updateBulkDeleteButton() {
        const selectedCount = document.querySelectorAll('.post-select:checked').length;
        bulkDeleteBtn.disabled = selectedCount === 0;
        bulkDeleteBtn.textContent = selectedCount > 0 ? 
            `Delete Selected (${selectedCount})` : 'Delete Selected';
    }
    
    // Sorting functionality
    function changeSort(column) {
        let currentSort = '<?php echo $sortBy; ?>';
        let currentOrder = '<?php echo $sortOrder; ?>';
        let newOrder = 'ASC';
        
        if (column === currentSort) {
            newOrder = currentOrder === 'ASC' ? 'DESC' : 'ASC';
        }
        
        const form = document.createElement('form');
        form.method = 'GET';
        form.action = 'manage_posts.php';
        
        const inputs = {
            'search': '<?php echo htmlspecialchars($search); ?>',
            'category': '<?php echo $category; ?>',
            'status': '<?php echo htmlspecialchars($status); ?>',
            'sort': column,
            'order': newOrder,
            'page': '<?php echo $page; ?>',
            'limit': '<?php echo $limit; ?>'
        };
        
        for (const [name, value] of Object.entries(inputs)) {
            if (value) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = name;
                input.value = value;
                form.appendChild(input);
            }
        }
        
        document.body.appendChild(form);
        form.submit();
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

        // Trigger alerts based on PHP session messages
        document.addEventListener('DOMContentLoaded', function() {
            <?php if (isset($_SESSION['error_messages'])): ?>
                <?php foreach ($_SESSION['error_messages'] as $error): ?>
                    showAlert(<?php echo json_encode($error); ?>, 'danger');
                <?php endforeach; ?>
                <?php unset($_SESSION['error_messages']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['success_message'])): ?>
                showAlert(<?php echo json_encode($_SESSION['success_message']); ?>, 'success');
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>
        });
    </script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>