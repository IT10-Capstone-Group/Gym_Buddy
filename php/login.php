<?php
session_start();
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Get user from database with trainer status if applicable
    $sql = "SELECT u.*, t.status AS trainer_status 
            FROM users u 
            LEFT JOIN trainers t ON u.trainer_id = t.id 
            WHERE u.username = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['user_role'] = $user['role'];
        
        // If user is a trainer, check approval status
        if ($user['role'] === 'trainer') {
            $_SESSION['trainer_status'] = $user['trainer_status'];
            
            // If trainer is not approved, redirect to waiting page
            if ($user['trainer_status'] !== 'approved') {
                header("Location: trainer_waiting.php");
                exit();
            }
            
            // If approved, redirect to trainer dashboard
            header("Location: trainer_dashboard.php");
            exit();
        } elseif ($user['role'] === 'admin') {
            header("Location: admin_dashboard.php");
            exit();
        } else {
            header("Location: ../index.php");
            exit();
        }
    } else {
        $error = "Invalid username or password";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - GymBuddy</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="auth-form">
        <h2>Login</h2>
        <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
        <?php if (isset($_SESSION['message'])) { 
            echo "<p class='success'>" . $_SESSION['message'] . "</p>";
            unset($_SESSION['message']);
        } ?>
        <form action="" method="post">
            <input type="text" name="username" placeholder="Username" required><br>
            <input type="password" name="password" placeholder="Password" required><br>
            <input type="submit" value="Login">
        </form>
        <p>Don't have an account? <a href="signup.php">Sign up here</a></p>
    </div>
    <a href="../index.php" class="back-to-home">Back to Home</a>
</body>
</html>