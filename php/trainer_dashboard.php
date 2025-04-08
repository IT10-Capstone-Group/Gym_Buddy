<?php
session_start();
include 'config.php';

// Check if user is logged in and is a trainer
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'trainer') {
    header("Location: ../index.php");
    exit();
}

// Check if trainer is approved
if (!isset($_SESSION['trainer_status']) || $_SESSION['trainer_status'] !== 'approved') {
    header("Location: trainer_waiting.php");
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

// Fetch upcoming bookings for the trainer with latest BMI data
$sql = "SELECT b.*, u.username, u.id as user_id,
        (SELECT bmi FROM bmi_tracker WHERE user_id = u.id ORDER BY date_recorded DESC LIMIT 1) as bmi,
        (SELECT weight FROM bmi_tracker WHERE user_id = u.id ORDER BY date_recorded DESC LIMIT 1) as weight,
        (SELECT height FROM bmi_tracker WHERE user_id = u.id ORDER BY date_recorded DESC LIMIT 1) as height,
        (SELECT date_recorded FROM bmi_tracker WHERE user_id = u.id ORDER BY date_recorded DESC LIMIT 1) as bmi_date
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

// Fetch all clients assigned to this trainer with their latest BMI data
$sql = "SELECT DISTINCT u.id, u.username, u.email, 
        (SELECT bmi FROM bmi_tracker WHERE user_id = u.id ORDER BY date_recorded DESC LIMIT 1) as bmi,
        (SELECT weight FROM bmi_tracker WHERE user_id = u.id ORDER BY date_recorded DESC LIMIT 1) as weight,
        (SELECT height FROM bmi_tracker WHERE user_id = u.id ORDER BY date_recorded DESC LIMIT 1) as height,
        (SELECT date_recorded FROM bmi_tracker WHERE user_id = u.id ORDER BY date_recorded DESC LIMIT 1) as bmi_date,
        (SELECT COUNT(*) FROM bmi_tracker WHERE user_id = u.id) as bmi_records_count,
        (SELECT COUNT(*) FROM bookings WHERE user_id = u.id AND trainer_id = ?) as booking_count
        FROM users u 
        JOIN bookings b ON u.id = b.user_id 
        WHERE b.trainer_id = ?
        ORDER BY u.username";
$stmt = $pdo->prepare($sql);
$stmt->execute([$trainer_id, $trainer_id]);
$clients = $stmt->fetchAll();

// Function to calculate BMI
function calculateBMI($height, $weight) {
    // Height should be in meters, weight in kg
    if ($height <= 0 || $weight <= 0) {
        return null;
    }
    
    // Convert height from cm to meters if needed
    $heightInMeters = $height > 3 ? $height / 100 : $height;
    
    // Calculate BMI: weight (kg) / (height (m))²
    $bmi = $weight / ($heightInMeters * $heightInMeters);
    return round($bmi, 1);
}

// Function to determine BMI category
function getBMICategory($bmi) {
    if ($bmi === null) {
        return 'Unknown';
    } elseif ($bmi < 18.5) {
        return 'Underweight';
    } elseif ($bmi >= 18.5 && $bmi < 25) {
        return 'Normal weight';
    } elseif ($bmi >= 25 && $bmi < 30) {
        return 'Overweight';
    } else {
        return 'Obese';
    }
}

// Function to get BMI category color
function getBMICategoryColor($category) {
    switch ($category) {
        case 'Underweight':
            return '#3498db'; // Blue
        case 'Normal weight':
            return '#2ecc71'; // Green
        case 'Overweight':
            return '#f39c12'; // Orange
        case 'Obese':
            return '#e74c3c'; // Red
        default:
            return '#95a5a6'; // Gray
    }
}

// Calculate BMI for each client if not already in database
foreach ($clients as &$client) {
    if ($client['bmi'] === null && $client['height'] > 0 && $client['weight'] > 0) {
        $client['bmi'] = calculateBMI($client['height'], $client['weight']);
    }
    $client['bmi_category'] = getBMICategory($client['bmi']);
    $client['bmi_color'] = getBMICategoryColor($client['bmi_category']);
}
unset($client); // Break the reference

// Calculate BMI for each booking client if not already in database
foreach ($bookings as &$booking) {
    if ($booking['bmi'] === null && $booking['height'] > 0 && $booking['weight'] > 0) {
        $booking['bmi'] = calculateBMI($booking['height'], $booking['weight']);
    }
    $booking['bmi_category'] = getBMICategory($booking['bmi']);
    $booking['bmi_color'] = getBMICategoryColor($booking['bmi_category']);
}
unset($booking); // Break the reference

// Handle BMI update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_bmi'])) {
    $client_id = $_POST['client_id'];
    $weight = $_POST['weight'];
    $height = $_POST['height'];
    
    // Calculate BMI
    $bmi = calculateBMI($height, $weight);
    
    // Insert new BMI record
    $sql = "INSERT INTO bmi_tracker (user_id, weight, height, bmi, date_recorded) VALUES (?, ?, ?, ?, NOW())";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$client_id, $weight, $height, $bmi]);
    
    $success_message = "BMI data updated successfully!";
    
    // Redirect to refresh the page
    header("Location: trainer_dashboard.php?tab=clients");
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
        
        /* BMI Styles */
        .bmi-indicator {
            display: inline-flex;
            align-items: center;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.85em;
            font-weight: bold;
            color: white;
            margin-left: 10px;
        }
        
        .bmi-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 5px;
        }
        
        .clients-section {
            margin-top: 30px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 20px;
        }
        
        .clients-section h2 {
            margin-top: 0;
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        
        .client-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .client-card {
            background-color: #f9f9f9;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            transition: transform 0.2s;
        }
        
        .client-card:hover {
            transform: translateY(-3px);
        }
        
        .client-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .client-name {
            font-size: 1.1em;
            font-weight: bold;
            margin: 0;
        }
        
        .client-details {
            margin-top: 10px;
        }
        
        .client-details p {
            margin: 5px 0;
            display: flex;
            align-items: center;
        }
        
        .client-details i {
            width: 20px;
            margin-right: 8px;
            color: #555;
        }
        
        .bmi-chart {
            margin-top: 15px;
            background-color: #f0f0f0;
            border-radius: 4px;
            height: 20px;
            position: relative;
            overflow: hidden;
        }
        
        .bmi-ranges {
            display: flex;
            height: 100%;
        }
        
        .bmi-range {
            height: 100%;
            position: relative;
        }
        
        .bmi-range-underweight {
            background-color: #3498db;
            width: 18.5%;
        }
        
        .bmi-range-normal {
            background-color: #2ecc71;
            width: 6.5%;
        }
        
        .bmi-range-overweight {
            background-color: #f39c12;
            width: 5%;
        }
        
        .bmi-range-obese {
            background-color: #e74c3c;
            width: 70%;
        }
        
        .bmi-marker {
            position: absolute;
            top: -5px;
            width: 3px;
            height: 30px;
            background-color: #000;
            transform: translateX(-50%);
        }
        
        .bmi-labels {
            display: flex;
            justify-content: space-between;
            margin-top: 5px;
            font-size: 0.7em;
            color: #555;
        }
        
        .tab-container {
            margin-bottom: 20px;
        }
        
        .tabs {
            display: flex;
            border-bottom: 1px solid #ddd;
        }
        
        .tab {
            padding: 10px 20px;
            cursor: pointer;
            border: 1px solid transparent;
            border-bottom: none;
            margin-right: 5px;
            border-radius: 5px 5px 0 0;
            transition: all 0.3s;
        }
        
        .tab.active {
            background-color: #fff;
            border-color: #ddd;
            border-bottom-color: #fff;
            margin-bottom: -1px;
            font-weight: bold;
        }
        
        .tab-content {
            display: none;
            padding: 20px;
            background-color: #fff;
            border: 1px solid #ddd;
            border-top: none;
            border-radius: 0 0 5px 5px;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .bmi-update-form {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px dashed #ddd;
        }
        
        .bmi-update-form .form-row {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
        }
        
        .bmi-update-form .form-group {
            flex: 1;
        }
        
        .bmi-update-form label {
            display: block;
            margin-bottom: 5px;
            font-size: 0.9em;
            color: #555;
        }
        
        .bmi-update-form input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .bmi-update-form button {
            background-color: #0077b6;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9em;
            margin-top: 10px;
        }
        
        .bmi-update-form button:hover {
            background-color: #005f92;
        }
        
        .bmi-history {
            margin-top: 15px;
            font-size: 0.9em;
        }
        
        .bmi-history-title {
            font-weight: bold;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            cursor: pointer;
        }
        
        .bmi-history-title i {
            margin-left: 5px;
            transition: transform 0.3s;
        }
        
        .bmi-history-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
        }
        
        .bmi-history.open .bmi-history-content {
            max-height: 500px;
        }
        
        .bmi-history.open .bmi-history-title i {
            transform: rotate(180deg);
        }
        
        .message {
            padding: 10px 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            font-weight: 500;
        }
        
        .message.success {
            background-color: rgba(76, 175, 80, 0.1);
            color: #4caf50;
            border-left: 4px solid #4caf50;
        }
        
        .message.error {
            background-color: rgba(244, 67, 54, 0.1);
            color: #f44336;
            border-left: 4px solid #f44336;
        }
        
        .bmi-date {
            font-size: 0.8em;
            color: #777;
            margin-top: 5px;
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

        <?php if (isset($success_message)): ?>
            <div class="message success">
                <i class="fas fa-check-circle"></i>
                <span><?php echo $success_message; ?></span>
            </div>
        <?php endif; ?>

        <div class="tab-container">
            <div class="tabs">
                <div class="tab <?php echo !isset($_GET['tab']) || $_GET['tab'] == 'bookings' ? 'active' : ''; ?>" data-tab="bookings">Upcoming Bookings</div>
                <div class="tab <?php echo isset($_GET['tab']) && $_GET['tab'] == 'availability' ? 'active' : ''; ?>" data-tab="availability">Manage Availability</div>
            </div>
            
            <div id="bookings" class="tab-content <?php echo !isset($_GET['tab']) || $_GET['tab'] == 'bookings' ? 'active' : ''; ?>">
                <section class="bookings">
                    <h2><i class="fas fa-calendar-alt"></i> Upcoming Bookings</h2>
                    <?php if (count($bookings) > 0): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Client</th>
                                    <th>BMI Status</th>
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
                                        <td>
                                            <?php if ($booking['bmi']): ?>
                                                <div class="bmi-indicator" style="background-color: <?php echo $booking['bmi_color']; ?>">
                                                    <span class="bmi-dot" style="background-color: <?php echo $booking['bmi_color']; ?>"></span>
                                                    <?php echo $booking['bmi']; ?> - <?php echo $booking['bmi_category']; ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="bmi-indicator" style="background-color: #95a5a6;">No BMI data</span>
                                            <?php endif; ?>
                                        </td>
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
            </div>
            
            
            <div id="availability" class="tab-content <?php echo isset($_GET['tab']) && $_GET['tab'] == 'availability' ? 'active' : ''; ?>">
                <section class="availability-section">
                    <h2><i class="fas fa-clock"></i> Manage Your Availability</h2>
                    
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
            </div>
        </div>
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
        
        // Tab functionality
        document.querySelectorAll('.tab').forEach(tab => {
            tab.addEventListener('click', function() {
                // Remove active class from all tabs and tab contents
                document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
                
                // Add active class to clicked tab
                this.classList.add('active');
                
                // Show corresponding tab content
                const tabId = this.getAttribute('data-tab');
                document.getElementById(tabId).classList.add('active');
                
                // Update URL with tab parameter
                const url = new URL(window.location.href);
                url.searchParams.set('tab', tabId);
                window.history.replaceState({}, '', url);
            });
        });
        
        // BMI chart marker positioning
        document.addEventListener('DOMContentLoaded', function() {
            // This ensures the BMI markers are positioned correctly after the page loads
            const bmiMarkers = document.querySelectorAll('.bmi-marker');
            bmiMarkers.forEach(marker => {
                // Make sure the marker is visible after page load
                marker.style.transition = 'left 0.5s ease-in-out';
            });
        });
        
        // Toggle BMI history
        function toggleBMIHistory(element) {
            const historyContainer = element.closest('.bmi-history');
            historyContainer.classList.toggle('open');
        }
    </script>
</body>
</html>

