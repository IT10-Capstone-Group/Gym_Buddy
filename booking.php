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

// Fetch trainer's availability
$sql = "SELECT * FROM trainer_availability WHERE trainer_id = ? ORDER BY day_of_week";
$stmt = $pdo->prepare($sql);
$stmt->execute([$trainer_id]);
$availability = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Format availability for JavaScript
$availabilityJSON = json_encode($availability);

// Fetch ONLY CONFIRMED bookings for the trainer
$sql = "SELECT date, time FROM bookings WHERE trainer_id = ? AND status = 'confirmed'";
$stmt = $pdo->prepare($sql);
$stmt->execute([$trainer_id]);
$confirmedBookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch ALL bookings (including pending) for the trainer
$sql = "SELECT date, time, status FROM bookings WHERE trainer_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$trainer_id]);
$allBookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Format bookings for JavaScript
$confirmedBookingsJSON = json_encode($confirmedBookings);
$allBookingsJSON = json_encode($allBookings);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $date = $_POST['date'];
    $time = $_POST['time'];
    
    // Check if the slot is already booked
    $sql = "SELECT id, status FROM bookings WHERE trainer_id = ? AND date = ? AND time = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$trainer_id, $date, $time]);
    $existingBooking = $stmt->fetch();
    
    if ($existingBooking) {
        $status = $existingBooking['status'];
        $error_message = "This time slot is " . ($status === 'confirmed' ? 'already booked' : 'pending confirmation') . ". Please select another time.";
    } else {
        // Check if the trainer is available at this time
        $dayOfWeek = date('w', strtotime($date));
        $sql = "SELECT * FROM trainer_availability 
                WHERE trainer_id = ? 
                AND day_of_week = ? 
                AND is_available = 1 
                AND ? BETWEEN start_time AND end_time";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$trainer_id, $dayOfWeek, $time]);
        $isAvailable = $stmt->fetch();
        
        if (!$isAvailable) {
            $error_message = "The trainer is not available at this time. Please select another time slot.";
        } else {
            // Insert new booking with pending status
            $sql = "INSERT INTO bookings (user_id, trainer_id, date, time, status) VALUES (?, ?, ?, ?, 'pending')";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$_SESSION['user_id'], $trainer_id, $date, $time]);
            
            $success_message = "Booking request submitted! Waiting for trainer confirmation.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book a Session - GymBuddy</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/booking.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        /* Fix for the red line under input fields */
        .date-picker,
        .time-picker {
            border-bottom: 1px solid #ddd !important; /* Override any red border-bottom */
            outline: none !important; /* Remove outline that might appear as a red line */
        }
        
        /* Only show red border when there's an error */
        .form-group.has-error .date-picker,
        .form-group.has-error .time-picker {
            border-bottom: 1px solid #dc3545 !important;
        }
        
        /* Fix for flatpickr input styling */
        .flatpickr-input {
            border-bottom: 1px solid #ddd !important;
            box-shadow: none !important;
        }
        
        /* Ensure error messages only appear when there's content */
        .error-message:empty {
            display: none;
        }
    </style>
</head>
<body>
    <!-- Header remains the same -->
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
                    <?php elseif(isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'trainer'): ?>
                        <li><a href="php/trainer_dashboard.php">Trainer Dashboard</a></li>
                    <?php elseif(isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'user'): ?>
                        <li><a href="php/user_dashboard.php">My Bookings</a></li>
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
        <div class="booking-container">
            <div class="trainer-info">
                <h1>Book a Session with <?php echo htmlspecialchars($trainer['name']); ?></h1>
                <?php if (isset($trainer['image_url']) && !empty($trainer['image_url'])): ?>
                    <img src="uploads/<?php echo htmlspecialchars($trainer['image_url']); ?>" alt="<?php echo htmlspecialchars($trainer['name']); ?>" class="trainer-image">
                <?php endif; ?>
                <?php if (isset($trainer['specialization']) && !empty($trainer['specialization'])): ?>
                    <p class="specialization">Specialization: <?php echo htmlspecialchars($trainer['specialization']); ?></p>
                <?php endif; ?>
            </div>
            
            <?php if (isset($success_message)): ?>
                <div class="message success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="message error"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <form action="" method="post" class="booking-form" id="booking-form">
                <div class="form-group" id="date-group">
                    <label for="date">Select Date:</label>
                    <input type="text" id="date" name="date" class="date-picker" required>
                    <div id="date-error" class="error-message"></div>
                </div>
                
                <div class="form-group" id="time-group">
                    <label for="time">Select Time:</label>
                    <input type="text" id="time" name="time" class="time-picker" required>
                    <div id="time-error" class="error-message"></div>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn book-btn">Request Booking</button>
                </div>
            </form>
            
            <div class="booking-info">
                <h3>Booking Information</h3>
                <p>Please select an available date and time for your session with <?php echo htmlspecialchars($trainer['name']); ?>.</p>
                <p>Your booking will be pending until confirmed by the trainer.</p>
                <p class="availability-note">Note: Only time slots during the trainer's available hours will be shown.</p>
            </div>
        </div>
    </main>

    <a href="trainers.php" class="back-button"><i class="fas fa-arrow-left"></i> Back to Trainers</a>

    <!-- Footer remains the same -->
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

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        // Pass PHP data to JavaScript
        const confirmedBookings = <?php echo $confirmedBookingsJSON; ?>;
        const allBookings = <?php echo $allBookingsJSON; ?>;
        const trainerAvailability = <?php echo $availabilityJSON; ?>;
        
        // Format bookings for time disabling
        const bookedDates = {};
        const pendingDates = {};
        
        allBookings.forEach(booking => {
            if (booking.status === 'confirmed') {
                if (!bookedDates[booking.date]) {
                    bookedDates[booking.date] = [];
                }
                bookedDates[booking.date].push(booking.time);
            } else if (booking.status === 'pending') {
                if (!pendingDates[booking.date]) {
                    pendingDates[booking.date] = [];
                }
                pendingDates[booking.date].push(booking.time);
            }
        });
        
        // Function to check if a date is available based on trainer's schedule
        function isDateAvailable(date) {
            const dayOfWeek = date.getDay();
            return trainerAvailability.some(av => 
                parseInt(av.day_of_week) === dayOfWeek && parseInt(av.is_available) === 1
            );
        }
        
        // Function to get available time slots for a specific date
        function getAvailableTimeSlots(date) {
            const dayOfWeek = date.getDay();
            const dayAvailability = trainerAvailability.find(av => 
                parseInt(av.day_of_week) === dayOfWeek && parseInt(av.is_available) === 1
            );
            
            if (!dayAvailability) return { startTime: null, endTime: null };
            
            return { 
                startTime: dayAvailability.start_time, 
                endTime: dayAvailability.end_time 
            };
        }
        
        // Store time picker instance
        let timePickerInstance = null;
        
        // Initialize date picker
        const datePicker = flatpickr("#date", {
            minDate: "today",
            dateFormat: "Y-m-d",
            disable: [
                function(date) {
                    // Disable past dates and dates when trainer is not available
                    const today = new Date();
                    today.setHours(0, 0, 0, 0);
                    
                    if (date < today) {
                        return true;
                    }
                    
                    return !isDateAvailable(date);
                }
            ],
            onChange: function(selectedDates, dateStr) {
                // Clear time picker when date changes
                document.getElementById('time').value = '';
                document.getElementById('time-error').textContent = '';
                
                // Remove error class when date is selected
                document.getElementById('date-group').classList.remove('has-error');
                
                // Update time picker based on selected date
                if (dateStr) {
                    initTimePicker(dateStr);
                }
            },
            onOpen: function(selectedDates, dateStr, instance) {
                // Add custom class to highlight days with bookings
                setTimeout(() => {
                    const days = instance.calendarContainer.querySelectorAll('.flatpickr-day');
                    days.forEach(day => {
                        const dateAttr = day.getAttribute('aria-label');
                        if (dateAttr) {
                            const date = flatpickr.formatDate(new Date(dateAttr), "Y-m-d");
                            if (bookedDates[date] && bookedDates[date].length > 0) {
                                day.classList.add('confirmed-booking');
                            }
                            if (pendingDates[date] && pendingDates[date].length > 0) {
                                day.classList.add('pending-booking');
                            }
                        }
                    });
                }, 100);
            }
        });
        
        // Initialize time picker based on selected date
        function initTimePicker(selectedDate) {
            const date = new Date(selectedDate);
            const availableSlots = getAvailableTimeSlots(date);
            const confirmedTimes = bookedDates[selectedDate] || [];
            const pendingTimes = pendingDates[selectedDate] || [];
            
            // Clear previous error messages
            document.getElementById('time-error').textContent = "";
            document.getElementById('time-group').classList.remove('has-error');
            
            if (!availableSlots.startTime) {
                document.getElementById('time-error').textContent = "No available time slots for this date.";
                document.getElementById('time-group').classList.add('has-error');
                return;
            }
            
            // Destroy previous instance if it exists
            if (timePickerInstance) {
                timePickerInstance.destroy();
            }
            
            // Create new time picker instance
            timePickerInstance = flatpickr("#time", {
                enableTime: true,
                noCalendar: true,
                dateFormat: "H:i",
                minTime: availableSlots.startTime,
                maxTime: availableSlots.endTime,
                minuteIncrement: 30,
                disable: confirmedTimes,
                onChange: function(selectedDates, timeStr) {
                    // Remove error class when time is selected
                    document.getElementById('time-group').classList.remove('has-error');
                    document.getElementById('time-error').textContent = "";
                    
                    if (selectedDates.length > 0) {
                        const time24 = flatpickr.formatDate(selectedDates[0], "H:i");
                        
                        if (confirmedTimes.includes(time24)) {
                            document.getElementById('time-error').textContent = "This time slot is already booked. Please select another time.";
                            document.getElementById('time-group').classList.add('has-error');
                            this.clear();
                        } else if (pendingTimes.includes(time24)) {
                            document.getElementById('time-error').textContent = "This time slot has a pending booking. You may still request this slot.";
                            // Don't add error class for pending bookings as they're still valid
                        }
                    }
                }
            });
        }
        
        // Form validation
        document.querySelector('.booking-form').addEventListener('submit', function(e) {
            let hasError = false;
            
            // Reset error states
            document.getElementById('date-group').classList.remove('has-error');
            document.getElementById('time-group').classList.remove('has-error');
            
            if (!document.getElementById('date').value) {
                document.getElementById('date-error').textContent = "Please select a date";
                document.getElementById('date-group').classList.add('has-error');
                hasError = true;
            } else {
                document.getElementById('date-error').textContent = "";
            }
            
            if (!document.getElementById('time').value) {
                document.getElementById('time-error').textContent = "Please select a time";
                document.getElementById('time-group').classList.add('has-error');
                hasError = true;
            } else {
                document.getElementById('time-error').textContent = "";
            }
            
            if (hasError) {
                e.preventDefault();
            }
        });
        
        // Clear validation styling on input focus
        document.getElementById('date').addEventListener('focus', function() {
            document.getElementById('date-group').classList.remove('has-error');
            document.getElementById('date-error').textContent = "";
        });
        
        document.getElementById('time').addEventListener('focus', function() {
            document.getElementById('time-group').classList.remove('has-error');
            document.getElementById('time-error').textContent = "";
        });
    </script>
</body>
</html>

