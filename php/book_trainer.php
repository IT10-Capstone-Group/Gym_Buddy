<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $trainer_id = $_POST['trainer_id'];
    $date = $_POST['date'];
    $time = $_POST['time'];
    $user_id = $_SESSION['user_id'];

    $sql = "INSERT INTO bookings (user_id, trainer_id, date, time) VALUES (?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);

    if ($stmt->execute([$user_id, $trainer_id, $date, $time])) {
        $_SESSION['message'] = "Booking successful! Waiting for confirmation.";
        header("Location: ../trainers.php");
        exit();
    } else {
        $error = "Booking failed. Please try again.";
    }
}

$trainer_id = $_GET['id'] ?? null;
if (!$trainer_id) {
    header("Location: ../trainers.php");
    exit();
}

$sql = "SELECT * FROM trainers WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$trainer_id]);
$trainer = $stmt->fetch();

if (!$trainer) {
    header("Location: ../trainers.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book a Trainer - GymBuddy</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <h1>Book a Session with <?php echo htmlspecialchars($trainer['name']); ?></h1>
    <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
    <form action="" method="post">
        <input type="hidden" name="trainer_id" value="<?php echo $trainer['id']; ?>">
        <label for="date">Date:</label>
        <input type="date" id="date" name="date" required><br>
        <label for="time">Time:</label>
        <input type="time" id="time" name="time" required><br>
        <input type="submit" value="Book Now">
    </form>
    <a href="../trainers.php">Back to Trainers</a>
    <a href="../index.php" class="back-to-home">Back to Home</a>
</body>
</html>