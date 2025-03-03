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
$allowedSortFields = ['id', 'title', 'username', 'created_at', 'category_name', 'views', 'comments_count'];
if (!in_array($sortBy, $allowedSortFields)) {
    $sortBy = 'created_at';
}

$allowedSortOrders = ['ASC', 'DESC'];
if (!in_array(strtoupper($sortOrder), $allowedSortOrders)) {
    $sortOrder = 'DESC';
}

// Prepare base query
$query = "SELECT p.id, p.title, p.content, p.status, p.views, p.created_at, 
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
$countQuery = str_replace("SELECT p.id, p.title, p.content, p.status, p.views, p.created_at, 
                 u.user_id, u.username, c.id as category_id, c.name as category_name,
                 (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comments_count", 
                "SELECT COUNT(*) as total", $query);

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
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Posts - Moon Arrow Studios</title>
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="icon" href="<?php echo $baseUrl; ?>media/moon.ico" type="image/x-icon" />
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <style>
        .action-buttons .btn {
            padding: 0.25rem 0.5rem;
        }
        .table-responsive {
            border-radius: 0.375rem;
            overflow: hidden;
        }
        .truncate-text {
            max-width: 250px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .sort-icon {
            margin-left: 0.25rem;
        }
        .admin-header {
            background-color: rgba(var(--bs-dark-rgb), 0.8);
            backdrop-filter: blur(10px);
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            border-radius: 0.375rem;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        .table th {
            cursor: pointer;
        }
    </style>
</head>

<body>
    <?php include('../header.php'); ?>

    <div class="container py-4">
        <div class="admin-header">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h1 class="mb-0"><i class="bi bi-chat-left-text-fill me-2"></i>Manage Posts</h1>
                <a href="dashboard.php" class="btn btn-outline-light">
                    <i class="bi bi-arrow-left me-1"></i>Back to Dashboard
                </a>
            </div>

            <!-- Filter and Search Form -->
            <form method="GET" class="row g-3">
                <div class="col-12 col-md-4">
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control" placeholder="Search posts..." name="search" value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <select class="form-select" name="category">
                        <option value="0">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo $category == $cat['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <select class="form-select" name="status">
                        <option value="">All Status</option>
                        <option value="published" <?php echo $status == 'published' ? 'selected' : ''; ?>>Published</option>
                        <option value="draft" <?php echo $status == 'draft' ? 'selected' : ''; ?>>Draft</option>
                        <option value="hidden" <?php echo $status == 'hidden' ? 'selected' : ''; ?>>Hidden</option>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <select class="form-select" name="limit">
                        <option value="10" <?php echo $limit == 10 ? 'selected' : ''; ?>>10 per page</option>
                        <option value="25" <?php echo $limit == 25 ? 'selected' : ''; ?>>25 per page</option>
                        <option value="50" <?php echo $limit == 50 ? 'selected' : ''; ?>>50 per page</option>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
                </div>
                <!-- Hidden fields to maintain sort order when filtering -->
                <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sortBy); ?>">
                <input type="hidden" name="order" value="<?php echo htmlspecialchars($sortOrder); ?>">
            </form>
        </div>
        
        <div class="card">
            <div class="card-header bg-dark">
                <div class="d-flex justify-content-between align-items-center">
                    <span><strong>Total:</strong> <?php echo $totalRecords; ?> posts</span>
                    <div class="btn-group">
                        <a href="../forum.php" class="btn btn-success btn-sm">
                            <i class="bi bi-plus-circle me-1"></i>Create New Post
                        </a>
                        <button type="button" class="btn btn-danger btn-sm" id="bulkDeleteBtn" disabled>
                            <i class="bi bi-trash me-1"></i>Delete Selected
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th width="40">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="selectAll">
                                    </div>
                                </th>
                                <th width="60" class="sortable" data-sort="id">
                                    ID
                                    <?php if ($sortBy === 'id'): ?>
                                        <i class="bi bi-arrow-<?php echo $sortOrder === 'ASC' ? 'up' : 'down'; ?> sort-icon"></i>
                                    <?php endif; ?>
                                </th>
                                <th class="sortable" data-sort="title">
                                    Title
                                    <?php if ($sortBy === 'title'): ?>
                                        <i class="bi bi-arrow-<?php echo $sortOrder === 'ASC' ? 'up' : 'down'; ?> sort-icon"></i>
                                    <?php endif; ?>
                                </th>
                                <th class="sortable" data-sort="username">
                                    Author
                                    <?php if ($sortBy === 'username'): ?>
                                        <i class="bi bi-arrow-<?php echo $sortOrder === 'ASC' ? 'up' : 'down'; ?> sort-icon"></i>
                                    <?php endif; ?>
                                </th>
                                <th class="sortable" data-sort="category_name">
                                    Category
                                    <?php if ($sortBy === 'category_name'): ?>
                                        <i class="bi bi-arrow-<?php echo $sortOrder === 'ASC' ? 'up' : 'down'; ?> sort-icon"></i>
                                    <?php endif; ?>
                                </th>
                                <th class="sortable" data-sort="views">
                                    Views
                                    <?php if ($sortBy === 'views'): ?>
                                        <i class="bi bi-arrow-<?php echo $sortOrder === 'ASC' ? 'up' : 'down'; ?> sort-icon"></i>
                                    <?php endif; ?>
                                </th>
                                <th class="sortable" data-sort="comments_count">
                                    Comments
                                    <?php if ($sortBy === 'comments_count'): ?>
                                        <i class="bi bi-arrow-<?php echo $sortOrder === 'ASC' ? 'up' : 'down'; ?> sort-icon"></i>
                                    <?php endif; ?>
                                </th>
                                <th class="sortable" data-sort="created_at">
                                    Date
                                    <?php if ($sortBy === 'created_at'): ?>
                                        <i class="bi bi-arrow-<?php echo $sortOrder === 'ASC' ? 'up' : 'down'; ?> sort-icon"></i>
                                    <?php endif; ?>
                                </th>
                                <th>Status</th>
                                <th width="140">Actions</th>
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
                                        <td>
                                            <?php if ($post['status'] === 'published'): ?>
                                                <span class="badge bg-success">Published</span>
                                            <?php elseif ($post['status'] === 'draft'): ?>
                                                <span class="badge bg-warning text-dark">Draft</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Hidden</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="action-buttons">
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
                                    <td colspan="10" class="text-center py-4">No posts found matching your criteria.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php if ($totalPages > 1): ?>
                <div class="card-footer">
                    <nav aria-label="Posts pagination">
                        <ul class="pagination justify-content-center mb-0">
                            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="<?php echo "?page=".($page-1)."&limit=$limit&search=".urlencode($search)."&category=$category&status=".urlencode($status)."&sort=$sortBy&order=$sortOrder"; ?>">
                                    <i class="bi bi-chevron-left"></i>
                                </a>
                            </li>
                            
                            <?php 
                            // Calculate the range of page numbers to display
                            $startPage = max(1, $page - 2);
                            $endPage = min($totalPages, $startPage + 4);
                            if ($endPage - $startPage < 4 && $startPage > 1) {
                                $startPage = max(1, $endPage - 4);
                            }
                            
                            for ($i = $startPage; $i <= $endPage; $i++): 
                            ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="<?php echo "?page=$i&limit=$limit&search=".urlencode($search)."&category=$category&status=".urlencode($status)."&sort=$sortBy&order=$sortOrder"; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="<?php echo "?page=".($page+1)."&limit=$limit&search=".urlencode($search)."&category=$category&status=".urlencode($status)."&sort=$sortBy&order=$sortOrder"; ?>">
                                    <i class="bi bi-chevron-right"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
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
            const sortableHeaders = document.querySelectorAll('th.sortable');
            sortableHeaders.forEach(header => {
                header.addEventListener('click', function() {
                    const sort = this.getAttribute('data-sort');
                    let order = 'ASC';
                    
                    // If already sorting by this column, toggle the order
                    if (sort === '<?php echo $sortBy; ?>') {
                        order = '<?php echo $sortOrder; ?>' === 'ASC' ? 'DESC' : 'ASC';
                    }
                    
                    // Redirect with new sort parameters
                    window.location.href = `?page=<?php echo $page; ?>&limit=<?php echo $limit; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo $category; ?>&status=<?php echo urlencode($status); ?>&sort=${sort}&order=${order}`;
                });
            });
        });
    </script>
</body>
</html>