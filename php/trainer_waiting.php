<?php
session_start();
include 'config.php';

// Check if user is logged in and is a trainer
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'trainer') {
    header("Location: login.php");
    exit();
}

// Check if trainer is already approved
if (isset($_SESSION['trainer_status']) && $_SESSION['trainer_status'] === 'approved') {
    header("Location: trainer_dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Waiting for Approval - GymBuddy</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="auth-form">
        <h2>Account Pending Approval</h2>
        <div class="message-box">
            <p>Your trainer account is currently pending approval by an administrator.</p>
            <p>You will be able to access your dashboard once your account has been approved.</p>
            <p>Thank you for your patience!</p>
        </div>
        
        <form action="logout.php" method="post">
            <input type="submit" value="Logout">
        </form>
    </div>

    
</body>
</html>

