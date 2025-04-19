<?php
session_start();

// Check if user is logged in and is a customer
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'customer') {
    header("Location: index.php");
    exit();
}

// Database connection
require_once 'includes/db_connection.php';

// Get user information
$user_id = $_SESSION['user_id'];

// Get filter parameters if any
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$date_filter = isset($_GET['date']) ? $_GET['date'] : '';

// Build query with filters
$query = "SELECT b.*, c.brand, c.model, c.registration_number, c.image_url, c.daily_rate 
          FROM bookings b 
          JOIN cars c ON b.car_id = c.car_id 
          WHERE b.user_id = ?";

$params = [$user_id];
$param_types = "i";

// Add status filter
if (!empty($status_filter)) {
    $query .= " AND b.booking_status = ?";
    $params[] = $status_filter;
    $param_types .= "s";
}

// Add date filter
if (!empty($date_filter)) {
    switch ($date_filter) {
        case 'upcoming':
            $query .= " AND b.pickup_date > NOW()";
            break;
        case 'past':
            $query .= " AND b.return_date < NOW()";
            break;
        case 'current':
            $query .= " AND b.pickup_date <= NOW() AND b.return_date >= NOW()";
            break;
    }
}

$query .= " ORDER BY b.booking_date DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param($param_types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings - Kushan Car Rental</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
     <link rel="stylesheet" href="css/my_booking.css">
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
                    <a class="nav-link" href="browse_cars.php">
                        <i class="fas fa-car me-2"></i> Browse Cars
                    </a>
                    <a class="nav-link active" href="my_bookings.php">
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
                                        <i class="fas fa-user-circle me-2"></i><?php echo htmlspecialchars($_SESSION['full_name']); ?>
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
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h1>My Bookings</h1>
                        <a href="browse_cars.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>New Booking
                        </a>
                    </div>
                    
                    <!-- Filters -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-6">
                                    <h6 class="mb-3">Filter by Date:</h6>
                                    <div class="btn-group" role="group">
                                        <a href="my_bookings.php" class="btn btn-outline-primary <?php echo empty($date_filter) ? 'active' : ''; ?>">All</a>
                                        <a href="my_bookings.php?date=upcoming" class="btn btn-outline-primary <?php echo $date_filter == 'upcoming' ? 'active' : ''; ?>">Upcoming</a>
                                        <a href="my_bookings.php?date=current" class="btn btn-outline-primary <?php echo $date_filter == 'current' ? 'active' : ''; ?>">Current</a>
                                        <a href="my_bookings.php?date=past" class="btn btn-outline-primary <?php echo $date_filter == 'past' ? 'active' : ''; ?>">Past</a>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="mb-3">Filter by Status:</h6>
                                    <div class="btn-group" role="group">
                                        <a href="my_bookings.php<?php echo !empty($date_filter) ? '?date='.$date_filter : ''; ?>" class="btn btn-outline-primary <?php echo empty($status_filter) ? 'active' : ''; ?>">All</a>
                                        <a href="my_bookings.php?status=pending<?php echo !empty($date_filter) ? '&date='.$date_filter : ''; ?>" class="btn btn-outline-warning <?php echo $status_filter == 'pending' ? 'active' : ''; ?>">Pending</a>
                                        <a href="my_bookings.php?status=confirmed<?php echo !empty($date_filter) ? '&date='.$date_filter : ''; ?>" class="btn btn-outline-success <?php echo $status_filter == 'confirmed' ? 'active' : ''; ?>">Confirmed</a>
                                        <a href="my_bookings.php?status=completed<?php echo !empty($date_filter) ? '&date='.$date_filter : ''; ?>" class="btn btn-outline-primary <?php echo $status_filter == 'completed' ? 'active' : ''; ?>">Completed</a>
                                        <a href="my_bookings.php?status=cancelled<?php echo !empty($date_filter) ? '&date='.$date_filter : ''; ?>" class="btn btn-outline-danger <?php echo $status_filter == 'cancelled' ? 'active' : ''; ?>">Cancelled</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Bookings List -->
                    <div class="row">
                        <?php if ($result->num_rows > 0): ?>
                            <?php while ($booking = $result->fetch_assoc()): ?>
                                <div class="col-md-6 col-lg-4">
                                    <div class="card booking-card">
                                        <div class="position-relative">
                                            <img src="<?php echo $booking['image_url'] ?? 'assets/images/default-car.jpg'; ?>" 
                                                 class="car-thumbnail" 
                                                 alt="<?php echo htmlspecialchars($booking['brand'] . ' ' . $booking['model']); ?>">
                                            <?php
                                            $statusClass = 'secondary';
                                            switch ($booking['booking_status']) {
                                                case 'pending':
                                                    $statusClass = 'warning';
                                                    break;
                                                case 'confirmed':
                                                    $statusClass = 'success';
                                                    break;
                                                case 'completed':
                                                    $statusClass = 'primary';
                                                    break;
                                                case 'cancelled':
                                                    $statusClass = 'danger';
                                                    break;
                                            }
                                            ?>
                                            <span class="status-badge badge bg-<?php echo $statusClass; ?>">
                                                <?php echo ucfirst($booking['booking_status']); ?>
                                            </span>
                                        </div>
                                        <div class="card-body">
                                            <h5 class="card-title"><?php echo htmlspecialchars($booking['brand'] . ' ' . $booking['model']); ?></h5>
                                            <p class="card-text text-muted mb-2">
                                                <i class="fas fa-hashtag me-2"></i><?php echo htmlspecialchars($booking['registration_number']); ?>
                                            </p>
                                            <p class="card-text mb-2">
                                                <i class="fas fa-calendar-alt me-2 text-primary"></i>
                                                <?php echo date('M d, Y H:i', strtotime($booking['pickup_date'])); ?> -
                                                <?php echo date('M d, Y H:i', strtotime($booking['return_date'])); ?>
                                            </p>
                                            <p class="card-text mb-2">
                                                <i class="fas fa-map-marker-alt me-2 text-danger"></i>
                                                Pickup: <?php echo htmlspecialchars($booking['pickup_location']); ?>
                                            </p>
                                            <p class="card-text mb-3">
                                                <i class="fas fa-map-marker-alt me-2 text-success"></i>
                                                Return: <?php echo htmlspecialchars($booking['return_location']); ?>
                                            </p>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="h5 mb-0">LKR <?php echo number_format($booking['total_amount'], 2); ?></span>
                                                <?php
                                                $paymentStatusClass = 'secondary';
                                                switch ($booking['payment_status']) {
                                                    case 'paid':
                                                        $paymentStatusClass = 'success';
                                                        break;
                                                    case 'partial':
                                                        $paymentStatusClass = 'warning';
                                                        break;
                                                    case 'pending':
                                                        $paymentStatusClass = 'danger';
                                                        break;
                                                }
                                                ?>
                                                <span class="badge bg-<?php echo $paymentStatusClass; ?>">
                                                    <?php echo ucfirst($booking['payment_status']); ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="card-footer bg-white">
                                            <div class="d-flex justify-content-between">
                                                <a href="view_booking.php?id=<?php echo $booking['booking_id']; ?>" 
                                                   class="btn btn-primary btn-sm">
                                                    <i class="fas fa-eye me-1"></i>View Details
                                                </a>
                                                <?php if ($booking['booking_status'] == 'confirmed' && strtotime($booking['pickup_date']) > time()): ?>
                                                    <a href="cancel_booking.php?id=<?php echo $booking['booking_id']; ?>" 
                                                       class="btn btn-danger btn-sm"
                                                       onclick="return confirm('Are you sure you want to cancel this booking?')">
                                                        <i class="fas fa-times me-1"></i>Cancel
                                                    </a>
                                                <?php elseif ($booking['booking_status'] == 'completed' && $booking['payment_status'] == 'paid'): ?>
                                                    <a href="write_review.php?booking_id=<?php echo $booking['booking_id']; ?>" 
                                                       class="btn btn-success btn-sm">
                                                        <i class="fas fa-star me-1"></i>Write Review
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-body text-center py-5">
                                        <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                                        <h3>No bookings found</h3>
                                        <p class="text-muted">You haven't made any bookings yet or no bookings match your filter criteria.</p>
                                        <a href="browse_cars.php" class="btn btn-primary mt-3">Browse Cars</a>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
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