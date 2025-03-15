<?php
session_start();
include 'php/config.php';

if (!isset($_GET['date']) || !isset($_GET['trainer_id'])) {
    echo json_encode([]);
    exit();
}

$date = $_GET['date'];
$trainer_id = $_GET['trainer_id'];

// Fetch bookings for this date and trainer
$sql = "SELECT b.time, u.username as user_name 
        FROM bookings b 
        JOIN users u ON b.user_id = u.id 
        WHERE b.trainer_id = ? AND b.date = ? AND b.status = 'confirmed'
        ORDER BY b.time";
$stmt = $pdo->prepare($sql);
$stmt->execute([$trainer_id, $date]);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($bookings);

