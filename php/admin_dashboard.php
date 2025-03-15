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
    } elseif (isset($_POST['update_trainer'])) {
        $trainer_id = $_POST['trainer_id'];
        $name = $_POST['name'];
        $specialization = $_POST['specialization'];
        $contact = $_POST['contact'];
        
        // Check if a new image was uploaded
        if ($_FILES['trainer_image']['size'] > 0) {
            // Get the old image to delete it later
            $sql = "SELECT image_url FROM trainers WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$trainer_id]);
            $trainer = $stmt->fetch();
            $old_image = $trainer['image_url'];
            
            // Upload new image
            $target_dir = "../uploads/";
            $file_extension = pathinfo($_FILES["trainer_image"]["name"], PATHINFO_EXTENSION);
            $new_filename = uniqid() . '.' . $file_extension;
            $target_file = $target_dir . $new_filename;
            
            if (move_uploaded_file($_FILES["trainer_image"]["tmp_name"], $target_file)) {
                $image_url = $new_filename;
                
                // Update trainer with new image
                $sql = "UPDATE trainers SET name = ?, specialization = ?, contact = ?, image_url = ? WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$name, $specialization, $contact, $image_url, $trainer_id]);
                
                // Delete old image
                $old_image_path = "../uploads/" . $old_image;
                if (file_exists($old_image_path)) {
                    unlink($old_image_path);
                }
                
                $success_message = "Trainer updated successfully.";
            } else {
                $error_message = "Sorry, there was an error uploading your file.";
            }
        } else {
            // Update trainer without changing the image
            $sql = "UPDATE trainers SET name = ?, specialization = ?, contact = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$name, $specialization, $contact, $trainer_id]);
            
            $success_message = "Trainer updated successfully.";
        }
        
        // Redirect to the main dashboard after update to remove the edit form
        header("Location: admin_dashboard.php");
        exit();
    } elseif (isset($_POST['delete_booking'])) {
        $booking_id = $_POST['booking_id'];
        
        $sql = "DELETE FROM bookings WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$booking_id]);
        
        $success_message = "Booking deleted successfully.";
    }
}

// Check if we're in edit mode
$edit_mode = false;
$trainer_to_edit = null;

if (isset($_GET['edit']) && !empty($_GET['edit'])) {
    $trainer_id = $_GET['edit'];
    $sql = "SELECT * FROM trainers WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$trainer_id]);
    $trainer_to_edit = $stmt->fetch();
    
    if ($trainer_to_edit) {
        $edit_mode = true;
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
                    <td class="action-buttons">
                        <a href="?edit=<?php echo $trainer['id']; ?>" class="btn btn-edit">Edit</a>
                        <form action="" method="post" style="display: inline;">
                            <input type="hidden" name="trainer_id" value="<?php echo $trainer['id']; ?>">
                            <input type="submit" name="delete_trainer" value="Delete" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this trainer?');">
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>

            <?php if ($edit_mode): ?>
                <h3>Edit Trainer</h3>
                <form action="" method="post" enctype="multipart/form-data" class="add-form">
                    <input type="hidden" name="trainer_id" value="<?php echo $trainer_to_edit['id']; ?>">
                    <input type="text" name="name" placeholder="Name" value="<?php echo htmlspecialchars($trainer_to_edit['name']); ?>" required>
                    <input type="text" name="specialization" placeholder="Specialization" value="<?php echo htmlspecialchars($trainer_to_edit['specialization']); ?>" required>
                    <input type="text" name="contact" placeholder="Contact" value="<?php echo htmlspecialchars($trainer_to_edit['contact']); ?>" required>
                    <div>
                        <p>Current Image:</p>
                        <img src="../uploads/<?php echo htmlspecialchars($trainer_to_edit['image_url']); ?>" alt="Current Image" width="100">
                    </div>
                    <input type="file" name="trainer_image" accept="image/*">
                    <p><small>Leave empty to keep current image</small></p>
                    <div>
                        <input type="submit" name="update_trainer" value="Update Trainer" class="btn">
                        <a href="admin_dashboard.php" class="btn btn-cancel">Cancel</a>
                    </div>
                </form>
            <?php else: ?>
                <h3>Add New Trainer</h3>
                <form action="" method="post" enctype="multipart/form-data" class="add-form">
                    <input type="text" name="name" placeholder="Name" required>
                    <input type="text" name="specialization" placeholder="Specialization" required>
                    <input type="text" name="contact" placeholder="Contact" required>
                    <input type="file" name="trainer_image" accept="image/*" required>
                    <input type="submit" name="add_trainer" value="Add Trainer" class="btn">
                </form>
            <?php endif; ?>
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