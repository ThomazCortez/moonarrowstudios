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

// Search parameters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$role_filter = isset($_GET['role']) ? $_GET['role'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'created_at';
$order = isset($_GET['order']) ? $_GET['order'] : 'DESC';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

// Base query
$query = "SELECT user_id, username, email, role, status, created_at FROM users WHERE 1=1";
$count_query = "SELECT COUNT(*) as total FROM users WHERE 1=1";
$query_params = [];

// Add search condition
if (!empty($search)) {
    $search_term = "%$search%";
    $query .= " AND (username LIKE ? OR email LIKE ?)";
    $count_query .= " AND (username LIKE ? OR email LIKE ?)";
    $query_params[] = $search_term;
    $query_params[] = $search_term;
}

// Add role filter
if (!empty($role_filter)) {
    $query .= " AND role = ?";
    $count_query .= " AND role = ?";
    $query_params[] = $role_filter;
}

// Add sorting
$valid_sort_columns = ['user_id', 'username', 'email', 'role', 'status', 'created_at'];
$valid_order_values = ['ASC', 'DESC'];

if (!in_array($sort, $valid_sort_columns)) {
    $sort = 'created_at';
}

if (!in_array(strtoupper($order), $valid_order_values)) {
    $order = 'DESC';
}

$query .= " ORDER BY $sort $order";

// Add pagination
$query .= " LIMIT ?, ?";

// Prepare and execute count query
$count_stmt = $conn->prepare($count_query);
if (!empty($query_params)) {
    $types = str_repeat('s', count($query_params));
    $count_stmt->bind_param($types, ...$query_params);
}
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);
$count_stmt->close();

// Prepare and execute main query
$stmt = $conn->prepare($query);
if (!empty($query_params)) {
    $query_params[] = $offset;
    $query_params[] = $records_per_page;
    $types = str_repeat('s', count($query_params) - 2) . 'ii';
    $stmt->bind_param($types, ...$query_params);
} else {
    $stmt->bind_param("ii", $offset, $records_per_page);
}
$stmt->execute();
$result = $stmt->get_result();
$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}
$stmt->close();

// Get user statistics
$query = "SELECT 
    COUNT(*) as total_users,
    SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as admin_count,
    SUM(CASE WHEN role = 'user' THEN 1 ELSE 0 END) as regular_user_count,
    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_users,
    SUM(CASE WHEN status = 'suspended' THEN 1 ELSE 0 END) as suspended_users
    FROM users";
$result = $conn->query($query);
$user_stats = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Moon Arrow Studios</title>
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
            width: 120px;
        }
        .cursor-pointer {
            cursor: pointer;
        }
    </style>
</head>

<body>
    <?php include('../header.php'); ?>

    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="mb-0">Manage Users</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Manage Users</li>
                    </ol>
                </nav>
            </div>
            <div>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                    <i class="bi bi-person-plus-fill me-2"></i>Add New User
                </button>
            </div>
        </div>

        <!-- User Stats -->
        <div class="row mb-4 d-flex justify-content-center">
            <div class="col-md-2 mb-3">
                <div class="card stats-card bg-primary bg-gradient text-white">
                    <div class="card-body text-center">
                        <h3 class="fs-2 mb-0"><?php echo $user_stats['total_users']; ?></h3>
                        <p class="mb-0">Total Users</p>
                    </div>
                </div>
            </div>
            <div class="col-md-2 mb-3">
                <div class="card stats-card bg-danger bg-gradient text-white">
                    <div class="card-body text-center">
                        <h3 class="fs-2 mb-0"><?php echo $user_stats['admin_count']; ?></h3>
                        <p class="mb-0">Admins</p>
                    </div>
                </div>
            </div>
            <div class="col-md-2 mb-3">
                <div class="card stats-card bg-info bg-gradient text-white">
                    <div class="card-body text-center">
                        <h3 class="fs-2 mb-0"><?php echo $user_stats['regular_user_count']; ?></h3>
                        <p class="mb-0">Users</p>
                    </div>
                </div>
            </div>
            <div class="col-md-2 mb-3">
                <div class="card stats-card bg-success bg-gradient text-white">
                    <div class="card-body text-center">
                        <h3 class="fs-2 mb-0"><?php echo $user_stats['active_users']; ?></h3>
                        <p class="mb-0">Active</p>
                    </div>
                </div>
            </div>
            <div class="col-md-2 mb-3">
                <div class="card stats-card bg-secondary bg-gradient text-white">
                    <div class="card-body text-center">
                        <h3 class="fs-2 mb-0"><?php echo $user_stats['suspended_users']; ?></h3>
                        <p class="mb-0">Suspended</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search and Filter -->
        <div class="card mb-4">
            <div class="card-body">
                <form action="manage_users.php" method="GET" class="row g-3">
                    <div class="col-md-6">
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" class="form-control" placeholder="Search by username or email" name="search" value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" name="role">
                            <option value="">All Roles</option>
                            <option value="admin" <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>>Admin</option>
                            <option value="user" <?php echo $role_filter === 'user' ? 'selected' : ''; ?>>User</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary me-2">Filter</button>
                        <a href="manage_users.php" class="btn btn-secondary">Reset</a>
                    </div>
                    <input type="hidden" name="sort" value="<?php echo $sort; ?>">
                    <input type="hidden" name="order" value="<?php echo $order; ?>">
                </form>
            </div>
        </div>

        <!-- User Table -->
        <div class="card mb-4">
            <div class="card-header bg-dark bg-gradient">
                <h5 class="mb-0"><i class="bi bi-people-fill me-2"></i>All Users</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0">
                        <thead>
                            <tr>
                                <th class="cursor-pointer" onclick="changeSort('user_id')">
                                    ID <?php echo getSortIcon('user_id'); ?>
                                </th>
                                <th class="cursor-pointer" onclick="changeSort('username')">
                                    Username <?php echo getSortIcon('username'); ?>
                                </th>
                                <th class="cursor-pointer" onclick="changeSort('email')">
                                    Email <?php echo getSortIcon('email'); ?>
                                </th>
                                <th class="cursor-pointer" onclick="changeSort('role')">
                                    Role <?php echo getSortIcon('role'); ?>
                                </th>
                                <th class="cursor-pointer" onclick="changeSort('status')">
                                    Status <?php echo getSortIcon('status'); ?>
                                </th>
                                <th class="cursor-pointer" onclick="changeSort('created_at')">
                                    Joined <?php echo getSortIcon('created_at'); ?>
                                </th>
                                <th class="actions-column">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($users)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4">No users found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?php echo $user['user_id']; ?></td>
                                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td>
                                            <?php
                                            $badge_class = 'bg-info';
                                            if ($user['role'] === 'admin') {
                                                $badge_class = 'bg-danger';
                                            }
                                            ?>
                                            <span class="badge <?php echo $badge_class; ?>"><?php echo ucfirst($user['role']); ?></span>
                                        </td>
                                        <td>
                                            <?php
                                            $status_class = $user['status'] === 'active' ? 'bg-success' : 'bg-secondary';
                                            ?>
                                            <span class="badge <?php echo $status_class; ?>"><?php echo ucfirst($user['status']); ?></span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                        <td>
                                            <a href="edit_user.php?id=<?php echo $user['user_id']; ?>" class="btn btn-sm btn-primary" title="Edit User">
                                                <i class="bi bi-pencil-fill"></i>
                                            </a>
                                            <?php if ($user['role'] !== 'admin' || $_SESSION['user_id'] !== $user['user_id']): ?>
                                                <button class="btn btn-sm btn-danger" title="Delete User" data-bs-toggle="modal" data-bs-target="#deleteUserModal" data-user-id="<?php echo $user['user_id']; ?>" data-username="<?php echo htmlspecialchars($user['username']); ?>">
                                                    <i class="bi bi-trash-fill"></i>
                                                </button>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-danger" disabled title="Cannot delete your own admin account">
                                                    <i class="bi bi-trash-fill"></i>
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        Showing <?php echo min(($page - 1) * $records_per_page + 1, $total_records); ?>-<?php echo min($page * $records_per_page, $total_records); ?> of <?php echo $total_records; ?> users
                    </div>
                    <?php if ($total_pages > 1): ?>
                        <nav aria-label="User pagination">
                            <ul class="pagination mb-0">
                                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role_filter); ?>&sort=<?php echo $sort; ?>&order=<?php echo $order; ?>" aria-label="Previous">
                                        <span aria-hidden="true">&laquo;</span>
                                    </a>
                                </li>
                                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role_filter); ?>&sort=<?php echo $sort; ?>&order=<?php echo $order; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                                <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role_filter); ?>&sort=<?php echo $sort; ?>&order=<?php echo $order; ?>" aria-label="Next">
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

    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="addUserModalLabel"><i class="bi bi-person-plus-fill me-2"></i>Add New User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="add_user.php" method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label for="role" class="form-label">Role</label>
                            <select class="form-select" id="role" name="role">
                                <option value="user">User</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="active">Active</option>
                                <option value="suspended">Suspended</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete User Modal -->
    <div class="modal fade" id="deleteUserModal" tabindex="-1" aria-labelledby="deleteUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteUserModalLabel"><i class="bi bi-exclamation-triangle-fill me-2"></i>Delete User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the user <strong id="deleteUserName"></strong>?</p>
                    <p class="text-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i>This action cannot be undone. All posts, assets, and comments by this user will also be deleted.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form action="delete_user.php" method="POST">
                        <input type="hidden" name="user_id" id="deleteUserId">
                        <button type="submit" class="btn btn-danger">Delete User</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    
    <script>
        // Set the correct ID when opening the delete modal
        document.addEventListener('DOMContentLoaded', function() {
            const deleteUserModal = document.getElementById('deleteUserModal');
            if (deleteUserModal) {
                deleteUserModal.addEventListener('show.bs.modal', function(event) {
                    const button = event.relatedTarget;
                    const userId = button.getAttribute('data-user-id');
                    const username = button.getAttribute('data-username');
                    document.getElementById('deleteUserId').value = userId;
                    document.getElementById('deleteUserName').textContent = username;
                });
            }
        });
        
        // Change sort function
        function changeSort(column) {
            let currentSort = '<?php echo $sort; ?>';
            let currentOrder = '<?php echo $order; ?>';
            let newOrder = 'ASC';
            
            if (column === currentSort) {
                newOrder = currentOrder === 'ASC' ? 'DESC' : 'ASC';
            }
            
            const form = document.createElement('form');
            form.method = 'GET';
            form.action = 'manage_users.php';
            
            const inputs = {
                'search': '<?php echo htmlspecialchars($search); ?>',
                'role': '<?php echo htmlspecialchars($role_filter); ?>',
                'sort': column,
                'order': newOrder,
                'page': '<?php echo $page; ?>'
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
        
        // Helper function to output sort icons
        <?php
        function getSortIcon($column) {
            global $sort, $order;
            
            if ($sort !== $column) {
                return '<i class="bi bi-arrow-down-up text-muted"></i>';
            } else if ($order === 'ASC') {
                return '<i class="bi bi-sort-alpha-down"></i>';
            } else {
                return '<i class="bi bi-sort-alpha-down-alt"></i>';
            }
        }
        ?>
    </script>
</body>
</html
