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
                <li><a href="videos.php">Videos</a></li>
                <li><a href="about.php">About Us</a></li>
                <?php if(isset($_SESSION['user_id'])): ?>
                    <?php if(isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                        <li><a href="php/admin_dashboard.php">Admin Dashboard</a></li>
                    <?php elseif(isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'trainer'): ?>
                        <li><a href="php/trainer_dashboard.php">Trainer Dashboard</a></li>
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
    <section class="home" id="home">
       
       <div class="content">
           <h3>Gym Buddy</h3>
           <span>Book a trainer, Anytime, Anywhere</span> 
           <p class="simple-shadow">Your ultimate fitness hub, designed to help you take control of your journey. Book expert trainers, follow workout paths tailored to your goals, discover nearby gyms with all the details you need, and watch high-quality workout tutorials. Start building your best self with us, wherever and however you like.</p>
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

    <script src="js/script.js"></script>
</body>
</html>

