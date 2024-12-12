<?php
session_start();
include 'php/config.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GymBuddy - Book a trainer, anytime, anywhere</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
</head>
<body>
    <header>
        <nav>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="trainers.php">Trainers</a></li>
                <li><a href="locations.php">Gym Locations</a></li>
                <li><a href="videos.php">Videos</a></li>
                <li><a href="about.php">About Us</a></li>
                <?php if(isset($_SESSION['user_id'])): ?>
                    <?php if(isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                        <li><a href="php/admin_dashboard.php">Admin Dashboard</a></li>
                    <?php endif; ?>
                    <li><a href="php/logout.php">Logout</a></li>
                <?php else: ?>
                    <li><a href="php/login.php">Login</a></li>
                    <li><a href="php/signup.php">Sign Up</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>

    <main>
        <section class="hero">
            <div class="hero-content">
                <h1>Welcome to GymBuddy</h1>
                <p>Book a trainer, anytime, anywhere</p>
                <a href="trainers.php" class="cta-button">Find a Trainer</a>
            </div>
        </section>

        <section class="features">
            <div class="feature">
                <i class="fas fa-dumbbell"></i>
                <h2>Expert Trainers</h2>
                <p>Connect with certified fitness professionals</p>
            </div>
            <div class="feature">
                <i class="fas fa-calendar-alt"></i>
                <h2>Flexible Scheduling</h2>
                <p>Book sessions that fit your lifestyle</p>
            </div>
            <div class="feature">
                <i class="fas fa-map-marker-alt"></i>
                <h2>Multiple Locations</h2>
                <p>Train at a gym near you</p>
            </div>
        </section>

        <section class="testimonials">
            <h2>What Our Clients Say</h2>
            <div class="testimonial">
                <img src="https://randomuser.me/api/portraits/women/65.jpg" alt="Client 1">
                <p>"GymBuddy helped me achieve my fitness goals faster than I ever thought possible!"</p>
                <h3>Sarah M.</h3>
            </div>
            <div class="testimonial">
                <img src="https://randomuser.me/api/portraits/men/32.jpg" alt="Client 2">
                <p>"The convenience of booking trainers through GymBuddy is unmatched. Highly recommended!"</p>
                <h3>John D.</h3>
            </div>
        </section>
    </main>

    <footer>
        <div class="footer-content">
            <div class="footer-section">
                <h3>Quick Links</h3>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="trainers.php">Trainers</a></li>
                    <li><a href="locations.php">Gym Locations</a></li>
                    <li><a href="videos.php">Videos</a></li>
                    <li><a href="about.php">About Us</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h3>Connect With Us</h3>
                <div class="social-icons">
                    <a href="#"><i class="fab fa-facebook"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-linkedin"></i></a>
                </div>
            </div>
            <div class="footer-section">
                <h3>Contact Us</h3>
                <p>Email: info@gymbuddy.com</p>
                <p>Phone: (123) 456-7890</p>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2023 GymBuddy. All rights reserved.</p>
        </div>
    </footer>

    <script src="js/script.js"></script>
</body>
</html>

