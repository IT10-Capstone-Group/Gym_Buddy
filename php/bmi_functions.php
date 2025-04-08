<?php
// Function to save BMI data to database
function saveBMIData($userId, $weight, $height, $bmi) {
    global $pdo;
    
    // Check if connection is valid
    if (!$pdo) {
        error_log("Database connection failed in saveBMIData");
        return "Database connection failed";
    }
    
    try {
        // Log the values being inserted
        error_log("Attempting to save BMI data: user_id=$userId, weight=$weight, height=$height, bmi=$bmi");
        
        $stmt = $pdo->prepare("INSERT INTO bmi_tracker (user_id, weight, height, bmi) VALUES (?, ?, ?, ?)");
        
        if (!$stmt) {
            error_log("Prepare statement failed in saveBMIData");
            return "Prepare statement failed";
        }
        
        $stmt->execute([$userId, $weight, $height, $bmi]);
        
        error_log("BMI data saved successfully");
        return true;
    } catch (PDOException $e) {
        error_log("Exception in saveBMIData: " . $e->getMessage());
        return "Exception: " . $e->getMessage();
    }
}

// Function to get BMI history for a user
function getBMIHistory($userId, $limit = 10) {
    global $pdo;
    
    if (!$pdo) {
        error_log("Database connection failed in getBMIHistory");
        return [];
    }
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM bmi_tracker WHERE user_id = ? ORDER BY date_recorded DESC LIMIT ?");
        
        if (!$stmt) {
            error_log("Prepare statement failed in getBMIHistory");
            return [];
        }
        
        $stmt->execute([$userId, $limit]);
        
        $history = $stmt->fetchAll();
        
        return $history;
    } catch (PDOException $e) {
        error_log("Exception in getBMIHistory: " . $e->getMessage());
        return [];
    }
}
?>