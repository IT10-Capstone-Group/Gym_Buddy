<?php
session_start();
include 'php/config.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - GymBuddy</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
</head>
<body>
    <header>
        <nav>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="trainers.php">Trainers</a></li>
                <li><a href="videos.php">Videos</a></li>
                <li><a href="about.php">About Us</a></li>
                <?php if(isset($_SESSION['user_id'])): ?>
                    <?php if(isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                        <li><a href="php/admin_dashboard.php">Admin Dashboard</a></li>
                    <?php elseif(isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'trainer'): ?>
                        <li><a href="php/trainer_dashboard.php">Trainer Dashboard</a></li>
                    <?php elseif(isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'user'): ?>
                        <li><a href="php/user_dashboard.php">My Bookings</a></li>
                    <?php endif; ?>
                    <li><a href="php/logout.php">Logout</a></li>
                <?php else: ?>
                    <li><a href="php/login.php">Login</a></li>
                    <li><a href="php/signup.php">Sign Up</a></li>
                <?php endif
?>
            </ul>
        </nav>
    </header>

    <main class="about-page">
        <h1>About Team IT-10</h1>
        <section class="about-content">
            <p>
            Welcome to IT-10, a group of passionate 4th-year college students dedicated to innovation and inclusivity. GymBuddy, our capstone project, showcases our commitment to revolutionizing fitness accessibility by connecting clients with trainers anytime, anywhere.</p>

            <br>
            <p>GymBuddy is a revolutionary platform that connects fitness enthusiasts with professional trainers. Our mission is to make fitness accessible to everyone, anytime, anywhere.</p>
            <p>Founded in 2024, GymBuddy has quickly become the go-to solution for people looking to achieve their fitness goals with personalized guidance from experienced trainers.</p>
            <br>
            <p>As aspiring developers and designers, we believe in leveraging technology to make a positive impact. IT-10 is dedicated to creating innovative solutions that enhance fitness accessibility for everyone. With GymBuddy, we aim to connect clients and trainers seamlessly, empowering individuals to achieve their health and wellness goals anytime, anywhere.</p>
            <h2>Our Team</h2>
            <ul>
                <li>Gabriel Nikolai Boone</li>
                <li>Joshua Lance Cristobal</li>
                <li>Keithz Izzy Joveres</li>
                
            </ul>
        </section>
    </main>

    <footer>
        <div class="footer-content">
            <div class="footer-section">
                <h3>Quick Links</h3>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="trainers.php">Trainers</a></li>
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
                <p>Email: Gymbuddy@gmail.com</p>
                <p>Phone: +93 960 456 6595</p>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2024 GymBuddy. All rights reserved.</p>
        </div>
    </footer>
    <a href="index.php" class="back-to-home">Back to Home</a>

    <script src="js/script.js"></script>
</body>
</html>

