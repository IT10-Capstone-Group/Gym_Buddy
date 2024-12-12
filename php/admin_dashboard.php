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
    if (isset($_POST['add_trainer'])) {
        $name = $_POST['name'];
        $specialization = $_POST['specialization'];
        $contact = $_POST['contact'];
        $image_url = $_POST['image_url'];
        
        $sql = "INSERT INTO trainers (name, specialization, contact, image_url) VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$name, $specialization, $contact, $image_url]);
    } elseif (isset($_POST['delete_trainer'])) {
        $trainer_id = $_POST['trainer_id'];
        
        $sql = "DELETE FROM trainers WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$trainer_id]);
    } elseif (isset($_POST['delete_booking'])) {
        $booking_id = $_POST['booking_id'];
        
        $sql = "DELETE FROM bookings WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$booking_id]);
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
        <h1>Admin Dashboard</h1>
        
        <section>
            <h2>Trainers</h2>
            <table>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Specialization</th>
                    <th>Contact</th>
                    <th>Action</th>
                </tr>
                <?php foreach ($trainers as $trainer): ?>
                <tr>
                    <td><?php echo htmlspecialchars($trainer['id']); ?></td>
                    <td><?php echo htmlspecialchars($trainer['name']); ?></td>
                    <td><?php echo htmlspecialchars($trainer['specialization']); ?></td>
                    <td><?php echo htmlspecialchars($trainer['contact']); ?></td>
                    <td>
                        <form action="" method="post">
                            <input type="hidden" name="trainer_id" value="<?php echo $trainer['id']; ?>">
                            <input type="submit" name="delete_trainer" value="Delete" class="btn btn-danger">
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>

            <h3>Add New Trainer</h3>
            <form action="" method="post" class="add-form">
                <input type="text" name="name" placeholder="Name" required>
                <input type="text" name="specialization" placeholder="Specialization" required>
                <input type="text" name="contact" placeholder="Contact" required>
                <input type="text" name="image_url" placeholder="Image URL" required>
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
                            <input type="submit" name="delete_booking" value="Delete" class="btn btn-danger">
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </section>
    </main>

    <footer>
        <p>&copy; 2023 GymBuddy. All rights reserved.</p>
    </footer>

    <a href="../index.php" class="back-to-home">Back to Home</a>

    <script src="../js/script.js"></script>
</body>
</html>

