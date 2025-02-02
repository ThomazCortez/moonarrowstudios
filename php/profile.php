<?php
// profile.php
session_start();
include 'db_connect.php';

// Get user ID from URL or session
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : ($_SESSION['user_id'] ?? null);

if (!$user_id) {
    header("Location: sign_in/sign_in_html.php");
    exit;
}

// Fetch user data
$stmt = $conn->prepare("SELECT *, DATE_FORMAT(created_at, '%M %Y') as formatted_join_date FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    header("Location: ../index.php");
    exit;
}

// Fetch follower count
$stmt = $conn->prepare("SELECT COUNT(*) as follower_count FROM follows WHERE following_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$follower_count = $stmt->get_result()->fetch_assoc()['follower_count'];

// Get search parameters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';
$sort_order = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

// Determine sort order
$order_by = match($sort_order) {
    'oldest' => 'posts.created_at ASC',
    'highest_score' => '(posts.upvotes - posts.downvotes) DESC, posts.created_at DESC',
    default => 'posts.created_at DESC'
};

// Build the base query
$sql = "SELECT posts.*, categories.name AS category_name, 
               (posts.upvotes - posts.downvotes) AS score 
        FROM posts 
        JOIN categories ON posts.category_id = categories.id 
        WHERE posts.user_id = ?";

// Add search conditions
if ($search) {
    $sql .= " AND (posts.title LIKE ? OR posts.content LIKE ? OR posts.hashtags LIKE ?)";
}
if ($category_filter) {
    $sql .= " AND category_id = ?";
}

$sql .= " ORDER BY " . $order_by;

// Prepare and execute the query with dynamic parameters
$types = "i"; // Start with user_id parameter type
$params = [$user_id];

if ($search) {
    $search_param = "%$search%";
    $types .= "sss"; // Add three string parameter types for title, content, and hashtags
    $params = array_merge($params, [$search_param, $search_param, $search_param]);
}
if ($category_filter) {
    $types .= "i"; // Add integer parameter type for category
    $params[] = $category_filter;
}

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$posts = $stmt->get_result();

// Fetch categories for the filter dropdown
$categories = $conn->query("SELECT * FROM categories");

// Check if the current user is following this profile
$is_following = false;
$is_logged_in = isset($_SESSION['user_id']);
$viewing_own_profile = $is_logged_in && $_SESSION['user_id'] == $user_id;

if ($is_logged_in && !$viewing_own_profile) {
    $stmt = $conn->prepare("SELECT 1 FROM follows WHERE follower_id = ? AND following_id = ?");
    $stmt->bind_param("ii", $_SESSION['user_id'], $user_id);
    $stmt->execute();
    $is_following = $stmt->get_result()->num_rows > 0;
}

// Handle follow/unfollow actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $is_logged_in && !$viewing_own_profile) {
    if (isset($_POST['follow'])) {
        $stmt = $conn->prepare("INSERT IGNORE INTO follows (follower_id, following_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $_SESSION['user_id'], $user_id);
        $stmt->execute();
    } elseif (isset($_POST['unfollow'])) {
        $stmt = $conn->prepare("DELETE FROM follows WHERE follower_id = ? AND following_id = ?");
        $stmt->bind_param("ii", $_SESSION['user_id'], $user_id);
        $stmt->execute();
    }
    header("Location: profile.php?id=" . $user_id);
    exit;
}
?>


<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <?php require 'header.php'; ?>
    <title>MoonArrow Studios - <?= htmlspecialchars($user['username']) ?>'s Profile</title>
    <style>
        .banner-container {
            height: 200px;
            overflow: visible;
            position: relative;
            margin-bottom: 80px;
        }
        
        .banner-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .profile-picture {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            border: 4px solid var(--color-canvas-default);
            position: absolute;
            bottom: -75px;
            left: 50px;
            object-fit: cover;
        }
        
        .profile-info {
            margin-left: 50px;
            margin-top: 20px;
        }
        
        .username {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .description {
            color: var(--color-fg-muted);
            margin-bottom: 20px;
            max-width: 600px;
        }
        
        .filter-section {
            margin: 20px 0;
            padding: 15px;
            background-color: var(--color-canvas-subtle);
            border-radius: 6px;
        }
        
        .posts-container {
            margin-top: 20px;
        }
        
        .profile-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .username-container {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .follow-button {
            position: static;
        }

        .user-stats {
            margin-bottom: 20px;
        }

        .user-stats p {
            margin: 0;
            color: var(--color-fg-muted);
        }

        .card {
            background-color: var(--color-card-bg);
            border: 1px solid var(--color-card-border);
            border-radius: 6px;
            margin-bottom: 16px;
            transition: transform 0.2s, border-color 0.2s, box-shadow 0.2s;
        }

        .card:hover {
            border-color: var(--color-accent-fg);
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .card-title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .card-body {
            padding: 16px;
        }

        /* Dark mode styles */
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
            }
        }
        /* Hashtags */
        .hashtags {
            color: var(--color-accent-fg);
            font-size: 12px;
        }

        .hashtag {
            background-color: var(--color-canvas-subtle);
            padding: 2px 8px;
            border-radius: 12px;
            margin-right: 4px;
        }
    </style>
</head>
<body>
    <div class="container-fluid p-0">
        <div class="banner-container">
            <?php if ($user['banner']): ?>
                <img src="<?= htmlspecialchars($user['banner']) ?>" alt="Profile banner" class="banner-img">
            <?php else: ?>
                <div class="bg-secondary w-100 h-100"></div>
            <?php endif; ?>
            
            <?php if ($user['profile_picture']): ?>
                <img src="<?= htmlspecialchars($user['profile_picture']) ?>" alt="Profile picture" class="profile-picture">
            <?php else: ?>
                <div class="profile-picture bg-dark d-flex align-items-center justify-content-center">
                    <i class="bi bi-person-fill text-light" style="font-size: 4rem;"></i>
                </div>
            <?php endif; ?>
        </div>

        <div class="container">
            <div class="profile-info">
                <div class="profile-header">
                    <div class="username-container">
                        <h1 class="username mb-0"><?= htmlspecialchars($user['username']) ?></h1>
                        <?php if ($is_logged_in && !$viewing_own_profile): ?>
                            <form method="POST" class="follow-button">
                                <?php if ($is_following): ?>
                                    <button type="submit" name="unfollow" class="btn btn-outline-primary">Unfollow</button>
                                <?php else: ?>
                                    <button type="submit" name="follow" class="btn btn-primary">Follow</button>
                                <?php endif; ?>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="user-stats">
                    <p class="mb-1">Joined <?= htmlspecialchars($user['formatted_join_date']) ?></p>
                    <p class="mb-1">Followers: <?= htmlspecialchars($follower_count) ?></p>
                </div>

                <?php if ($user['description']): ?>
                    <p class="description"><?= nl2br(htmlspecialchars($user['description'])) ?></p>
                <?php endif; ?>
            </div>

            <hr class="my-4">

            <div class="">
                <form method="GET" class="d-flex align-items-center gap-2">
                    <input type="hidden" name="id" value="<?= $user_id ?>">
                    <input type="text" name="search" class="form-control bg-dark" 
                        placeholder="Search" value="<?= htmlspecialchars($search) ?>">
                    
                    <select name="category" class="form-select bg-dark text-light">
                        <option value="">All Categories</option>
                        <?php 
                        $categories->data_seek(0);
                        while ($cat = $categories->fetch_assoc()): 
                        ?>
                            <option value="<?= $cat['id'] ?>" 
                                    <?= $category_filter == $cat['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>

                    <select name="sort" class="form-select bg-dark text-light">
                        <option value="newest" <?= $sort_order == 'newest' ? 'selected' : '' ?>>Newest</option>
                        <option value="oldest" <?= $sort_order == 'oldest' ? 'selected' : '' ?>>Oldest</option>
                        <option value="highest_score" <?= $sort_order == 'highest_score' ? 'selected' : '' ?>>Highest Score</option>
                    </select>

                    <button type="submit" class="btn btn-primary">Search</button>
                </form>
            </div>

            <div class="posts-container">
                <h2><?= htmlspecialchars($user['username']) ?>'s Posts</h2>
                <?php while ($post = $posts->fetch_assoc()): ?>
                    <div class="card mb-3">
                        <div class="card-body">
                            <h3 class="card-title">
                                <a href="view_post.php?id=<?= $post['id'] ?>" class="text-decoration-none">
                                    <?= htmlspecialchars($post['title']) ?>
                                </a>
                            </h3>
                            <p class="card-text text-muted">
                                <em>Posted on <?= $post['created_at'] ?></em>
                            </p>
                            <p class="card-text">
                                <strong>Category:</strong> <?= htmlspecialchars($post['category_name']) ?>
                            </p>
                            <p class="card-text">
                                <strong>Hashtags:</strong> <?= htmlspecialchars($post['hashtags']) ?>
                            </p>
                            <p class="card-text">
                                <strong>Rating:</strong> 
                                <i class="bi bi-caret-up-fill"></i><?= $post['upvotes'] ?? 0 ?> 
                                <i class="bi bi-caret-down-fill"></i><?= $post['downvotes'] ?? 0 ?> 
                                Score: <?= $post['score'] ?? 0 ?>
                            </p>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
    <script>
        // Modify the post display section to show hashtags as badges
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.card-text').forEach(element => {
        if (element.innerHTML.includes('<strong>Hashtags:</strong>')) {
            const hashtags = element.innerHTML.split('<strong>Hashtags:</strong> ')[1].trim();
            if (hashtags) {
                const hashtagArray = hashtags.split(' ');
                const hashtagBadges = hashtagArray.map(tag => 
                    `<span class="badge bg-dark me-1">${tag}</span>`
                ).join('');
                element.innerHTML = `<strong>Hashtags:</strong> ${hashtagBadges}`;
            }
        }
    });
});
    </script>
</body>
</html>