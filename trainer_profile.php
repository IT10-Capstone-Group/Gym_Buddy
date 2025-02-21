<?php
session_start();
include 'php/config.php';

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

// Fetch current bookings for the trainer
$sql = "SELECT b.*, u.username as user_name FROM bookings b 
        JOIN users u ON b.user_id = u.id 
        WHERE b.trainer_id = ? AND b.date >= CURDATE() 
        ORDER BY b.date, b.time";
$stmt = $pdo->prepare($sql);
$stmt->execute([$trainer_id]);
$bookings = $stmt->fetchAll();

// Handle booking confirmation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirm_booking'])) {
    $booking_id = $_POST['booking_id'];
    $sql = "UPDATE bookings SET status = 'confirmed' WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$booking_id]);
    
    // Refresh the page to show updated bookings
    header("Location: trainer_profile.php?trainer_id=" . $trainer_id);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($trainer['name']); ?>'s Profile - GymBuddy</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/trainer_profile.css">
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

    <main class="trainer-profile-page">
        <div class="trainer-profile">
            <h1 class="heading"><?php echo htmlspecialchars($trainer['name']); ?>'s Profile</h1>
            <div class="trainer-info">
                <div class="trainer-image">
                    <img src="uploads/<?php echo htmlspecialchars($trainer['image_url']); ?>" alt="<?php echo htmlspecialchars($trainer['name']); ?>">
                </div>
                <div class="trainer-details">
                    <p><strong>Specialization:</strong> <?php echo htmlspecialchars($trainer['specialization']); ?></p>
                    <p><strong>Contact:</strong> <?php echo htmlspecialchars($trainer['contact']); ?></p>
                    <p><strong>Description:</strong> <?php echo htmlspecialchars($trainer['description']); ?></p>
                    <a href="booking.php?trainer_id=<?php echo $trainer['id']; ?>" class="btn book-now">Book Now!</a>
                </div>
            </div>
        </div>

        <?php if(isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
        <div class="current-bookings">
            <h2>Current Bookings</h2>
            <?php if (empty($bookings)): ?>
                <p>No current bookings.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Client</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bookings as $booking): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($booking['date']); ?></td>
                                <td><?php echo htmlspecialchars($booking['time']); ?></td>
                                <td><?php echo htmlspecialchars($booking['user_name']); ?></td>
                                <td><?php echo ucfirst(htmlspecialchars($booking['status'])); ?></td>
                                <td>
                                    <?php if ($booking['status'] === 'pending'): ?>
                                        <form method="post">
                                            <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                            <button type="submit" name="confirm_booking" class="btn confirm-btn">Confirm</button>
                                        </form>
                                    <?php else: ?>
                                        <?php echo ucfirst($booking['status']); ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php endif; ?>
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

