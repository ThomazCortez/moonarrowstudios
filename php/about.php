<?php
// Start the session at the top of the page
session_start();

include 'header.php';

// Database connection (update with your database credentials)
require 'db_connect.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MoonArrow Studios - About</title>
    <!-- Animate.css for animations -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <!-- AOS (Animate On Scroll) Library -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css" rel="stylesheet">
    
    <style>
        .section {
            padding: 60px 0;
        }
        .bg-light-gray {
            background-color: var(--color-canvas-subtle);
        }
        .navbar {
            transition: all 0.3s ease;
        }
        .navbar.scrolled {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .hero-text {
            opacity: 0;
            transform: translateY(20px);
            animation: fadeInUp 1s ease forwards;
            animation-delay: 0.3s;
        }
        @keyframes fadeInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .card {
            transition: all 0.3s ease;
        }
        .card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .profile-pic {
            width: 275px;
            height: 275px;
            border-radius: 50%;
            object-fit: cover;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin: 0 auto;
            display: block;
        }
        
        .school-image {
            height: 75%;
            width: 75%;
        }
        
        /* Responsive Design */
        @media (max-width: 1200px) {
            .display-4 {
                font-size: 2.5rem;
            }
            .profile-pic {
                width: 250px;
                height: 250px;
            }
        }
        
        @media (max-width: 992px) {
            .section {
                padding: 40px 0;
            }
            .display-4 {
                font-size: 2.2rem;
            }
            .profile-pic {
                width: 200px;
                height: 200px;
                margin-bottom: 30px;
            }
            .school-image {
                height: 60%;
                width: 60%;
                margin-bottom: 30px;
            }
            .order-lg-1,
            .order-lg-2 {
                order: 0 !important;
            }
            .text-center-mobile {
                text-align: center;
            }
        }
        
        @media (max-width: 768px) {
            .section {
                padding: 30px 0;
            }
            .display-4 {
                font-size: 1.8rem;
            }
            .display-5 {
                font-size: 1.5rem;
            }
            .lead {
                font-size: 1.1rem;
            }
            .profile-pic {
                width: 180px;
                height: 180px;
            }
            .school-image {
                height: 50%;
                width: 50%;
            }
            .card {
                margin-bottom: 20px;
            }
            .counter h2 {
                font-size: 1.8rem;
            }
            .py-5 {
                padding: 2rem 0 !important;
            }
        }
        
        @media (max-width: 576px) {
            .container {
                padding-left: 15px;
                padding-right: 15px;
            }
            .section {
                padding: 20px 0;
            }
            .display-4 {
                font-size: 1.5rem;
            }
            .display-5 {
                font-size: 1.3rem;
            }
            .lead {
                font-size: 1rem;
            }
            .profile-pic {
                width: 150px;
                height: 150px;
            }
            .school-image {
                height: 80%;
                width: 80%;
            }
            .counter h2 {
                font-size: 1.5rem;
            }
            .mb-5 {
                margin-bottom: 2rem !important;
            }
            .mb-4 {
                margin-bottom: 1.5rem !important;
            }
            h2 {
                font-size: 1.8rem;
            }
            h4 {
                font-size: 1.3rem;
            }
            p {
                font-size: 0.95rem;
                line-height: 1.6;
            }
        }
        
        @media (max-width: 480px) {
            .display-4 {
                font-size: 1.3rem;
            }
            .profile-pic {
                width: 120px;
                height: 120px;
            }
            .counter h2 {
                font-size: 1.3rem;
            }
            .card-body {
                padding: 1rem;
            }
            h2 {
                font-size: 1.5rem;
            }
            .py-5 {
                padding: 1.5rem 0 !important;
            }
        }
        
        @media (max-width: 360px) {
            .container {
                padding-left: 10px;
                padding-right: 10px;
            }
            .display-4 {
                font-size: 1.2rem;
            }
            .profile-pic {
                width: 100px;
                height: 100px;
            }
            .counter h2 {
                font-size: 1.2rem;
            }
            h2 {
                font-size: 1.4rem;
            }
            .section {
                padding: 15px 0;
            }
        }
        
        /* Ensure images are responsive */
        img {
            max-width: 100%;
            height: auto;
        }
        
        /* Fix for Bootstrap columns on mobile */
        @media (max-width: 767px) {
            .col-lg-6,
            .col-md-4,
            .col-md-3 {
                margin-bottom: 30px;
            }
            .row.align-items-center > div {
                text-align: center;
            }
        }
        
        /* Improve text readability on mobile */
        @media (max-width: 768px) {
            .text-center-mobile {
                text-align: center !important;
            }
            .mx-auto-mobile {
                margin-left: auto !important;
                margin-right: auto !important;
            }
        }
    </style>
</head>
<body>
    <!-- Hero Section -->
    <div class="bg-dark text-white">
        <div class="container py-5">
            <div class="row justify-content-center text-center">
                <div class="col-lg-8">
                    <h1 class="display-4 animate__animated animate__fadeInUp">About</h1>
                    <div class="hero-text">
                        <p class="lead">Welcome to MoonArrow Studios</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- About Me Section -->
    <section class="section" id="about-me">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 text-center-mobile" data-aos="fade-right" data-aos-delay="100">
                    <h2 class="mb-4">About Me</h2>
                    <p class="lead">Hello! I'm Thomaz, a student at Escola Secundária Manuel Teixeira Gomes.</p>
                    <p>I was born in Portugal, and I can both speak English and Portuguese easily. In my free time, I like to play videogames, watch videos, listen to music and even play piano. I plan on getting a computer engineer degree in the future.</p>
                </div>
                <div class="col-lg-6 text-center" data-aos="fade-left" data-aos-delay="200">
                    <img src="../media/ultrakill_pfp.png" alt="Your Photo" class="profile-pic animate__animated animate__infinite animate__slower">
                </div>
            </div>
        </div>
    </section>

    <!-- My School Section -->
    <section class="section bg-light-gray" id="my-school">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 order-lg-2 text-center-mobile" data-aos="fade-left" data-aos-delay="100">
                    <h2 class="mb-4">My School</h2>
                    <p class="lead">Escola Secundária Manuel Teixeira Gomes - Where My Journey Began</p>
                    <p>I had a really good experience in that school, even though it's quite old, I still enjoyed my time there. I learnt a lot about programming, databases, etc, and improved my autonomy in the way I work. The teachers were always nice and liked doing their job, and I made lots of friends during my time in the school.</p>
                </div>
                <div class="col-lg-6 order-lg-1 text-center" data-aos="fade-right" data-aos-delay="200">
                    <img src="../media/aemtg.jpg" alt="School Photo" class="img-fluid rounded shadow school-image animate__animated animate__infinite animate__slower">
                </div>
            </div>
        </div>
    </section>

    <!-- Project Purpose Section -->
    <section class="section" id="project-purpose">
        <div class="container">
            <div class="row">
                <div class="col-lg-12 text-center mb-5" data-aos="zoom-in">
                    <h2>Why I Made This Website</h2>
                    <p class="lead">My Final School Project</p>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-8 mx-auto text-center" data-aos="fade-up" data-aos-delay="100">
                    <p>This website was created as part of my final school project. My goal with this project was to improve my skills on making websites, including html, css, javascript and php. I chose this theme for my project because I thought it would be interesting and challenging to make, because it requires a lot of programming. The name "MoonArrow Studios" has no meaning, and I couldn't really think of a name that wasn't already used by other companies.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Why Use This Site Section -->
    <section class="section bg-light-gray" id="why-use">
        <div class="container">
            <div class="row">
                <div class="col-lg-12 text-center mb-5" data-aos="zoom-in">
                    <h2>Why Use This Site?</h2>
                    <p class="lead">What Makes My Website Stand Out</p>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4 mb-4" data-aos="flip-left" data-aos-delay="100">
                    <div class="card h-100">
                        <div class="card-body">
                            <h4 class="card-title">Two in One</h4>
                            <p class="card-text">A forum where you can talk to other game developers and a marketplace with free assets where you can use in your projects, all in one single website.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4" data-aos="flip-left" data-aos-delay="200">
                    <div class="card h-100">
                        <div class="card-body">
                            <h4 class="card-title">User Friendly</h4>
                            <p class="card-text">It's really easy to navigate around the website, and easy to understand.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4" data-aos="flip-left" data-aos-delay="300">
                    <div class="card h-100">
                        <div class="card-body">
                            <h4 class="card-title">Customization </h4>
                            <p class="card-text">You can change how your profile looks, tamper with settings, modify or delete your posts and assets whenever you want.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Animated Stats Section -->
    <section class="section text-center bg-dark text-white">
        <div class="container">
            <div class="row">
                <div class="col-lg-12 mb-4" data-aos="fade-up">
                    <h2>Project Stats</h2>
                </div>
            </div>
            <div class="row">
                <!-- Project Creation Date -->
                <div class="col-md-3 col-sm-6 mb-4" data-aos="zoom-in" data-aos-delay="100">
                    <div class="counter">
                        <h2 class="display-5 mt-2">October 2024</h2>
                        <p>Project Created</p>
                    </div>
                </div>
                <!-- Pages Created -->
                <div class="col-md-3 col-sm-6 mb-4" data-aos="zoom-in" data-aos-delay="200">
                    <div class="counter">
                        <h2 class="display-4 count" data-count="25">0</h2>
                        <p>Pages Created</p>
                    </div>
                </div>
                <!-- Lines of Code Written -->
                <div class="col-md-3 col-sm-6 mb-4" data-aos="zoom-in" data-aos-delay="300">
                    <div class="counter">
                        <h2 class="display-4 count" data-count="30000">0</h2>
                        <p>Lines of Code</p>
                    </div>
                </div>
                <!-- GitHub Commits -->
                <div class="col-md-3 col-sm-6 mb-4" data-aos="zoom-in" data-aos-delay="400">
                    <div class="counter">
                        <h2 class="display-4 count" data-count="250">0</h2>
                        <p>GitHub Commits</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- AOS (Animate On Scroll) JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
    <!-- jQuery for custom animations -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    
    <script>
        // Initialize AOS
        AOS.init({
            duration: 1000,
            once: false
        });
        
        // Navbar scroll effect
        $(window).scroll(function() {
            if ($(window).scrollTop() > 50) {
                $('.navbar').addClass('scrolled');
            } else {
                $('.navbar').removeClass('scrolled');
            }
        });
        
        // Counter animation
        $('.count').each(function() {
            $(this).prop('Counter', 0).animate({
                Counter: $(this).data('count')
            }, {
                duration: 3000,
                easing: 'swing',
                step: function(now) {
                    $(this).text(Math.ceil(now) + '+');
                }
            });
        });
    </script>
</body>
</html>