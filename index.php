<?php
session_start();
include 'php/config.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GymBuddy - Book a trainer, anytime, anywhere</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/user_dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
</head>
<body>
    <header>
        <nav>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="trainers.php">Trainers</a></li>
                <li><a href="videos.php">Videos</a></li>
                <li><a href="about.php">About Us</a></li>
                <?php if(isset($_SESSION['user_id'])): ?>
                    <?php if(isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                        <li><a href="php/admin_dashboard.php">Admin Dashboard</a></li>
                    <?php elseif(isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'trainer'): ?>
                        <li><a href="php/trainer_dashboard.php">Trainer Dashboard</a></li>
                    <?php elseif(isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'user'): ?>
                        <li><a href="php/user_dashboard.php">My Bookings</a></li>
                    <?php endif; ?>
                    <li><a href="php/logout.php">Logout</a></li>
                <?php else: ?>
                    <li><a href="php/login.php">Login</a></li>
                    <li><a href="php/signup.php">Sign Up</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>

    <main>
    <section class="home" id="home">
       <div class="content">
           <h3>Gym Buddy</h3>
           <span>Book a trainer, Anytime, Anywhere</span> 
           <p class="simple-shadow">Your ultimate fitness hub, designed to help you take control of your journey. Book expert trainers, follow workout paths tailored to your goals, discover nearby gyms with all the details you need, and watch high-quality workout tutorials. Start building your best self with us, wherever and however you like.</p>
           <a href="trainers.php" class="cta-button">Find a Trainer</a>
           <?php if(isset($_SESSION['user_id']) && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'user'): ?>
               <a href="#" class="cta-button" id="bmiTrackerBtn" style="margin-top: 10px;">BMI TRACKER</a>
           <?php endif; ?>
       </div>
      
   </section>
        
        <section class="features">
            <div class="feature">
                <i class="fas fa-dumbbell"></i>
                <h2>Expert Trainers</h2>
                <p>Connect with certified fitness professionals</p>
            </div>
            <div class="feature">
                <i class="fas fa-calendar-alt"></i>
                <h2>Flexible Scheduling</h2>
                <p>Book sessions that fit your lifestyle</p>
            </div>
            <div class="feature">
                <i class="fas fa-user-friends"></i>
                <h2>Train Your Way</h2>
                <p>Choose in-person or virtual sessions for maximum convenience</p>
            </div>
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
    <script src="js/script.js"></script>
    
    <?php if(isset($_SESSION['user_id']) && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'user'): ?>
<div id="bmiTrackerModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2><i class="fas fa-weight"></i> BMI Tracker</h2>
        
        <div class="bmi-form">
            <form id="weightForm">
                <div>
                    <label for="height">Height (cm):</label>
                    <input type="number" id="height" name="height" min="100" max="250" required>
                </div>
                <div>
                    <label for="weight">Weight (kg):</label>
                    <input type="number" id="weight" name="weight" min="30" max="300" step="0.1" required>
                </div>
                <button type="submit" class="cta-button">Track Weight</button>
            </form>
        </div>
        
        <div id="bmiResult" class="bmi-result"></div>
        
        <h3>Weight History</h3>
        <div id="weightHistory" class="weight-history">
            <p>Loading weight history...</p>
        </div>
    </div>
</div>

<script>
    // BMI Tracker Modal Functionality
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('bmiTrackerModal');
        const btn = document.getElementById('bmiTrackerBtn');
        const closeBtn = document.getElementsByClassName('close')[0];
        const weightForm = document.getElementById('weightForm');
        const weightHistory = document.getElementById('weightHistory');
        const bmiResult = document.getElementById('bmiResult');
        
        // Load weight history from database
        function loadWeightHistory() {
            fetch('php/bmi_ajax.php?action=history')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.history) {
                        if (data.history.length === 0) {
                            weightHistory.innerHTML = '<p>No weight entries yet. Start tracking today!</p>';
                            return;
                        }
                        
                        weightHistory.innerHTML = '';
                        data.history.forEach(entry => {
                            const entryDiv = document.createElement('div');
                            entryDiv.className = 'weight-entry';
                            entryDiv.innerHTML = `
                                <span>${new Date(entry.date_recorded).toLocaleDateString()}</span>
                                <span>${entry.weight} kg</span>
                                <span>BMI: ${parseFloat(entry.bmi).toFixed(1)}</span>
                            `;
                            weightHistory.appendChild(entryDiv);
                        });
                    } else {
                        weightHistory.innerHTML = '<p>Error loading weight history.</p>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    weightHistory.innerHTML = '<p>Error loading weight history.</p>';
                });
        }
        
        // Open modal
        btn.onclick = function() {
            modal.style.display = 'block';
            loadWeightHistory();
        }
        
        // Close modal
        closeBtn.onclick = function() {
            modal.style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        }
        
        // Handle form submission
        weightForm.onsubmit = function(e) {
            e.preventDefault();
            
            const height = parseFloat(document.getElementById('height').value);
            const weight = parseFloat(document.getElementById('weight').value);
            
            if (!height || !weight) return;
            
            // Create form data
            const formData = new FormData();
            formData.append('action', 'save');
            formData.append('height', height);
            formData.append('weight', weight);
            
            // Send data to server
            fetch('php/bmi_ajax.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Display BMI result
                    bmiResult.innerHTML = `
                        <p>Your BMI: <strong>${data.bmi}</strong></p>
                        <p>Category: <strong>${data.category.category}</strong></p>
                    `;
                    bmiResult.style.backgroundColor = data.category.color + '30'; // Add transparency
                    bmiResult.style.borderLeft = `4px solid ${data.category.color}`;
                    
                    // Reload weight history
                    loadWeightHistory();
                } else {
                    bmiResult.innerHTML = `<p>Error: ${data.error || 'Failed to save data'}</p>`;
                    bmiResult.style.backgroundColor = '#ff000030';
                    bmiResult.style.borderLeft = '4px solid #ff0000';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                bmiResult.innerHTML = '<p>Error: Failed to save data</p>';
                bmiResult.style.backgroundColor = '#ff000030';
                bmiResult.style.borderLeft = '4px solid #ff0000';
            });
        }
        
        // Initialize
        loadWeightHistory();
    });
</script>
<?php endif; ?>
</body>
</html>