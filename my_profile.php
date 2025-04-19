<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Database connection
require_once 'includes/db_connection.php';

// Get user information
$user_id = $_SESSION['user_id'];
$query = "SELECT * FROM users WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $driving_license = $_POST['driving_license'];
    
    // Update user profile
    $update_query = "UPDATE users SET full_name = ?, email = ?, phone = ?, address = ?, driving_license = ? WHERE user_id = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("sssssi", $full_name, $email, $phone, $address, $driving_license, $user_id);
    
    if ($update_stmt->execute()) {
        $success_message = "Profile updated successfully!";
        // Refresh user data
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
    } else {
        $error_message = "Error updating profile. Please try again.";
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Verify current password
    if (password_verify($current_password, $user['password'])) {
        if ($new_password === $confirm_password) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $password_query = "UPDATE users SET password = ? WHERE user_id = ?";
            $password_stmt = $conn->prepare($password_query);
            $password_stmt->bind_param("si", $hashed_password, $user_id);
            
            if ($password_stmt->execute()) {
                $success_message = "Password changed successfully!";
            } else {
                $error_message = "Error changing password. Please try again.";
            }
        } else {
            $error_message = "New passwords do not match.";
        }
    } else {
        $error_message = "Current password is incorrect.";
    }
}

// Get user's booking statistics
$stats_query = "SELECT 
    COUNT(*) as total_bookings,
    SUM(CASE WHEN booking_status = 'completed' THEN 1 ELSE 0 END) as completed_bookings,
    SUM(CASE WHEN booking_status = 'confirmed' AND pickup_date > NOW() THEN 1 ELSE 0 END) as upcoming_bookings,
    SUM(CASE WHEN booking_status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_bookings,
    SUM(total_amount) as total_spent
    FROM bookings WHERE user_id = ?";
$stats_stmt = $conn->prepare($stats_query);
$stats_stmt->bind_param("i", $user_id);
$stats_stmt->execute();
$booking_stats = $stats_stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Kushan Car Rental</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
     <link rel="stylesheet" href="css/my_profile.css">
    <link rel="stylesheet" href="css/footer.css">
    
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0 sidebar">
                <div class="p-3 text-center">
                    <img src="assets/images/logo.png" alt="Kushan Car Rental" class="company-logo">
                    <h5>KUSHAN CAR RENTAL</h5>
                </div>
                <nav class="nav flex-column">
                    <a class="nav-link" href="dashboard.php">
                        <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                    </a>
                    <?php if ($user['user_role'] == 'admin'): ?>
                    <a class="nav-link" href="cars.php">
                        <i class="fas fa-car me-2"></i> Cars Management
                    </a>
                    <a class="nav-link" href="bookings.php">
                        <i class="fas fa-calendar-alt me-2"></i> Bookings
                    </a>
                    <a class="nav-link" href="users.php">
                        <i class="fas fa-users me-2"></i> Users
                    </a>
                    <a class="nav-link" href="payments.php">
                        <i class="fas fa-money-bill me-2"></i> Payments
                    </a>
                    <a class="nav-link" href="reports.php">
                        <i class="fas fa-chart-bar me-2"></i> Reports
                    </a>
                    <?php elseif ($user['user_role'] == 'customer'): ?>
                    <a class="nav-link" href="browse_cars.php">
                        <i class="fas fa-car me-2"></i> Browse Cars
                    </a>
                    <a class="nav-link" href="my_bookings.php">
                        <i class="fas fa-calendar-check me-2"></i> My Bookings
                    </a>
                    <a class="nav-link active" href="my_profile.php">
                        <i class="fas fa-user me-2"></i> My Profile
                    </a>
                    <?php endif; ?>
                    <a class="nav-link" href="auth/logout.php">
                        <i class="fas fa-sign-out-alt me-2"></i> Logout
                    </a>
                </nav>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 px-0">
                <!-- Navbar -->
                <nav class="navbar navbar-expand-lg navbar-light">
                    <div class="container-fluid">
                        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                            <span class="navbar-toggler-icon"></span>
                        </button>
                        <div class="collapse navbar-collapse" id="navbarNav">
                            <ul class="navbar-nav ms-auto">
                                <li class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                                        <i class="fas fa-user-circle me-2"></i><?php echo htmlspecialchars($user['full_name']); ?>
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li><a class="dropdown-item" href="my_profile.php">Profile</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item" href="auth/logout.php">Logout</a></li>
                                    </ul>
                                </li>
                            </ul>
                        </div>
                    </div>
                </nav>
                
                <!-- Main Content Area -->
                <div class="main-content">
                    <?php if(isset($success_message)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $success_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php endif; ?>
                    
                    <?php if(isset($error_message)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $error_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php endif; ?>
                    
                    <div class="row">
                        <!-- Profile Overview -->
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body text-center">
                                    <img src="https://www.gravatar.com/avatar/<?php echo md5(strtolower(trim($user['email']))); ?>?s=150&d=mp" alt="Profile Picture" class="profile-image mb-3">
                                    <h4><?php echo htmlspecialchars($user['full_name']); ?></h4>
                                    <p class="text-muted"><?php echo ucfirst($user['user_role']); ?></p>
                                    <hr>
                                    <p><i class="fas fa-envelope me-2"></i><?php echo htmlspecialchars($user['email']); ?></p>
                                    <p><i class="fas fa-phone me-2"></i><?php echo htmlspecialchars($user['phone']); ?></p>
                                    <p><i class="fas fa-calendar-alt me-2"></i>Member since <?php echo date('M Y', strtotime($user['created_at'])); ?></p>
                                </div>
                            </div>
                            
                            <!-- Booking Statistics -->
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="m-0">Booking Statistics</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-6">
                                            <div class="stats-box">
                                                <div class="stats-icon text-primary">
                                                    <i class="fas fa-car"></i>
                                                </div>
                                                <h4 class="mb-0"><?php echo $booking_stats['total_bookings']; ?></h4>
                                                <small class="text-muted">Total Bookings</small>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="stats-box">
                                                <div class="stats-icon text-success">
                                                    <i class="fas fa-check-circle"></i>
                                                </div>
                                                <h4 class="mb-0"><?php echo $booking_stats['completed_bookings']; ?></h4>
                                                <small class="text-muted">Completed</small>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="stats-box">
                                                <div class="stats-icon text-info">
                                                    <i class="fas fa-clock"></i>
                                                </div>
                                                <h4 class="mb-0"><?php echo $booking_stats['upcoming_bookings']; ?></h4>
                                                <small class="text-muted">Upcoming</small>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="stats-box">
                                                <div class="stats-icon text-warning">
                                                    <i class="fas fa-money-bill"></i>
                                                </div>
                                                <h4 class="mb-0">LKR <?php echo number_format($booking_stats['total_spent'], 2); ?></h4>
                                                <small class="text-muted">Total Spent</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Profile Edit Forms -->
                        <div class="col-md-8">
                            <!-- Personal Information -->
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="m-0">Personal Information</h6>
                                </div>
                                <div class="card-body">
                                    <form method="POST">
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label for="full_name" class="form-label">Full Name</label>
                                                <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="email" class="form-label">Email Address</label>
                                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label for="phone" class="form-label">Phone Number</label>
                                                <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="nic_number" class="form-label">NIC Number</label>
                                                <input type="text" class="form-control" id="nic_number" value="<?php echo htmlspecialchars($user['nic_number']); ?>" readonly>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="address" class="form-label">Address</label>
                                            <textarea class="form-control" id="address" name="address" rows="3"><?php echo htmlspecialchars($user['address']); ?></textarea>
                                        </div>
                                        <div class="mb-3">
                                            <label for="driving_license" class="form-label">Driving License Number</label>
                                            <input type="text" class="form-control" id="driving_license" name="driving_license" value="<?php echo htmlspecialchars($user['driving_license']); ?>">
                                        </div>
                                        <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
                                    </form>
                                </div>
                            </div>
                            
                            <!-- Change Password -->
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="m-0">Change Password</h6>
                                </div>
                                <div class="card-body">
                                    <form method="POST">
                                        <div class="mb-3">
                                            <label for="current_password" class="form-label">Current Password</label>
                                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="new_password" class="form-label">New Password</label>
                                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                        </div>
                                        <button type="submit" name="change_password" class="btn btn-primary">Change Password</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Footer -->
<footer class="footer mt-auto py-3">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-4">
                <h5>Kushan Car Rental</h5>
                <p>Your trusted partner for reliable car rentals in Sri Lanka. Offering a wide range of vehicles for all your transportation needs.</p>
                <div class="social-links">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </div>
            <div class="col-md-4">
                <h5>Quick Links</h5>
                <ul class="footer-links">
                    <li><a href="browse_cars.php"><i class="fas fa-angle-right"></i> Browse Cars</a></li>
                    <li><a href="my_bookings.php"><i class="fas fa-angle-right"></i> My Bookings</a></li>
                    <li><a href="contact.php"><i class="fas fa-angle-right"></i> Contact Us</a></li>
                </ul>
            </div>
            <div class="col-md-4">
                <h5>Contact Information</h5>
                <ul class="contact-info">
                    <li><i class="fas fa-map-marker-alt"></i> 123 Main Street, Colombo, Sri Lanka</li>
                    <li><i class="fas fa-phone"></i> +94 11 234 5678</li>
                    <li><i class="fas fa-envelope"></i> info@kushancarrental.com</li>
                    <li><i class="fas fa-clock"></i> Mon - Fri: 8:00 AM - 6:00 PM</li>
                </ul>
            </div>
        </div>
        <hr>
        <div class="row">
            <div class="col-md-6">

                <p class="copyright">&copy; <?php echo date('Y'); ?> Kushan Car Rental. All Rights Reserved.</p>
                                                
            </div>
            
        </div>
    </div>
</footer>
    <!-- Bootstrap JS and dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/view.js"></script>
</body>
</html>