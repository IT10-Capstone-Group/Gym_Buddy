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
    <link rel="stylesheet" href="css/calendar.css">
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

       
        <?php
        $sql = "SELECT date FROM bookings WHERE trainer_id = ? AND status = 'confirmed'";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$trainer_id]);
        $confirmed_dates = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Function to generate calendar
        function generateCalendar($year, $month, $confirmed_dates) {
            $calendar = "";
            
            // Create array containing abbreviations of days of week
            $daysOfWeek = array('Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat');

            // Get the first day of the month
            $firstDayOfMonth = mktime(0,0,0,$month,1,$year);

            // Get the number of days in the month
            $numberDays = date('t', $firstDayOfMonth);

            // Get info about the first day of the month
            $dateComponents = getdate($firstDayOfMonth);

            // Get the name of the month
            $monthName = $dateComponents['month'];

            // Get the index value 0-6 of the first day of the month
            $dayOfWeek = $dateComponents['wday'];

            // Create the table tag opener and day headers
            $calendar .= "<table class='calendar'>";
            $calendar .= "<caption>$monthName $year</caption>";
            $calendar .= "<tr>";

            // Create the calendar headers
            foreach($daysOfWeek as $day) {
                $calendar .= "<th class='header'>$day</th>";
            }

            $calendar .= "</tr><tr>";

            // Initiate the day counter
            $currentDay = 1;

            // The variable $dayOfWeek is used to ensure that the calendar
            // display consists of exactly 7 columns
            if ($dayOfWeek > 0) { 
                $calendar .= "<td colspan='$dayOfWeek'>&nbsp;</td>"; 
            }

            $month = str_pad($month, 2, "0", STR_PAD_LEFT);

            while ($currentDay <= $numberDays) {
                // Seventh column (Saturday) reached. Start a new row.
                if ($dayOfWeek == 7) {
                    $dayOfWeek = 0;
                    $calendar .= "</tr><tr>";
                }
                
                $currentDayRel = str_pad($currentDay, 2, "0", STR_PAD_LEFT);
                $date = "$year-$month-$currentDayRel";
                
                if (in_array($date, $confirmed_dates)) {
                    $calendar .= "<td class='day confirmed' data-date='$date'>$currentDay</td>";
                } else {
                    $calendar .= "<td class='day'>$currentDay</td>";
                }
                
                $currentDay++;
                $dayOfWeek++;
            }

            // Complete the row of the last week in month, if necessary
            if ($dayOfWeek != 7) { 
                $remainingDays = 7 - $dayOfWeek;
                $calendar .= "<td colspan='$remainingDays'>&nbsp;</td>"; 
            }

            $calendar .= "</tr>";
            $calendar .= "</table>";

            return $calendar;
        }

        ?>

        <div class="calendar-section">
            <h2>Booking Calendar</h2>
            <div class="calendar-controls">
                <button id="prevMonth">Previous Month</button>
                
                <button id="nextMonth">Next Month</button>
            </div>
            <div id="calendar">
                <?php
                $current_year = date('Y');
                $current_month = date('m');
                echo generateCalendar($current_year, $current_month, $confirmed_dates);
                ?>
            </div>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            let currentYear = <?php echo $current_year; ?>;
            let currentMonth = <?php echo $current_month; ?>;

            function updateCalendar() {
                fetch(`get_calendar.php?year=${currentYear}&month=${currentMonth}&trainer_id=<?php echo $trainer_id; ?>`)
                    .then(response => response.text())
                    .then(data => {
                        document.getElementById('calendar').innerHTML = data;
                        document.getElementById('currentMonthYear').textContent = `${getMonthName(currentMonth)} ${currentYear}`;
                    });
            }

            function getMonthName(monthNumber) {
                const months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
                return months[monthNumber - 1];
            }

            document.getElementById('prevMonth').addEventListener('click', function() {
                currentMonth--;
                if (currentMonth < 1) {
                    currentMonth = 12;
                    currentYear--;
                }
                updateCalendar();
            });

            document.getElementById('nextMonth').addEventListener('click', function() {
                currentMonth++;
                if (currentMonth > 12) {
                    currentMonth = 1;
                    currentYear++;
                }
                updateCalendar();
            });

            // Add event listener for confirmed dates
            document.getElementById('calendar').addEventListener('mouseover', function(e) {
                if (e.target.classList.contains('confirmed')) {
                    let date = e.target.getAttribute('data-date');
                    // Fetch booking details for this date
                    fetch(`get_booking_details.php?date=${date}&trainer_id=<?php echo $trainer_id; ?>`)
                        .then(response => response.json())
                        .then(data => {
                            let details = `Bookings on ${date}:\n`;
                            data.forEach(booking => {
                                details += `${booking.time} - ${booking.user_name}\n`;
                            });
                            e.target.title = details;
                        });
                }
            });

            // Initial calendar update
            updateCalendar();
        });
        </script>
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

