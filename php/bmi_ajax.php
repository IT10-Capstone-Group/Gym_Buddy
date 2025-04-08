<?php
session_start();
include 'config.php';
include 'bmi_functions.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check database connection first
if (!isset($pdo)) {
    echo json_encode(['error' => 'Database connection failed. Please check your database settings.']);
    exit;
}

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'user') {
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

$userId = $_SESSION['user_id'];

// Handle POST request to save BMI data
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save') {
    $weight = isset($_POST['weight']) ? floatval($_POST['weight']) : 0;
    $height = isset($_POST['height']) ? floatval($_POST['height']) : 0;
    
    if ($weight <= 0 || $height <= 0) {
        echo json_encode(['error' => 'Invalid weight or height']);
        exit;
    }
    
    // Calculate BMI
    $heightInMeters = $height / 100;
    $bmi = $weight / ($heightInMeters * $heightInMeters);
    
    // Save to database
    $result = saveBMIData($userId, $weight, $height, $bmi);
    
    if ($result === true) {
        echo json_encode([
            'success' => true,
            'bmi' => round($bmi, 1),
            'category' => getBMICategory($bmi)
        ]);
    } else {
        // Return the specific error message
        echo json_encode(['error' => 'Database error: ' . $result]);
    }
    exit;
}

// Handle GET request to fetch BMI history
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'history') {
    $history = getBMIHistory($userId);
    echo json_encode(['success' => true, 'history' => $history]);
    exit;
}

// Helper function to get BMI category
function getBMICategory($bmi) {
    if ($bmi < 18.5) return ['category' => 'Underweight', 'color' => '#3498db'];
    if ($bmi < 25) return ['category' => 'Normal weight', 'color' => '#2ecc71'];
    if ($bmi < 30) return ['category' => 'Overweight', 'color' => '#f39c12'];
    return ['category' => 'Obesity', 'color' => '#e74c3c'];
}

echo json_encode(['error' => 'Invalid request']);
?>