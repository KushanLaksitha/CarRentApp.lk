<?php
session_start();

// Check if user is logged in and is a customer
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'customer') {
    header("Location: index.php");
    exit();
}

// Database connection
require_once 'includes/db_connection.php';

// Check if booking ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = "Invalid booking ID.";
    header("Location: my_bookings.php");
    exit();
}

$booking_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];

// Get booking details with car and payment information
$query = "SELECT b.*, c.brand, c.model, c.year, c.registration_number, c.color, c.fuel_type, 
                c.transmission, c.seating_capacity, c.image_url, c.daily_rate,
                u.full_name, u.email, u.phone
          FROM bookings b 
          JOIN cars c ON b.car_id = c.car_id 
          JOIN users u ON b.user_id = u.user_id
          WHERE b.booking_id = ? AND b.user_id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $booking_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows != 1) {
    $_SESSION['error'] = "Booking not found or you don't have permission to view it.";
    header("Location: my_bookings.php");
    exit();
}

$booking = $result->fetch_assoc();

// Calculate booking duration and additional info
$pickup_timestamp = strtotime($booking['pickup_date']);
$return_timestamp = strtotime($booking['return_date']);
$duration_seconds = $return_timestamp - $pickup_timestamp;
$duration_days = ceil($duration_seconds / (60 * 60 * 24));

// Get payment history
$payment_query = "SELECT * FROM payments WHERE booking_id = ? ORDER BY payment_date DESC";
$stmt = $conn->prepare($payment_query);
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$payments_result = $stmt->get_result();

// Check if there's a review for this booking
$review_query = "SELECT * FROM reviews WHERE booking_id = ? AND user_id = ?";
$stmt = $conn->prepare($review_query);
$stmt->bind_param("ii", $booking_id, $user_id);
$stmt->execute();
$review_result = $stmt->get_result();
$has_reviewed = ($review_result->num_rows > 0);
$review = $has_reviewed ? $review_result->fetch_assoc() : null;

// Format dates for display
$formatted_pickup = date('D, M d, Y \a\t h:i A', $pickup_timestamp);
$formatted_return = date('D, M d, Y \a\t h:i A', $return_timestamp);
$booking_date = date('M d, Y \a\t h:i A', strtotime($booking['booking_date']));

// Calculate remaining balance
$remaining_balance = $booking['total_amount'] - $booking['paid_amount'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Details - Kushan Car Rental</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
     <link rel="stylesheet" href="css/view_booking.css">
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
                    <!-- Flash Messages -->
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php 
                            echo $_SESSION['error']; 
                            unset($_SESSION['error']);
                            ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php 
                            echo $_SESSION['success']; 
                            unset($_SESSION['success']);
                            ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <a href="my_bookings.php" class="btn btn-outline-primary mb-2">
                                <i class="fas fa-arrow-left me-2"></i>Back to My Bookings
                            </a>
                            <h1 class="mb-0">Booking #<?php echo $booking_id; ?></h1>
                        </div>
                        <div>
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
                            <span class="badge bg-<?php echo $statusClass; ?> status-large">
                                <?php echo ucfirst($booking['booking_status']); ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="row">
                        <!-- Left Column: Car Details -->
                        <div class="col-lg-8">
                            <div class="card mb-4">
                                <div class="card-body">
                                    <div class="row mb-4">
                                        <div class="col-md-6">
                                            <img src="<?php echo $booking['image_url'] ?? 'assets/images/default-car.jpg'; ?>" 
                                                 class="car-image mb-3" 
                                                 alt="<?php echo htmlspecialchars($booking['brand'] . ' ' . $booking['model']); ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <h3><?php echo htmlspecialchars($booking['brand'] . ' ' . $booking['model'] . ' (' . $booking['year'] . ')'); ?></h3>
                                            <p class="text-muted mb-2">
                                                <i class="fas fa-hashtag me-2"></i><?php echo htmlspecialchars($booking['registration_number']); ?>
                                            </p>
                                            <div class="mb-3">
                                                <div class="d-flex align-items-center mb-2">
                                                    <div class="feature-icon">
                                                        <i class="fas fa-palette"></i>
                                                    </div>
                                                    <div>
                                                        <div class="text-muted small">Color</div>
                                                        <div><?php echo htmlspecialchars($booking['color']); ?></div>
                                                    </div>
                                                </div>
                                                <div class="d-flex align-items-center mb-2">
                                                    <div class="feature-icon">
                                                        <i class="fas fa-users"></i>
                                                    </div>
                                                    <div>
                                                        <div class="text-muted small">Capacity</div>
                                                        <div><?php echo $booking['seating_capacity']; ?> Passengers</div>
                                                    </div>
                                                </div>
                                                <div class="d-flex align-items-center mb-2">
                                                    <div class="feature-icon">
                                                        <i class="fas fa-gas-pump"></i>
                                                    </div>
                                                    <div>
                                                        <div class="text-muted small">Fuel Type</div>
                                                        <div><?php echo ucfirst($booking['fuel_type']); ?></div>
                                                    </div>
                                                </div>
                                                <div class="d-flex align-items-center">
                                                    <div class="feature-icon">
                                                        <i class="fas fa-cog"></i>
                                                    </div>
                                                    <div>
                                                        <div class="text-muted small">Transmission</div>
                                                        <div><?php echo ucfirst($booking['transmission']); ?></div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="mt-4">
                                                <h5>Daily Rate: LKR <?php echo number_format($booking['daily_rate'], 2); ?></h5>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <hr>
                                    
                                    <h4 class="mb-3">Booking Details</h4>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <p class="mb-1"><strong>Booking Date:</strong></p>
                                                <p><?php echo $booking_date; ?></p>
                                            </div>
                                            <div class="mb-3">
                                                <p class="mb-1"><strong>Pickup Date & Time:</strong></p>
                                                <p><?php echo $formatted_pickup; ?></p>
                                            </div>
                                            <div class="mb-3">
                                                <p class="mb-1"><strong>Return Date & Time:</strong></p>
                                                <p><?php echo $formatted_return; ?></p>
                                            </div>
                                            <div class="mb-3">
                                                <p class="mb-1"><strong>Duration:</strong></p>
                                                <p><?php echo $duration_days; ?> day<?php echo $duration_days > 1 ? 's' : ''; ?></p>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <p class="mb-1"><strong>Pickup Location:</strong></p>
                                                <p><?php echo htmlspecialchars($booking['pickup_location']); ?></p>
                                            </div>
                                            <div class="mb-3">
                                                <p class="mb-1"><strong>Return Location:</strong></p>
                                                <p><?php echo htmlspecialchars($booking['return_location']); ?></p>
                                            </div>
                                            <?php if (!empty($booking['notes'])): ?>
                                            <div class="mb-3">
                                                <p class="mb-1"><strong>Notes:</strong></p>
                                                <p><?php echo nl2br(htmlspecialchars($booking['notes'])); ?></p>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer bg-white">
                                    <div class="d-flex justify-content-between">
                                        <?php if ($booking['booking_status'] == 'confirmed' && strtotime($booking['pickup_date']) > time()): ?>
                                            <a href="cancel_booking.php?id=<?php echo $booking['booking_id']; ?>" 
                                               class="btn btn-danger"
                                               onclick="return confirm('Are you sure you want to cancel this booking?')">
                                                <i class="fas fa-times me-2"></i>Cancel Booking
                                            </a>
                                        <?php elseif ($booking['booking_status'] == 'completed' && $booking['payment_status'] == 'paid' && !$has_reviewed): ?>
                                            <a href="write_review.php?booking_id=<?php echo $booking['booking_id']; ?>" 
                                               class="btn btn-success">
                                                <i class="fas fa-star me-2"></i>Write Review
                                            </a>
                                        <?php endif; ?>
                                        <?php if ($booking['booking_status'] == 'confirmed' || $booking['booking_status'] == 'pending'): ?>
                                            <a href="print_booking.php?id=<?php echo $booking['booking_id']; ?>" 
                                               class="btn btn-primary" target="_blank">
                                                <i class="fas fa-print me-2"></i>Print Receipt
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if ($has_reviewed): ?>
                            <!-- User's Review -->
                            <div class="card mb-4">
                                <div class="card-header bg-white">
                                    <h5 class="mb-0">Your Review</h5>
                                </div>
                                <div class="card-body">
                                    <div class="star-rating mb-2">
                                        <?php 
                                        for ($i = 1; $i <= 5; $i++) {
                                            if ($i <= $review['rating']) {
                                                echo '<i class="fas fa-star"></i>';
                                            } else {
                                                echo '<i class="fas fa-star text-muted"></i>';
                                            }
                                        }
                                        ?>
                                        <span class="ms-2"><?php echo $review['rating']; ?>/5</span>
                                    </div>
                                    <p class="card-text"><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                                    <p class="text-muted small">
                                        Posted on <?php echo date('M d, Y', strtotime($review['review_date'])); ?>
                                    </p>
                                    <div class="d-flex justify-content-end">
                                        <a href="edit_review.php?id=<?php echo $review['review_id']; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-edit me-1"></i>Edit Review
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Right Column: Payment and Timeline -->
                        <div class="col-lg-4">
                            <!-- Payment Information -->
                            <div class="card mb-4">
                                <div class="card-header bg-white">
                                    <h5 class="mb-0">Payment Details</h5>
                                </div>
                                <div class="card-body">
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
                                        case 'refunded':
                                            $paymentStatusClass = 'info';
                                            break;
                                    }
                                    ?>
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h6 class="mb-0">Payment Status:</h6>
                                        <span class="badge bg-<?php echo $paymentStatusClass; ?>">
                                            <?php echo ucfirst($booking['payment_status']); ?>
                                        </span>
                                    </div>
                                    
                                    <table class="table table-sm">
                                        <tr>
                                            <td>Total Amount</td>
                                            <td class="text-end">LKR <?php echo number_format($booking['total_amount'], 2); ?></td>
                                        </tr>
                                        <tr>
                                            <td>Paid Amount</td>
                                            <td class="text-end">LKR <?php echo number_format($booking['paid_amount'], 2); ?></td>
                                        </tr>
                                        <?php if ($booking['payment_status'] != 'paid' && $booking['payment_status'] != 'refunded'): ?>
                                        <tr>
                                            <td><strong>Remaining Balance</strong></td>
                                            <td class="text-end"><strong>LKR <?php echo number_format($remaining_balance, 2); ?></strong></td>
                                        </tr>
                                        <?php endif; ?>
                                    </table>
                                    
                                    <?php if ($booking['payment_status'] != 'paid' && $booking['payment_status'] != 'refunded' && 
                                              ($booking['booking_status'] == 'confirmed' || $booking['booking_status'] == 'pending')): ?>
                                    <div class="d-grid gap-2 mt-3">
                                        <a href="make_payment.php?booking_id=<?php echo $booking_id; ?>" class="btn btn-success">
                                            <i class="fas fa-credit-card me-2"></i>Make Payment
                                        </a>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Payment History -->
                            <?php if ($payments_result->num_rows > 0): ?>
                            <div class="card mb-4">
                                <div class="card-header bg-white">
                                    <h5 class="mb-0">Payment History</h5>
                                </div>
                                <div class="card-body p-0">
                                    <div class="timeline p-3">
                                        <?php while ($payment = $payments_result->fetch_assoc()): ?>
                                        <div class="timeline-item">
                                            <div class="timeline-dot"></div>
                                            <div class="mb-2">
                                                <?php
                                                $paymentType = 'Payment';
                                                $iconClass = 'fa-credit-card text-success';
                                                if (strpos($payment['payment_method'], 'refund') !== false) {
                                                    $paymentType = 'Refund';
                                                    $iconClass = 'fa-undo text-info';
                                                }
                                                ?>
                                                <i class="fas <?php echo $iconClass; ?> me-2"></i>
                                                <strong><?php echo $paymentType; ?> of LKR <?php echo number_format($payment['amount'], 2); ?></strong>
                                            </div>
                                            <div class="text-muted small mb-2">
                                                <?php echo ucfirst($payment['payment_method']); ?> • 
                                                <?php echo date('M d, Y h:i A', strtotime($payment['payment_date'])); ?>
                                            </div>
                                            <?php if (!empty($payment['transaction_id'])): ?>
                                            <div class="text-muted small">
                                                Transaction ID: <?php echo htmlspecialchars($payment['transaction_id']); ?>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                        <?php endwhile; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Customer Details -->
                            <div class="card mb-4">
                                <div class="card-header bg-white">
                                    <h5 class="mb-0">Customer Details</h5>
                                </div>
                                <div class="card-body">
                                    <p class="mb-1"><strong>Name:</strong> <?php echo htmlspecialchars($booking['full_name']); ?></p>
                                    <p class="mb-1"><strong>Email:</strong> <?php echo htmlspecialchars($booking['email']); ?></p>
                                    <p class="mb-0"><strong>Phone:</strong> <?php echo htmlspecialchars($booking['phone']); ?></p>
                                </div>
                            </div>
                            
                            <!-- Need Help? -->
                            <div class="card">
                                <div class="card-body text-center">
                                    <i class="fas fa-headset fa-3x text-primary mb-3"></i>
                                    <h5>Need Help?</h5>
                                    <p>Contact our customer support team for any questions or issues.</p>
                                    <a href="contact.php" class="btn btn-outline-primary">
                                        <i class="fas fa-envelope me-2"></i>Contact Support
                                    </a>
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