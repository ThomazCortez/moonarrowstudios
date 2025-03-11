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
$status = isset($_GET['status']) ? $_GET['status'] : '';
$commentType = isset($_GET['type']) ? $_GET['type'] : 'post'; // Default to post comments
$sortBy = isset($_GET['sort']) ? $_GET['sort'] : 'created_at';
$sortOrder = isset($_GET['order']) ? $_GET['order'] : 'DESC';

// Validate sort parameters
$allowedSortFields = ['id', 'content', 'username', 'created_at', 'upvotes', 'downvotes', 'reported_count'];
if (!in_array($sortBy, $allowedSortFields)) {
    $sortBy = 'created_at';
}

$allowedSortOrders = ['ASC', 'DESC'];
if (!in_array(strtoupper($sortOrder), $allowedSortOrders)) {
    $sortOrder = 'DESC';
}

// Prepare query based on comment type
if ($commentType === 'asset') {
    // Query for asset comments
    $query = "SELECT c.id, c.content, c.status, c.upvotes, c.downvotes, c.created_at, c.reported_count,
                    u.user_id, u.username, a.title as asset_title, c.parent_id,
                    'asset' as comment_type
             FROM comments_asset c
             JOIN users u ON c.user_id = u.user_id
             JOIN assets a ON c.asset_id = a.id
             WHERE 1=1";
} else {
    // Query for post comments
    $query = "SELECT c.id, c.content, c.status, c.upvotes, c.downvotes, c.created_at, c.reported_count,
                    u.user_id, u.username, p.title as post_title, NULL as parent_id,
                    'post' as comment_type
             FROM comments c
             JOIN users u ON c.user_id = u.user_id
             JOIN posts p ON c.post_id = p.id
             WHERE 1=1";
}

// Add filters
$params = [];
$types = "";

if (!empty($search)) {
    if ($commentType === 'asset') {
        $query .= " AND (c.content LIKE ? OR u.username LIKE ? OR a.title LIKE ?)";
    } else {
        $query .= " AND (c.content LIKE ? OR u.username LIKE ? OR p.title LIKE ?)";
    }
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= "sss";
}

if (!empty($status)) {
    $query .= " AND c.status = ?";
    $params[] = $status;
    $types .= "s";
}

// Count total records for pagination
$countQuery = preg_replace('/SELECT.*?FROM/is', "SELECT COUNT(*) as total FROM", $query, 1);

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

// Get comments
$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$comments = [];
while ($row = $result->fetch_assoc()) {
    $comments[] = $row;
}
$stmt->close();

// Get combined comment statistics
$statsQuery = "SELECT 
    (SELECT COUNT(*) FROM comments) + (SELECT COUNT(*) FROM comments_asset) as total_comments,
    (SELECT COUNT(*) FROM comments WHERE status = 'published') + 
    (SELECT COUNT(*) FROM comments_asset WHERE status = 'published') as published_count,
    (SELECT COUNT(*) FROM comments WHERE status = 'draft') + 
    (SELECT COUNT(*) FROM comments_asset WHERE status = 'draft') as draft_count,
    (SELECT COUNT(*) FROM comments WHERE status = 'hidden') + 
    (SELECT COUNT(*) FROM comments_asset WHERE status = 'hidden') as hidden_count,
    (SELECT SUM(reported_count) FROM comments) + 
    (SELECT COALESCE(SUM(reported_count), 0) FROM comments_asset) as total_reports";
$statsResult = $conn->query($statsQuery);
$comment_stats = $statsResult->fetch_assoc();

// Get comment type count
$typeCountQuery = "SELECT 
    (SELECT COUNT(*) FROM comments) as post_comments_count,
    (SELECT COUNT(*) FROM comments_asset) as asset_comments_count";
$typeCountResult = $conn->query($typeCountQuery);
$comment_type_count = $typeCountResult->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MoonArrow Studios - Manage Comments</title>
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
        .reply-comment {
            margin-left: 20px;
            border-left: 3px solid #6c757d;
            padding-left: 10px;
        }
    </style>
</head>

<body>
    <?php include('../header.php'); ?>

    <div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="mb-0">Manage Comments</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Manage Comments</li>
            </ol>
        </nav>
    </div>
    <div>
        <button id="bulkDeleteBtn" class="btn btn-danger me-2" disabled>
            Delete Selected
        </button>
    </div>
</div>

        <!-- Comment Stats -->
        <div class="row mb-4 d-flex justify-content-center">
            <div class="col-md-2 mb-3">
                <div class="card stats-card bg-primary bg-gradient text-white">
                    <div class="card-body text-center">
                        <h3 class="fs-2 mb-0"><?php echo $comment_stats['total_comments']; ?></h3>
                        <p class="mb-0">Total Comments</p>
                    </div>
                </div>
            </div>
            <div class="col-md-2 mb-3">
                <div class="card stats-card bg-success bg-gradient text-white">
                    <div class="card-body text-center">
                        <h3 class="fs-2 mb-0"><?php echo $comment_stats['published_count']; ?></h3>
                        <p class="mb-0">Published</p>
                    </div>
                </div>
            </div>
            <div class="col-md-2 mb-3">
                <div class="card stats-card bg-warning bg-gradient text-dark">
                    <div class="card-body text-center">
                        <h3 class="fs-2 mb-0"><?php echo $comment_stats['draft_count']; ?></h3>
                        <p class="mb-0">Drafts</p>
                    </div>
                </div>
            </div>
            <div class="col-md-2 mb-3">
                <div class="card stats-card bg-secondary bg-gradient text-white">
                    <div class="card-body text-center">
                        <h3 class="fs-2 mb-0"><?php echo $comment_stats['hidden_count']; ?></h3>
                        <p class="mb-0">Hidden</p>
                    </div>
                </div>
            </div>
            <div class="col-md-2 mb-3">
                <div class="card stats-card bg-danger bg-gradient text-white">
                    <div class="card-body text-center">
                        <h3 class="fs-2 mb-0"><?php echo $comment_stats['total_reports']; ?></h3>
                        <p class="mb-0">Reports</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Comment Type Tabs -->
        <ul class="nav nav-tabs mb-4">
            <li class="nav-item">
                <a class="nav-link <?php echo $commentType === 'post' ? 'active' : ''; ?>" href="?type=post">
                    Post Comments <span class="badge bg-primary"><?php echo $comment_type_count['post_comments_count']; ?></span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $commentType === 'asset' ? 'active' : ''; ?>" href="?type=asset">
                    Asset Comments <span class="badge bg-primary"><?php echo $comment_type_count['asset_comments_count']; ?></span>
                </a>
            </li>
        </ul>

        <!-- Search and Filter -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-5">
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" class="form-control" placeholder="Search comments..." name="search" value="<?php echo htmlspecialchars($search); ?>">
                        </div>
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
                    <input type="hidden" name="type" value="<?php echo htmlspecialchars($commentType); ?>">
                    <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sortBy); ?>">
                    <input type="hidden" name="order" value="<?php echo htmlspecialchars($sortOrder); ?>">
                </form>
            </div>
        </div>

        <!-- Comment Table -->
        <div class="card mb-4">
            <div class="card-header bg-dark bg-gradient">
                <h5 class="mb-0">
                    <i class="bi bi-chat-left-text-fill me-2"></i>
                    <?php echo ($commentType === 'asset') ? 'Asset Comments' : 'Post Comments'; ?>
                </h5>
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
                            <th class="cursor-pointer" onclick="changeSort('content')">
                                Content <?php echo getSortIcon('content'); ?>
                            </th>
                            <th class="cursor-pointer" onclick="changeSort('username')">
                                Author <?php echo getSortIcon('username'); ?>
                            </th>
                            <th>
                                <?php echo ($commentType === 'asset') ? 'Asset Title' : 'Post Title'; ?>
                            </th>
                            <?php if ($commentType === 'asset'): ?>
                            <th>Parent ID</th>
                            <?php endif; ?>
                            <th class="cursor-pointer" onclick="changeSort('upvotes')">
                                Upvotes <?php echo getSortIcon('upvotes'); ?>
                            </th>
                            <th class="cursor-pointer" onclick="changeSort('downvotes')">
                                Downvotes <?php echo getSortIcon('downvotes'); ?>
                            </th>
                            <th class="cursor-pointer" onclick="changeSort('created_at')">
                                Created At <?php echo getSortIcon('created_at'); ?>
                            </th>
                            <th class="cursor-pointer" onclick="changeSort('reported_count')">
                                Reports <?php echo getSortIcon('reported_count'); ?>
                            </th>
                            <th>Status</th>
                            <th class="actions-column">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($comments) > 0): ?>
                            <?php foreach ($comments as $comment): ?>
                                <tr<?php echo ($commentType === 'asset' && $comment['parent_id']) ? ' class="reply-comment"' : ''; ?>>
                                    <td>
                                        <div class="form-check">
                                            <input class="form-check-input comment-select" type="checkbox" 
                                                data-type="<?php echo $comment['comment_type']; ?>"
                                                value="<?php echo $comment['id']; ?>">
                                        </div>
                                    </td>
                                    <td><?php echo $comment['id']; ?></td>
                                    <td class="truncate-text"><?php echo htmlspecialchars($comment['content']); ?></td>
                                    <td><?php echo htmlspecialchars($comment['username']); ?></td>
                                    <td>
                                        <?php if ($commentType === 'asset'): ?>
                                            <?php echo htmlspecialchars($comment['asset_title']); ?>
                                        <?php else: ?>
                                            <?php echo htmlspecialchars($comment['post_title']); ?>
                                        <?php endif; ?>
                                    </td>
                                    <?php if ($commentType === 'asset'): ?>
                                    <td>
                                        <?php if ($comment['parent_id']): ?>
                                            <a href="php/admin/view_comment.php?id=<?php echo $comment['parent_id']; ?>&type=asset" class="badge bg-info">
                                                <?php echo $comment['parent_id']; ?>
                                            </a>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <?php endif; ?>
                                    <td><?php echo $comment['upvotes']; ?></td>
                                    <td><?php echo $comment['downvotes']; ?></td>
                                    <td><?php echo date('M d, Y', strtotime($comment['created_at'])); ?></td>
                                    <td><?php echo $comment['reported_count']; ?></td>
                                    <td>
                                        <?php if ($comment['status'] === 'published'): ?>
                                            <span class="badge bg-success">Visible</span>
                                        <?php elseif ($comment['status'] === 'draft'): ?>
                                            <span class="badge bg-warning text-dark">Draft</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Hidden</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="actions-column">
                                        <div class="btn-group btn-group-sm">
                                            <a href="<?php echo $baseUrl; ?>php/admin/view_comment.php?id=<?php echo $comment['id']; ?>&type=<?php echo $comment['comment_type']; ?>" class="btn btn-primary" title="View">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="edit_comment.php?id=<?php echo $comment['id']; ?>&type=<?php echo $comment['comment_type']; ?>" class="btn btn-warning" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <button class="btn btn-danger delete-comment" data-bs-toggle="modal" data-bs-target="#deleteCommentModal" 
                                                data-comment-id="<?php echo $comment['id']; ?>" 
                                                data-comment-type="<?php echo $comment['comment_type']; ?>"
                                                data-comment-content="<?php echo htmlspecialchars($comment['content']); ?>" title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="<?php echo ($commentType === 'asset') ? '13' : '12'; ?>" class="text-center py-4">No comments found matching your criteria.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                </div>
            </div>
            <div class="card-footer">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        Showing <?php echo min(($page - 1) * $limit + 1, $totalRecords); ?>-<?php echo min($page * $limit, $totalRecords); ?> of <?php echo $totalRecords; ?> comments
                    </div>
                    <?php if ($totalPages > 1): ?>
                        <nav aria-label="Comments pagination">
                            <ul class="pagination mb-0">
                                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&limit=<?php echo $limit; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&sort=<?php echo $sortBy; ?>&order=<?php echo $sortOrder; ?>&type=<?php echo $commentType; ?>" aria-label="Previous">
                                        <span aria-hidden="true">&laquo;</span>
                                    </a>
                                </li>
                                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>&limit=<?php echo $limit; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&sort=<?php echo $sortBy; ?>&order=<?php echo $sortOrder; ?>&type=<?php echo $commentType; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                                <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&limit=<?php echo $limit; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&sort=<?php echo $sortBy; ?>&order=<?php echo $sortOrder; ?>&type=<?php echo $commentType; ?>" aria-label="Next">
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

    <!-- Delete Comment Modal -->
    <div class="modal fade" id="deleteCommentModal" tabindex="-1" aria-labelledby="deleteCommentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteCommentModalLabel">Delete Comment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the comment: <strong id="commentContentToDelete"></strong>?</p>
                    <p class="text-danger">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form action="delete_comment.php" method="POST">
                        <input type="hidden" name="comment_id" id="deleteCommentId">
                        <input type="hidden" name="comment_type" id="deleteCommentType">
                        <button type="submit" class="btn btn-danger">Delete Comment</button>
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
                    <h5 class="modal-title" id="bulkDeleteModalLabel">Delete Multiple Comments</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the selected comments?</p>
                    <p class="text-danger">This action cannot be undone.</p>
                    <p>Number of comments selected: <strong id="selectedCount">0</strong></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form action="bulk_delete_comments.php" method="POST">
                        <input type="hidden" name="comment_ids" id="bulkDeleteCommentIds">
                        <input type="hidden" name="comment_types" id="bulkDeleteCommentTypes">
                        <button type="submit" class="btn btn-danger">Delete Selected Comments</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Single delete modal functionality
            const deleteCommentModal = document.getElementById('deleteCommentModal');
            if (deleteCommentModal) {
                deleteCommentModal.addEventListener('show.bs.modal', function(event) {
                    const button = event.relatedTarget;
                    const commentId = button.getAttribute('data-comment-id');
                    const commentType = button.getAttribute('data-comment-type');
                    const commentContent = button.getAttribute('data-comment-content');
                    
                    document.getElementById('deleteCommentId').value = commentId;
                    document.getElementById('deleteCommentType').value = commentType;
                    document.getElementById('commentContentToDelete').textContent = commentContent;
                });
            }
            
            // Select all checkbox functionality
            const selectAllCheckbox = document.getElementById('selectAll');
            const commentCheckboxes = document.querySelectorAll('.comment-select');
            const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
            
            if (selectAllCheckbox) {
                selectAllCheckbox.addEventListener('change', function() {
                    const isChecked = this.checked;
                    
                    commentCheckboxes.forEach(checkbox => {
                        checkbox.checked = isChecked;
                    });
                    
                    updateBulkDeleteButton();
                });
            }
            
            // Individual checkbox functionality
            commentCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    updateBulkDeleteButton();
                    
                    // Update "select all" checkbox state
                    const allChecked = [...commentCheckboxes].every(cb => cb.checked);
                    const someChecked = [...commentCheckboxes].some(cb => cb.checked);
                    
                    if (selectAllCheckbox) {
                        selectAllCheckbox.checked = allChecked;
                        selectAllCheckbox.indeterminate = someChecked && !allChecked;
                    }
                });
            });
            
            // Bulk delete button functionality
            if (bulkDeleteBtn) {
                bulkDeleteBtn.addEventListener('click', function() {
                    const selectedComments = [...document.querySelectorAll('.comment-select:checked')].map(cb => ({ 
                        id: cb.value,
                        type: cb.getAttribute('data-type')
                    }));
                    
                    const commentIds = selectedComments.map(item => item.id);
                    const commentTypes = selectedComments.map(item => item.type);
                    
                    document.getElementById('bulkDeleteCommentIds').value = JSON.stringify(commentIds);
                    document.getElementById('bulkDeleteCommentTypes').value = JSON.stringify(commentTypes);
                    document.getElementById('selectedCount').textContent = selectedComments.length;
                    
                    const bulkDeleteModal = new bootstrap.Modal(document.getElementById('bulkDeleteModal'));
                    bulkDeleteModal.show();
                });
            }
            
            // Function to update bulk delete button state
            function updateBulkDeleteButton() {
                const selectedCount = document.querySelectorAll('.comment-select:checked').length;
                bulkDeleteBtn.disabled = selectedCount === 0;
                bulkDeleteBtn.textContent = selectedCount > 0 ? 
                    `Delete Selected (${selectedCount})` : 'Delete Selected';
            }
            
            // Make the changeSort function available globally
            window.changeSort = function(column) {
                let currentSort = '<?php echo $sortBy; ?>';
                let currentOrder = '<?php echo $sortOrder; ?>';
                let newOrder = 'ASC';
                
                if (column === currentSort) {
                    newOrder = currentOrder === 'ASC' ? 'DESC' : 'ASC';
                }
                
                const form = document.createElement('form');
                form.method = 'GET';
                form.action = 'manage_comments.php';
                
                const inputs = {
                    'search': '<?php echo htmlspecialchars($search); ?>',
                    'status': '<?php echo htmlspecialchars($status); ?>',
                    'type': '<?php echo htmlspecialchars($commentType); ?>',
                    'sort': column,
                    'order': newOrder,
                    'page': '<?php echo $page; ?>',
                    'limit': '<?php echo $limit; ?>'
                };
                
                for (const [key, value] of Object.entries(inputs)) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = key;
                    input.value = value;
                    form.appendChild(input);
                }
                
                document.body.appendChild(form);
                form.submit();
            };
        });
    </script>
</body>
</html>