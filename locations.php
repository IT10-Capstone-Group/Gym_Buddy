<?php
session_start();
include 'php/config.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gym Locations - GymBuddy</title>
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

    <main>
        <h1>Gym Locations</h1>
        <div class="location-grid">
            <div class="location">
                <h2>Carmona, Cavite</h2>
                <iframe src="https://www.google.com/maps/embed?pb=!1m16!1m12!1m3!1d15456.901423966!2d120.95972217775646!3d14.31515444610126!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!2m1!1sgym%20in%20carmona%20cavite!5e0!3m2!1sen!2sph!4v1682929433319!5m2!1sen!2sph" width="100%" height="300" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>
            <div class="location">
                <h2>Balibago, Sta. Rosa</h2>
                <iframe src="https://www.google.com/maps/embed?pb=!1m16!1m12!1m3!1d15458.681325523406!2d121.10095217774338!3d14.29072894776837!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!2m1!1sgym%20in%20balibago%20sta%20rosa!5e0!3m2!1sen!2sph!4v1682929490604!5m2!1sen!2sph" width="100%" height="300" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>
            <div class="location">
                <h2>Biñan, Laguna</h2>
                <iframe src="https://www.google.com/maps/embed?pb=!1m16!1m12!1m3!1d30917.121541253235!2d121.06291651083983!3d14.293736899999998!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!2m1!1sgym%20in%20binan%20laguna!5e0!3m2!1sen!2sph!4v1682929525604!5m2!1sen!2sph" width="100%" height="300" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>
        </div>
    </main>

    <footer>
        <p>&copy; 2023 GymBuddy. All rights reserved.</p>
    </footer>

    <a href="index.php" class="back-to-home">Back to Home</a>

    <script src="js/script.js"></script>
</body>
</html>

