<?php
session_start();

// Check if user is logged in and is a customer
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'customer') {
    header("Location: index.php");
    exit();
}

// Check if booking ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: my_bookings.php");
    exit();
}

$booking_id = intval($_GET['id']);

// Database connection
require_once 'includes/db_connection.php';

// Get booking details including car and user information
$query = "SELECT b.*, c.brand, c.model, c.image_url, c.registration_number, 
                 c.daily_rate, cat.category_name, u.full_name, u.email, u.phone
          FROM bookings b 
          JOIN cars c ON b.car_id = c.car_id
          LEFT JOIN car_categories cat ON c.category_id = cat.category_id
          JOIN users u ON b.user_id = u.user_id
          WHERE b.booking_id = ? AND b.user_id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $booking_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: my_bookings.php");
    exit();
}

$booking = $result->fetch_assoc();

// Calculate booking duration
$pickup_date = new DateTime($booking['pickup_date']);
$return_date = new DateTime($booking['return_date']);
$interval = $pickup_date->diff($return_date);
$days = $interval->days;

// Process payment form
$payment_error = '';
$payment_success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['make_payment'])) {
    $payment_method = $_POST['payment_method'];
    $amount = floatval($_POST['amount']);
    
    // Basic validation
    if (empty($payment_method) || $amount <= 0) {
        $payment_error = "Please select a payment method and enter a valid amount";
    } else if ($amount > ($booking['total_amount'] - $booking['paid_amount'])) {
        $payment_error = "Payment amount cannot exceed the remaining balance";
    } else {
        // Generate transaction ID
        $transaction_id = 'TXN' . time() . rand(1000, 9999);
        
        // Insert payment
        $payment_query = "INSERT INTO payments (booking_id, amount, payment_method, transaction_id, payment_status) 
                         VALUES (?, ?, ?, ?, 'success')";
        
        $payment_stmt = $conn->prepare($payment_query);
        $payment_stmt->bind_param("idss", $booking_id, $amount, $payment_method, $transaction_id);
        
        if ($payment_stmt->execute()) {
            // Update booking payment info
            $new_paid_amount = $booking['paid_amount'] + $amount;
            $payment_status = ($new_paid_amount >= $booking['total_amount']) ? 'paid' : 
                             (($new_paid_amount > 0) ? 'partial' : 'pending');
            
            $update_query = "UPDATE bookings SET paid_amount = ?, payment_status = ?, booking_status = 'confirmed' 
                            WHERE booking_id = ?";
            
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param("dsi", $new_paid_amount, $payment_status, $booking_id);
            
            if ($update_stmt->execute()) {
                $payment_success = "Payment of LKR " . number_format($amount, 2) . " processed successfully!";
                
                // Refresh booking data
                $stmt->execute();
                $result = $stmt->get_result();
                $booking = $result->fetch_assoc();
            } else {
                $payment_error = "Error updating booking payment status.";
            }
        } else {
            $payment_error = "Error processing payment. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmation - Kushan Car Rental</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/booking_confirm.css">
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
                        <div>
                            <h1>Booking Confirmation</h1>
                            <p class="text-muted">Your booking details and payment information</p>
                        </div>
                        <div class="no-print">
                            <a href="my_bookings.php" class="btn btn-outline-primary me-2">
                                <i class="fas fa-list me-2"></i> All Bookings
                            </a>
                            <button onclick="window.print()" class="btn btn-outline-secondary">
                                <i class="fas fa-print me-2"></i> Print
                            </button>
                        </div>
                    </div>
                    
                    <?php if(!empty($payment_error)): ?>
                        <div class="alert alert-danger no-print" role="alert">
                            <?php echo $payment_error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if(!empty($payment_success)): ?>
                        <div class="alert alert-success no-print" role="alert">
                            <?php echo $payment_success; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="row">
                        <!-- Booking Information -->
                        <div class="col-lg-8">
                            <div class="card">
                                <div class="booking-header">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h3 class="mb-0">Booking #<?php echo $booking['booking_id']; ?></h3>
                                        <span class="booking-status status-<?php echo strtolower($booking['booking_status']); ?>">
                                            <?php echo ucfirst($booking['booking_status']); ?>
                                        </span>
                                    </div>
                                    <p class="mb-0 mt-2">
                                        <i class="fas fa-calendar me-2"></i> Booked on <?php echo date('F d, Y', strtotime($booking['booking_date'])); ?>
                                    </p>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h5>Car Information</h5>
                                            <div class="car-info-box mb-4">
                                                <img src="<?php echo $booking['image_url'] ?? 'assets/images/default-car.jpg'; ?>" 
                                                     class="car-image mb-3" 
                                                     alt="<?php echo htmlspecialchars($booking['brand'] . ' ' . $booking['model']); ?>">
                                                <h5><?php echo htmlspecialchars($booking['brand'] . ' ' . $booking['model']); ?></h5>
                                                <p class="text-muted mb-2">
                                                    <i class="fas fa-tag me-2"></i><?php echo htmlspecialchars($booking['category_name']); ?> |
                                                    <i class="fas fa-id-card me-2 ms-2"></i><?php echo htmlspecialchars($booking['registration_number']); ?>
                                                </p>
                                                <p class="mb-0">Daily Rate: LKR <?php echo number_format($booking['daily_rate'], 2); ?></p>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <h5>Booking Details</h5>
                                            <div class="booking-detail d-flex justify-content-between">
                                                <span>Pickup Date:</span>
                                                <span class="fw-bold"><?php echo date('F d, Y', strtotime($booking['pickup_date'])); ?></span>
                                            </div>
                                            <div class="booking-detail d-flex justify-content-between">
                                                <span>Return Date:</span>
                                                <span class="fw-bold"><?php echo date('F d, Y', strtotime($booking['return_date'])); ?></span>
                                            </div>
                                            <div class="booking-detail d-flex justify-content-between">
                                                <span>Duration:</span>
                                                <span class="fw-bold"><?php echo $days; ?> day<?php echo $days !== 1 ? 's' : ''; ?></span>
                                            </div>
                                            <div class="booking-detail d-flex justify-content-between">
                                                <span>Pickup Location:</span>
                                                <span class="fw-bold"><?php echo htmlspecialchars($booking['pickup_location']); ?></span>
                                            </div>
                                            <div class="booking-detail d-flex justify-content-between">
                                                <span>Return Location:</span>
                                                <span class="fw-bold"><?php echo htmlspecialchars($booking['return_location']); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <hr>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h5>Customer Information</h5>
                                            <div class="booking-detail">
                                                <span>Name:</span>
                                                <span class="fw-bold"><?php echo htmlspecialchars($booking['full_name']); ?></span>
                                            </div>
                                            <div class="booking-detail">
                                                <span>Email:</span>
                                                <span><?php echo htmlspecialchars($booking['email']); ?></span>
                                            </div>
                                            <div class="booking-detail">
                                                <span>Phone:</span>
                                                <span><?php echo htmlspecialchars($booking['phone'] ?? 'Not provided'); ?></span>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <h5>Payment Information</h5>
                                            <div class="booking-detail d-flex justify-content-between">
                                                <span>Payment Status:</span>
                                                <span class="booking-status payment-status-<?php echo strtolower($booking['payment_status']); ?>">
                                                    <?php echo ucfirst($booking['payment_status']); ?>
                                                </span>
                                            </div>
                                            <div class="booking-detail d-flex justify-content-between">
                                                <span>Total Amount:</span>
                                                <span class="fw-bold">LKR <?php echo number_format($booking['total_amount'], 2); ?></span>
                                            </div>
                                            <div class="booking-detail d-flex justify-content-between">
                                                <span>Paid Amount:</span>
                                                <span>LKR <?php echo number_format($booking['paid_amount'], 2); ?></span>
                                            </div>
                                            <div class="booking-detail d-flex justify-content-between">
                                                <span>Remaining Balance:</span>
                                                <span class="fw-bold">LKR <?php echo number_format($booking['total_amount'] - $booking['paid_amount'], 2); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <?php if (!empty($booking['notes'])): ?>
                                    <div class="mt-4">
                                        <h5>Additional Notes</h5>
                                        <p><?php echo nl2br(htmlspecialchars($booking['notes'])); ?></p>
                                    </div>
                                    <?php endif; ?>

                                    <div class="alert alert-info mt-4 mb-0">
                                        <i class="fas fa-info-circle me-2"></i> Thank you for choosing Kushan Car Rental. Please make sure to bring your ID and driving license during pickup.
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Payment Section -->
                        <div class="col-lg-4 no-print">
                            <?php if ($booking['payment_status'] !== 'paid'): ?>
                                <div class="card">
                                    <div class="card-body">
                                        <h4 class="mb-4">Make Payment</h4>
                                        <div class="alert alert-warning">
                                            <i class="fas fa-exclamation-triangle me-2"></i> Remaining Balance: 
                                            <span class="fw-bold">LKR <?php echo number_format($booking['total_amount'] - $booking['paid_amount'], 2); ?></span>
                                        </div>
                                        
                                        <form action="" method="POST">
                                            <div class="mb-3">
                                                <label for="amount" class="form-label">Payment Amount (LKR)</label>
                                                <input type="number" class="form-control" id="amount" name="amount" 
                                                       min="1" max="<?php echo $booking['total_amount'] - $booking['paid_amount']; ?>" 
                                                       value="<?php echo $booking['total_amount'] - $booking['paid_amount']; ?>" 
                                                       step="0.01" required>
                                            </div>
                                            
                                            <div class="mb-4">
                                                <label class="form-label">Payment Method</label>
                                                <div class="payment-methods">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" name="payment_method" id="creditCard" value="credit_card" checked>
                                                        <label class="form-check-label w-100" for="creditCard">
                                                            <i class="fab fa-cc-visa me-2"></i> Credit Card
                                                        </label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" name="payment_method" id="debitCard" value="debit_card">
                                                        <label class="form-check-label w-100" for="debitCard">
                                                            <i class="fab fa-cc-mastercard me-2"></i> Debit Card
                                                        </label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" name="payment_method" id="onlinePayment" value="online_payment">
                                                        <label class="form-check-label w-100" for="onlinePayment">
                                                            <i class="fas fa-money-bill-wave me-2"></i> Online Payment
                                                        </label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" name="payment_method" id="bankTransfer" value="bank_transfer">
                                                        <label class="form-check-label w-100" for="bankTransfer">
                                                            <i class="fas fa-university me-2"></i> Bank Transfer
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <button type="submit" name="make_payment" class="btn btn-primary w-100">
                                                <i class="fas fa-lock me-2"></i> Make Secure Payment
                                            </button>
                                        </form>
                                        
                                        <div class="mt-3 text-center">
                                            <small class="text-muted">
                                                <i class="fas fa-shield-alt me-1"></i> All payments are secure and encrypted
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="card">
                                    <div class="card-body text-center">
                                        <i class="fas fa-check-circle text-success fa-4x mb-3"></i>
                                        <h4>Payment Completed</h4>
                                        <p>Thank you for your payment. Your booking is now confirmed.</p>
                                        <a href="my_bookings.php" class="btn btn-primary mt-3">
                                            <i class="fas fa-list me-2"></i> View All Bookings
                                        </a>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <div class="card mt-4">
                                <div class="card-body">
                                    <h5>Need Help?</h5>
                                    <p>If you have any questions or need assistance with your booking, please contact our customer support.</p>
                                    <div class="d-grid gap-2">
                                        <a href="tel:+94123456789" class="btn btn-outline-primary">
                                            <i class="fas fa-phone me-2"></i> +94 123 456 789
                                        </a>
                                        <a href="mailto:support@kushancarrental.lk" class="btn btn-outline-primary">
                                            <i class="fas fa-envelope me-2"></i> Email Support
                                        </a>
                                    </div>
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