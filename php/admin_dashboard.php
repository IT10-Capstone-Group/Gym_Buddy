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
        
        // File upload handling
        $target_dir = "../uploads/";
        $file_extension = pathinfo($_FILES["trainer_image"]["name"], PATHINFO_EXTENSION);
        $new_filename = uniqid() . '.' . $file_extension;
        $target_file = $target_dir . $new_filename;
        
        if (move_uploaded_file($_FILES["trainer_image"]["tmp_name"], $target_file)) {
            $image_url = $new_filename;
            
            $sql = "INSERT INTO trainers (name, specialization, contact, image_url, status) VALUES (?, ?, ?, ?, 'approved')";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$name, $specialization, $contact, $image_url]);
            
            $success_message = "New trainer added successfully.";
        } else {
            $error_message = "Sorry, there was an error uploading your file.";
        }
    } elseif (isset($_POST['delete_trainer'])) {
        $trainer_id = $_POST['trainer_id'];
        
        // Begin transaction
        $pdo->beginTransaction();
        
        try {
            // Get the image filename before deleting the trainer
            $sql = "SELECT image_url FROM trainers WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$trainer_id]);
            $trainer = $stmt->fetch();
            
            if ($trainer) {
                // Delete the image file
                if ($trainer && !empty($trainer['image_url'])) {
                    $image_path = "../uploads/" . $trainer['image_url'];
                    if (file_exists($image_path) && is_file($image_path)) {
                        unlink($image_path);
                    }
                }
                
                // First delete related records in trainer_availability
                $sql = "DELETE FROM trainer_availability WHERE trainer_id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$trainer_id]);
                
                // Then delete any bookings associated with this trainer
                $sql = "DELETE FROM bookings WHERE trainer_id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$trainer_id]);
                
                // Update any users that reference this trainer
                $sql = "UPDATE users SET trainer_id = NULL WHERE trainer_id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$trainer_id]);
                
                // Finally delete the trainer from the database
                $sql = "DELETE FROM trainers WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$trainer_id]);
                
                $pdo->commit();
                $success_message = "Trainer deleted successfully.";
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            $error_message = "Error: " . $e->getMessage();
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
        header("Location: admin_dashboard.php?tab=trainers");
        exit();
    } elseif (isset($_POST['delete_booking'])) {
        $booking_id = $_POST['booking_id'];
        
        $sql = "DELETE FROM bookings WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$booking_id]);
        
        $success_message = "Booking deleted successfully.";
    } elseif (isset($_POST['approve_trainer'])) {
        $trainer_id = $_POST['trainer_id'];
        
        $sql = "UPDATE trainers SET status = 'approved' WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$trainer_id]);
        
        $success_message = "Trainer approved successfully.";
        
        // Redirect to refresh the page
        header("Location: admin_dashboard.php?tab=applications");
        exit();
    } elseif (isset($_POST['reject_trainer'])) {
        $trainer_id = $_POST['trainer_id'];
        
        // Begin transaction
        $pdo->beginTransaction();
        
        try {
            // Get the image filename before deleting the trainer
            $sql = "SELECT image_url FROM trainers WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$trainer_id]);
            $trainer = $stmt->fetch();
            
            if ($trainer && $trainer['image_url']) {
                // Delete the image file
                $image_path = "../uploads/" . $trainer['image_url'];
                if (file_exists($image_path)) {
                    unlink($image_path);
                }
            }
            
            // Get user_id associated with this trainer
            $sql = "SELECT id FROM users WHERE trainer_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$trainer_id]);
            $user = $stmt->fetch();
            
            // First delete related records in trainer_availability
            $sql = "DELETE FROM trainer_availability WHERE trainer_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$trainer_id]);
            
            // Then delete any bookings associated with this trainer
            $sql = "DELETE FROM bookings WHERE trainer_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$trainer_id]);
            
            if ($user) {
                // Delete the user account
                $sql = "DELETE FROM users WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$user['id']]);
            }
            
            // Finally delete the trainer
            $sql = "DELETE FROM trainers WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$trainer_id]);
            
            $pdo->commit();
            $success_message = "Trainer application rejected.";
            
            // Redirect to refresh the page
            header("Location: admin_dashboard.php?tab=applications");
            exit();
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $error_message = "Error: " . $e->getMessage();
        }
    } elseif (isset($_POST['update_settings'])) {
        $cancellation_time = (int)$_POST['cancellation_time'] * 60; // Convert minutes to seconds
        
        // Check if setting already exists
        $sql = "SELECT * FROM settings WHERE setting_name = 'cancellation_time'";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $setting = $stmt->fetch();
        
        if ($setting) {
            // Update existing setting
            $sql = "UPDATE settings SET setting_value = ? WHERE setting_name = 'cancellation_time'";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$cancellation_time]);
        } else {
            // Insert new setting
            $sql = "INSERT INTO settings (setting_name, setting_value) VALUES ('cancellation_time', ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$cancellation_time]);
        }
        
        $success_message = "Settings updated successfully.";
    }
}

// Get active tab from URL or default to trainers
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'trainers';

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
        $active_tab = 'trainers'; // Ensure we're on the trainers tab when editing
    }
}

// Fetch approved trainers
$sql = "SELECT * FROM trainers WHERE status = 'approved' OR status IS NULL";
$stmt = $pdo->query($sql);
$trainers = $stmt->fetchAll();

// Fetch pending trainers
$sql = "SELECT trainers.*, users.email 
        FROM trainers 
        JOIN users ON trainers.id = users.trainer_id 
        WHERE trainers.status = 'pending'";
$stmt = $pdo->query($sql);
$pending_trainers = $stmt->fetchAll();

// Get selected trainer filter (if any)
$selected_trainer = isset($_GET['trainer_filter']) ? $_GET['trainer_filter'] : 'all';

// Fetch bookings based on filter
if ($selected_trainer == 'all') {
    $sql = "SELECT bookings.*, users.username, trainers.name AS trainer_name, trainers.id AS trainer_id 
            FROM bookings 
            JOIN users ON bookings.user_id = users.id 
            JOIN trainers ON bookings.trainer_id = trainers.id
            ORDER BY trainers.name, bookings.date DESC";
} else {
    $sql = "SELECT bookings.*, users.username, trainers.name AS trainer_name, trainers.id AS trainer_id 
            FROM bookings 
            JOIN users ON bookings.user_id = users.id 
            JOIN trainers ON bookings.trainer_id = trainers.id 
            WHERE trainers.id = ?
            ORDER BY bookings.date DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$selected_trainer]);
    $bookings = $stmt->fetchAll();
} 

if ($selected_trainer == 'all') {
    $stmt = $pdo->query($sql);
    $bookings = $stmt->fetchAll();
}

// Group bookings by trainer
$bookings_by_trainer = [];
foreach ($bookings as $booking) {
    $trainer_id = $booking['trainer_id'];
    $trainer_name = $booking['trainer_name'];
    
    if (!isset($bookings_by_trainer[$trainer_id])) {
        $bookings_by_trainer[$trainer_id] = [
            'name' => $trainer_name,
            'bookings' => []
        ];
    }
    
    $bookings_by_trainer[$trainer_id]['bookings'][] = $booking;
}

// Fetch current cancellation time setting
$sql = "SELECT * FROM settings WHERE setting_name = 'cancellation_time'";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$cancellation_setting = $stmt->fetch();
$cancellation_time = isset($cancellation_setting['setting_value']) ? (int)$cancellation_setting['setting_value'] / 60 : 1; // Convert seconds to minutes, default to 1 minute

// Fetch users for the users tab
$sql = "SELECT * FROM users ORDER BY username";
$stmt = $pdo->query($sql);
$users = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - GymBuddy</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin_dashboard.css">
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
        
        <!-- Tabbed Navigation -->
        <div class="tabs-container">
            <div class="tabs">
                <a href="?tab=trainers" class="tab <?php echo $active_tab == 'trainers' ? 'active' : ''; ?>">
                    <i class="fas fa-user-tie"></i> Trainers
                </a>
                <a href="?tab=applications" class="tab <?php echo $active_tab == 'applications' ? 'active' : ''; ?>">
                    <i class="fas fa-clipboard-list"></i> Applications
                    <?php if (!empty($pending_trainers)): ?>
                        <span class="badge"><?php echo count($pending_trainers); ?></span>
                    <?php endif; ?>
                </a>
                <a href="?tab=bookings" class="tab <?php echo $active_tab == 'bookings' ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-check"></i> Bookings
                </a>
                <a href="?tab=clients" class="tab <?php echo $active_tab == 'clients' ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i> Clients/Trainers
                </a>
                <a href="?tab=settings" class="tab <?php echo $active_tab == 'settings' ? 'active' : ''; ?>">
                    <i class="fas fa-cog"></i> Settings
                </a>
            </div>
            
            <!-- Tab Content -->
            <div class="tab-content">
                <!-- Trainers Tab -->
                <div id="trainers" class="tab-pane <?php echo $active_tab == 'trainers' ? 'active' : ''; ?>">
                    <div class="tab-header">
                        <h2><i class="fas fa-user-tie"></i> Trainers Management</h2>
                    </div>
                    
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Specialization</th>
                                            <th>Contact</th>
                                            <th>Image</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($trainers as $trainer): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($trainer['id']); ?></td>
                                            <td><?php echo htmlspecialchars($trainer['name']); ?></td>
                                            <td><?php echo htmlspecialchars($trainer['specialization']); ?></td>
                                            <td><?php echo htmlspecialchars($trainer['contact']); ?></td>
                                            <td><img src="../uploads/<?php echo htmlspecialchars($trainer['image_url']); ?>" alt="<?php echo htmlspecialchars($trainer['name']); ?>" width="50"></td>
                                            <td class="action-buttons">
                                                <a href="?tab=trainers&edit=<?php echo $trainer['id']; ?>" class="btn btn-edit">Edit</a>
                                                <form action="" method="post" style="display: inline;">
                                                    <input type="hidden" name="trainer_id" value="<?php echo $trainer['id']; ?>">
                                                    <input type="submit" name="delete_trainer" value="Delete" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this trainer?');">
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="form-container">
                                <?php if ($edit_mode): ?>
                                    <h3>Edit Trainer</h3>
                                    <form action="" method="post" enctype="multipart/form-data" class="add-form">
                                        <input type="hidden" name="trainer_id" value="<?php echo $trainer_to_edit['id']; ?>">
                                        <div class="form-group">
                                            <label for="name">Name:</label>
                                            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($trainer_to_edit['name']); ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="specialization">Specialization:</label>
                                            <input type="text" id="specialization" name="specialization" value="<?php echo htmlspecialchars($trainer_to_edit['specialization']); ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="contact">Contact:</label>
                                            <input type="text" id="contact" name="contact" value="<?php echo htmlspecialchars($trainer_to_edit['contact']); ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label>Current Image:</label>
                                            <div class="current-image">
                                                <img src="../uploads/<?php echo htmlspecialchars($trainer_to_edit['image_url']); ?>" alt="Current Image" width="100">
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label for="trainer_image">New Image:</label>
                                            <input type="file" id="trainer_image" name="trainer_image" accept="image/*">
                                            <p class="help-text">Leave empty to keep current image</p>
                                        </div>
                                        <div class="form-actions">
                                            <input type="submit" name="update_trainer" value="Update Trainer" class="btn btn-primary">
                                            <a href="admin_dashboard.php?tab=trainers" class="btn btn-secondary">Cancel</a>
                                        </div>
                                    </form>
                                <?php else: ?>
                                    <h3>Add New Trainer</h3>
                                    <form action="" method="post" enctype="multipart/form-data" class="add-form">
                                        <div class="form-group">
                                            <label for="name">Name:</label>
                                            <input type="text" id="name" name="name" placeholder="Enter trainer name" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="specialization">Specialization:</label>
                                            <input type="text" id="specialization" name="specialization" placeholder="Enter specialization" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="contact">Contact:</label>
                                            <input type="text" id="contact" name="contact" placeholder="Enter contact information" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="trainer_image">Image:</label>
                                            <input type="file" id="trainer_image" name="trainer_image" accept="image/*" required>
                                        </div>
                                        <div class="form-actions">
                                            <input type="submit" name="add_trainer" value="Add Trainer" class="btn btn-primary">
                                        </div>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Applications Tab -->
                <div id="applications" class="tab-pane <?php echo $active_tab == 'applications' ? 'active' : ''; ?>">
                    <div class="tab-header">
                        <h2><i class="fas fa-clipboard-list"></i> Trainer Applications</h2>
                    </div>
                    
                    <div class="card">
                        <div class="card-body">
                            <?php if (empty($pending_trainers)): ?>
                                <div class="empty-state">
                                    <i class="fas fa-clipboard-check empty-icon"></i>
                                    <p>No pending trainer applications.</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table>
                                        <thead>
                                            <tr>
                                                <th>Name</th>
                                                <th>Email</th>
                                                <th>Specialization</th>
                                                <th>Contact</th>
                                                <th>Image</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($pending_trainers as $trainer): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($trainer['name']); ?></td>
                                                <td><?php echo htmlspecialchars($trainer['email']); ?></td>
                                                <td><?php echo htmlspecialchars($trainer['specialization']); ?></td>
                                                <td><?php echo htmlspecialchars($trainer['contact']); ?></td>
                                                <td><img src="../uploads/<?php echo htmlspecialchars($trainer['image_url']); ?>" alt="<?php echo htmlspecialchars($trainer['name']); ?>" width="50"></td>
                                                <td class="action-buttons">
                                                    <form action="" method="post" style="display: inline;">
                                                        <input type="hidden" name="trainer_id" value="<?php echo $trainer['id']; ?>">
                                                        <input type="submit" name="approve_trainer" value="Approve" class="btn btn-success">
                                                        <input type="submit" name="reject_trainer" value="Reject" class="btn btn-danger" onclick="return confirm('Are you sure you want to reject this trainer application? This will delete their account.');">
                                                    </form>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Bookings Tab -->
                <div id="bookings" class="tab-pane <?php echo $active_tab == 'bookings' ? 'active' : ''; ?>">
                    <div class="tab-header">
                        <h2><i class="fas fa-calendar-check"></i> Bookings Management</h2>
                    </div>
                    
                    <div class="card">
                        <div class="card-body">
                            <!-- Trainer Filter Dropdown -->
                            <div class="filter-container">
                                <form action="" method="get" class="filter-form">
                                    <input type="hidden" name="tab" value="bookings">
                                    <div class="form-group">
                                        <label for="trainer_filter">Filter by Trainer:</label>
                                        <div class="select-wrapper">
                                            <select name="trainer_filter" id="trainer_filter">
                                                <option value="all" <?php echo $selected_trainer == 'all' ? 'selected' : '' ?>>All Trainers</option>
                                                <?php foreach ($trainers as $trainer): ?>
                                                    <option value="<?php echo $trainer['id']; ?>" <?php echo $selected_trainer == $trainer['id'] ? 'selected' : ''; ?>>                                                     
                                                        <?php echo htmlspecialchars($trainer['name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <button type="submit" class="btn btn-filter">Apply Filter</button>
                                    </div>
                                </form>
                            </div>
                            
                            <?php if (empty($bookings)): ?>
                                <div class="empty-state">
                                    <i class="fas fa-calendar-times empty-icon"></i>
                                    <p>No bookings found.</p>
                                </div>
                            <?php else: ?>
                                <?php if ($selected_trainer == 'all'): ?>
                                    <!-- Display bookings grouped by trainer -->
                                    <div class="accordion-container">
                                        <?php foreach ($bookings_by_trainer as $trainer_id => $trainer_data): ?>
                                            <div class="accordion">
                                                <div class="accordion-header" onclick="toggleAccordion('trainer-<?php echo $trainer_id; ?>')">
                                                    <h3><?php echo htmlspecialchars($trainer_data['name']); ?>'s Bookings</h3>
                                                    <i class="fas fa-chevron-down accordion-icon"></i>
                                                </div>
                                                <div id="trainer-<?php echo $trainer_id; ?>" class="accordion-content">
                                                    <div class="table-responsive">
                                                        <table class="booking-table">
                                                            <thead>
                                                                <tr>
                                                                    <th>ID</th>
                                                                    <th>User</th>
                                                                    <th>Date</th>
                                                                    <th>Time</th>
                                                                    <th>Status</th>
                                                                    <th>Payment</th>
                                                                    <th>Action</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php foreach ($trainer_data['bookings'] as $booking): ?>
                                                                <tr>
                                                                    <td><?php echo htmlspecialchars($booking['id']); ?></td>
                                                                    <td><?php echo htmlspecialchars($booking['username']); ?></td>
                                                                    <td><?php echo htmlspecialchars($booking['date']); ?></td>
                                                                    <td><?php echo htmlspecialchars($booking['time']); ?></td>
                                                                    <td><span class="status-badge status-<?php echo strtolower($booking['status']); ?>"><?php echo htmlspecialchars($booking['status']); ?></span></td>
                                                                    <td><span class="payment-badge payment-<?php echo strtolower($booking['payment_status'] ?? 'na'); ?>"><?php echo htmlspecialchars($booking['payment_status'] ?? 'N/A'); ?></span></td>
                                                                    <td>
                                                                        <form action="" method="post">
                                                                            <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                                                            <button type="submit" name="delete_booking" class="btn-icon btn-delete" onclick="return confirm('Are you sure you want to delete this booking?');">
                                                                                <i class="fas fa-trash-alt"></i>
                                                                            </button>
                                                                        </form>
                                                                    </td>
                                                                </tr>
                                                                <?php endforeach; ?>
                                                            </tbody>
                                                          
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <!-- Display bookings for a specific trainer -->
                                    <div class="table-responsive">
                                        <table class="booking-table">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>User</th>
                                                    <th>Date</th>
                                                    <th>Time</th>
                                                    <th>Status</th>
                                                    <th>Payment</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($bookings as $booking): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($booking['id']); ?></td>
                                                    <td><?php echo htmlspecialchars($booking['username']); ?></td>
                                                    <td><?php echo htmlspecialchars($booking['date']); ?></td>
                                                    <td><?php echo htmlspecialchars($booking['time']); ?></td>
                                                    <td><span class="status-badge status-<?php echo strtolower($booking['status']); ?>"><?php echo htmlspecialchars($booking['status']); ?></span></td>
                                                    <td><span class="payment-badge payment-<?php echo strtolower($booking['payment_status'] ?? 'na'); ?>"><?php echo htmlspecialchars($booking['payment_status'] ?? 'N/A'); ?></span></td>
                                                    <td>
                                                        <form action="" method="post">
                                                            <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                                            <button type="submit" name="delete_booking" class="btn-icon btn-delete" onclick="return confirm('Are you sure you want to delete this booking?');">
                                                                <i class="fas fa-trash-alt"></i>
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Users Tab -->
                <div id="clients" class="tab-pane <?php echo $active_tab == 'clients' ? 'active' : ''; ?>">
                    <div class="tab-header">
                        <h2><i class="fas fa-users"></i> Clients/Trainers Management</h2>
                    </div>
                    
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Username</th>
                                            <th>Email</th>
                                            <th>Role</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($user['id']); ?></td>
                                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td><span class="role-badge role-<?php echo strtolower($user['role']); ?>">
                                            <?php echo htmlspecialchars($user['role'] === 'user' ? 'client' : $user['role']); ?>
                                            </span></td>
                                            
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Settings Tab -->
                <div id="settings" class="tab-pane <?php echo $active_tab == 'settings' ? 'active' : ''; ?>">
                    <div class="tab-header">
                        <h2><i class="fas fa-cog"></i> System Settings</h2>
                    </div>
                    
                    <div class="card">
                        <div class="card-body">
                            <form action="" method="post" class="settings-form">
                                <div class="form-group">
                                    <label for="cancellation_time">Booking Cancellation Time (minutes):</label>
                                    <input type="number" id="cancellation_time" name="cancellation_time" value="<?php echo $cancellation_time; ?>" min="1" required>
                                    <p class="help-text">This is the time window (in minutes) during which users can cancel their bookings after creation.</p>
                                </div>
                                <div class="form-actions">
                                    <input type="submit" name="update_settings" value="Update Settings" class="btn btn-primary">
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer>
        <div class="footer-content">
            <div class="footer-section">
                <h3>Quick Links</h3>
                <ul>
                    <li><a href="../index.php">Home</a></li>
                    <li><a href="../trainers.php">Trainers</a></li>
                    <li><a href="../videos.php">Videos</a></li>
                    <li><a href="../about.php">About Us</a></li>
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

    <script>
        // Function to toggle accordion content
        function toggleAccordion(id) {
            const content = document.getElementById(id);
            const header = content.previousElementSibling;
            const icon = header.querySelector('.accordion-icon');
            
            if (content.style.maxHeight) {
                content.style.maxHeight = null;
                icon.classList.remove('fa-chevron-up');
                icon.classList.add('fa-chevron-down');
            } else {
                content.style.maxHeight = content.scrollHeight + "px";
                icon.classList.remove('fa-chevron-down');
                icon.classList.add('fa-chevron-up');
            }
        }
        
        // Open the first accordion by default in the bookings tab
        document.addEventListener('DOMContentLoaded', function() {
            const activeTab = '<?php echo $active_tab; ?>';
            if (activeTab === 'bookings') {
                const firstAccordion = document.querySelector('.accordion-content');
                if (firstAccordion) {
                    const firstHeader = firstAccordion.previousElementSibling;
                    const firstIcon = firstHeader.querySelector('.accordion-icon');
                    firstAccordion.style.maxHeight = firstAccordion.scrollHeight + "px";
                    firstIcon.classList.remove('fa-chevron-down');
                    firstIcon.classList.add('fa-chevron-up');
                }
            }
        });
    </script>
</body>
</html>

