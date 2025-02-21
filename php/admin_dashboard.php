<?php
session_start();
include 'config.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// Create uploads directory if it doesn't exist
$uploads_dir = "../uploads";
if (!file_exists($uploads_dir)) {
    mkdir($uploads_dir, 0777, true);
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_trainer'])) {
        $name = $_POST['name'];
        $specialization = $_POST['specialization'];
        $contact = $_POST['contact'];
        
        // File upload handling
        $target_dir = "../uploads/";
        $file_extension = pathinfo($_FILES["trainer_image"]["name"], PATHINFO_EXTENSION);
        $new_filename = uniqid() . '.' . $file_extension;
        $target_file = $target_dir . $new_filename;
        
        if (move_uploaded_file($_FILES["trainer_image"]["tmp_name"], $target_file)) {
            $image_url = $new_filename;
            
            $sql = "INSERT INTO trainers (name, specialization, contact, image_url) VALUES (?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$name, $specialization, $contact, $image_url]);
            
            $success_message = "New trainer added successfully.";
        } else {
            $error_message = "Sorry, there was an error uploading your file.";
        }
    } elseif (isset($_POST['delete_trainer'])) {
        $trainer_id = $_POST['trainer_id'];
        
        // Get the image filename before deleting the trainer
        $sql = "SELECT image_url FROM trainers WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$trainer_id]);
        $trainer = $stmt->fetch();
        
        if ($trainer) {
            // Delete the image file
            $image_path = "../uploads/" . $trainer['image_url'];
            if (file_exists($image_path)) {
                unlink($image_path);
            }
            
            // Delete the trainer from the database
            $sql = "DELETE FROM trainers WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$trainer_id]);
            
            $success_message = "Trainer deleted successfully.";
        }
    } elseif (isset($_POST['delete_booking'])) {
        $booking_id = $_POST['booking_id'];
        
        $sql = "DELETE FROM bookings WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$booking_id]);
        
        $success_message = "Booking deleted successfully.";
    }
}

// Fetch trainers
$sql = "SELECT * FROM trainers";
$stmt = $pdo->query($sql);
$trainers = $stmt->fetchAll();

// Fetch bookings
$sql = "SELECT bookings.*, users.username, trainers.name AS trainer_name 
        FROM bookings 
        JOIN users ON bookings.user_id = users.id 
        JOIN trainers ON bookings.trainer_id = trainers.id";
$stmt = $pdo->query($sql);
$bookings = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - GymBuddy</title>
    <link rel="stylesheet" href="../css/style.css">
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
                <li><a href="admin_dashboard.php">Admin Dashboard</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>

    <main class="admin-dashboard">
        <h1>Admin Dashboard</h1>
        
        <?php if (isset($success_message)): ?>
            <div class="success-message"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="error-message"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <section>
            <h2>Trainers</h2>
            <table>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Specialization</th>
                    <th>Contact</th>
                    <th>Image</th>
                    <th>Action</th>
                </tr>
                <?php foreach ($trainers as $trainer): ?>
                <tr>
                    <td><?php echo htmlspecialchars($trainer['id']); ?></td>
                    <td><?php echo htmlspecialchars($trainer['name']); ?></td>
                    <td><?php echo htmlspecialchars($trainer['specialization']); ?></td>
                    <td><?php echo htmlspecialchars($trainer['contact']); ?></td>
                    <td><img src="../uploads/<?php echo htmlspecialchars($trainer['image_url']); ?>" alt="<?php echo htmlspecialchars($trainer['name']); ?>" width="50"></td>
                    <td>
                        <form action="" method="post">
                            <input type="hidden" name="trainer_id" value="<?php echo $trainer['id']; ?>">
                            <input type="submit" name="delete_trainer" value="Delete" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this trainer?');">
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>

            <h3>Add New Trainer</h3>
            <form action="" method="post" enctype="multipart/form-data" class="add-form">
                <input type="text" name="name" placeholder="Name" required>
                <input type="text" name="specialization" placeholder="Specialization" required>
                <input type="text" name="contact" placeholder="Contact" required>
                <input type="file" name="trainer_image" accept="image/*" required>
                <input type="submit" name="add_trainer" value="Add Trainer" class="btn">
            </form>
        </section>

        <section>
            <h2>Bookings</h2>
            <table>
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>Trainer</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Action</th>
                </tr>
                <?php foreach ($bookings as $booking): ?>
                <tr>
                    <td><?php echo htmlspecialchars($booking['id']); ?></td>
                    <td><?php echo htmlspecialchars($booking['username']); ?></td>
                    <td><?php echo htmlspecialchars($booking['trainer_name']); ?></td>
                    <td><?php echo htmlspecialchars($booking['date']); ?></td>
                    <td><?php echo htmlspecialchars($booking['time']); ?></td>
                    <td>
                        <form action="" method="post">
                            <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                            <input type="submit" name="delete_booking" value="Delete" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this booking?');">
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

    <a href="../index.php" class="back-to-home">Back to Home</a>

    <script src="../js/script.js"></script>
</body>
</html>

