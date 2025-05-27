<?php
$baseUrl = '/moonarrowstudios/'; // Set your base URL here
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
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

        .header {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", "Noto Sans", Helvetica, Arial, sans-serif;
            background-color: var(--color-canvas-default);
            color: var(--color-fg-default);
            line-height: 1.5;
            font-size: 14px;
        }

        /* Enhanced Responsive Navbar */
        .header .navbar {
            background-color: var(--color-header-bg);
            border-bottom: 1px solid var(--color-border-muted);
            z-index: 1030;
            padding: 0.5rem 1rem;
            min-height: 60px;
        }

        /* Container fluid adjustments */
        .navbar .container-fluid {
            position: relative;
            align-items: center;
        }

        /* Logo Responsive Sizing */
        .navbar-brand {
            padding: 0;
            margin-right: 1rem;
            flex-shrink: 0;
        }

        .navbar-brand img {
            height: 40px;
            width: auto;
            max-width: 200px;
            transition: height 0.3s ease;
        }

        /* Enhanced Button Styles */
        .header .btn {
            border-radius: 6px;
            padding: 6px 16px;
            font-size: 14px;
            font-weight: 500;
            line-height: 20px;
            transition: all 0.2s cubic-bezier(0.3, 0, 0.5, 1);
            white-space: nowrap;
        }

        .header .btn-primary {
            color: #ffffff;
            background-color: #0d6efd;
            border: 1px solid rgba(27, 31, 36, 0.15);
            box-shadow: 0 1px 0 rgba(27, 31, 36, 0.1);
        }

        .header .btn-primary:hover {
            background-color: #0b5ed7;
            transform: translateY(-1px);
        }

        .header .btn-secondary {
            color: var(--color-fg-default);
            background-color: var(--color-canvas-subtle);
            border: 1px solid var(--color-border-default);
        }

        .header .btn-secondary:hover {
            background-color: var(--color-border-muted);
            transform: translateY(-1px);
        }

        /* Navigation Links */
        .navbar-nav {
            margin-right: auto !important;
            margin-left: 0 !important;
        }

        .nav-link {
            color: var(--color-fg-default) !important;
            padding: 0.5rem 1rem !important;
            border-radius: 6px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            font-weight: 500;
        }

        .nav-link i {
            margin-right: 8px;
            font-size: 1rem;
        }

        .nav-link:hover {
            color: var(--color-accent-fg) !important;
            background-color: var(--color-canvas-subtle);
        }

        /* Active nav item with enhanced animation */
        .nav-link.active {
            color: var(--color-accent-fg) !important;
            font-weight: 600;
            position: relative;
            background-color: var(--color-canvas-subtle);
        }

        .nav-link.active::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 1rem;
            right: 1rem;
            height: 2px;
            background-color: var(--color-accent-fg);
            width: 0;
            animation: draw-underline var(--animation-duration) ease forwards;
        }

        @keyframes draw-underline {
            0% { width: 0; }
            100% { width: calc(100% - 2rem); }
        }

        /* Enhanced Profile Container */
        .profile-container {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 8px;
        }

        .profile-btn {
            background: transparent;
            border: none;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            border-radius: 50%;
        }
        
        .profile-btn:hover {
            transform: translateY(-2px);
        }
        
        /* REMOVED: Profile button focus styles that created the white box */
        .profile-btn:focus {
            box-shadow: none !important;
            outline: none !important;
            border: none !important;
        }
        
        /* Profile Image Styling */
        .profile-img-container {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            overflow: hidden;
            border: 2px solid var(--color-border-muted);
            transition: all 0.2s ease;
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
            width: 42px;
            height: 42px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--color-canvas-subtle);
            border: 2px solid var(--color-border-muted);
            transition: all 0.2s ease;
        }
        
        .profile-btn:hover .profile-icon-container {
            border-color: var(--color-accent-fg);
        }

        .profile-icon {
            font-size: 1.2rem;
            color: var(--color-fg-muted);
        }

        /* Enhanced Dropdown Menu */
        .dropdown-menu {
            border-radius: 8px;
            border: 1px solid var(--color-border-default);
            background-color: var(--color-modal-bg);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
            padding: 8px 0;
            min-width: 200px;
            margin-top: 8px;
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
            padding: 10px 16px;
            font-size: 14px;
            color: var(--color-fg-default);
            display: flex;
            align-items: center;
            transition: background-color 0.15s ease;
            text-decoration: none;
        }

        .dropdown-item:hover {
            background-color: var(--color-canvas-subtle);
            color: var(--color-accent-fg);
        }

        .dropdown-item.text-danger:hover {
            background-color: rgba(220, 53, 69, 0.1);
            color: #dc3545;
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

        /* Auth Buttons Container */
        .auth-buttons {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-shrink: 0;
        }

        /* Mobile Toggle Button */
        .navbar-toggler {
            border: 1px solid var(--color-border-default);
            padding: 4px 8px;
            margin-left: 8px;
            order: 3;
        }

        .navbar-toggler:focus {
            box-shadow: 0 0 0 2px rgba(13, 110, 253, 0.25);
        }

        /* RESPONSIVE BREAKPOINTS */

        /* Large screens (desktops, 1200px and up) */
        @media (min-width: 1200px) {
            .navbar-brand img {
                height: 45px;
            }
            
            .nav-link {
                padding: 0.6rem 1.2rem !important;
            }
            
            .profile-img-container,
            .profile-icon-container {
                width: 46px;
                height: 46px;
            }
        }

        /* Medium screens (tablets, 768px to 1199px) */
        @media (min-width: 768px) and (max-width: 1199px) {
            .navbar-brand img {
                height: 38px;
            }
            
            .nav-link {
                padding: 0.5rem 0.8rem !important;
                font-size: 14px;
            }
            
            .header .btn {
                padding: 5px 12px;
                font-size: 13px;
            }
        }

        /* Small screens (landscape phones, 576px to 767px) */
        @media (min-width: 576px) and (max-width: 767px) {
            .navbar {
                padding: 0.4rem 0.8rem;
                min-height: 56px;
            }
            
            .navbar-brand img {
                height: 34px;
            }
            
            .nav-link {
                padding: 0.4rem 0.6rem !important;
                font-size: 13px;
            }
            
            .nav-link i {
                margin-right: 6px;
                font-size: 0.9rem;
            }
            
            .header .btn {
                padding: 4px 10px;
                font-size: 12px;
            }
            
            .profile-img-container,
            .profile-icon-container {
                width: 36px;
                height: 36px;
            }
            
            .profile-icon {
                font-size: 1rem;
            }
        }

        /* Extra small screens (portrait phones, less than 576px) */
        @media (max-width: 575px) {
            .navbar {
                padding: 0.3rem 0.6rem;
                min-height: 52px;
            }
            
            .navbar-brand {
                margin-right: 0.5rem;
            }
            
            .navbar-brand img {
                height: 30px;
                max-width: 150px;
            }
            
            .nav-link {
                padding: 0.4rem 0.5rem !important;
                font-size: 12px;
            }
            
            .nav-link i {
                margin-right: 5px;
                font-size: 0.85rem;
            }
            
            .header .btn {
                padding: 3px 8px;
                font-size: 11px;
            }
            
            .profile-img-container,
            .profile-icon-container {
                width: 32px;
                height: 32px;
            }
            
            .profile-icon {
                font-size: 0.9rem;
            }
            
            .dropdown-menu {
                min-width: 180px;
            }
            
            .dropdown-item {
                padding: 8px 12px;
                font-size: 13px;
            }
        }

        /* Mobile Layout Adjustments */
        @media (max-width: 991px) {
            /* Mobile container layout */
            .mobile-nav-container {
                display: flex;
                justify-content: space-between;
                align-items: center;
                width: 100%;
            }
            
            .navbar-collapse {
                flex-basis: 100%;
                margin-top: 1rem;
            }
            
            /* Position profile for mobile */
            .profile-container {
                position: absolute;
                right: 60px;
                top: 50%;
                transform: translateY(-50%);
                z-index: 1040;
            }
            
            /* Mobile navigation styling */
            .navbar-nav {
                margin: 0;
                padding: 0.5rem 0;
            }
            
            .nav-item {
                width: 100%;
            }
            
            .nav-link {
                padding: 0.7rem 1rem !important;
                border-radius: 8px;
                margin: 2px 0;
            }
            
            /* Mobile auth buttons */
            .auth-buttons {
                justify-content: center;
                margin-top: 1rem;
                padding-top: 1rem;
                border-top: 1px solid var(--color-border-muted);
            }
            
            /* UPDATED: Mobile active nav styling - simple vertical line */
            .nav-link.active::after {
                display: none;
            }
            
            .nav-link.active {
                background-color: transparent !important;
                border-left: 3px solid var(--color-accent-fg);
                border-radius: 0;
                padding-left: calc(1rem - 3px) !important;
            }
        }

        /* Ultra-wide screens optimization */
        @media (min-width: 1400px) {
            .container-fluid {
                max-width: 1320px;
                margin: 0 auto;
            }
        }

        /* High DPI display optimization */
        @media (-webkit-min-device-pixel-ratio: 2), (min-resolution: 192dpi) {
            .navbar-brand img {
                image-rendering: -webkit-optimize-contrast;
                image-rendering: crisp-edges;
            }
        }

        /* Accessibility improvements */
        @media (prefers-reduced-motion: reduce) {
            * {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }

        /* Focus improvements for keyboard navigation */
        .nav-link:focus {
            outline: none;
            box-shadow: none;
        }
        
        .btn:focus {
            outline: none;
            box-shadow: none;
        }

        /* Smooth transitions for all interactive elements */
        .nav-link,
        .btn,
        .dropdown-item,
        .profile-btn {
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }
    </style>
</head>

<body class="header">
    <nav class="navbar navbar-expand-lg sticky-top" data-bs-theme="">
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
                    <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item">
                        <a href="<?php echo $baseUrl; ?>php/home.php" class="nav-link <?= $current_page == 'home.php' ? 'active' : '' ?>">
                            <i class="bi bi-house-fill"></i>Home
                        </a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a href="<?php echo $baseUrl; ?>php/forum.php" class="nav-link <?= $current_page == 'forum.php' ? 'active' : '' ?>">
                            <i class="bi bi-chat-left-text-fill"></i>Forum
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?php echo $baseUrl; ?>php/marketplace.php" class="nav-link <?= $current_page == 'marketplace.php' ? 'active' : '' ?>">
                            <i class="bi bi-bag-fill"></i>Marketplace
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?php echo $baseUrl; ?>php/about.php" class="nav-link <?= $current_page == 'about.php' ? 'active' : '' ?>">
                            <i class="bi bi-question-circle-fill"></i>About
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
            // Enhanced navigation functionality
            const navLinks = document.querySelectorAll('.nav-link');
            
            navLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    // Remove active class from all links
                    navLinks.forEach(l => l.classList.remove('active'));
                    // Add active class to clicked link
                    this.classList.add('active');
                    
                    // Close mobile menu after clicking (if open)
                    const navbarCollapse = document.getElementById('navbarContent');
                    if (navbarCollapse.classList.contains('show')) {
                        const bsCollapse = new bootstrap.Collapse(navbarCollapse);
                        bsCollapse.hide();
                    }
                });
            });
            
            // REMOVED the problematic dropdown event listener
            // Dropdown items now work with their natural href navigation
            
            // Enhanced responsive behavior
            function handleResize() {
                const navbar = document.querySelector('.navbar');
                const windowWidth = window.innerWidth;
                
                // Add responsive classes based on screen size
                navbar.classList.remove('navbar-xs', 'navbar-sm', 'navbar-md', 'navbar-lg', 'navbar-xl');
                
                if (windowWidth < 576) {
                    navbar.classList.add('navbar-xs');
                } else if (windowWidth < 768) {
                    navbar.classList.add('navbar-sm');
                } else if (windowWidth < 992) {
                    navbar.classList.add('navbar-md');
                } else if (windowWidth < 1200) {
                    navbar.classList.add('navbar-lg');
                } else {
                    navbar.classList.add('navbar-xl');
                }
            }
            
            // Initial call and resize listener
            handleResize();
            window.addEventListener('resize', handleResize);
            
            // Back button functionality (if needed)
            const goBackBtn = document.getElementById("goBackBtn");
            if (goBackBtn && window.history.length > 1) {
                goBackBtn.addEventListener("click", function() {
                    window.history.back();
                });
            } else if (goBackBtn) {
                goBackBtn.setAttribute("disabled", "true");
            }
            
            // Animation replay functionality
            function replayActiveAnimation() {
                const activeLinks = document.querySelectorAll('.nav-link.active');
                activeLinks.forEach(link => {
                    link.classList.remove('active');
                    void link.offsetWidth; // Force reflow
                    link.classList.add('active');
                });
            }
            
            // Optional: Add keyboard navigation
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    // Close any open dropdowns
                    const openDropdowns = document.querySelectorAll('.dropdown-menu.show');
                    openDropdowns.forEach(dropdown => {
                        const bsDropdown = bootstrap.Dropdown.getInstance(dropdown.previousElementSibling);
                        if (bsDropdown) bsDropdown.hide();
                    });
                    
                    // Close mobile menu if open
                    const navbarCollapse = document.getElementById('navbarContent');
                    if (navbarCollapse.classList.contains('show')) {
                        const bsCollapse = new bootstrap.Collapse(navbarCollapse);
                        bsCollapse.hide();
                    }
                }
            });
        });
    </script>
</body>
</html>