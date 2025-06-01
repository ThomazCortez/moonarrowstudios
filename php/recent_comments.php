<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}

$host = 'localhost';
$db   = 'moonarrowstudios';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$db;charset=$charset",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

require 'header.php';

// Search and filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'newest';

// Pagination setup
$perPage = 10;
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($currentPage - 1) * $perPage;

// Current user ID
$current_user_id = $_SESSION['user_id'];

// Initialize search condition and parameters
$searchCondition = "";
$searchParams = [];
if (!empty($search)) {
    $searchCondition = " AND (content LIKE :search OR username LIKE :search)";
    $searchParams[':search'] = "%$search%";
}

// Order by clause based on filter
$orderClause = "ORDER BY ";
switch ($filter) {
    case 'oldest':
        $orderClause .= "created_at ASC";
        break;
    case 'most_upvotes':
        $orderClause .= "score DESC";
        break;
    case 'most_downvotes':
        $orderClause .= "score ASC";
        break;
    case 'most_controversial':
        $orderClause .= "interaction_count DESC";
        break;
    case 'newest':
    default:
        $orderClause .= "created_at DESC";
        break;
}

// Fetch comments with parent title information
$stmt = $pdo->prepare("
    SELECT SQL_CALC_FOUND_ROWS 
        id, 
        content, 
        created_at, 
        user_id,
        username, 
        upvotes, 
        downvotes,
        parent_title,
        parent_type,
        parent_id,
        (upvotes - downvotes) AS score,
        (upvotes + downvotes) AS interaction_count
    FROM (
        SELECT 
            c.id, 
            c.content, 
            c.created_at, 
            u.user_id,
            u.username, 
            c.upvotes, 
            c.downvotes,
            p.title AS parent_title,
            'post' AS parent_type,
            c.post_id AS parent_id
        FROM comments c
        JOIN users u ON c.user_id = u.user_id
        JOIN posts p ON c.post_id = p.id
        WHERE c.status = 'published'
        AND p.status = 'published'

        UNION ALL

        SELECT 
            ca.id, 
            ca.content, 
            ca.created_at, 
            ua.user_id,
            ua.username, 
            ca.upvotes, 
            ca.downvotes,
            a.title AS parent_title,
            'asset' AS parent_type,
            ca.asset_id AS parent_id
        FROM comments_asset ca
        JOIN users ua ON ca.user_id = ua.user_id
        JOIN assets a ON ca.asset_id = a.id
        WHERE ca.status = 'published'
        AND a.status = 'published'
    ) AS combined
    WHERE user_id != :user_id
    $searchCondition
    $orderClause
    LIMIT :offset, :perPage
");

// Bind parameters
$stmt->bindValue(':user_id', $current_user_id, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':perPage', $perPage, PDO::PARAM_INT);

// Bind search parameter if needed
if (!empty($search)) {
    $stmt->bindValue(':search', $searchParams[':search'], PDO::PARAM_STR);
}

$stmt->execute();

$recent_comments = $stmt->fetchAll();

$totalStmt = $pdo->query("SELECT FOUND_ROWS()");
$totalComments = $totalStmt->fetchColumn();
$totalPages = ceil($totalComments / $perPage);

function formatTimeAgo($datetime) {
    $now = new DateTime();
    $created = new DateTime($datetime);
    $diff = $now->getTimestamp() - $created->getTimestamp();

    $minutes = floor($diff / 60);
    $hours = floor($diff / 3600);
    $days = floor($diff / 86400);

    if ($minutes < 60) {
        return $minutes . ' minutes ago';
    } elseif ($hours < 24) {
        return $hours . ' hours ago';
    } else {
        return $days . ' days ago';
    }
}

function truncateComment($html, $length = 150) {
    $text = strip_tags($html);
    if (strlen($text) > $length) {
        return substr($text, 0, $length) . '...';
    }
    return $text;
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recent Comments - MoonArrow Studios</title>
    <link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        /* Consistent CSS from following_assets.php */
        :root {
            --color-canvas-default: #ffffff;
            --color-canvas-subtle: #f6f8fa;
            --color-border-default: #d0d7de;
            --color-border-muted: #d8dee4;
            --color-btn-primary-bg: #2da44e;
            --color-btn-primary-hover-bg: #2c974b;
            --color-fg-default: #1F2328;
            --color-fg-muted: #656d76;
            --color-accent-fg: #0969da;
            --color-input-bg: #ffffff;
            --color-card-bg: #ffffff;
            --color-card-border: #d0d7de;
            --color-header-bg: #f6f8fa;
            --color-modal-bg: #ffffff;
        }

        @media (prefers-color-scheme: dark) {
            :root {
                --color-canvas-default: #0d1117;
                --color-canvas-subtle: #161b22;
                --color-border-default: #30363d;
                --color-border-muted: #21262d;
                --color-btn-primary-bg: #238636;
                --color-btn-primary-hover-bg: #2ea043;
                --color-fg-default: #c9d1d9;
                --color-fg-muted: #8b949e;
                --color-accent-fg: #58a6ff;
                --color-input-bg: #0d1117;
                --color-card-bg: #161b22;
                --color-card-border: #30363d;
                --color-header-bg: #161b22;
                --color-modal-bg: #161b22;
            }
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", "Noto Sans", Helvetica, Arial, sans-serif;
            background-color: var(--color-canvas-default);
            color: var(--color-fg-default);
            line-height: 1.5;
            font-size: 14px;
        }

        /* Form Controls */
        .form-control, .form-select {
            padding: 5px 12px;
            font-size: 14px;
            line-height: 20px;
            color: var(--color-fg-default);
            background-color: var(--color-input-bg);
            border: 1px solid var(--color-border-default);
            border-radius: 6px;
            transition: border-color .15s ease-in-out, box-shadow .15s ease-in-out;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--color-accent-fg);
            outline: none;
            box-shadow: 0 0 0 3px rgba(9, 105, 218, 0.3);
        }

        /* Buttons */
        .btn {
            border-radius: 6px;
            padding: 5px 16px;
            font-size: 14px;
            font-weight: 500;
            line-height: 20px;
            transition: .2s cubic-bezier(0.3, 0, 0.5, 1);
        }

        .btn-primary:hover {
            background-color: var(--color-btn-primary-hover-bg);
        }

        /* Enhanced Card Styles */
        .card {
            position: relative;
            overflow: hidden;
            background-color: var(--color-card-bg);
            border: 1px solid transparent;
            border-radius: 6px;
            margin-bottom: 16px;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            transform: translateZ(0);
            will-change: transform;
        }

        .card::before,
        .card::after {
            content: '';
            position: absolute;
            left: 0;
            width: 100%;
            height: 2px;
            background: rgba(88, 166, 255, 0.3);
            transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 2;
            opacity: 0;
        }

        .card::before {
            top: 0;
            transform: translateX(-105%);
            box-shadow: 0 0 15px rgba(88, 166, 255, 0.3);
        }

        .card::after {
            bottom: 0;
            transform: translateX(105%);
            box-shadow: 0 0 15px rgba(88, 166, 255, 0.3);
        }

        .card:hover {
            box-shadow: 0 0 25px 5px rgba(88, 166, 255, 0.2),
                        0 4px 20px rgba(0, 0, 0, 0.3);
            border-color: rgba(88, 166, 255, 0.3);
            transform: translateY(-2px);
        }

        .card:hover::before,
        .card:hover::after {
            transform: translateX(0);
            opacity: 1;
        }

        .card-body {
            position: relative;
            z-index: 1;
            padding: 16px;
        }

        .card-body::before,
        .card-body::after {
            content: '';
            position: absolute;
            top: 0;
            height: 100%;
            width: 2px;
            background: rgba(88, 166, 255, 0.3);
            transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 2;
            opacity: 0;
            box-shadow: 0 0 15px rgba(88, 166, 255, 0.3);
        }

        .card-body::before {
            left: 0;
            transform: translateY(105%);
        }

        .card-body::after {
            right: 0;
            transform: translateY(-105%);
        }

        .card:hover .card-body::before,
        .card:hover .card-body::after {
            transform: translateY(0);
            opacity: 1;
        }

        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .card-text {
            font-size: 0.9rem;
            color: var(--color-fg-muted);
            margin-bottom: 0.5rem;
        }

        /* Profile Hover Card */
        .profile-hover-card {
            position: fixed;
            width: 300px;
            background-color: var(--color-card-bg);
            border: 1px solid var(--color-card-border);
            border-radius: 6px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.12);
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.2s ease, visibility 0.2s ease;
            z-index: 1000;
            pointer-events: none;
        }

        .profile-hover-card.visible {
            opacity: 1;
            visibility: visible;
        }

        .hover-card-banner {
            width: 100%;
            height: 80px;
            object-fit: cover;
            border-top-left-radius: 6px;
            border-top-right-radius: 6px;
            background-color: rgb(108, 117, 125);
        }

        .hover-card-content {
            padding: 12px;
            position: relative;
        }

        .hover-card-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            border: 3px solid var(--color-card-bg);
            position: absolute;
            top: -24px;
            left: 12px;
            background-color: var(--color-canvas-subtle);
        }

        .hover-card-info {
            margin-top: 28px;
        }

        .hover-card-username {
            font-size: 16px;
            font-weight: 600;
            color: var(--color-fg-default);
            margin-bottom: 4px;
        }

        .hover-card-meta {
            font-size: 12px;
            color: var(--color-fg-muted);
        }

        /* Pagination */
        .pagination {
            margin: 0;
            display: flex;
            justify-content: center;
            gap: 5px;
        }

        .pagination .page-item .page-link {
            color: var(--color-fg-default);
            background-color: var(--color-card-bg);
            border: 1px solid var(--color-border-default);
            padding: 0.5rem 0.75rem;
            margin: 0 0.25rem;
            border-radius: 6px;
            transition: all 0.2s ease;
        }

        .pagination .page-item .page-link:hover {
            background-color: var(--color-canvas-subtle);
        }

        .pagination .page-item.active .page-link {
            background-color: var(--color-accent-fg);
            color: #ffffff;
            border-color: var(--color-accent-fg);
        }

        .pagination .page-item.disabled .page-link {
            color: var(--color-fg-muted);
            pointer-events: none;
            background-color: var(--color-canvas-subtle);
        }

        .pagination-wrapper {
            margin-top: auto;
            padding: 20px 0;
            display: flex;
            justify-content: center;
        }



        /* Responsive Design */
        @media (max-width: 768px) {
            .d-flex.justify-content-between {
                flex-direction: column;
            }
            
            form.d-flex {
                flex-direction: column;
                width: 100%;
            }
            
            form.d-flex input,
            form.d-flex select,
            form.d-flex button {
                width: 100%;
                margin-right: 0 !important;
                margin-bottom: 0.5rem;
            }
        }

        .no-posts-message {
            text-align: center;
            padding: 40px 20px;
            color: var(--color-fg-muted);
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Page Header -->
        <div class="my-4 animate__animated animate__fadeIn">
            <h1>Recent Comments</h1>
            <p>Latest Comments on your Posts and Assets</p>
        </div>

        <!-- Search and Filter Section -->
<form method="GET" class="d-flex align-items-center justify-content-between search-filter-container animate__animated animate__fadeIn">
    <div class="d-flex align-items-center flex-grow-1">
        <!-- Search Box -->
        <input type="text" name="search" class="form-control me-2 animate__animated animate__fadeInLeft" 
               placeholder="Search comments or users..." value="<?= htmlspecialchars($search) ?>">
        
        <!-- Filter Dropdown -->
        <select name="filter" class="form-select me-2 animate__animated animate__fadeInLeft">
            <option value="newest" <?= $filter == 'newest' ? 'selected' : '' ?>>Newest First</option>
            <option value="oldest" <?= $filter == 'oldest' ? 'selected' : '' ?>>Oldest First</option>
            <option value="most_upvotes" <?= $filter == 'most_upvotes' ? 'selected' : '' ?>>Most Upvotes</option>
        </select>
        
        <!-- Search Button -->
        <button type="submit" class="btn btn-primary animate__animated animate__fadeInLeft">
            <i class="bi bi-search me-1"></i> Search
        </button>
    </div>
</form>
<br>

        <!-- Comments List -->
<div class="comments-list animate__animated animate__fadeIn">
    <?php if (!empty($recent_comments)): ?>
        <?php 
        $comment_count = 0;
        foreach ($recent_comments as $comment): 
            $comment_count++;
            $animation_delay = min($comment_count * 0.1, 1);
        ?>
            <!-- REMOVED COLUMN WRAPPER AND ADJUSTED MARGIN -->
            <div class="card animate__animated animate__fadeInUp mb-3" style="animation-delay: <?= $animation_delay ?>s;">
                <div class="card-body">
                    <!-- Parent Title -->
                    <?php if (!empty($comment['parent_title'])): ?>
                        <h5 class="parent-title">
                            Comment on 
                            <a href="view_<?= $comment['parent_type'] ?>.php?id=<?= $comment['parent_id'] ?>">
                                <?= htmlspecialchars($comment['parent_title']) ?>
                            </a>
                        </h5>
                    <?php endif; ?>
                    
                    <!-- User and timestamp -->
                    <p class="card-text text-light">
                        <em>Posted <?= formatTimeAgo($comment['created_at']) ?> by 
                            <a href="profile.php?id=<?= $comment['user_id'] ?>" 
                               class="text-decoration-none username-link">
                                <?= htmlspecialchars($comment['username']) ?>
                            </a>
                        </em>
                    </p>
                    
                    <!-- Comment content -->
                    <div class="comment-content" style="border-left: 4px solid #007bff; padding-left: 15px; margin: 10px 0; border-radius: 0;">
                        <?= nl2br(htmlspecialchars(truncateComment($comment['content']))) ?>
                    </div>
                    
                    <!-- Stats -->
                    <p class="card-text">
                        <strong>Rating:</strong> 
                        <i class="bi bi-caret-up-fill"></i> <?= $comment['upvotes'] ?> 
                        <i class="bi bi-caret-down-fill"></i> <?= $comment['downvotes'] ?> 
                        Score: <?= $comment['upvotes'] - $comment['downvotes'] ?>
                    </p>
                    
                    <!-- View Comment Button -->
                    <div class="mt-3">
                        <a href="admin/view_comment.php?id=<?= $comment['id'] ?>" 
                           class="btn btn-primary btn-sm">
                            <i class="bi bi-eye"></i> View Comment
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
                
                <!-- Pagination -->
                <div class="pagination-wrapper animate__animated animate__fadeIn">
    <nav aria-label="Page navigation">
        <ul class="pagination">
            <!-- First Page -->
            <li class="page-item <?= ($currentPage <= 1) ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=1<?= !empty($search) ? '&search='.urlencode($search) : '' ?><?= !empty($filter) ? '&filter='.urlencode($filter) : '' ?>">First</a>
            </li>
            
            <!-- Previous Page -->
            <li class="page-item <?= ($currentPage <= 1) ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= max(1, $currentPage - 1) ?><?= !empty($search) ? '&search='.urlencode($search) : '' ?><?= !empty($filter) ? '&filter='.urlencode($filter) : '' ?>">Previous</a>
            </li>
            
            <!-- Page Numbers -->
            <?php
            $start_page = max(1, $currentPage - 2);
            $end_page = min($totalPages, $currentPage + 2);
            
            for ($i = $start_page; $i <= $end_page; $i++): 
            ?>
                <li class="page-item <?= ($i == $currentPage) ? 'active animate__animated animate__pulse animate__infinite' : '' ?>">
                    <a class="page-link" href="?page=<?= $i ?><?= !empty($search) ? '&search='.urlencode($search) : '' ?><?= !empty($filter) ? '&filter='.urlencode($filter) : '' ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
            
            <!-- Next Page -->
            <li class="page-item <?= ($currentPage >= $totalPages) ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= min($totalPages, $currentPage + 1) ?><?= !empty($search) ? '&search='.urlencode($search) : '' ?><?= !empty($filter) ? '&filter='.urlencode($filter) : '' ?>">Next</a>
            </li>
            
            <!-- Last Page -->
            <li class="page-item <?= ($currentPage >= $totalPages) ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= $totalPages ?><?= !empty($search) ? '&search='.urlencode($search) : '' ?><?= !empty($filter) ? '&filter='.urlencode($filter) : '' ?>">Last</a>
            </li>
        </ul>
    </nav>
</div>
            <?php else: ?>
                <div class="no-comments-message animate__animated animate__fadeIn">
                    <i class="bi bi-chat-left-quote" style="font-size: 4rem; opacity: 0.5;"></i>
                    <h3>No Comments Found</h3>
                    <p>
                        <?php if (!empty($search)): ?>
                            No comments matching your search criteria were found.
                        <?php else: ?>
                            There are no recent comments to display. Be the first to comment!
                        <?php endif; ?>
                    </p>
                    <?php if (!empty($search)): ?>
                        <a href="recent_comments.php" class="btn btn-primary mt-3">
                            <i class="bi bi-arrow-counterclockwise me-1"></i> Reset Filters
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Profile Hover Card -->
    <div class="profile-hover-card" id="profileHoverCard"></div>

    <script>
        let hoverCard = document.createElement('div');
    hoverCard.className = 'profile-hover-card';
    document.body.appendChild(hoverCard);
    
    let hoverTimeout;
    let currentUsername;
    
    document.querySelectorAll('a[href^="profile.php"]').forEach(link => {
        link.addEventListener('mouseenter', async (e) => {
            clearTimeout(hoverTimeout);
            const userId = new URLSearchParams(link.href.split('?')[1]).get('id');
            currentUsername = link;
            
            const rect = link.getBoundingClientRect();
            hoverCard.style.left = `${rect.left}px`;
            hoverCard.style.top = `${rect.bottom + 8}px`;
            
            try {
                const response = await fetch(`fetch_user_preview.php?user_id=${userId}`);
                const userData = await response.json();
                
                const avatarContent = userData.profile_picture 
                    ? `<img class="hover-card-avatar" src="${userData.profile_picture}" alt="${userData.username}'s avatar">` 
                    : `<div class="hover-card-avatar d-flex align-items-center justify-content-center bg-dark">
                         <i class="bi bi-person-fill text-light" style="font-size: 1.5rem;"></i>
                       </div>`;
                
                let bannerContent = userData.banner 
                    ? `<img src="${userData.banner}" class="hover-card-banner" alt="User banner">`
                    : `<div class="hover-card-banner" style="background-color: rgb(108, 117, 125);"></div>`;
                
                hoverCard.innerHTML = `
                    ${bannerContent}
                    <div class="hover-card-content">
                        ${avatarContent}
                        <div class="hover-card-info">
                            <div class="hover-card-username">${userData.username}</div>
                            <div class="hover-card-meta">
                                Joined ${userData.formatted_join_date}<br>
                                ${userData.follower_count} followers
                            </div>
                        </div>
                    </div>
                `;
                
                hoverCard.classList.add('visible');
            } catch (error) {
                console.error('Error fetching user data:', error);
            }
        });
        
        link.addEventListener('mouseleave', () => {
            hoverTimeout = setTimeout(() => {
                if (currentUsername === link) {
                    hoverCard.classList.remove('visible');
                }
            }, 200);
        });
    });
    
    hoverCard.addEventListener('mouseenter', () => {
        clearTimeout(hoverTimeout);
    });
    
    hoverCard.addEventListener('mouseleave', () => {
        hoverCard.classList.remove('visible');
    });
    </script>
</body>
</html>