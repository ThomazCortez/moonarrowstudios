<?php
// Start the session at the top of the page to check for user login state
session_start();

require 'header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - Bootstrap Template</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- AOS Animation Library -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css" rel="stylesheet">
    <style>
        .about-header {
            background-color: #f8f9fa;
            padding: 100px 0;
            position: relative;
            overflow: hidden;
        }
        
        .about-content {
            padding: 80px 0;
        }
        
        .team-section {
            background-color: #f1f2f3;
            padding: 80px 0;
        }
        
        .team-card {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            margin-bottom: 30px;
        }
        
        .team-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.2);
        }
        
        .stats-section {
            background-color: #343a40;
            color: white;
            padding: 60px 0;
        }
        
        .stat-item {
            text-align: center;
            padding: 20px;
        }
        
        .stat-number {
            font-size: 3rem;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .img-fluid {
            border-radius: 10px;
        }
        
        .divider {
            height: 4px;
            width: 60px;
            background-color: #0d6efd;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <!-- About Header Section -->
    <section class="about-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6" data-aos="fade-right" data-aos-duration="1000">
                    <h1 class="display-4 fw-bold">About Our Company</h1>
                    <div class="divider"></div>
                    <p class="lead">We're a passionate team dedicated to creating innovative solutions that make a difference in people's lives.</p>
                </div>
                <div class="col-lg-6" data-aos="fade-left" data-aos-duration="1000" data-aos-delay="300">
                    <img src="/api/placeholder/600/400" alt="About Us" class="img-fluid shadow">
                </div>
            </div>
        </div>
    </section>
    
    <!-- About Content Section -->
    <section class="about-content">
        <div class="container">
            <div class="row">
                <div class="col-lg-6" data-aos="fade-up" data-aos-duration="1000">
                    <h2>Our Story</h2>
                    <div class="divider"></div>
                    <p>Founded in 2010, our company began with a simple mission: to create products that solve real problems. What started as a small team of three has now grown into a thriving company with offices worldwide.</p>
                    <p>We believe in innovation, quality, and putting our customers first. Every decision we make is guided by these core values.</p>
                </div>
                <div class="col-lg-6" data-aos="fade-up" data-aos-duration="1000" data-aos-delay="300">
                    <h2>Our Mission</h2>
                    <div class="divider"></div>
                    <p>Our mission is to develop sustainable solutions that improve efficiency, enhance experiences, and create lasting value for our clients and their customers.</p>
                    <p>Through continuous innovation and dedication to excellence, we strive to push boundaries and set new standards in our industry.</p>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Stats Section -->
    <section class="stats-section">
        <div class="container">
            <div class="row text-center">
                <div class="col-12 mb-5" data-aos="fade-up">
                    <h2 class="display-5">Our Impact By Numbers</h2>
                    <div class="divider mx-auto"></div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-3 col-6" data-aos="zoom-in" data-aos-duration="800">
                    <div class="stat-item">
                        <div class="stat-number" id="clients-count">0</div>
                        <p>Happy Clients</p>
                    </div>
                </div>
                <div class="col-md-3 col-6" data-aos="zoom-in" data-aos-duration="800" data-aos-delay="200">
                    <div class="stat-item">
                        <div class="stat-number" id="projects-count">0</div>
                        <p>Projects Completed</p>
                    </div>
                </div>
                <div class="col-md-3 col-6" data-aos="zoom-in" data-aos-duration="800" data-aos-delay="400">
                    <div class="stat-item">
                        <div class="stat-number" id="awards-count">0</div>
                        <p>Awards Won</p>
                    </div>
                </div>
                <div class="col-md-3 col-6" data-aos="zoom-in" data-aos-duration="800" data-aos-delay="600">
                    <div class="stat-item">
                        <div class="stat-number" id="years-count">0</div>
                        <p>Years of Experience</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Team Section -->
    <section class="team-section">
        <div class="container">
            <div class="row text-center">
                <div class="col-12 mb-5" data-aos="fade-up">
                    <h2 class="display-5">Meet Our Team</h2>
                    <div class="divider mx-auto"></div>
                    <p class="lead">We're a diverse group of talented individuals working together to achieve extraordinary results.</p>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-3 col-md-6" data-aos="flip-left" data-aos-duration="1000">
                    <div class="team-card">
                        <img src="/api/placeholder/400/400" alt="Team Member 1" class="card-img-top">
                        <div class="card-body text-center">
                            <h5 class="card-title">Jane Doe</h5>
                            <p class="text-muted">CEO & Founder</p>
                            <p class="card-text">Visionary leader with 15+ years of industry experience.</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6" data-aos="flip-left" data-aos-duration="1000" data-aos-delay="200">
                    <div class="team-card">
                        <img src="/api/placeholder/400/400" alt="Team Member 2" class="card-img-top">
                        <div class="card-body text-center">
                            <h5 class="card-title">John Smith</h5>
                            <p class="text-muted">CTO</p>
                            <p class="card-text">Tech expert with a passion for innovative solutions.</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6" data-aos="flip-left" data-aos-duration="1000" data-aos-delay="400">
                    <div class="team-card">
                        <img src="/api/placeholder/400/400" alt="Team Member 3" class="card-img-top">
                        <div class="card-body text-center">
                            <h5 class="card-title">Sarah Johnson</h5>
                            <p class="text-muted">Design Director</p>
                            <p class="card-text">Award-winning designer with an eye for detail.</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6" data-aos="flip-left" data-aos-duration="1000" data-aos-delay="600">
                    <div class="team-card">
                        <img src="/api/placeholder/400/400" alt="Team Member 4" class="card-img-top">
                        <div class="card-body text-center">
                            <h5 class="card-title">Mike Wilson</h5>
                            <p class="text-muted">Marketing Lead</p>
                            <p class="card-text">Strategic thinker with a knack for storytelling.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Bootstrap JS and Popper.js -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- AOS Animation Library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
    <!-- Number Counter Animation -->
    <script>
        // Initialize AOS
        AOS.init({
            once: true
        });
        
        // Number counter animation
        function animateCounter(id, start, end, duration) {
            let startTimestamp = null;
            const element = document.getElementById(id);
            const step = (timestamp) => {
                if (!startTimestamp) startTimestamp = timestamp;
                const progress = Math.min((timestamp - startTimestamp) / duration, 1);
                element.innerText = Math.floor(progress * (end - start) + start);
                if (progress < 1) {
                    window.requestAnimationFrame(step);
                }
            };
            window.requestAnimationFrame(step);
        }
        
        // Run counter animations when elements are in viewport
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    animateCounter('clients-count', 0, 500, 2000);
                    animateCounter('projects-count', 0, 1200, 2000);
                    animateCounter('awards-count', 0, 35, 2000);
                    animateCounter('years-count', 0, 12, 2000);
                    observer.disconnect();
                }
            });
        });
        
        observer.observe(document.querySelector('.stats-section'));
    </script>
</body>
</html>