<?php
$baseUrl = '/moonarrowstudios/'; // Set your base URL here
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="icon" href="/moonarrowstudios/media/moon.ico" type="image/x-icon" />
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

        .header {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", "Noto Sans", Helvetica, Arial, sans-serif;
            background-color: var(--color-canvas-default);
            color: var(--color-fg-default);
            line-height: 1.5;
            font-size: 14px;
        }

        /* Buttons */
        .header .btn {
            border-radius: 6px;
            padding: 5px 16px;
            font-size: 14px;
            font-weight: 500;
            line-height: 20px;
            transition: .2s cubic-bezier(0.3, 0, 0.5, 1);
        }

        .header .btn-primary {
            color: #ffffff;
            background-color: var(--color-btn-primary-bg);
            border: 1px solid rgba(27, 31, 36, 0.15);
            box-shadow: 0 1px 0 rgba(27, 31, 36, 0.1);
        }

        .header .btn-primary:hover {
            background-color: var(--color-btn-primary-hover-bg);
        }

        .header .navbar {
            background-color: var(--color-header-bg);
            border-bottom: 1px solid var(--color-border-muted);
            z-index: 1030; /* Ensures it stays above content */
        }
        
        /* Responsive styles */
        .navbar-brand img {
            height: 40px;
        }
        
        /* Left-aligned navigation on all screen sizes */
        .navbar-nav {
            margin-right: auto !important;
            margin-left: 0 !important;
        }
        
        @media (max-width: 768px) {
            .navbar-brand img {
                height: 35px;
            }
            
            .auth-buttons .btn {
                padding: 4px 10px;
                font-size: 12px;
            }
        }
        
        @media (max-width: 576px) {
            .navbar-brand img {
                height: 30px;
            }
            
            .nav-link {
                padding: 0.3rem 0.5rem !important;
                font-size: 12px;
            }
            
            .nav-link i {
                margin-right: 5px !important;
            }
            
            .auth-buttons .btn {
                padding: 3px 8px;
                font-size: 11px;
            }
        }

        /* Active nav item */
        .nav-link.active {
            color: var(--color-accent-fg) !important;
            font-weight: 500;
        }
        
        /* Special styles for mobile layout */
        @media (max-width: 991px) {
            .mobile-nav-container {
                display: flex;
                justify-content: space-between;
                align-items: center;
                width: 100%;
            }
            
            .navbar-collapse {
                flex-basis: 100%;
            }
            
            .profile-container {
                position: absolute;
                right: 90px;
                top: 12px;
            }
        }

        /* Enhanced Profile Container */
        .profile-container {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            height: auto;
            margin: 0 5px;
        }

        /* Profile Button Styling */
        .profile-btn {
            background: transparent;
            border: none;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.2s ease;
            width: auto;
            height: auto;
        }
        
        .profile-btn:hover {
            transform: translateY(-2px);
        }
        
        .profile-btn:focus {
            box-shadow: none;
            outline: none;
        }
        
        /* Profile Image Styling */
        .profile-img-container {
            width: 46px;
            height: 46px;
            border-radius: 50%;
            overflow: hidden;
            border: 2px solid var(--color-border-muted);
            transition: border-color 0.2s ease;
        }
        
        .profile-btn:hover .profile-img-container {
            border-color: var(--color-accent-fg);
        }
        
        .profile-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        
        .profile-icon-container {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--color-canvas-subtle);
            border: 2px solid var(--color-border-muted);
            transition: border-color 0.2s ease;
        }
        
        .profile-btn:hover .profile-icon-container {
            border-color: var(--color-accent-fg);
        }

        .profile-icon {
            font-size: 1.3rem;
            color: var(--color-fg-muted);
        }

        /* Enhanced Dropdown Menu */
        .dropdown-menu {
            border-radius: 8px;
            border: 1px solid var(--color-border-default);
            background-color: var(--color-modal-bg);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
            padding: 8px 0;
            min-width: 180px;
            margin-top: 10px;
            animation: dropdown-appear 0.2s ease-out;
        }

        @keyframes dropdown-appear {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .dropdown-item {
            padding: 8px 16px;
            font-size: 14px;
            color: var(--color-fg-default);
            display: flex;
            align-items: center;
            transition: background-color 0.15s ease;
        }

        .dropdown-item:hover {
            background-color: var(--color-canvas-subtle);
        }

        .dropdown-item i {
            margin-right: 12px;
            font-size: 16px;
            width: 18px;
            text-align: center;
        }

        .dropdown-divider {
            margin: 8px 0;
            border-top: 1px solid var(--color-border-muted);
        }

        /* Dropdown arrow indicator */
        .dropdown-menu::before {
            content: '';
            position: absolute;
            top: -6px;
            right: 15px;
            width: 12px;
            height: 12px;
            background-color: var(--color-modal-bg);
            transform: rotate(45deg);
            border-left: 1px solid var(--color-border-default);
            border-top: 1px solid var(--color-border-default);
        }

        /* Mobile profile position fix */
        @media (max-width: 991px) {
            .profile-container {
                position: absolute;
                right: 70px;
                top: 50%;
                transform: translateY(-50%);
            }
            
            .profile-btn {
                width: 36px;
                height: 36px;
            }
        }
    </style>
</head>

<body class="header">
    <nav class="navbar navbar-expand-lg p-2 sticky-top" data-bs-theme="">
        <div class="container-fluid">
            <!-- Logo -->
            <a class="navbar-brand" href="<?php echo $baseUrl; ?>">
                <img src="https://i.ibb.co/Q2djKKj/horizontal-logo-transformed.png" alt="Moon Arrow Studios">
            </a>

            <!-- Profile picture for mobile/tablet (when logged in) -->
            <?php if (isset($_SESSION['user_id'])): ?>
            <div class="profile-container d-lg-none">
                <?php
                // Database connection
                require_once 'db_connect.php';
                
                // Fetch the user's profile picture and role
                $user_id = $_SESSION['user_id'];
                $query = "SELECT profile_picture, role FROM users WHERE user_id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $stmt->bind_result($profile_picture, $user_role);
                $stmt->fetch();
                $stmt->close();

                $hasProfilePicture = !empty($profile_picture);
                $isAdmin = ($user_role === 'admin');
                ?>
                
                <div class="dropdown">
                    <button class="btn profile-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <?php if ($hasProfilePicture): ?>
                            <div class="profile-img-container">
                                <img src="<?= htmlspecialchars($profile_picture); ?>" alt="Profile" class="profile-img">
                            </div>
                        <?php else: ?>
                            <div class="profile-icon-container">
                                <i class="bi bi-person-fill profile-icon"></i>
                            </div>
                        <?php endif; ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="<?php echo $baseUrl; ?>php/profile.php"><i class="bi bi-person"></i>Profile</a></li>
                        <li><a class="dropdown-item" href="<?php echo $baseUrl; ?>php/settings.php"><i class="bi bi-gear"></i>Settings</a></li>
                        <?php if ($isAdmin): ?>
                        <li><a class="dropdown-item" href="<?php echo $baseUrl; ?>php/admin/dashboard.php"><i class="bi bi-speedometer2"></i>Dashboard</a></li>
                        <?php endif; ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="<?php echo $baseUrl; ?>php/sign_out.php"><i class="bi bi-box-arrow-right"></i>Sign Out</a></li>
                    </ul>
                </div>
            </div>
            <?php endif; ?>

            <!-- Mobile Toggle Button -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent" aria-controls="navbarContent" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- Collapsible Content -->
            <div class="collapse navbar-collapse" id="navbarContent">
                <?php
                // Get the current file name
                $current_page = basename($_SERVER['PHP_SELF']);
                ?>
                <!-- Left-aligned Navigation -->
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a href="<?php echo $baseUrl; ?>php/forum.php" class="nav-link px-2 <?= $current_page == 'forum.php' ? 'active' : '' ?>">
                            <i class="bi bi-chat-left-text-fill me-2"></i>Forum
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?php echo $baseUrl; ?>php/marketplace.php" class="nav-link px-2 <?= $current_page == 'marketplace.php' ? 'active' : '' ?>">
                            <i class="bi bi-bag-fill me-2"></i>Marketplace
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?php echo $baseUrl; ?>php/about.php" class="nav-link px-2 <?= $current_page == 'about.php' ? 'active' : '' ?>">
                            <i class="bi bi-question-circle-fill me-2"></i>About
                        </a>
                    </li>
                </ul>

                <!-- Right Side: Desktop profile or login buttons -->
                <div class="auth-buttons d-flex align-items-center">
                    <?php 
                    if (isset($_SESSION['user_id'])): 
                        // Only show on desktop since we have a separate profile for mobile
                    ?>
                        <!-- Profile Dropdown (Desktop only) -->
                        <div class="dropdown d-none d-lg-block">
                            <button class="btn profile-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <?php if ($hasProfilePicture): ?>
                                    <div class="profile-img-container">
                                        <img src="<?= htmlspecialchars($profile_picture); ?>" alt="Profile" class="profile-img">
                                    </div>
                                <?php else: ?>
                                    <div class="profile-icon-container">
                                        <i class="bi bi-person-fill profile-icon"></i>
                                    </div>
                                <?php endif; ?>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="<?php echo $baseUrl; ?>php/profile.php"><i class="bi bi-person"></i>Profile</a></li>
                                <li><a class="dropdown-item" href="<?php echo $baseUrl; ?>php/settings.php"><i class="bi bi-gear"></i>Settings</a></li>
                                <?php if ($isAdmin): ?>
                                <li><a class="dropdown-item" href="<?php echo $baseUrl; ?>php/admin/dashboard.php"><i class="bi bi-speedometer2"></i>Dashboard</a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="<?php echo $baseUrl; ?>php/sign_out.php"><i class="bi bi-box-arrow-right"></i>Sign Out</a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <!-- Login/Signup Buttons -->
                        <button type="button" class="btn btn-primary me-2" onclick="window.location.href='<?php echo $baseUrl; ?>php/sign_in/sign_in_html.php'">Sign In</button>
                        <button type="button" class="btn btn-secondary" onclick="window.location.href='<?php echo $baseUrl; ?>php/sign_up/sign_up_html.php'">Sign Up</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const goBackBtn = document.getElementById("goBackBtn");
            if (goBackBtn && window.history.length > 1) {
                goBackBtn.addEventListener("click", function() {
                    window.history.back();
                });
            } else if (goBackBtn) {
                goBackBtn.setAttribute("disabled", "true");
            }
        });
    </script>
</body>
</html>