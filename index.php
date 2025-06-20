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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>MoonArrow Studios</title>
    <link href="https://fonts.googleapis.com/css2?family=Anonymous+Pro&display=swap" rel="stylesheet">
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
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", "Noto Sans", Helvetica, Arial, sans-serif;
            background-color: var(--color-canvas-default);
            color: var(--color-fg-default);
            line-height: 1.5;
            font-size: 16px;
            overflow-x: hidden;
        }

        .container {
            padding-left: 1rem;
            padding-right: 1rem;
        }

        /* Modify scroll snap for better mobile experience */
        @media (min-width: 992px) {
            body {
                height: 100vh;
                overflow-y: auto;
                scroll-snap-type: y mandatory;
            }

            .section {
                scroll-snap-align: start;
                scroll-snap-stop: always;
            }
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
        }

        .section.visible {
            opacity: 1;
            transform: translateY(0);
        }

        .animated-arrow {
            position: absolute;
            right: 2rem;
            top: -5vh;
            opacity: 0;
            pointer-events: none;
            z-index: 10;
            animation: arrowFlow 3s ease-in-out infinite;
            display: none; /* Hide on mobile by default */
        }

        .animated-arrow-left {
            position: absolute;
            left: 2rem;
            top: -5vh;
            opacity: 0;
            pointer-events: none;
            z-index: 10;
            animation: arrowFlow 3s ease-in-out infinite;
            display: none; /* Hide on mobile by default */
        }

        /* Only show arrows on larger screens */
        @media (min-width: 992px) {
            .animated-arrow, .animated-arrow-left {
                display: block;
            }
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
                transform: translateY(90vh);
                opacity: 1;
            }
            100% {
                transform: translateY(100vh);
                opacity: 0;
            }
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
            background-color: #0d6efd;
            border: 1px solid rgba(27, 31, 36, 0.15);
            box-shadow: 0 1px 0 rgba(27, 31, 36, 0.1);
            transition: .2s cubic-bezier(0.3, 0, 0.5, 1);
            width: fit-content;
        }

        .btn-custom:hover {
            background-color: #0b5ed7;
            transform: translateY(-2px);
            color: #ffffff;
            text-decoration: none;
        }

        .minimalist-container {
            max-width: 100%;
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
            max-width: 100%;
        }

        html {
            scroll-behavior: smooth;
        }

        .user-count-container {
            position: absolute;
            bottom: 4rem;
            left: 0;
            right: 0;
            text-align: center;
            z-index: 10;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 3rem;
            padding: 0 1rem; /* Added padding */
            box-sizing: border-box; /* Ensure padding is included in width */
        }

        .bottom-counter {
            bottom: 3rem; /* Consistent with other sections */
        }

        .line-1 {
            position: relative;
            margin: 0 auto;
            font-size: 1.25rem;
            text-align: center;
            color: var(--color-fg-default);
            font-family: 'Anonymous Pro', monospace;
            display: block; /* Changed to block */
            width: 100%; /* Full width */
            max-width: 100%; /* Prevent overflow */
            box-sizing: border-box;
            overflow: hidden;
            line-height: 1.5; /* Better line height */
        }
        
        /* NEW ANIMATIONS */
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
          height: 1.05em;
          background-color: var(--color-accent-fg);
          animation: blink 1s step-end infinite;
          position: relative;
          top: 0.2em;
          margin-left: 2px; /* Added spacing */
        }

        /* Review Cards Styling */
        .review-card {
            background-color: var(--color-canvas-default);
            border: 1px solid var(--color-border-default);
            border-radius: 8px;
            padding: 1.5rem;
            height: 100%;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .review-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
        }

        .review-header {
            display: flex;
            margin-bottom: 1rem;
            align-items: center;
        }

        .review-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            overflow: hidden;
            margin-right: 1rem;
            background-color: var(--color-canvas-subtle);
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .review-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .review-info {
            flex-grow: 1;
        }

        .review-info h5 {
            margin: 0;
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--color-fg-default);
        }

        .review-job {
            margin: 0.2rem 0 0.5rem;
            font-size: 0.9rem;
            color: var(--color-fg-muted);
        }

        .review-stars {
            display: flex;
            gap: 2px;
        }

        .review-body {
            font-size: 0.95rem;
            line-height: 1.6;
            color: var(--color-fg-default);
        }

        .join-card {
            background-color: var(--color-canvas-default);
            padding: 3rem 2rem;
            margin-top: 2rem;
            position: relative;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .join-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
        }

        .join-image-container {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100%;
        }

        .join-image {
            width: 280px;
            height: 280px;
            background-color: var(--color-canvas-subtle);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .join-image:hover {
            transform: scale(1.05);
        }

        .community-icon {
            max-width: 70%;
            max-height: 70%;
        }

        .join-content {
            padding: 1rem;
        }

        .join-content h2 {
            margin-bottom: 1.5rem;
            font-size: 2.2rem;
            background: linear-gradient(90deg, var(--color-fg-default), var(--color-accent-fg));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            display: inline-block;
        }

        .features-list {
            margin-bottom: 1.5rem;
        }

        .feature-item {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }

        .feature-icon {
            color: var(--color-btn-primary-bg);
            margin-right: 0.75rem;
            flex-shrink: 0;
        }

        .feature-text {
            font-size: 1.1rem;
            font-weight: 500;
        }

        .join-actions {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .btn-outline-custom {
            background-color: transparent;
            border: 1px solid var(--color-border-default);
            color: var(--color-fg-default);
            padding: 0.7rem 1rem;
            border-radius: 6px;
            font-size: 0.95rem;
            transition: all 0.2s ease;
        }

        .btn-outline-custom:hover {
            background-color: var(--color-canvas-subtle);
            border-color: var(--color-border-muted);
            color: var(--color-accent-fg);
        }

        .pulse-btn {
            position: relative;
            overflow: hidden;
        }

        .pulse-btn:after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 5px;
            height: 5px;
            background: rgba(255, 255, 255, 0.5);
            opacity: 0;
            border-radius: 100%;
            transform: scale(1, 1) translate(-50%);
            transform-origin: 50% 50%;
            animation: ripple 2s infinite;
        }

        @keyframes ripple {
            0% {
                transform: scale(0, 0);
                opacity: 0.5;
            }
            20% {
                transform: scale(25, 25);
                opacity: 0.3;
            }
            100% {
                opacity: 0;
                transform: scale(40, 40);
            }
        }

        /* Touch-Friendly Interactive Elements */
        @media (hover: none) and (pointer: coarse) {
            .btn-custom:hover {
                transform: none;
                background-color: #0d6efd;
            }
            
            .dark-container:hover {
                transform: none;
                box-shadow: 0 8px 30px rgba(0, 0, 0, 0.25);
            }
            
            .review-card:hover {
                transform: none;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            }
            
            .join-image:hover {
                transform: none;
            }
        }

        /* Tablet Responsive */
        @media (max-width: 991px) {
            .section {
                min-height: auto;
                padding: 3rem 0;
                height: auto;
            }
            
            .row.flex-row-reverse {
                flex-direction: column-reverse !important;
            }
            
            h2 {
                font-size: 28px;
                text-align: center;
            }

            .lead {
                font-size: 18px;
                text-align: center;
            }

            .content {
                padding: 1rem;
                text-align: center;
                align-items: center;
            }

            .minimalist-container {
                padding: 0.5rem;
                height: 220px;
                margin-bottom: 1.5rem;
            }

            .btn-custom {
                margin: 0 auto;
            }

            
            
            .line-1 {
                font-size: 1rem;
            }
            
            .review-card {
                margin-bottom: 1.5rem;
            }
            
            .join-card {
                padding: 2rem 1.5rem;
            }
            
            .join-image {
                width: 220px;
                height: 220px;
                margin: 0 auto;
            }
            
            .join-content {
                text-align: center;
            }

            .join-content h2 {
                font-size: 1.8rem;
            }
            
            .feature-item {
                justify-content: center;
            }

            .user-count-container {
                display: none;
            }
            
        }

        /* Mobile Landscape and Small Tablets */
        @media (max-width: 767px) {
            .section {
                padding: 2rem 0;
            }
            
            .content {
                gap: 1.5rem;
            }
            
            h2 {
                font-size: 32px;
                line-height: 1.2;
            }
            
            .lead {
                font-size: 18px;
                line-height: 1.5;
            }
            
            .minimalist-container {
                height: 250px;
                margin-bottom: 2rem;
            }
            
            .dark-container {
                padding: 1rem;
            }
            
            .review-card {
                margin-bottom: 2rem;
                padding: 1.25rem;
            }
            
            .review-header {
                margin-bottom: 1.5rem;
            }
            
            .join-card {
                margin-top: 1rem;
                padding: 2rem 1rem;
            }
            
            .join-content h2 {
                font-size: 1.75rem;
                margin-bottom: 1.25rem;
            }
            
            .feature-text {
                font-size: 1rem;
            }
        }

        /* Mobile Portrait */
        @media (max-width: 575px) {
            .container {
                padding-left: 0.5rem;
                padding-right: 0.5rem;
            }
            
            .section {
                padding: 1.5rem 0;
            }
            
            .minimalist-container {
                height: 200px;
                margin-bottom: 1.5rem;
            }
            
            h2 {
                font-size: 26px;
            }
            
            .lead {
                font-size: 16px;
            }
            
            .btn-custom {
                width: 100%;
                max-width: 280px;
                padding: 0.8rem 1rem;
                font-size: 1rem;
                margin: 0 auto;
            }
            
            
            .line-1 {
                font-size: 1rem;
                padding: 0;
                line-height: 1.4;
                white-space: normal; /* Allow wrapping */
                text-align: center;
            }
            
            .review-header {
                flex-direction: column;
                text-align: center;
            }
            
            .review-avatar {
                margin-right: 0;
                margin-bottom: 1rem;
            }
            
            .review-stars {
                justify-content: center;
            }
            
            .review-body {
                text-align: center;
            }
            
            .join-card {
                padding: 1.5rem 1rem;
            }
            
            .join-image {
                width: 180px;
                height: 180px;
            }
            
            .join-content h2 {
                font-size: 1.5rem;
            }
            
            .feature-text {
                font-size: 1rem;
            }
            
            .btn-custom {
                padding: 0.7rem 1.2rem;
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
                <div class="col-lg-6 content">
                    <h2>MoonArrow Studios</h2>
                    <p class="lead">A community-driven platform for game developers to collaborate, share resources, and bring their creative visions to life.</p>
                    <button class="btn btn-custom" onclick="location.href='php/about.php';">More Info</button>
                </div>
                <div class="col-lg-6">
                    <div class="minimalist-container">
                        <div class="dark-container">
                            <img src="media/moon-image" alt="Section 1 Image">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="user-count-container">
            <div class="line-1" id="count-line-1">
                <!-- Content will be inserted by JavaScript -->
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
            <div class="row align-items-center flex-column flex-lg-row">
                <div class="col-lg-6">
                    <div class="minimalist-container">
                        <div class="dark-container">
                            <img src="media/forum-icon.png" alt="Section 2 Image" class="forum-icon">
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 content">
                    <h2>Forum</h2>
                    <p class="lead">A dynamic forum where game developers connect, share insights, and troubleshoot challenges together.</p>
                    <button class="btn btn-custom" onclick="location.href='php/forum.php';">View Forum</button>
                </div>
            </div>
        </div>
        <div class="user-count-container">
            <div class="line-1" id="count-line-2">
                <!-- Content will be inserted by JavaScript -->
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
            <div class="row align-items-center flex-column-reverse flex-lg-row">
                <div class="col-lg-6 content">
                    <h2>Marketplace</h2>
                    <p class="lead">A marketplace offering free, copyright-free assets to streamline game creation, including sprites, sounds, 3D models, and much more.</p>
                    <button class="btn btn-custom" onclick="location.href='php/marketplace.php';">View Marketplace</button>
                </div>
                <div class="col-lg-6">
                    <div class="minimalist-container">
                        <div class="dark-container">
                            <img src="media/share-icon" alt="Section 3 Image">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="user-count-container">
            <div class="line-1" id="count-line-3">
                <!-- Content will be inserted by JavaScript -->
            </div>
        </div>
    </section>

    <!-- Section 4: Reviews -->
    <section class="section alternate-bg" id="section4">
        <div class="animated-arrow-left">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="6 9 12 15 18 9"></polyline>
            </svg>
        </div>
        <div class="container">
            <div class="row justify-content-center mb-5">
                <div class="col-lg-8 text-center">
                    <h2>What Our Community Says</h2>
                    <p class="lead">Hear from fellow game developers about their experiences with MoonArrow Studios.</p>
                </div>
            </div>
            <div class="row">
                <!-- Review Card 1 -->
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="review-card">
                        <div class="review-header">
                            <div class="review-avatar">
                                <img src="media/profile-1.jpg" alt="User Avatar">
                            </div>
                            <div class="review-info">
                                <h5>Ricardo Sousa</h5>
                                <p class="review-job">Indie Game Developer</p>
                                <div class="review-stars">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="#FFD700" stroke="#FFD700" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="#FFD700" stroke="#FFD700" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="#FFD700" stroke="#FFD700" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="#FFD700" stroke="#FFD700" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="#FFD700" stroke="#FFD700" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                                </div>
                            </div>
                        </div>
                        <div class="review-body">
                            <p>"MoonArrow Studios completely transformed my game development journey. The assets marketplace saved me countless hours, and the community has been incredibly supportive and knowledgeable."</p>
                        </div>
                    </div>
                </div>
                
                <!-- Review Card 2 -->
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="review-card">
                        <div class="review-header">
                            <div class="review-avatar">
                                <img src="media/profile-2.jpg" alt="User Avatar">
                            </div>
                            <div class="review-info">
                                <h5>Alex Rodriguez</h5>
                                <p class="review-job">3D Artist & Game Designer</p>
                                <div class="review-stars">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="#FFD700" stroke="#FFD700" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="#FFD700" stroke="#FFD700" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="#FFD700" stroke="#FFD700" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="#FFD700" stroke="#FFD700" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="#FFD700" stroke="#FFD700" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                                </div>
                            </div>
                        </div>
                        <div class="review-body">
                            <p>"I've been able to contribute my 3D models and in return received valuable feedback and resources. The forum discussions helped me learn advanced techniques I wouldn't have discovered otherwise."</p>
                        </div>
                    </div>
                </div>
                
                <!-- Review Card 3 -->
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="review-card">
                        <div class="review-header">
                            <div class="review-avatar">
                                <img src="media/profile-3.png" alt="User Avatar">
                            </div>
                            <div class="review-info">
                                <h5>Paul Evans</h5>
                                <p class="review-job">Audio Engineer & Composer</p>
                                <div class="review-stars">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="#FFD700" stroke="#FFD700" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="#FFD700" stroke="#FFD700" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="#FFD700" stroke="#FFD700" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="#FFD700" stroke="#FFD700" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="#FFD700" stroke="#FFD700" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                                </div>
                            </div>
                        </div>
                        <div class="review-body">
                            <p>"As an audio specialist, finding the right community was crucial. MoonArrow Studios has connected me with developers who value quality sound design, and I've found amazing collaboration opportunities here."</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="section" id="section5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="join-card">
                        <div class="row align-items-center">
                        <div class="col-lg-6">
                        <div class="minimalist-container">
                            <div class="dark-container">
                                <img src="media/community.png" alt="Section 3 Image" class="forum-icon">
                            </div>
                        </div>
                    </div>
                            <div class="col-lg-6 ">
                                <h2>Join The Community</h2>
                                <div class="features-list">
                                    <div class="feature-item">
                                        <div class="feature-icon">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                            </svg>
                                        </div>
                                        <div class="feature-text">Create and share your own posts</div>
                                    </div>
                                    <div class="feature-item">
                                        <div class="feature-icon">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                            </svg>
                                        </div>
                                        <div class="feature-text">Upload your own game assets</div>
                                    </div>
                                    <div class="feature-item">
                                        <div class="feature-icon">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                            </svg>
                                        </div>
                                        <div class="feature-text">Comment and vote on content</div>
                                    </div>
                                    <div class="feature-item">
                                        <div class="feature-icon">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                            </svg>
                                        </div>
                                        <div class="feature-text">Connect with fellow developers</div>
                                    </div>
                                </div>
                                <div class="join-actions">
                                    <button class="btn btn-custom" onclick="location.href='php/sign_up/sign_up_html.php';">Sign Up Now</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="user-count-container bottom-counter">
            <div class="line-1" id="count-line-4">
                <!-- Content will be inserted by JavaScript -->
            </div>
        </div>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // PHP content to be displayed with typewriter effect
            const countTexts = {
                line1: "<?= 'Join our ' . getUserCount($conn) . ' developers today.' ?>",
                line2: "<?= 'Discover ' . getPostCount($conn) . ' community posts.' ?>",
                line3: "<?= getAssetCount($conn) . ' copyright free assets to choose from.' ?>",
                line4: "Ready to level up your game development?"
            };

            // Function to create typewriter effect
            function createTypewriter(elementId, text) {
                const element = document.getElementById(elementId);
                
                // Clear existing content
                element.innerHTML = '';
                
                // Create container for the text
                const textContainer = document.createElement('span');
                element.appendChild(textContainer);
                
                // Create cursor element
                const cursor = document.createElement('span');
                cursor.className = 'cursor';
                element.appendChild(cursor);
                
                let i = 0;
                
                const typeWriter = () => {
                    if (i < text.length) {
                        textContainer.textContent = text.substring(0, i + 1);
                        i++;
                        setTimeout(typeWriter, 50);
                    }
                };
                typeWriter();
            }

            // Initialize typewriters when sections become visible
            const sections = document.querySelectorAll('.section');
            const sectionObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const sectionId = entry.target.id;
                        
                        // Animate the appropriate counter
                        switch(sectionId) {
                            case 'section1':
                                createTypewriter('count-line-1', countTexts.line1);
                                break;
                            case 'section2':
                                createTypewriter('count-line-2', countTexts.line2);
                                break;
                            case 'section3':
                                createTypewriter('count-line-3', countTexts.line3);
                                break;
                            case 'section5':
                                createTypewriter('count-line-4', countTexts.line4);
                                break;
                        }
                        
                        // Standard section animation
                        entry.target.classList.add('visible');
                        
                        // Stop observing after first trigger
                        sectionObserver.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.2 });

            sections.forEach(section => {
                sectionObserver.observe(section);
            });
            
            // Mark first section as initially visible
            const firstSection = document.querySelector('#section1');
            if (firstSection) {
                firstSection.classList.add('visible');
            }
        });
    </script>
</body>
</html>