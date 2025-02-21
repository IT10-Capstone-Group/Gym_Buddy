<?php
session_start();
include 'php/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: php/login.php");
    exit();
}

if (!isset($_GET['trainer_id'])) {
    header("Location: trainers.php");
    exit();
}

$trainer_id = $_GET['trainer_id'];

// Fetch trainer details
$sql = "SELECT * FROM trainers WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$trainer_id]);
$trainer = $stmt->fetch();

if (!$trainer) {
    header("Location: trainers.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $date = $_POST['date'];
    $time = $_POST['time'];
    
    $sql = "INSERT INTO bookings (user_id, trainer_id, date, time) VALUES (?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$_SESSION['user_id'], $trainer_id, $date, $time]);
    
    $success_message = "Booking successful!";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book a Session - GymBuddy</title>
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
        <div class="book">
        <h1>Book a Session with <?php echo htmlspecialchars($trainer['name']); ?></h1>
        </div>
        <?php if (isset($success_message)): ?>
            <p class="success-message"><?php echo $success_message; ?></p>
        <?php endif; ?>
        <form action="" method="post" class="booking-form">
            <label for="date">Date:</label>
            <input type="date" id="date" name="date" required>
            
            <label for="time">Time:</label>
            <input type="time" id="time" name="time" required>
            
            <input type="submit" value="Book Session" class="btn">
        </form>
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

    <a href="trainers.php" class="back-to-home">Back to Trainers</a>

    <script src="js/script.js"></script>
</body>
</html>

