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

// Fetch cancellation settings from the database
$sql = "SELECT * FROM settings WHERE setting_name = 'cancellation_time'";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$cancellation_setting = $stmt->fetch();
$cancellation_time = isset($cancellation_setting['setting_value']) ? (int)$cancellation_setting['setting_value'] : 60; // Default to 60 seconds if not set

// Fetch all bookings for the user
$sql = "SELECT b.*, t.name as trainer_name, t.specialization, t.image_url, 
        UNIX_TIMESTAMP(b.created_at) as booking_timestamp
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
$cancelled_bookings = [];

foreach ($bookings as $booking) {
    if ($booking['status'] === 'pending') {
        $pending_bookings[] = $booking;
    } else if ($booking['status'] === 'confirmed') {
        $confirmed_bookings[] = $booking;
    } else if ($booking['status'] === 'cancelled') {
        $cancelled_bookings[] = $booking;
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
    <style>
        .booking-card.cancelled {
            border-left: 4px solid #dc3545;
        }
        .status-cancelled {
            background-color: #dc3545;
            color: white;
        }
        /* Countdown timer styles */
        .cancel-timer-container {
            margin-top: 10px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .cancel-progress-bar {
            width: 100%;
            height: 6px;
            background-color: #e9ecef;
            border-radius: 3px;
            overflow: hidden;
            margin-bottom: 5px;
        }
        .cancel-progress {
            height: 100%;
            background-color: #dc3545;
            transition: width 1s linear;
        }
        .cancel-timer-text {
            font-size: 12px;
            color: #6c757d;
        }
        .cancel-btn {
            position: relative;
            overflow: hidden;
        }
        .cancel-btn.disabled {
            opacity: 0.5;
            cursor: not-allowed;
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
                                <?php 
                                $current_time = time();
                                $booking_time = isset($booking['booking_timestamp']) ? $booking['booking_timestamp'] : 0;
                                $time_diff = $current_time - $booking_time;
                                $can_cancel = $time_diff <= $cancellation_time;
                                $remaining_time = $can_cancel ? $cancellation_time - $time_diff : 0;
                                $progress_percentage = $can_cancel ? ($remaining_time / $cancellation_time) * 100 : 0;
                                
                                // Calculate minutes and seconds for display
                                $remaining_minutes = floor($remaining_time / 60);
                                $remaining_seconds = $remaining_time % 60;
                                $display_in_minutes = $remaining_minutes > 0;
                                ?>
                                <form method="post" onsubmit="return confirmCancellation(<?php echo $can_cancel ? 'true' : 'false'; ?>);">
                                    <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                    <button type="submit" name="cancel_booking" class="cancel-btn <?php echo !$can_cancel ? 'disabled' : ''; ?>" <?php echo !$can_cancel ? 'disabled' : ''; ?> data-booking-id="<?php echo $booking['id']; ?>">
                                        <i class="fas fa-times"></i> Cancel Booking
                                    </button>
                                    <?php if ($can_cancel): ?>
                                    <div class="cancel-timer-container" id="timer-<?php echo $booking['id']; ?>" 
                                         data-remaining="<?php echo $remaining_time; ?>" 
                                         data-total="<?php echo $cancellation_time; ?>"
                                         data-display-minutes="<?php echo $display_in_minutes ? '1' : '0'; ?>">
                                        <div class="cancel-progress-bar">
                                            <div class="cancel-progress" style="width: <?php echo $progress_percentage; ?>%"></div>
                                        </div>
                                        <div class="cancel-timer-text">
                                            Time remaining to cancel: 
                                            <?php if ($display_in_minutes): ?>
                                                <span class="countdown-minutes"><?php echo $remaining_minutes; ?></span> min
                                                <?php if ($remaining_seconds > 0): ?>
                                                    <span class="countdown-seconds"><?php echo $remaining_seconds; ?></span> sec
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="countdown-seconds"><?php echo $remaining_seconds; ?></span> sec
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php else: ?>
                                    <div class="cancel-timer-text">
                                        Cancellation period has expired
                                    </div>
                                    <?php endif; ?>
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
                                <?php 
                                $current_time = time();
                                $booking_time = isset($booking['booking_timestamp']) ? $booking['booking_timestamp'] : 0;
                                $time_diff = $current_time - $booking_time;
                                $can_cancel = $time_diff <= $cancellation_time;
                                $remaining_time = $can_cancel ? $cancellation_time - $time_diff : 0;
                                $progress_percentage = $can_cancel ? ($remaining_time / $cancellation_time) * 100 : 0;
                                
                                // Calculate minutes and seconds for display
                                $remaining_minutes = floor($remaining_time / 60);
                                $remaining_seconds = $remaining_time % 60;
                                $display_in_minutes = $remaining_minutes > 0;
                                ?>
                                <form method="post" onsubmit="return confirmCancellation(<?php echo $can_cancel ? 'true' : 'false'; ?>);">
                                    <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                    <button type="submit" name="cancel_booking" class="cancel-btn <?php echo !$can_cancel ? 'disabled' : ''; ?>" <?php echo !$can_cancel ? 'disabled' : ''; ?> data-booking-id="<?php echo $booking['id']; ?>">
                                        <i class="fas fa-times"></i> Cancel Booking
                                    </button>
                                    <?php if ($can_cancel): ?>
                                    <div class="cancel-timer-container" id="timer-<?php echo $booking['id']; ?>" 
                                         data-remaining="<?php echo $remaining_time; ?>" 
                                         data-total="<?php echo $cancellation_time; ?>"
                                         data-display-minutes="<?php echo $display_in_minutes ? '1' : '0'; ?>">
                                        <div class="cancel-progress-bar">
                                            <div class="cancel-progress" style="width: <?php echo $progress_percentage; ?>%"></div>
                                        </div>
                                        <div class="cancel-timer-text">
                                            Time remaining to cancel: 
                                            <?php if ($display_in_minutes): ?>
                                                <span class="countdown-minutes"><?php echo $remaining_minutes; ?></span> min
                                                <?php if ($remaining_seconds > 0): ?>
                                                    <span class="countdown-seconds"><?php echo $remaining_seconds; ?></span> sec
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="countdown-seconds"><?php echo $remaining_seconds; ?></span> sec
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php else: ?>
                                    <div class="cancel-timer-text">
                                        Cancellation period has expired
                                    </div>
                                    <?php endif; ?>
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

        <!-- Cancelled Bookings Section -->
        <section class="bookings-section">
            <h2><i class="fas fa-ban"></i> Cancelled Bookings</h2>
            
            <?php if (count($cancelled_bookings) > 0): ?>
                <div class="booking-cards">
                    <?php foreach ($cancelled_bookings as $booking): ?>
                        <div class="booking-card cancelled">
                            <div class="booking-header">
                                <h3>Booking #<?php echo $booking['id']; ?></h3>
                                <span class="booking-status status-cancelled">Cancelled</span>
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
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="no-bookings">You don't have any cancelled bookings.</p>
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
        // Initialize all timers on page load
        document.addEventListener('DOMContentLoaded', function() {
            const timerContainers = document.querySelectorAll('.cancel-timer-container');
            
            timerContainers.forEach(container => {
                const remainingTime = parseInt(container.getAttribute('data-remaining'));
                const totalTime = parseInt(container.getAttribute('data-total'));
                const displayMinutes = container.getAttribute('data-display-minutes') === '1';
                const bookingId = container.id.replace('timer-', '');
                const cancelButton = document.querySelector(`button[data-booking-id="${bookingId}"]`);
                const progressBar = container.querySelector('.cancel-progress');
                
                if (remainingTime > 0) {
                    let timeLeft = remainingTime;
                    
                    // Update the countdown every second
                    const countdownInterval = setInterval(() => {
                        timeLeft--;
                        
                        if (timeLeft <= 0) {
                            clearInterval(countdownInterval);
                            container.innerHTML = '<div class="cancel-timer-text">Cancellation period has expired</div>';
                            
                            if (cancelButton) {
                                cancelButton.classList.add('disabled');
                                cancelButton.disabled = true;
                            }
                        } else {
                            // Calculate minutes and seconds
                            const minutes = Math.floor(timeLeft / 60);
                            const seconds = timeLeft % 60;
                            
                            // Update the display based on remaining time
                            if (minutes > 0) {
                                // Display in minutes and seconds
                                container.querySelector('.cancel-timer-text').innerHTML = 
                                    `Time remaining to cancel: <span class="countdown-minutes">${minutes}</span> min` + 
                                    (seconds > 0 ? ` <span class="countdown-seconds">${seconds}</span> sec` : '');
                            } else {
                                // Display in seconds only
                                container.querySelector('.cancel-timer-text').innerHTML = 
                                    `Time remaining to cancel: <span class="countdown-seconds">${seconds}</span> sec`;
                            }
                            
                            // Update progress bar
                            const percentage = (timeLeft / totalTime) * 100;
                            progressBar.style.width = percentage + '%';
                        }
                    }, 1000);
                }
            });
        });

        // Confirmation function for cancellation
        function confirmCancellation(canCancel) {
            if (!canCancel) {
                alert('The cancellation period for this booking has expired.');
                return false;
            }
            return confirm('Are you sure you want to cancel this booking?');
        }
    </script>
</body>
</html>

