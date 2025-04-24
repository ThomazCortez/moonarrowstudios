<?php
session_start();
require 'db_connect.php';

// Get user ID from URL or session - IMPORTANT: Prioritize URL parameter
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : ($_SESSION['user_id'] ?? null);

if (!$user_id) {
    header("Location: sign_in/sign_in_html.php");
    exit;
}

// Get active tab from URL parameter
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'posts';

// Fetch user data including social links
$stmt = $conn->prepare("SELECT *, DATE_FORMAT(created_at, '%M %Y') as formatted_join_date FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    header("Location: index.php");
    exit;
}

// Fetch follower count
$stmt = $conn->prepare("SELECT COUNT(*) as follower_count FROM follows WHERE following_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$follower_count = $stmt->get_result()->fetch_assoc()['follower_count'];

// Posts filtering
$post_search = $_GET['post_search'] ?? '';
$post_category_filter = (int)($_GET['post_category'] ?? 0);
$post_sort_order = $_GET['post_sort'] ?? 'newest';
$post_order_by = match($post_sort_order) {
    'oldest' => 'posts.created_at ASC',
    'highest_score' => '(posts.upvotes - posts.downvotes) DESC',
    default => 'posts.created_at DESC'
};

// Assets filtering
$asset_search = $_GET['asset_search'] ?? '';
$asset_category_filter = (int)($_GET['asset_category'] ?? 0);
$asset_sort_order = $_GET['asset_sort'] ?? 'newest';
$asset_order_by = match($asset_sort_order) {
    'oldest' => 'assets.created_at ASC',
    'highest_score' => '(assets.upvotes - assets.downvotes) DESC',
    default => 'assets.created_at DESC'
};

// Fetch posts
$post_sql = "SELECT posts.*, categories.name AS category_name, 
            (posts.upvotes - posts.downvotes) AS score 
            FROM posts 
            JOIN categories ON posts.category_id = categories.id 
            WHERE posts.user_id = ?";
$post_params = [$user_id];
$post_types = "i";

if ($post_search) {
    $post_sql .= " AND (posts.title LIKE ? OR posts.content LIKE ? OR posts.hashtags LIKE ?)";
    $post_types .= "sss";
    array_push($post_params, "%$post_search%", "%$post_search%", "%$post_search%");
}
if ($post_category_filter) {
    $post_sql .= " AND posts.category_id = ?";
    $post_types .= "i";
    $post_params[] = $post_category_filter;
}

$post_sql .= " ORDER BY " . $post_order_by;
$post_stmt = $conn->prepare($post_sql);
$post_stmt->bind_param($post_types, ...$post_params);
$post_stmt->execute();
$posts = $post_stmt->get_result();

// Fetch assets
$asset_sql = "SELECT assets.*, asset_categories.name AS category_name, 
             (assets.upvotes - assets.downvotes) AS score 
             FROM assets 
             JOIN asset_categories ON assets.category_id = asset_categories.id 
             WHERE assets.user_id = ? AND assets.status != 'hidden'";
$asset_params = [$user_id];
$asset_types = "i";

if ($asset_search) {
    $asset_sql .= " AND (assets.title LIKE ? OR assets.content LIKE ? OR assets.hashtags LIKE ?)";
    $asset_types .= "sss";
    array_push($asset_params, "%$asset_search%", "%$asset_search%", "%$asset_search%");
}
if ($asset_category_filter) {
    $asset_sql .= " AND assets.category_id = ?";
    $asset_types .= "i";
    $asset_params[] = $asset_category_filter;
}

$asset_sql .= " ORDER BY " . $asset_order_by;
$asset_stmt = $conn->prepare($asset_sql);
$asset_stmt->bind_param($asset_types, ...$asset_params);
$asset_stmt->execute();
$assets = $asset_stmt->get_result();

// Check if following
$is_following = false;
$is_logged_in = isset($_SESSION['user_id']);
$viewing_own_profile = $is_logged_in && $_SESSION['user_id'] == $user_id;

if ($is_logged_in && !$viewing_own_profile) {
    $stmt = $conn->prepare("SELECT 1 FROM follows WHERE follower_id = ? AND following_id = ?");
    $stmt->bind_param("ii", $_SESSION['user_id'], $user_id);
    $stmt->execute();
    $is_following = $stmt->get_result()->num_rows > 0;
}

// Handle follow/unfollow
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

// Fetch categories for filters
$post_categories = $conn->query("SELECT * FROM categories");
$asset_categories = $conn->query("SELECT * FROM asset_categories");
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <?php require 'header.php'; ?>
    <title>MoonArrow Studios - <?= htmlspecialchars($user['username']) ?></title>
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
        
        .posts-container, .assets-container {
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

        .hashtags .badge {
            margin-right: 4px;
            margin-bottom: 4px;
        }

        .social-links {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }

        .social-links a {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            color: var(--color-canvas-default);
            transition: transform 0.2s, opacity 0.2s;
        }

        .social-links a:hover {
            transform: scale(1.1);
            opacity: 0.9;
        }

        .youtube-icon {
            background-color: #FF0000;
        }

        .linkedin-icon {
            background-color: #0077B5;
        }

        .twitter-icon {
            background-color: #1DA1F2;
        }

        .instagram-icon {
            background-color: #E4405F;
        }

        .github-icon {
            background-color: #333;
        }

        .portfolio-icon {
            background-color: #6c757d;
        }

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

                <!-- Social Links Section -->
                <?php if ($user['youtube'] || $user['linkedin'] || $user['twitter'] || $user['instagram'] || $user['github'] || $user['portfolio']): ?>
                    <div class="social-links">
                        <?php if ($user['youtube']): ?>
                            <a href="<?= htmlspecialchars($user['youtube']) ?>" target="_blank" class="youtube-icon" title="YouTube">
                                <i class="bi bi-youtube"></i>
                            </a>
                        <?php endif; ?>
                        
                        <?php if ($user['linkedin']): ?>
                            <a href="<?= htmlspecialchars($user['linkedin']) ?>" target="_blank" class="linkedin-icon" title="LinkedIn">
                                <i class="bi bi-linkedin"></i>
                            </a>
                        <?php endif; ?>
                        
                        <?php if ($user['twitter']): ?>
                            <a href="<?= htmlspecialchars($user['twitter']) ?>" target="_blank" class="twitter-icon" title="Twitter">
                                <i class="bi bi-twitter"></i>
                            </a>
                        <?php endif; ?>
                        
                        <?php if ($user['instagram']): ?>
                            <a href="<?= htmlspecialchars($user['instagram']) ?>" target="_blank" class="instagram-icon" title="Instagram">
                                <i class="bi bi-instagram"></i>
                            </a>
                        <?php endif; ?>
                        
                        <?php if ($user['github']): ?>
                            <a href="<?= htmlspecialchars($user['github']) ?>" target="_blank" class="github-icon" title="GitHub">
                                <i class="bi bi-github"></i>
                            </a>
                        <?php endif; ?>
                        
                        <?php if ($user['portfolio']): ?>
                            <a href="<?= htmlspecialchars($user['portfolio']) ?>" target="_blank" class="portfolio-icon" title="Portfolio">
                                <i class="bi bi-briefcase-fill"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <hr class="my-4">

            <ul class="nav nav-tabs mb-4">
                <li class="nav-item">
                    <a class="nav-link <?= $active_tab === 'posts' ? 'active' : '' ?>" data-bs-toggle="tab" href="#posts">Posts</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $active_tab === 'assets' ? 'active' : '' ?>" data-bs-toggle="tab" href="#assets">Assets</a>
                </li>
            </ul>

            <div class="tab-content">
                <!-- Posts Tab -->
                <div class="tab-pane fade <?= $active_tab === 'posts' ? 'show active' : '' ?>" id="posts">
                    <form method="GET" action="profile.php" class="d-flex gap-2 mb-4 flex-wrap">
                    <?php if(isset($_GET['id'])): ?>
        <input type="hidden" name="id" value="<?= (int)$_GET['id'] ?>">
    <?php endif; ?>
                        <!-- Preserve asset filter values -->
                        <input type="hidden" name="asset_search" value="<?= htmlspecialchars($asset_search) ?>">
                        <input type="hidden" name="asset_category" value="<?= htmlspecialchars($asset_category_filter) ?>">
                        <input type="hidden" name="asset_sort" value="<?= htmlspecialchars($asset_sort_order) ?>">
                        <!-- Set tab to posts explicitly -->
                        <input type="hidden" name="tab" value="posts">
                        
                        <input type="text" name="post_search" class="form-control" placeholder="Search posts"
                            value="<?= htmlspecialchars($post_search) ?>" style="max-width: 300px;">
                        
                        <select name="post_category" class="form-select" style="max-width: 200px;">
                            <option value="">All Categories</option>
                            <?php 
                            // Reset result pointer to reuse the results
                            $post_categories->data_seek(0);
                            while ($cat = $post_categories->fetch_assoc()): 
                            ?>
                                <option value="<?= $cat['id'] ?>" <?= $post_category_filter == $cat['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['name']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                        
                        <select name="post_sort" class="form-select" style="max-width: 200px;">
                            <option value="newest" <?= $post_sort_order === 'newest' ? 'selected' : '' ?>>Newest</option>
                            <option value="oldest" <?= $post_sort_order === 'oldest' ? 'selected' : '' ?>>Oldest</option>
                            <option value="highest_score" <?= $post_sort_order === 'highest_score' ? 'selected' : '' ?>>Highest Score</option>
                        </select>
                        
                        <button type="submit" class="btn btn-primary">Search</button>
                    </form>

                    <div class="posts-container">
                        <?php if ($posts->num_rows > 0): ?>
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
                                        <div class="hashtags">
                                            <?php if (!empty($post['hashtags'])): ?>
                                                <?php $tags = explode(' ', $post['hashtags']); ?>
                                                <?php foreach ($tags as $tag): ?>
                                                    <span class="badge bg-dark"><?= htmlspecialchars($tag) ?></span>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                        <p class="card-text mt-2">
                                            <i class="bi bi-caret-up-fill"></i><?= $post['upvotes'] ?? 0 ?> 
                                            <i class="bi bi-caret-down-fill"></i><?= $post['downvotes'] ?? 0 ?> 
                                            Score: <?= $post['score'] ?? 0 ?>
                                        </p>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="alert alert-info">No posts found.</div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Assets Tab -->
                <div class="tab-pane fade <?= $active_tab === 'assets' ? 'show active' : '' ?>" id="assets">
                    <form method="GET" action="profile.php" class="d-flex gap-2 mb-4 flex-wrap">
                        <!-- Always include the explicit user_id from URL, not from session -->
                        <?php if(isset($_GET['id'])): ?>
        <input type="hidden" name="id" value="<?= (int)$_GET['id'] ?>">
    <?php endif; ?>
                        <!-- Preserve post filter values -->
                        <input type="hidden" name="post_search" value="<?= htmlspecialchars($post_search) ?>">
                        <input type="hidden" name="post_category" value="<?= htmlspecialchars($post_category_filter) ?>">
                        <input type="hidden" name="post_sort" value="<?= htmlspecialchars($post_sort_order) ?>">
                        <!-- Set tab to assets explicitly -->
                        <input type="hidden" name="tab" value="assets">
                        
                        <input type="text" name="asset_search" class="form-control" placeholder="Search assets"
                            value="<?= htmlspecialchars($asset_search) ?>" style="max-width: 300px;">
                        
                        <select name="asset_category" class="form-select" style="max-width: 200px;">
                            <option value="">All Categories</option>
                            <?php 
                            // Reset result pointer to reuse the results
                            $asset_categories->data_seek(0);
                            while ($cat = $asset_categories->fetch_assoc()): 
                            ?>
                                <option value="<?= $cat['id'] ?>" <?= $asset_category_filter == $cat['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['name']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                        
                        <select name="asset_sort" class="form-select" style="max-width: 200px;">
                            <option value="newest" <?= $asset_sort_order === 'newest' ? 'selected' : '' ?>>Newest</option>
                            <option value="oldest" <?= $asset_sort_order === 'oldest' ? 'selected' : '' ?>>Oldest</option>
                            <option value="highest_score" <?= $asset_sort_order === 'highest_score' ? 'selected' : '' ?>>Highest Score</option>
                        </select>
                        
                        <button type="submit" class="btn btn-primary">Search</button>
                    </form>

                    <div class="assets-container">
                        <?php if ($assets->num_rows > 0): ?>
                            <?php while ($asset = $assets->fetch_assoc()): ?>
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <h3 class="card-title">
                                            <a href="view_asset.php?id=<?= $asset['id'] ?>" class="text-decoration-none">
                                                <?= htmlspecialchars($asset['title']) ?>
                                            </a>
                                        </h3>
                                        <p class="card-text text-muted">
                                            <em>Posted on <?= $asset['created_at'] ?></em>
                                        </p>
                                        <div class="hashtags">
                                            <?php if (!empty($asset['hashtags'])): ?>
                                                <?php $tags = explode(' ', $asset['hashtags']); ?>
                                                <?php foreach ($tags as $tag): ?>
                                                    <span class="badge bg-dark"><?= htmlspecialchars($tag) ?></span>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                        <p class="card-text mt-2">
                                            <i class="bi bi-caret-up-fill"></i><?= $asset['upvotes'] ?> 
                                            <i class="bi bi-caret-down-fill"></i><?= $asset['downvotes'] ?>
                                            Score: <?= $asset['score'] ?>
                                        </p>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="alert alert-info">No assets found.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Update URL when tab changes
            document.querySelectorAll('.nav-tabs a').forEach(tab => {
                tab.addEventListener('shown.bs.tab', event => {
                    const tabName = event.target.getAttribute('href').substring(1);
                    // Update URL but preserve other query parameters
                    const urlParams = new URLSearchParams(window.location.search);
                    urlParams.set('tab', tabName);
                    window.history.replaceState(null, '', `?${urlParams.toString()}`);
                });
            });

            // Activate correct tab based on URL parameter
            const urlParams = new URLSearchParams(window.location.search);
            const activeTab = urlParams.get('tab') || 'posts';
            const tabTrigger = document.querySelector(`.nav-tabs a[href="#${activeTab}"]`);
            if (tabTrigger) {
                new bootstrap.Tab(tabTrigger).show();
            }
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>