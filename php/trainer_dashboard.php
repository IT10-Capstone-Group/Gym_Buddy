<?php
session_start();
include 'config.php';

// Check if user is logged in and is a trainer
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'trainer') {
    header("Location: ../index.php");
    exit();
}

// Fetch trainer information
$user_id = $_SESSION['user_id'];
$sql = "SELECT t.* FROM trainers t
        JOIN users u ON u.trainer_id = t.id
        WHERE u.id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$trainer = $stmt->fetch();

// If trainer information is not found, we'll use a default array
if (!$trainer) {
    $trainer = [
        'name' => 'Unknown Trainer',
        'specialization' => 'Not specified',
        'contact' => 'Not provided',
        'image_url' => 'default_trainer.jpg'
    ];
}

$trainer_id = $trainer['id'];

// Handle availability update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_availability'])) {
    // First, delete existing availability
    $sql = "DELETE FROM trainer_availability WHERE trainer_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$trainer_id]);
    
    // Insert new availability
    $sql = "INSERT INTO trainer_availability (trainer_id, day_of_week, start_time, end_time, is_available) 
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    
    // Check if availability array exists before trying to loop through it
    if (isset($_POST['availability']) && is_array($_POST['availability'])) {
        foreach ($_POST['availability'] as $day => $times) {
            if (isset($times['is_available'])) {
                $stmt->execute([
                    $trainer_id,
                    $day,
                    $times['start_time'],
                    $times['end_time'],
                    1
                ]);
            }
        }
    }
    
    $success_message = "Availability updated successfully!";
}

// Fetch current availability
$sql = "SELECT * FROM trainer_availability WHERE trainer_id = ? ORDER BY day_of_week";
$stmt = $pdo->prepare($sql);
$stmt->execute([$trainer_id]);
$availability = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Create availability array with defaults
$days = [
    0 => 'Sunday',
    1 => 'Monday',
    2 => 'Tuesday',
    3 => 'Wednesday',
    4 => 'Thursday',
    5 => 'Friday',
    6 => 'Saturday'
];

$current_availability = [];
foreach ($days as $day_num => $day_name) {
    $current_availability[$day_num] = [
        'is_available' => false,
        'start_time' => '09:00:00',
        'end_time' => '17:00:00'
    ];
}

// Override defaults with actual availability
foreach ($availability as $av) {
    $current_availability[$av['day_of_week']] = [
        'is_available' => $av['is_available'],
        'start_time' => $av['start_time'],
        'end_time' => $av['end_time']
    ];
}

// Fetch upcoming bookings for the trainer
$sql = "SELECT b.*, u.username
        FROM bookings b 
        JOIN users u ON b.user_id = u.id 
        WHERE b.trainer_id = ? AND b.date >= CURDATE()
        ORDER BY b.date, b.time";
$stmt = $pdo->prepare($sql);
$stmt->execute([$trainer_id]);
$bookings = $stmt->fetchAll();

// Fetch statistics
$sql = "SELECT COUNT(*) as total_bookings FROM bookings WHERE trainer_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$trainer_id]);
$total_bookings = $stmt->fetch()['total_bookings'];

$sql = "SELECT COUNT(*) as confirmed_bookings FROM bookings WHERE trainer_id = ? AND status = 'confirmed'";
$stmt = $pdo->prepare($sql);
$stmt->execute([$trainer_id]);
$confirmed_bookings = $stmt->fetch()['confirmed_bookings'];

// Handle booking confirmation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirm_booking'])) {
    $booking_id = $_POST['booking_id'];
    $sql = "UPDATE bookings SET status = 'confirmed' WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$booking_id]);
    
    header("Location: trainer_dashboard.php");
    exit();
}

// Handle payment confirmation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirm_payment'])) {
    $booking_id = $_POST['booking_id'];
    $sql = "UPDATE bookings SET payment_status = 'paid' WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$booking_id]);
    
    header("Location: trainer_dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trainer Dashboard - GymBuddy</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/trainer_dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <style>
        .payment-btn {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9em;
        }
        .payment-btn:hover {
            background-color: #218838;
        }
        .payment-status {
            font-weight: bold;
        }
        .status-paid {
            color: #28a745;
        }
        .status-unpaid {
            color: #dc3545;
        }
    </style>
</head>
<body>
    <header>
        <nav>
            <ul>
                <li><a href="../index.php">Home</a></li>
                <li><a href="../trainers.php">Trainers</a></li>
                <li><a href="../videos.php">Videos</a></li>
                <li><a href="../about.php">About Us</a></li>
                <li><a href="trainer_dashboard.php">Trainer Dashboard</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>

    <main class="dashboard-container">
        <section class="trainer-info">
            <img src="../uploads/<?php echo htmlspecialchars($trainer['image_url']); ?>" alt="<?php echo htmlspecialchars($trainer['name']); ?>" class="trainer-image">
            <div class="trainer-details">
                <h2>Welcome, <?php echo htmlspecialchars($trainer['name']); ?></h2>
                <p><i class="fas fa-dumbbell"></i> <strong>Specialization:</strong> <?php echo htmlspecialchars($trainer['specialization']); ?></p>
                <p><i class="fas fa-phone"></i> <strong>Contact:</strong> <?php echo htmlspecialchars($trainer['contact']); ?></p>
            </div>
        </section>

        <section class="dashboard-stats">
            <div class="stat-card">
                <i class="fas fa-calendar-check"></i>
                <h3>Total Bookings</h3>
                <p><?php echo $total_bookings; ?></p>
            </div>
            <div class="stat-card">
                <i class="fas fa-check-circle"></i>
                <h3>Confirmed Bookings</h3>
                <p><?php echo $confirmed_bookings; ?></p>
            </div>
        </section>

        <section class="availability-section">
    <h2><i class="fas fa-clock"></i> Manage Your Availability</h2>
    <?php if (isset($success_message)): ?>
        <div class="message success">
            <i class="fas fa-check-circle"></i>
            <span><?php echo $success_message; ?></span>
        </div>
    <?php endif; ?>
    
    <form method="post" class="availability-form">
        <?php foreach ($days as $day_num => $day_name): ?>
            <div class="day-availability">
                <div class="day-header">
                    <label class="day-toggle">
                        <span><?php echo $day_name; ?></span>
                        <input type="checkbox" 
                               name="availability[<?php echo $day_num; ?>][is_available]" 
                               <?php echo $current_availability[$day_num]['is_available'] ? 'checked' : ''; ?>>
                    </label>
                </div>
                <div class="time-slots">
                    <div class="time-input">
                        <label>
                            <i class="fas fa-sun"></i>
                            <span>Start Time</span>
                        </label>
                        <input type="time" 
                               name="availability[<?php echo $day_num; ?>][start_time]" 
                               value="<?php echo substr($current_availability[$day_num]['start_time'], 0, 5); ?>"
                               <?php echo !$current_availability[$day_num]['is_available'] ? 'disabled' : ''; ?>>
                    </div>
                    <div class="time-input">
                        <label>
                            <i class="fas fa-moon"></i>
                            <span>End Time</span>
                        </label>
                        <input type="time" 
                               name="availability[<?php echo $day_num; ?>][end_time]" 
                               value="<?php echo substr($current_availability[$day_num]['end_time'], 0, 5); ?>"
                               <?php echo !$current_availability[$day_num]['is_available'] ? 'disabled' : ''; ?>>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        <button type="submit" name="update_availability" class="update-btn">
            <i class="fas fa-save"></i>
            <span>Update Availability</span>
        </button>
    </form>
</section>

        <section class="bookings">
            <h2><i class="fas fa-calendar-alt"></i> Upcoming Bookings</h2>
            <?php if (count($bookings) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Client</th>
                            <th>Status</th>
                            <th>Action</th>
                            <th>Payment</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bookings as $booking): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($booking['date']); ?></td>
                                <td><?php echo htmlspecialchars($booking['time']); ?></td>
                                <td><?php echo htmlspecialchars($booking['username']); ?></td>
                                <td><?php echo htmlspecialchars(ucfirst($booking['status'])); ?></td>
                                <td>
                                    <?php if ($booking['status'] === 'pending'): ?>
                                        <form method="post" style="display: inline-block; margin-right: 5px;">
                                            <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                            <button type="submit" name="confirm_booking" class="btn confirm-btn">Confirm</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (isset($booking['payment_status']) && $booking['payment_status'] === 'paid'): ?>
                                        <span class="payment-status status-paid"><i class="fas fa-check-circle"></i> Paid</span>
                                    <?php else: ?>
                                        <span class="payment-status status-unpaid"><i class="fas fa-times-circle"></i> Unpaid</span>
                                        <form method="post" style="display: inline-block; margin-left: 5px;">
                                            <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                            <button type="submit" name="confirm_payment" class="payment-btn">
                                                <i class="fas fa-money-bill-wave"></i> Confirm
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="no-bookings">No upcoming bookings.</p>
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

    <script>
        // Enable/disable time inputs based on checkbox state
        document.querySelectorAll('.day-toggle input[type="checkbox"]').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const timeInputs = this.closest('.day-availability').querySelectorAll('input[type="time"]');
                timeInputs.forEach(input => {
                    input.disabled = !this.checked;
                });
            });
        });
    </script>
</body>
</html>