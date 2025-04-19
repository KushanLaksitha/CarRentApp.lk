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

// Get statistics based on user role
$stats = [];

if ($user['user_role'] == 'customer') {
    // Customer's total bookings
    $result = $conn->query("SELECT COUNT(*) as total FROM bookings WHERE user_id = $user_id");
    $stats['total_bookings'] = $result->fetch_assoc()['total'];
    
    // Customer's upcoming bookings
    $result = $conn->query("SELECT COUNT(*) as upcoming FROM bookings WHERE user_id = $user_id AND booking_status = 'confirmed' AND pickup_date > NOW()");
    $stats['upcoming_bookings'] = $result->fetch_assoc()['upcoming'];
    
    // Customer's recent bookings
    $recent_bookings = $conn->query("
        SELECT b.*, c.brand, c.model 
        FROM bookings b 
        JOIN cars c ON b.car_id = c.car_id 
        WHERE b.user_id = $user_id 
        ORDER BY b.booking_date DESC 
        LIMIT 5
    ");
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Kushan Car Rental</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/dashboard.css">
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
                    <a class="nav-link active" href="dashboard.php">
                        <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                    </a>
                    <a class="nav-link" href="browse_cars.php">
                        <i class="fas fa-car me-2"></i> Browse Cars
                    </a>
                    <a class="nav-link" href="my_bookings.php">
                        <i class="fas fa-calendar-check me-2"></i> My Bookings
                    </a>
                    <a class="nav-link" href="my_profile.php">
                        <i class="fas fa-user me-2"></i> My Profile
                    </a>
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
                    <h1>Welcome, <?php echo htmlspecialchars($user['full_name']); ?>!</h1>
                    <p class="text-muted">Here's an overview of your <?php echo ucfirst($user['user_role']); ?> dashboard</p>
                    
                    <?php if ($user['user_role'] == 'admin'): ?>
                    <!-- Admin Dashboard -->
                    <div class="row">
                        <div class="col-xl-3 col-md-6">
                            <div class="card stats-card primary">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Cars</div>
                                            <div class="h5 mb-0 font-weight-bold"><?php echo $stats['total_cars']; ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-car fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-3 col-md-6">
                            <div class="card stats-card success">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Available Cars</div>
                                            <div class="h5 mb-0 font-weight-bold"><?php echo $stats['available_cars']; ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-3 col-md-6">
                            <div class="card stats-card info">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total Bookings</div>
                                            <div class="h5 mb-0 font-weight-bold"><?php echo $stats['total_bookings']; ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-calendar-alt fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-3 col-md-6">
                            <div class="card stats-card warning">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Total Revenue (LKR)</div>
                                            <div class="h5 mb-0 font-weight-bold"><?php echo number_format($stats['total_revenue'], 2); ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Recent Bookings Table -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <h6 class="m-0">Recent Bookings</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Customer</th>
                                            <th>Car</th>
                                            <th>Pickup Date</th>
                                            <th>Return Date</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($booking = $recent_bookings->fetch_assoc()): ?>
                                        <tr>
                                            <td>#<?php echo $booking['booking_id']; ?></td>
                                            <td><?php echo htmlspecialchars($booking['full_name']); ?></td>
                                            <td><?php echo htmlspecialchars($booking['brand'] . ' ' . $booking['model']); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($booking['pickup_date'])); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($booking['return_date'])); ?></td>
                                            <td>
                                                <?php
                                                $statusClass = 'secondary';
                                                switch ($booking['booking_status']) {
                                                    case 'confirmed': $statusClass = 'success'; break;
                                                    case 'cancelled': $statusClass = 'danger'; break;
                                                    case 'completed': $statusClass = 'primary'; break;
                                                }
                                                ?>
                                                <span class="badge bg-<?php echo $statusClass; ?>"><?php echo ucfirst($booking['booking_status']); ?></span>
                                            </td>
                                            <td>
                                                <a href="view_booking.php?id=<?php echo $booking['booking_id']; ?>" class="btn btn-sm btn-primary">View</a>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <?php elseif ($user['user_role'] == 'customer'): ?>
                    <!-- Customer Dashboard -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card stats-card primary">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Bookings</div>
                                            <div class="h5 mb-0 font-weight-bold"><?php echo $stats['total_bookings']; ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-calendar-check fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card stats-card success">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Upcoming Bookings</div>
                                            <div class="h5 mb-0 font-weight-bold"><?php echo $stats['upcoming_bookings']; ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body text-center">
                                    <i class="fas fa-car fa-3x mb-3 text-primary"></i>
                                    <h5 class="card-title">Browse Cars</h5>
                                    <p class="card-text">Find the perfect car for your needs</p>
                                    <a href="browse_cars.php" class="btn btn-primary">Browse Now</a>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body text-center">
                                    <i class="fas fa-history fa-3x mb-3 text-success"></i>
                                    <h5 class="card-title">Booking History</h5>
                                    <p class="card-text">View your past and upcoming rentals</p>
                                    <a href="my_bookings.php" class="btn btn-success">View History</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- My Recent Bookings -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <h6 class="m-0">My Recent Bookings</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Car</th>
                                            <th>Pickup Date</th>
                                            <th>Return Date</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($booking = $recent_bookings->fetch_assoc()): ?>
                                        <tr>
                                            <td>#<?php echo $booking['booking_id']; ?></td>
                                            <td><?php echo htmlspecialchars($booking['brand'] . ' ' . $booking['model']); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($booking['pickup_date'])); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($booking['return_date'])); ?></td>
                                            <td>
                                                <?php
                                                $statusClass = 'secondary';
                                                switch ($booking['booking_status']) {
                                                    case 'confirmed': $statusClass = 'success'; break;
                                                    case 'cancelled': $statusClass = 'danger'; break;
                                                    case 'completed': $statusClass = 'primary'; break;
                                                }
                                                ?>
                                                <span class="badge bg-<?php echo $statusClass; ?>"><?php echo ucfirst($booking['booking_status']); ?></span>
                                            </td>
                                            <td>
                                                <a href="view_booking.php?id=<?php echo $booking['booking_id']; ?>" class="btn btn-sm btn-primary">View</a>
                                                <?php if ($booking['booking_status'] == 'confirmed' && strtotime($booking['pickup_date']) > time()): ?>
                                                <a href="cancel_booking.php?id=<?php echo $booking['booking_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to cancel this booking?')">Cancel</a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
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