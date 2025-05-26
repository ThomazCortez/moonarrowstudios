<?php
session_start();
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
            --color-alert-error-bg: #FFEBE9;
            --color-alert-error-border: rgba(255, 129, 130, 0.4);
            --color-alert-error-fg: #cf222e;
            --animation-duration: 0.8s;
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
                --color-alert-error-bg: #ff000015;
                --color-alert-error-border: rgba(248, 81, 73, 0.4);
                --color-alert-error-fg: #f85149;
            }
        }

        body {
            background-color: var(--color-canvas-default);
            color: var(--color-fg-default);
            transition: all 0.3s ease;
            min-height: 100vh;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", "Noto Sans", Helvetica, Arial, sans-serif;
        }

        .main-container {
            background-color: var(--color-canvas-default);
            min-height: 100vh;
            padding-top: 2rem;
        }

        .greeting-section {
            text-align: center;
            margin-bottom: 3rem;
            animation: fadeInUp var(--animation-duration) ease-out;
        }

        .greeting-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--color-fg-default);
            margin-bottom: 0.5rem;
        }

        .greeting-subtitle {
            font-size: 1.2rem;
            color: var(--color-fg-muted);
            font-weight: 400;
        }

        .search-card {
            background-color: var(--color-card-bg);
            border: 1px solid var(--color-card-border);
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            animation: fadeInUp var(--animation-duration) ease-out 0.2s both;
        }

        .search-toggle {
            margin-bottom: 1.5rem;
        }

        .toggle-btn {
            background-color: var(--color-canvas-subtle);
            border: 1px solid var(--color-border-default);
            color: var(--color-fg-default);
            transition: all 0.3s ease;
        }

        .toggle-btn:hover {
            background-color: var(--color-canvas-default);
            border-color: var(--color-accent-fg);
            color: var(--color-accent-fg);
        }

        .toggle-btn.active {
            background-color: var(--color-btn-primary-bg);
            border-color: var(--color-btn-primary-bg);
            color: white;
        }

        .toggle-btn.active:hover {
            background-color: var(--color-btn-primary-hover-bg);
            border-color: var(--color-btn-primary-hover-bg);
            color: white;
        }

        .search-input {
            background-color: var(--color-input-bg);
            border: 1px solid var(--color-border-default);
            color: var(--color-fg-default);
        }

        .search-input:focus {
            background-color: var(--color-input-bg);
            border-color: var(--color-accent-fg);
            color: var(--color-fg-default);
            box-shadow: 0 0 0 0.2rem rgba(88, 166, 255, 0.25);
        }

        .search-btn {
            background-color: var(--color-btn-primary-bg);
            border-color: var(--color-btn-primary-bg);
            transition: all 0.3s ease;
        }

        .search-btn:hover {
            background-color: var(--color-btn-primary-hover-bg);
            border-color: var(--color-btn-primary-hover-bg);
            transform: translateY(-1px);
        }

        .content-card {
            background-color: var(--color-card-bg);
            border: 1px solid var(--color-card-border);
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            height: 100%;
            animation: fadeInUp var(--animation-duration) ease-out 0.4s both;
        }

        .content-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .card-header-custom {
            background-color: var(--color-header-bg);
            border-bottom: 1px solid var(--color-card-border);
            border-radius: 12px 12px 0 0;
        }

        .card-icon {
            width: 40px;
            height: 40px;
            background-color: var(--color-btn-primary-bg);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
        }

        .card-title-custom {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--color-fg-default);
            margin: 0;
        }

        .content-item {
            padding: 1rem;
            border-bottom: 1px solid var(--color-border-muted);
            transition: all 0.3s ease;
            cursor: pointer;
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
            font-size: 0.95rem;
            line-height: 1.4;
        }

        .item-meta {
            font-size: 0.85rem;
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
            padding: 2rem;
        }

        .btn-outline-custom {
            border-color: var(--color-border-default);
            color: var(--color-fg-default);
        }

        .btn-outline-custom:hover {
            background-color: var(--color-canvas-subtle);
            border-color: var(--color-accent-fg);
            color: var(--color-accent-fg);
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 768px) {
            .greeting-title {
                font-size: 2rem;
            }
            
            .search-toggle .btn {
                font-size: 0.875rem;
                padding: 0.5rem 1rem;
            }
        }

        /* Custom scrollbar for dark theme */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: var(--color-canvas-default);
        }

        ::-webkit-scrollbar-thumb {
            background: var(--color-border-default);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--color-fg-muted);
        }
        /* Add/Update these styles in your CSS */
.card {
    position: relative;
    overflow: hidden;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1) !important;
    transform: translateZ(0); /* Hardware acceleration */
    will-change: transform; /* Prepare browser for animation */
    border: 1px solid transparent; /* Add this line */
}

.card::before,
.card::after {
    content: '';
    position: absolute;
    left: 0;
    width: 100%;
    height: 2px;
    background: rgba(88, 166, 255, 0.3); /* Use your theme's blue color */
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
                0 4px 20px rgba(0, 0, 0, 0.3) !important;
    border-color: rgba(88, 166, 255, 0.3) !important;
}

.card:hover::before,
.card:hover::after {
    transform: translateX(0);
    opacity: 1;
}

.card-body {
    position: relative;
    z-index: 1; /* Ensure content stays above borders */
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
/* Add these to your existing CSS */
.anim-typewriter {
    animation: 
        typing 6s ease forwards,
        blink 1s step-end infinite;
}

@keyframes typing {
    from { width: 0; }
    to { width: 100%; }
}

@keyframes blink {
    0%, 100% { border-right-color: var(--color-fg-default); }
    50% { border-right-color: transparent; }
}
.line-1 {
    border-right: 2px solid var(--color-fg-default);
    white-space: nowrap;
    overflow: hidden;
    display: inline-block;
}
    </style>
</head>
<body>
    <div class="main-container">
        <div class="container">
            <!-- Greeting Section -->
            <div class="greeting-section">
            <h1 class="greeting-title line-1" id="greeting-text"><?= htmlspecialchars($randomGreeting) ?>, <?= htmlspecialchars($username) ?></h1>
                <p class="greeting-subtitle">Ready to explore today?</p>
            </div>

            <!-- Search Section -->
            <div class="card search-card">
                <div class="card-body p-4">
                    <div class="search-toggle d-flex justify-content-center">
                        <div class="btn-group" role="group">
                            <button type="button" class="btn toggle-btn active" data-type="forum">
                                <i class="bi bi-chat-dots me-2"></i>Forum
                            </button>
                            <button type="button" class="btn toggle-btn" data-type="marketplace">
                                <i class="bi bi-shop me-2"></i>Marketplace
                            </button>
                        </div>
                    </div>
                    <div class="row g-2">
                        <div class="col">
                            <input type="text" class="form-control search-input" placeholder="Search in forum..." id="search-input">
                        </div>
                        <div class="col-auto">
                            <button class="btn btn-primary search-btn" onclick="performSearch()">
                                <i class="bi bi-search me-2"></i>Search
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Content Grid -->
            <div class="row g-4">
                <!-- Latest from Following -->
                <div class="col-lg-4 col-md-6">
                    <div class="card content-card">
    <div class="card-header card-header-custom d-flex align-items-center p-3">
        <div class="card-icon me-3">
            <i class="bi bi-people"></i>
        </div>
        <h5 class="card-title-custom">Latest from Following</h5>
    </div>
    <div class="card-body p-0" id="following-content">
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
<div class="card content-card">
    <div class="card-header card-header-custom d-flex align-items-center p-3">
        <div class="card-icon me-3">
            <i class="bi bi-chat-square-text"></i>
        </div>
        <h5 class="card-title-custom">Recent Comments</h5>
    </div>
    <div class="card-body p-0" id="comments-content">
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
                    <div class="card content-card">
    <div class="card-header card-header-custom d-flex align-items-center p-3">
        <div class="card-icon me-3">
            <i class="bi bi-person-plus"></i>
        </div>
        <h5 class="card-title-custom">New Followers</h5>
    </div>
    <div class="card-body p-0" id="followers-content">
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

<script>
        // Simplified JavaScript
        const greetings = <?= json_encode($greetings) ?>;
        const currentUser = { username: <?= json_encode($username) ?> };

        function initializePage() {
            setRandomGreeting();
            setupSearchToggle();
        }

        function setRandomGreeting() {
            const randomGreeting = greetings[Math.floor(Math.random() * greetings.length)];
            document.getElementById('greeting-text').textContent = `${randomGreeting}, ${currentUser.username}`;
        }

        function setupSearchToggle() {
            const toggleBtns = document.querySelectorAll('.toggle-btn');
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
            if (!searchTerm) return;
            
            // Actual search implementation would go here
            console.log(`Searching for "${searchTerm}" in ${document.querySelector('.toggle-btn.active').dataset.type}`);
        }

        document.addEventListener('DOMContentLoaded', initializePage);

        // Handle greeting animation
const greetingLine = document.getElementById('greeting-text');
if (greetingLine) {
    // Remove existing animation class first to reset
    greetingLine.classList.remove('anim-typewriter');
    
    // Force reflow to ensure animation restarts
    void greetingLine.offsetWidth;
    
    // Add animation class back
    greetingLine.classList.add('anim-typewriter');
    
    // Calculate text width based on screen size
    const text = greetingLine.textContent.trim();
    let charWidth;
    if (window.innerWidth < 576) {
        charWidth = 0.5; // Mobile
    } else if (window.innerWidth < 992) {
        charWidth = 0.55; // Tablet
    } else {
        charWidth = 0.6; // Desktop
    }
    const textWidth = text.length * charWidth;
    greetingLine.style.maxWidth = textWidth + 'em';
}
    </script>
</body>
</html>