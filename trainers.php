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
        <h1>Our Trainers</h1>
        <div class="trainers-grid">
            <?php foreach ($trainers as $trainer): ?>
                <div class="trainer-card">
                    <img src="<?php echo htmlspecialchars($trainer['image_url']); ?>" alt="<?php echo htmlspecialchars($trainer['name']); ?>">
                    <h2><?php echo htmlspecialchars($trainer['name']); ?></h2>
                    <p><strong>Specialization:</strong> <?php echo htmlspecialchars($trainer['specialization']); ?></p>
                    <p><strong>Contact:</strong> <?php echo htmlspecialchars($trainer['contact']); ?></p>
                    <a href="booking.php?trainer_id=<?php echo $trainer['id']; ?>" class="btn book-now">Book Now!</a>
                </div>
            <?php endforeach; ?>
        </div>
    </main>

    <footer>
        <p>&copy; 2023 GymBuddy. All rights reserved.</p>
    </footer>

    <a href="index.php" class="back-to-home">Back to Home</a>

    <script src="js/script.js"></script>
</body>
</html>

