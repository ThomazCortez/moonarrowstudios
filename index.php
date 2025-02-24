<?php
// Start the session at the top of the page to check for user login state
session_start();

require_once 'php/db_connect.php'; // Adjust the path as needed

include 'php/header.php';

// Function to safely get user count
function getUserCount($conn) {
    try {
        if (!$conn) {
            return "many"; // Fallback text if no connection
        }
        $sql = "SELECT COUNT(*) as count FROM users";
        $result = $conn->query($sql);
        if ($result) {
            $row = $result->fetch_assoc();
            return number_format($row['count']);
        }
        return "many"; // Fallback if query fails
    } catch (Exception $e) {
        error_log("Error getting user count: " . $e->getMessage());
        return "many"; // Fallback if any error occurs
    }
}
// Add these functions at the top with your existing getUserCount function
function getPostCount($conn) {
    try {
        if (!$conn) {
            return "many";
        }
        $sql = "SELECT COUNT(*) as count FROM posts";
        $result = $conn->query($sql);
        if ($result) {
            $row = $result->fetch_assoc();
            return number_format($row['count']);
        }
        return "many";
    } catch (Exception $e) {
        error_log("Error getting post count: " . $e->getMessage());
        return "many";
    }
}

// Add these functions at the top with your existing getUserCount function
function getAssetCount($conn) {
    try {
        if (!$conn) {
            return "many";
        }
        $sql = "SELECT COUNT(*) as count FROM assets";
        $result = $conn->query($sql);
        if ($result) {
            $row = $result->fetch_assoc();
            return number_format($row['count']);
        }
        return "many";
    } catch (Exception $e) {
        error_log("Error getting asset count: " . $e->getMessage());
        return "many";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MoonArrow Studios</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
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
            --container-bg: #1a1a1a;
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
                --container-bg: #1a1a1a;
            }
        }

        body {
            margin: 0; /* Remove default margin */
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", "Noto Sans", Helvetica, Arial, sans-serif;
            background-color: var(--color-canvas-default);
            color: var(--color-fg-default);
            line-height: 1.5;
            font-size: 16px;
            overflow-y: auto;
            scroll-snap-type: y mandatory;
            height: 100vh; /* Add this */
        }

        .section {
            min-height: 100vh;
            display: flex;
            align-items: center;
            background-color: var(--color-canvas-default);
            padding: 4rem 0;
            opacity: 0;
            transform: translateY(30px);
            transition: opacity 0.8s ease-out, transform 0.8s ease-out;
            position: relative;
            overflow: hidden;
            scroll-snap-align: start;
            scroll-snap-stop: always; /* Add this to prevent partial snapping */
        }

        .section.visible {
            opacity: 1;
            transform: translateY(0);
        }

        /* Add this CSS to your existing styles - I'll just show the new/modified parts */

        .animated-arrow {
    position: absolute;
    right: 2rem; /* Keep it at the right */
    top: -5vh; /* Start just above the section */
    opacity: 0;
    pointer-events: none;
    z-index: 10;
    animation: arrowFlow 3s ease-in-out infinite;
}

.animated-arrow-left {
    position: absolute;
    left: 2rem; /* Keep it at the left */
    top: -5vh; /* Start just above the section */
    opacity: 0;
    pointer-events: none;
    z-index: 10;
    animation: arrowFlow 3s ease-in-out infinite;
}

@keyframes arrowFlow {
    0%, 10% {
        transform: translateY(-10vh);
        opacity: 0;
    }
    20% {
        transform: translateY(0);
        opacity: 1;
    }
    80% {
        transform: translateY(90vh); /* Moves down the full section */
        opacity: 1;
    }
    100% {
        transform: translateY(100vh); /* Ends just below */
        opacity: 0;
    }
}


/* Make sure your section has this for proper arrow positioning */
.section {
    position: relative;
    overflow: hidden;
}

        .content {
            display: flex;
            flex-direction: column;
            gap: 2rem;
            padding: 2rem;
        }

        .btn-custom {
            border-radius: 6px;
            padding: 8px 24px;
            font-size: 16px;
            font-weight: 500;
            line-height: 24px;
            color: #ffffff;
            background-color: var(--color-btn-primary-bg);
            border: 1px solid rgba(27, 31, 36, 0.15);
            box-shadow: 0 1px 0 rgba(27, 31, 36, 0.1);
            transition: .2s cubic-bezier(0.3, 0, 0.5, 1);
            width: fit-content;
        }

        .btn-custom:hover {
            background-color: var(--color-btn-primary-hover-bg);
            transform: translateY(-2px);
            color: #ffffff;
            text-decoration: none;
        }

        .minimalist-container {
            max-width: 540px;
            height: 360px;
            margin: 0 auto;
            padding: 1rem;
            position: relative;
        }

        .dark-container {
            width: 100%;
            height: 100%;
            background-color: var(--container-bg);
            border-radius: 12px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.25);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .dark-container:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.35);
        }

        .dark-container img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        h2 {
            font-size: 36px;
            font-weight: 600;
            color: var(--color-fg-default);
        }

        .lead {
            color: var(--color-fg-muted);
            font-size: 20px;
            line-height: 1.6;
        }

        .alternate-bg {
            background-color: var(--color-canvas-subtle);
        }

        .forum-icon {
            height: 256px;
            width: 256px;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .section {
                padding: 2rem 0;
                min-height: auto;
            }

            h2 {
                font-size: 28px;
            }

            .lead {
                font-size: 18px;
            }

            .content {
                padding: 1rem;
                text-align: center;
                align-items: center;
            }

            .minimalist-container {
                padding: 0.5rem;
                height: 280px;
            }

            .row {
                flex-direction: column-reverse;
            }

            .col-md-6 {
                margin-bottom: 2rem;
            }

            .animated-arrow {
                right: 1rem;
            }
        }
        html {
    scroll-behavior: smooth; /* Enables smooth scrolling */
    height: 100%;
    overflow: hidden; /* Prevent double scrollbars */
}
/* Update the CSS to ensure proper positioning */
.user-count-container {
    position: absolute;
    bottom: 6rem;
    left: 0;
    right: 0;
    text-align: center;
    z-index: 10;
}

.line-1 {
    position: relative;
    margin: 0 auto;
    border-right: 2px solid var(--color-fg-default);
    font-size: 1.25rem;
    text-align: center;
    white-space: nowrap;
    overflow: hidden;
    color: var(--color-fg-default);
    font-family: 'Anonymous Pro', monospace;
    display: inline-block;
    width: 0;
    max-width: fit-content;
}

.anim-typewriter {
    animation: 
        typing 6s ease forwards,  /* Increased from 4s to 6s and added ease */
        blink 1s step-end infinite;  /* New separate blink animation */
}

@keyframes typing {
    from { width: 0; }
    to { width: 100%; }
}

@keyframes blink {
    0%, 100% { border-right-color: var(--color-fg-default); }
    50% { border-right-color: transparent; }
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .user-count-container {
        bottom: 4rem;
    }
    
    .line-1 {
        font-size: 1rem;
    }
}
    </style>
</head>
<body class="home">
    <!-- Section 1 -->
    <section class="section" id="section1">
    <div class="animated-arrow">
    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <polyline points="6 9 12 15 18 9"></polyline>
    </svg>
</div>
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6 content">
                    <h2>MoonArrow Studios</h2>
                    <p class="lead">A community-driven platform for game developers to collaborate, share resources, and bring their creative visions to life.</p>
                    <button class="btn btn-custom" onclick="location.href='php/sign_up/sign_up_html.php';">Get Started</button>
                </div>
                <div class="col-md-6">
                    <div class="minimalist-container">
                        <div class="dark-container">
                            <img src="media/moon-image" alt="Section 1 Image">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="user-count-container">
    <div class="line-1 anim-typewriter">
        <?php 
            $userCount = getUserCount($conn);
            echo "Join our " . $userCount . " developers today";
        ?>
    </div>
</div>
    </section>

    <!-- Section 2 -->
    <section class="section alternate-bg" id="section2">
        <div class="animated-arrow-left">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="6 9 12 15 18 9"></polyline>
            </svg>
        </div>
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <div class="minimalist-container">
                        <div class="dark-container">
                            <img src="media/forum-icon.png" alt="Section 2 Image" class="forum-icon">
                        </div>
                    </div>
                </div>
                <div class="col-md-6 content">
                    <h2>Forum</h2>
                    <p class="lead">A dynamic forum where game developers connect, share insights, and troubleshoot challenges together.</p>
                    <button class="btn btn-custom" onclick="location.href='php/forum.php';">View Forum</button>
                </div>
            </div>
        </div>
        <div class="user-count-container">
    <div class="line-1 anim-typewriter">
        <?php 
            $postCount = getPostCount($conn);
            echo "Discover " . $postCount . " community posts";
        ?>
    </div>
</div>
    </section>

    <!-- Section 3 -->
    <section class="section" id="section3">
        <div class="animated-arrow">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="6 9 12 15 18 9"></polyline>
            </svg>
        </div>
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6 content">
                    <h2>Marketplace</h2>
                    <p class="lead">A marketplace offering free, copyright-free assets to streamline game creation, including sprites, sounds, 3D models, and much more.</p>
                    <button class="btn btn-custom" onclick="location.href='php/marketplace.php';">View Marketplace</button>
                </div>
                <div class="col-md-6">
                    <div class="minimalist-container">
                        <div class="dark-container">
                            <img src="media/share-icon" alt="Section 3 Image">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="user-count-container">
    <div class="line-1 anim-typewriter">
        <?php 
            $assetCount = getAssetCount($conn);
            echo $assetCount . " copyright free assets to choose from.";
        ?>
    </div>
</div>
</div>
    </section>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
    <script>
        // Enhanced Intersection Observer for smooth section animations
        const sections = document.querySelectorAll('.section');
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                }
            });
        }, {
            threshold: 0.2,
            rootMargin: '0px 0px -100px 0px'
        });

        sections.forEach(section => {
            observer.observe(section);
        });
        // Add this JavaScript to calculate and set the exact width
        document.addEventListener('DOMContentLoaded', function() {
    const line = document.querySelector('.line-1');
    const text = line.textContent;
    const charWidth = 0.6;
    const textWidth = text.length * charWidth;
    
    const style = document.createElement('style');
    style.textContent = `
        @keyframes typing {
            from { width: 0; }
            to { width: ${textWidth}em; }
        }
        .line-1 {
            max-width: ${textWidth}em;
        }
    `;
    document.head.appendChild(style);
});
document.addEventListener('DOMContentLoaded', function() {
    const sections = document.querySelectorAll('.section');
    const typewriterLines = document.querySelectorAll('.line-1');

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                const line = entry.target.querySelector('.line-1');
                if (line) {
                    const text = line.textContent;
                    const charWidth = 0.6;
                    const textWidth = text.length * charWidth;

                    const style = document.createElement('style');
                    style.textContent = `
                        @keyframes typing {
                            from { width: 0; }
                            to { width: ${textWidth}em; }
                        }
                        .line-1 {
                            max-width: ${textWidth}em;
                        }
                    `;
                    document.head.appendChild(style);

                    line.classList.add('anim-typewriter');
                }
            } else {
                entry.target.classList.remove('visible');
                const line = entry.target.querySelector('.line-1');
                if (line) {
                    line.classList.remove('anim-typewriter');
                }
            }
        });
    }, {
        threshold: 0.2,
        rootMargin: '0px 0px -100px 0px'
    });

    sections.forEach(section => {
        observer.observe(section);
    });
});
    </script>
</body>
</html>