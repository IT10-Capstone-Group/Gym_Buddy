<?php
session_start();
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role']; // Get the selected role
    
    // Start transaction to ensure data consistency
    $pdo->beginTransaction();
    
    try {
        if ($role == 'trainer') {
            // Handle trainer registration
            $specialization = $_POST['specialization'];
            $contact = $_POST['contact'];
            
            // Insert into trainers table with pending status - no image upload
            $sql = "INSERT INTO trainers (name, specialization, contact, status) VALUES (?, ?, ?, 'pending')";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$username, $specialization, $contact]);
            
            // Get the trainer ID for linking with user account
            $trainer_id = $pdo->lastInsertId();
            
            // Insert into users table with trainer role and trainer_id
            $sql = "INSERT INTO users (username, email, password, role, trainer_id) VALUES (?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$username, $email, $password, $role, $trainer_id]);
            
        } else {
            // Regular user registration
            $sql = "INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$username, $email, $password, $role]);
        }
        
        // Commit the transaction
        $pdo->commit();
        
        $_SESSION['message'] = "Registration successful. Please log in.";
        header("Location: login.php");
        exit();
        
    } catch (Exception $e) {
        // Rollback the transaction if something failed
        $pdo->rollBack();
        $error = "Registration failed: " . $e->getMessage();
    }
}

// Default role selection
$selected_role = isset($_GET['role']) ? $_GET['role'] : 'user';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - GymBuddy</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .role-selection {
            margin: 15px 0;
            display: flex;
            gap: 15px;
            align-items: center;
        }
        .role-selection label {
            display: flex;
            align-items: center;
            gap: 5px;
            cursor: pointer;
        }
        .trainer-fields {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
        }
        .trainer-fields h3 {
            margin-top: 0;
            margin-bottom: 15px;
        }
        .hidden {
            display: none;
        }
    </style>
</head>
<body>
    <div class="auth-form">
        <h2>Sign Up</h2>
        <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
        
        <form action="" method="post" enctype="multipart/form-data">
            <!-- Role selection -->
            <div class="role-selection">
                <p>Register as:</p>
                <label>
                    <input type="radio" name="role" value="user" <?php echo ($selected_role == 'user') ? 'checked' : ''; ?> onchange="toggleTrainerFields()"> Client
                </label>
                <label>
                    <input type="radio" name="role" value="trainer" <?php echo ($selected_role == 'trainer') ? 'checked' : ''; ?> onchange="toggleTrainerFields()"> Trainer
                </label>
            </div>
            
            <!-- Common fields for all users -->
            <input type="text" name="username" placeholder="Username" required><br>
            <input type="email" name="email" placeholder="Email" required><br>
            <input type="password" name="password" placeholder="Password" required><br>
            <input type="password" name="confirm_password" placeholder="Confirm Password" required><br>
            
            <!-- Trainer-specific fields (initially hidden) -->
            <div id="trainer-fields" class="trainer-fields <?php echo ($selected_role == 'trainer') ? '' : 'hidden'; ?>">
                <h3>Trainer Information</h3>
                <input type="text" name="specialization" placeholder="Specialization (e.g., Cardio, Weight Training)" <?php echo ($selected_role == 'trainer') ? 'required' : ''; ?>><br>
                <input type="text" name="contact" placeholder="Contact Number" <?php echo ($selected_role == 'trainer') ? 'required' : ''; ?>><br>
                
                <p><small>Note: Your trainer account will need to be approved by an administrator before you can start accepting bookings. Profile images will be added by the admin.</small></p>
            </div>
            
            <input type="submit" value="Sign Up">
        </form>
        
        <p>Already have an account? <a href="login.php">Login here</a></p>
    </div>
    
    <a href="../index.php" class="back-to-home">Back to Home</a>
    
    <script>
        function toggleTrainerFields() {
            const trainerFields = document.getElementById('trainer-fields');
            const trainerRadio = document.querySelector('input[name="role"][value="trainer"]');
            const trainerInputs = trainerFields.querySelectorAll('input');
            
            if (trainerRadio.checked) {
                trainerFields.classList.remove('hidden');
                // Make trainer fields required
                trainerInputs.forEach(input => {
                    if (input.type !== 'file') {
                        input.required = true;
                    }
                });
            } else {
                trainerFields.classList.add('hidden');
                // Remove required attribute from trainer fields
                trainerInputs.forEach(input => {
                    input.required = false;
                });
            }
        }
        
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', toggleTrainerFields);
    </script>
</body>
</html>

