<?php
session_start();
include 'php/config.php';

// Fetch trainers from the database
$sql = "SELECT * FROM trainers";
$stmt = $pdo->query($sql);
$trainers = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trainers - GymBuddy</title>
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

        <section class="about" id="about">



<div class="row">


   <div class="content">
        <h3>Introducing the trainers</h3>
        <p>
            Welcome to our Trainers page! Meet our dedicated trainers, each with unique expertise to guide you on your fitness journey. Explore their profiles to find the perfect coach for your goals!</p>
      
   </div>
</div>


</section>
<section class="trainers">

<h1 class="heading">Meet the Trainers</h1>


</section>
<div class="trainers-grid">
    <?php foreach ($trainers as $trainer): ?>
        <div class="trainer-card">
            <img src="uploads/<?php echo htmlspecialchars($trainer['image_url']); ?>" alt="<?php echo htmlspecialchars($trainer['name']); ?>">
            <h2><?php echo htmlspecialchars($trainer['name']); ?></h2>
            <p><strong>Specialization:</strong> <?php echo htmlspecialchars($trainer['specialization']); ?></p>
            <p><strong>Contact:</strong> <?php echo htmlspecialchars($trainer['contact']); ?></p>
            <a href="booking.php?trainer_id=<?php echo $trainer['id']; ?>" class="btn book-now">Book Now!</a>
            <a href="trainer_profile.php?trainer_id=<?php echo $trainer['id']; ?>" class="btn book-now">View Profile</a>
        </div>
    <?php endforeach; ?>
</div>

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

