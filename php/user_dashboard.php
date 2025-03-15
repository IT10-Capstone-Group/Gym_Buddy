<?php
session_start();
include 'config.php';

// Check if user is logged in and is a regular user
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'user') {
    header("Location: ../index.php");
    exit();
}

// Fetch user information
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Fetch all bookings for the user
$sql = "SELECT b.*, t.name as trainer_name, t.specialization, t.image_url 
        FROM bookings b 
        JOIN trainers t ON b.trainer_id = t.id 
        WHERE b.user_id = ? 
        ORDER BY b.date, b.time";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$bookings = $stmt->fetchAll();

// Separate bookings by status
$pending_bookings = [];
$confirmed_bookings = [];

foreach ($bookings as $booking) {
    if ($booking['status'] === 'pending') {
        $pending_bookings[] = $booking;
    } else if ($booking['status'] === 'confirmed') {
        $confirmed_bookings[] = $booking;
    }
}

// Handle booking cancellation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cancel_booking'])) {
    $booking_id = $_POST['booking_id'];
    $sql = "UPDATE bookings SET status = 'cancelled' WHERE id = ? AND user_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$booking_id, $user_id]);
    
    header("Location: user_dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - GymBuddy</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/user_dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
</head>
<body>
    <header>
        <nav>
            <ul>
                <li><a href="../index.php">Home</a></li>
                <li><a href="../trainers.php">Trainers</a></li>
                <li><a href="../videos.php">Videos</a></li>
                <li><a href="../about.php">About Us</a></li>
                <li><a href="user_dashboard.php">My Bookings</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>

    <main class="dashboard-container">
        <section class="user-info">
            <div class="user-details">
                <h2>Welcome, <?php echo htmlspecialchars($user['username']); ?></h2>
                <p><i class="fas fa-envelope"></i> <strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
            </div>
        </section>

        <!-- Pending Bookings Section -->
        <section class="bookings-section">
            <h2><i class="fas fa-clock"></i> Pending Bookings</h2>
            
            <?php if (count($pending_bookings) > 0): ?>
                <div class="booking-cards">
                    <?php foreach ($pending_bookings as $booking): ?>
                        <div class="booking-card pending">
                            <div class="booking-header">
                                <h3>Booking #<?php echo $booking['id']; ?></h3>
                                <span class="booking-status status-pending">Pending</span>
                            </div>
                            <div class="booking-body">
                                <div class="booking-trainer">
                                    <img src="../uploads/<?php echo htmlspecialchars($booking['image_url']); ?>" alt="<?php echo htmlspecialchars($booking['trainer_name']); ?>" class="trainer-image">
                                    <div>
                                        <h4><?php echo htmlspecialchars($booking['trainer_name']); ?></h4>
                                        <p><?php echo htmlspecialchars($booking['specialization']); ?></p>
                                    </div>
                                </div>
                                <div class="booking-details">
                                    <p><i class="fas fa-calendar"></i> <?php echo date('F j, Y', strtotime($booking['date'])); ?></p>
                                    <p><i class="fas fa-clock"></i> <?php echo date('g:i A', strtotime($booking['time'])); ?></p>
                                    <div class="payment-status <?php echo $booking['payment_status'] === 'paid' ? 'payment-paid' : 'payment-unpaid'; ?>">
                                        <i class="fas <?php echo $booking['payment_status'] === 'paid' ? 'fa-check-circle' : 'fa-times-circle'; ?>"></i>
                                        <?php echo ucfirst($booking['payment_status']); ?>
                                    </div>
                                </div>
                            </div>
                            <div class="booking-actions">
                                <form method="post" onsubmit="return confirm('Are you sure you want to cancel this booking?');">
                                    <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                    <button type="submit" name="cancel_booking" class="cancel-btn">
                                        <i class="fas fa-times"></i> Cancel Booking
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="no-bookings">You don't have any pending bookings.</p>
            <?php endif; ?>
        </section>

        <!-- Confirmed Bookings Section -->
        <section class="bookings-section">
            <h2><i class="fas fa-check-circle"></i> Confirmed Bookings</h2>
            
            <?php if (count($confirmed_bookings) > 0): ?>
                <div class="booking-cards">
                    <?php foreach ($confirmed_bookings as $booking): ?>
                        <div class="booking-card confirmed">
                            <div class="booking-header">
                                <h3>Booking #<?php echo $booking['id']; ?></h3>
                                <span class="booking-status status-confirmed">Confirmed</span>
                            </div>
                            <div class="booking-body">
                                <div class="booking-trainer">
                                    <img src="../uploads/<?php echo htmlspecialchars($booking['image_url']); ?>" alt="<?php echo htmlspecialchars($booking['trainer_name']); ?>" class="trainer-image">
                                    <div>
                                        <h4><?php echo htmlspecialchars($booking['trainer_name']); ?></h4>
                                        <p><?php echo htmlspecialchars($booking['specialization']); ?></p>
                                    </div>
                                </div>
                                <div class="booking-details">
                                    <p><i class="fas fa-calendar"></i> <?php echo date('F j, Y', strtotime($booking['date'])); ?></p>
                                    <p><i class="fas fa-clock"></i> <?php echo date('g:i A', strtotime($booking['time'])); ?></p>
                                    <div class="payment-status <?php echo $booking['payment_status'] === 'paid' ? 'payment-paid' : 'payment-unpaid'; ?>">
                                        <i class="fas <?php echo $booking['payment_status'] === 'paid' ? 'fa-check-circle' : 'fa-times-circle'; ?>"></i>
                                        <?php echo ucfirst($booking['payment_status']); ?>
                                    </div>
                                </div>
                            </div>
                            <?php if (strtotime($booking['date']) > time()): ?>
                            <div class="booking-actions">
                                <form method="post" onsubmit="return confirm('Are you sure you want to cancel this booking?');">
                                    <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                    <button type="submit" name="cancel_booking" class="cancel-btn">
                                        <i class="fas fa-times"></i> Cancel Booking
                                    </button>
                                </form>
                            </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="no-bookings">You don't have any confirmed bookings.</p>
            <?php endif; ?>
        </section>
    </main>

    <footer>
        <div class="footer-content">
            <div class="footer-section">
                <h3>Quick Links</h3>
                <ul>
                    <li><a href="../index.php">Home</a></li>
                    <li><a href="../trainers.php">Trainers</a></li>
                    <li><a href="../videos.php">Videos</a></li>
                    <li><a href="../about.php">About Us</a></li>
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
</body>
</html>