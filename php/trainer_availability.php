<?php
session_start();
include 'config.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['set_availability'])) {
        $trainer_id = $_POST['trainer_id'];
        $date = $_POST['date'];
        $start_time = $_POST['start_time'];
        $end_time = $_POST['end_time'];
        
        // Check if availability already exists
        $sql = "SELECT * FROM trainer_availability WHERE trainer_id = ? AND date = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$trainer_id, $date]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            // Update existing availability
            $sql = "UPDATE trainer_availability SET start_time = ?, end_time = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$start_time, $end_time, $existing['id']]);
        } else {
            // Insert new availability
            $sql = "INSERT INTO trainer_availability (trainer_id, date, start_time, end_time) VALUES (?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$trainer_id, $date, $start_time, $end_time]);
        }
        
        $success_message = "Trainer availability set successfully.";
    } elseif (isset($_POST['delete_availability'])) {
        $availability_id = $_POST['availability_id'];
        
        $sql = "DELETE FROM trainer_availability WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$availability_id]);
        
        $success_message = "Availability slot deleted successfully.";
    }
}

// Fetch trainers
$sql = "SELECT * FROM trainers";
$stmt = $pdo->query($sql);
$trainers = $stmt->fetchAll();

// Fetch availabilities
$sql = "SELECT ta.*, t.name AS trainer_name 
        FROM trainer_availability ta 
        JOIN trainers t ON ta.trainer_id = t.id 
        ORDER BY ta.date, ta.start_time";
$stmt = $pdo->query($sql);
$availabilities = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trainer Availability - GymBuddy</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <style>
        .admin-dashboard {
            padding: 20px;
        }
        .admin-dashboard h1, .admin-dashboard h2 {
            color: #333;
            margin-bottom: 20px;
        }
        .admin-dashboard table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .admin-dashboard th, .admin-dashboard td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .admin-dashboard th {
            background-color: #f2f2f2;
        }
        .admin-dashboard .btn {
            padding: 5px 10px;
            background-color: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
        }
        .admin-dashboard .btn-danger {
            background-color: #f44336;
        }
        .admin-dashboard .add-form {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
        }
        .admin-dashboard .add-form input, .admin-dashboard .add-form select {
            padding: 5px;
        }
        .success-message, .error-message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .success-message {
            background-color: #dff0d8;
            border: 1px solid #d6e9c6;
            color: #3c763d;
        }
        .error-message {
            background-color: #f2dede;
            border: 1px solid #ebccd1;
            color: #a94442;
        }
    </style>
</head>
<body>
    <header>
        <nav>
            <ul>
                <li><a href="../index.php">Home</a></li>
                <li><a href="../trainers.php">Trainers</a></li>
                <li><a href="../locations.php">Gym Locations</a></li>
                <li><a href="../videos.php">Videos</a></li>
                <li><a href="../about.php">About Us</a></li>
                <li><a href="admin_dashboard.php">Admin Dashboard</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>

    <main class="admin-dashboard">
        <h1>Trainer Availability Management</h1>
        
        <?php if (isset($success_message)): ?>
            <div class="success-message"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="error-message"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <section>
            <h2>Set Trainer Availability</h2>
            <form action="" method="post" class="add-form">
                <select name="trainer_id" required>
                    <option value="">Select Trainer</option>
                    <?php foreach ($trainers as $trainer): ?>
                        <option value="<?php echo $trainer['id']; ?>"><?php echo htmlspecialchars($trainer['name']); ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="date" name="date" required>
                <input type="time" name="start_time" required>
                <input type="time" name="end_time" required>
                <input type="submit" name="set_availability" value="Set Availability" class="btn">
            </form>
        </section>

        <section>
            <h2>Current Availabilities</h2>
            <table>
                <tr>
                    <th>Trainer</th>
                    <th>Date</th>
                    <th>Start Time</th>
                    <th>End Time</th>
                    <th>Action</th>
                </tr>
                <?php foreach ($availabilities as $availability): ?>
                <tr>
                    <td><?php echo htmlspecialchars($availability['trainer_name']); ?></td>
                    <td><?php echo htmlspecialchars($availability['date']); ?></td>
                    <td><?php echo htmlspecialchars($availability['start_time']); ?></td>
                    <td><?php echo htmlspecialchars($availability['end_time']); ?></td>
                    <td>
                        <form action="" method="post">
                            <input type="hidden" name="availability_id" value="<?php echo $availability['id']; ?>">
                            <input type="submit" name="delete_availability" value="Delete" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this availability slot?');">
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </section>
    </main>

    <footer>
        <div class="footer-content">
            <div class="footer-section">
                <h3>Quick Links</h3>
                <ul>
                    <li><a href="../index.php">Home</a></li>
                    <li><a href="../trainers.php">Trainers</a></li>
                    <li><a href="../locations.php">Gym Locations</a></li>
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

    <a href="../index.php" class="back-to-home">Back to Home</a>

    <script src="../js/script.js"></script>
</body>
</html>