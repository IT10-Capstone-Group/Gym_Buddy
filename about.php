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

    <main class="about-page">
        <h1>About GymBuddy</h1>
        <section class="about-content">
            <p>GymBuddy is a revolutionary platform that connects fitness enthusiasts with professional trainers. Our mission is to make fitness accessible to everyone, anytime, anywhere.</p>
            <p>Founded in 2023, GymBuddy has quickly become the go-to solution for people looking to achieve their fitness goals with personalized guidance from experienced trainers.</p>
            <h2>Our Team</h2>
            <ul>
                <li>John Doe - Founder & CEO</li>
                <li>Jane Smith - Head of Trainer Relations</li>
                <li>Mike Johnson - Lead Developer</li>
                <li>Sarah Brown - Customer Experience Manager</li>
            </ul>
            <h2>Our Values</h2>
            <ul>
                <li>Accessibility: Making fitness available to everyone</li>
                <li>Quality: Providing top-notch trainers and resources</li>
                <li>Innovation: Continuously improving our platform</li>
                <li>Community: Fostering a supportive fitness community</li>
            </ul>
        </section>
    </main>

    <footer>
        <p>&copy; 2023 GymBuddy. All rights reserved.</p>
    </footer>

    <a href="index.php" class="back-to-home">Back to Home</a>

    <script src="js/script.js"></script>
</body>
</html>

