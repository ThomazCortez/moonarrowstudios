<?php
session_start();

// Check if user is logged in, redirect to index.php if not
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
include 'header.php';

// Fetch current user's username
$username = 'User';
if (isset($_SESSION['user_id'])) {
    $current_user_id = $_SESSION['user_id'];
    $stmt = $pdo->prepare("SELECT username FROM users WHERE user_id = ?");
    $stmt->execute([$current_user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $username = $user['username'] ?? $username;
}

// Random greeting
$greetings = ["Hello", "Welcome back", "Hey there", "Good to see you", "Welcome", "Hi", "Greetings", "Good day"];
$randomGreeting = $greetings[array_rand($greetings)];

// Function to format time
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

// Fetch Latest from Following
$following_posts = [];
if (isset($current_user_id)) {
    try {
        $stmt = $pdo->prepare("
            SELECT p.id, p.title, p.created_at, p.upvotes, u.username 
            FROM posts p 
            JOIN users u ON p.user_id = u.user_id 
            WHERE p.user_id IN (SELECT following_id FROM follows WHERE follower_id = ?)
            ORDER BY p.created_at DESC 
            LIMIT 3
        ");
        $stmt->execute([$current_user_id]);
        $following_posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching following posts: " . $e->getMessage());
    }
}

// Fetch Recent Comments
$recent_comments = [];
try {
    $stmt = $pdo->prepare("
        SELECT c.id, c.content, c.created_at, u.username, c.upvotes 
        FROM comments c 
        JOIN users u ON c.user_id = u.user_id 
        ORDER BY c.created_at DESC 
        LIMIT 3
    ");
    $stmt->execute();
    $recent_comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching recent comments: " . $e->getMessage());
}

// Fetch New Followers
$new_followers = [];
if (isset($current_user_id)) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                f.follower_id, 
                u.username, 
                f.created_at,
                (SELECT COUNT(*) FROM follows WHERE following_id = f.follower_id) AS follower_count
            FROM follows f
            JOIN users u ON f.follower_id = u.user_id
            WHERE f.following_id = ?
            ORDER BY f.created_at DESC
            LIMIT 3
        ");
        $stmt->execute([$current_user_id]);
        $new_followers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching new followers: " . $e->getMessage());
    }
}

// Function to truncate comments
function truncateComment($html, $length = 80) {
    $text = strip_tags($html);
    if (strlen($text) > $length) {
        return substr($text, 0, $length) . '...';
    }
    return $text;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Homepage</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.5/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
    :root {
      --color-canvas-default: #ffffff;
      --color-canvas-subtle: #f8f9fa;
      --color-border-default: #dee2e6;
      --color-border-muted: #e9ecef;
      --color-btn-primary-bg: #0d6efd;
      --color-btn-primary-hover-bg: #0b5ed7;
      --color-fg-default: #212529;
      --color-fg-muted: #6c757d;
      --color-accent-fg: #0d6efd;
      --color-input-bg: #ffffff;
      --color-card-bg: #ffffff;
      --color-card-border: #dee2e6;
      --color-shadow-sm: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
      --color-shadow-md: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    }

    @media (prefers-color-scheme: dark) {
      :root {
        --color-canvas-default: #0d1117;
        --color-canvas-subtle: #161b22;
        --color-border-default: #30363d;
        --color-border-muted: #21262d;
        --color-btn-primary-bg: #238636;
        --color-btn-primary-hover-bg: #2ea043;
        --color-fg-default: #e6edf3;
        --color-fg-muted: #8b949e;
        --color-accent-fg: #58a6ff;
        --color-input-bg: #0d1117;
        --color-card-bg: #161b22;
        --color-card-border: #30363d;
        --color-shadow-sm: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.3);
        --color-shadow-md: 0 0.5rem 1rem rgba(0, 0, 0, 0.4);
      }
    }

    body {
      background-color: var(--color-canvas-default);
      color: var(--color-fg-default);
      transition: all 0.3s ease;
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", "Noto Sans", Helvetica, Arial, sans-serif;
    }

    .main-container {
      background-color: var(--color-canvas-default);
      min-height: 100vh;
      padding-top: 3rem;
      padding-bottom: 3rem;
    }

    /* Greeting Section */
    .greeting-section {
    display: flex;
    justify-content: center;
    align-items: center;
    margin-bottom: 4rem;
    animation: fadeInUp 0.6s ease-out;
    /* Ensure items are centered on cross-axis */
    align-items: center;
    }

    .greeting-icon {
    width: 3em;
    height: 3em;
    margin-right: 0.5em;
    object-fit: contain;
    /* Remove vertical-align (doesn't work in flex) and adjust position */
    transform: scaleX(-1);
    align-self: center; /* Explicitly center in flex container */
    margin-top: 0.2em; /* Fine-tune vertical position if needed */
    }

    .greeting-title {
      font-size: 2.5rem;
      font-weight: 300;
      color: var(--color-fg-default);
      margin: 0;
      letter-spacing: -0.01em;
    }

    .greeting-subtitle {
      font-size: 1.1rem;
      color: var(--color-fg-muted);
      font-weight: 400;
      text-align: center;
      margin-top: 0.5rem;
    }

    /* Search Section - Prominent without card */
    .search-section {
      margin-bottom: 4rem;
      animation: fadeInUp 0.6s ease-out 0.2s both;
    }

    .search-toggle-container {
      text-align: center;
      margin-bottom: 2rem;
    }

    .btn-group .btn {
      border-color: var(--color-border-default);
      background-color: var(--color-canvas-subtle);
      color: var(--color-fg-default);
      font-weight: 500;
      padding: 0.75rem 1.5rem;
      border-radius: 0;
    }
    .btn-group .btn:first-child {
      border-top-left-radius: 0.75rem;
      border-bottom-left-radius: 0.75rem;
    }
    .btn-group .btn:last-child {
      border-top-right-radius: 0.75rem;
      border-bottom-right-radius: 0.75rem;
    }
    .btn-group .btn:hover {
      background-color: var(--color-canvas-default);
      border-color: var(--color-accent-fg);
      color: var(--color-accent-fg);
      z-index: 2;
    }
    .btn-group .btn.active {
      background-color: #0969da;
      border-color: var(--color-btn-primary-bg);
      color: white;
      z-index: 3;
    }
    .btn-group .btn.active:hover {
      background-color: var(--color-accent-fg);
      border-color: var(--color-btn-primary-hover-bg);
    }

    .search-container {
      max-width: 600px;
      margin: 0 auto;
      position: relative;
    }

    .search-input-group {
      position: relative;
      box-shadow: var(--color-shadow-md);
      border-radius: 1rem;
      overflow: hidden;
      background: var(--color-input-bg);
      border: 2px solid var(--color-border-default);
      transition: all 0.3s ease;
    }
    .search-input-group:focus-within {
      border-color: var(--color-accent-fg);
      box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
      transform: translateY(-2px);
    }
    .search-input-group .form-control {
      border: none;
      background: transparent;
      padding: 1rem 1.25rem;
      font-size: 1.1rem;
      color: var(--color-fg-default);
      border-radius: 0;
    }
    .search-input-group .form-control:focus {
      box-shadow: none;
      background: transparent;
    }
    .search-input-group .btn {
      border: none;
      background-color: #0969da;
      color: white;
      padding: 1rem 1.5rem;
      font-weight: 600;
      border-radius: 0;
    }
    .search-input-group .btn:hover {
      background-color: var(--color-accent-fg);
    }

    /* Content Cards */
    .content-grid {
      animation: fadeInUp 0.6s ease-out 0.4s both;
    }
    .card {
      border: 1px solid var(--color-card-border);
      background-color: var(--color-card-bg);
      border-radius: 1rem;
      box-shadow: var(--color-shadow-sm);
      transition: all 0.3s ease;
      height: 100%;
    }
    .card:hover {
      transform: translateY(-4px);
      box-shadow: var(--color-shadow-md);
      border-color: var(--color-accent-fg);
    }
    .card-header {
      background: linear-gradient(135deg, var(--color-canvas-subtle), var(--color-card-bg));
      border-bottom: 1px solid var(--color-border-muted);
      border-radius: 1rem 1rem 0 0 !important;
      padding: 1.25rem;
    }
    .card-icon {
      width: 48px;
      height: 48px;
      background: var(--color-border-default);
      border-radius: 0.75rem;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-size: 1.3rem;
    }
    .card-title {
      font-size: 1.2rem;
      font-weight: 600;
      color: var(--color-fg-default);
      margin: 0;
    }
    .card-body {
      padding: 0;
    }
    .content-item {
      padding: 1.25rem;
      border-bottom: 1px solid var(--color-border-muted);
      cursor: pointer;
      transition: all 0.2s ease;
    }
    .content-item:hover {
      background-color: var(--color-canvas-subtle);
    }
    .content-item:last-child {
      border-bottom: none;
    }
    .item-title {
      font-weight: 600;
      color: var(--color-fg-default);
      margin-bottom: 0.5rem;
      font-size: 1rem;
      line-height: 1.5;
    }
    .item-meta {
      font-size: 0.875rem;
      color: var(--color-fg-muted);
    }
    .meta-item {
      margin-right: 1rem;
    }
    .meta-item i {
      margin-right: 0.25rem;
    }
    .empty-state {
      text-align: center;
      color: var(--color-fg-muted);
      font-style: italic;
      padding: 3rem 2rem;
    }

    /* Animations */
    @keyframes fadeInUp {
      from { opacity: 0; transform: translateY(20px); }
      to   { opacity: 1; transform: translateY(0); }
    }
    @keyframes blink {
      0%,100% { opacity: 1; }
      50%     { opacity: 0; }
    }
    .cursor {
    display: inline-block;
    width: 2px;
    height: 1.05em; /* Adjust height slightly to better match character height */
    background-color: var(--color-accent-fg);
    animation: blink 1s step-end infinite;
    position: relative;
    top: 0.2em; /* Adjust downward to align with text baseline */
    }


    /* Responsive adjustments */
    @media (max-width: 768px) {
      .greeting-title { font-size: 2rem; }
      .search-section { margin-bottom: 3rem; }
      .btn-group .btn { padding: 0.5rem 1rem; font-size: 0.9rem; }
      .search-input-group .form-control { padding: 0.875rem 1rem; font-size: 1rem; }
      .search-input-group .btn { padding: 0.875rem 1.25rem; }
    }

    /* Custom scrollbar */
    ::-webkit-scrollbar { width: 8px; }
    ::-webkit-scrollbar-track { background: var(--color-canvas-default); }
    ::-webkit-scrollbar-thumb { background: var(--color-border-default); border-radius: 4px; }
    ::-webkit-scrollbar-thumb:hover { background: var(--color-fg-muted); }
  </style>
</head>
<body>
    <div class="main-container">
        <div class="container">
            <!-- Greeting Section -->
            <div class="greeting-section">
            <img src="../media/moon-image.png" alt="👋" class="greeting-icon"/>
            <h1 class="greeting-title" id="greeting-text">
                <span class="cursor"></span>
            </h1>
            </div>
            <p class="greeting-subtitle">Ready to explore today?</p>

            <!-- Search Section - No Card Wrapper -->
            <div class="search-section">
                <div class="search-toggle-container">
                    <div class="btn-group" role="group" aria-label="Search toggle">
                        <button type="button" class="btn active" data-type="forum">
                            <i class="bi bi-chat-dots me-2"></i>Forum
                        </button>
                        <button type="button" class="btn" data-type="marketplace">
                            <i class="bi bi-shop me-2"></i>Marketplace
                        </button>
                    </div>
                </div>
                
                <div class="search-container">
                    <div class="search-input-group d-flex">
                        <input type="text" class="form-control" placeholder="Search in forum..." id="search-input">
                        <button class="btn" type="button" onclick="performSearch()">
                            <i class="bi bi-search me-2"></i>Search
                        </button>
                    </div>
                </div>
            </div>

            <!-- Content Grid -->
            <div class="row g-4 content-grid">
                <!-- Latest from Following -->
                <div class="col-lg-4 col-md-6">
                    <div class="card">
                        <div class="card-header d-flex align-items-center">
                            <div class="card-icon me-3">
                                <i class="bi bi-people"></i>
                            </div>
                            <h5 class="card-title">Latest from Following</h5>
                        </div>
                        <div class="card-body" id="following-content">
                            <?php if (!empty($following_posts)): ?>
                                <?php foreach ($following_posts as $post): ?>
                                    <div class="content-item">
                                        <div class="item-title"><?= htmlspecialchars($post['title']) ?></div>
                                        <div class="item-meta">
                                            <span class="meta-item">
                                                <i class="bi bi-person"></i><?= htmlspecialchars($post['username']) ?>
                                            </span>
                                            <span class="meta-item">
                                                <i class="bi bi-clock"></i><?= formatTimeAgo($post['created_at']) ?>
                                            </span>
                                            <span class="meta-item">
                                                <i class="bi bi-heart"></i><?= $post['upvotes'] ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="empty-state">No recent posts from people you follow</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Recent Comments -->
                <div class="col-lg-4 col-md-6">
                    <div class="card">
                        <div class="card-header d-flex align-items-center">
                            <div class="card-icon me-3">
                                <i class="bi bi-chat-square-text"></i>
                            </div>
                            <h5 class="card-title">Recent Comments</h5>
                        </div>
                        <div class="card-body" id="comments-content">
                            <?php if (!empty($recent_comments)): ?>
                                <?php foreach ($recent_comments as $comment): ?>
                                    <div class="content-item">
                                        <div class="item-title">"<?= htmlspecialchars(truncateComment($comment['content'])) ?>"</div>
                                        <div class="item-meta">
                                            <span class="meta-item">
                                                <i class="bi bi-person"></i><?= htmlspecialchars($comment['username']) ?>
                                            </span>
                                            <span class="meta-item">
                                                <i class="bi bi-clock"></i><?= formatTimeAgo($comment['created_at']) ?>
                                            </span>
                                            <span class="meta-item">
                                                <i class="bi bi-arrow-up"></i><?= $comment['upvotes'] ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="empty-state">No recent comments on your content</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- New Followers -->
                <div class="col-lg-4 col-md-6">
                    <div class="card">
                        <div class="card-header d-flex align-items-center">
                            <div class="card-icon me-3">
                                <i class="bi bi-person-plus"></i>
                            </div>
                            <h5 class="card-title">New Followers</h5>
                        </div>
                        <div class="card-body" id="followers-content">
                            <?php if (!empty($new_followers)): ?>
                                <?php foreach ($new_followers as $follower): ?>
                                    <div class="content-item">
                                        <div class="item-title"><?= htmlspecialchars($follower['username']) ?> started following you</div>
                                        <div class="item-meta">
                                            <span class="meta-item">
                                                <i class="bi bi-person"></i>@<?= htmlspecialchars($follower['username']) ?>
                                            </span>
                                            <span class="meta-item">
                                                <i class="bi bi-clock"></i><?= formatTimeAgo($follower['created_at']) ?>
                                            </span>
                                            <span class="meta-item">
                                                <i class="bi bi-people"></i><?= number_format($follower['follower_count']) ?> followers
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="empty-state">No new followers recently</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        // Keep original PHP variables and functionality
        const greetings = <?= json_encode($greetings) ?>;
        const currentUser = { username: <?= json_encode($username) ?> };

        function initializePage() {
            setRandomGreeting();
            setupSearchToggle();
        }

        function setRandomGreeting() {
        const randomGreeting = greetings[Math.floor(Math.random() * greetings.length)];
        const greetingElement = document.getElementById('greeting-text');
        const text = `${randomGreeting}, ${currentUser.username}`;
        
        greetingElement.innerHTML = '<span class="cursor"></span>';
        let i = 0;
        const typeWriter = () => {
            if (i < text.length) {
            greetingElement.innerHTML = text.substring(0, i + 1) + '<span class="cursor"></span>';
            i++;
            setTimeout(typeWriter, 50);
            }
        };
        typeWriter();
        }



        function setupSearchToggle() {
            const toggleBtns = document.querySelectorAll('.btn-group .btn');
            const searchInput = document.getElementById('search-input');

            toggleBtns.forEach(btn => {
                btn.addEventListener('click', () => {
                    toggleBtns.forEach(b => b.classList.remove('active'));
                    btn.classList.add('active');
                    searchInput.placeholder = btn.dataset.type === 'forum' 
                        ? 'Search in forum...' 
                        : 'Search in marketplace...';
                });
            });

            searchInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') performSearch();
            });
        }

        function performSearch() {
        const searchTerm = document.getElementById('search-input').value.trim();
        const activeType = document.querySelector('.btn-group .btn.active').dataset.type;
        
        // Construct URL parameters
        const params = new URLSearchParams({
            search: searchTerm,
            category: '',       // Empty by default (can be populated from other UI elements)
            filter: 'highest_score'    // Default filter from example
        });

        // Redirect to the appropriate page
        window.location.href = `${activeType}.php?${params.toString()}`;
    }

        document.addEventListener('DOMContentLoaded', initializePage);
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>