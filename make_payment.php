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
if (!isset($_GET['booking_id']) || empty($_GET['booking_id'])) {
    $_SESSION['error'] = "Invalid booking ID.";
    header("Location: my_bookings.php");
    exit();
}

$booking_id = intval($_GET['booking_id']);
$user_id = $_SESSION['user_id'];

// Get booking details
$query = "SELECT b.*, c.brand, c.model, c.year, c.image_url 
          FROM bookings b 
          JOIN cars c ON b.car_id = c.car_id 
          WHERE b.booking_id = ? AND b.user_id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $booking_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows != 1) {
    $_SESSION['error'] = "Booking not found or you don't have permission to make payment.";
    header("Location: my_bookings.php");
    exit();
}

$booking = $result->fetch_assoc();

// Check if booking can be paid for
if ($booking['booking_status'] == 'cancelled' || $booking['payment_status'] == 'paid') {
    $_SESSION['error'] = "Payment cannot be processed for this booking.";
    header("Location: view_booking.php?id=" . $booking_id);
    exit();
}

// Calculate remaining balance
$remaining_balance = $booking['total_amount'] - $booking['paid_amount'];

// Process payment form submission
$payment_error = '';
$payment_success = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate payment amount
    $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
    $payment_method = isset($_POST['payment_method']) ? $_POST['payment_method'] : '';
    $transaction_id = isset($_POST['transaction_id']) ? trim($_POST['transaction_id']) : '';
    
    // Validate payment
    if ($amount <= 0 || $amount > $remaining_balance) {
        $payment_error = "Invalid payment amount. Please enter an amount between 1 and " . number_format($remaining_balance, 2);
    } elseif (empty($payment_method)) {
        $payment_error = "Please select a payment method.";
    } else {
        // Begin transaction
        $conn->begin_transaction();
        
        try {
            // Insert payment record
            $payment_query = "INSERT INTO payments (booking_id, amount, payment_method, transaction_id, payment_status) 
                             VALUES (?, ?, ?, ?, 'success')";
            $stmt = $conn->prepare($payment_query);
            $stmt->bind_param("idss", $booking_id, $amount, $payment_method, $transaction_id);
            $stmt->execute();
            
            // Update booking payment info
            $new_paid_amount = $booking['paid_amount'] + $amount;
            $new_payment_status = ($new_paid_amount >= $booking['total_amount']) ? 'paid' : 'partial';
            
            $update_query = "UPDATE bookings SET paid_amount = ?, payment_status = ? WHERE booking_id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("dsi", $new_paid_amount, $new_payment_status, $booking_id);
            $stmt->execute();
            
            // If this is the first payment for a pending booking, update to confirmed
            if ($booking['booking_status'] == 'pending' && $booking['paid_amount'] == 0) {
                $update_status_query = "UPDATE bookings SET booking_status = 'confirmed' WHERE booking_id = ?";
                $stmt = $conn->prepare($update_status_query);
                $stmt->bind_param("i", $booking_id);
                $stmt->execute();
            }
            
            // Commit transaction
            $conn->commit();
            
            $_SESSION['success'] = "Payment of LKR " . number_format($amount, 2) . " has been processed successfully.";
            header("Location: view_booking.php?id=" . $booking_id);
            exit();
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            $payment_error = "Payment processing failed. Please try again later.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Make Payment - Kushan Car Rental</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
     <link rel="stylesheet" href="css/make_payment.css">
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
                            <a href="view_booking.php?id=<?php echo $booking_id; ?>" class="btn btn-outline-primary mb-2">
                                <i class="fas fa-arrow-left me-2"></i>Back to Booking
                            </a>
                            <h1 class="mb-0">Make Payment</h1>
                        </div>
                    </div>
                    
                    <?php if (!empty($payment_error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo $payment_error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <div class="row">
                        <!-- Payment Form -->
                        <div class="col-lg-8">
                            <div class="card">
                                <div class="card-body">
                                    <h3 class="card-title mb-4">Payment Information</h3>
                                    
                                    <form method="post" action="">
                                        <div class="mb-4">
                                            <label class="form-label">Amount to Pay (LKR)</label>
                                            <div class="input-group">
                                                <span class="input-group-text">LKR</span>
                                                <input type="number" class="form-control form-control-lg" name="amount" 
                                                       value="<?php echo number_format($remaining_balance, 2, '.', ''); ?>" 
                                                       min="1" max="<?php echo $remaining_balance; ?>" step="0.01" required>
                                            </div>
                                            <div class="form-text">
                                                Remaining balance: LKR <?php echo number_format($remaining_balance, 2); ?>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-4">
                                            <label class="form-label">Payment Method</label>
                                            <div class="payment-options">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="payment_method" 
                                                           id="creditCard" value="credit_card" required>
                                                    <label class="form-check-label payment-label" for="creditCard">
                                                        <i class="fas fa-credit-card me-2 text-primary"></i>Credit Card
                                                    </label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="payment_method" 
                                                           id="debitCard" value="debit_card">
                                                    <label class="form-check-label payment-label" for="debitCard">
                                                        <i class="fas fa-credit-card me-2 text-success"></i>Debit Card
                                                    </label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="payment_method" 
                                                           id="bankTransfer" value="bank_transfer">
                                                    <label class="form-check-label payment-label" for="bankTransfer">
                                                        <i class="fas fa-university me-2 text-info"></i>Bank Transfer
                                                    </label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="payment_method" 
                                                           id="onlinePayment" value="online_payment">
                                                    <label class="form-check-label payment-label" for="onlinePayment">
                                                        <i class="fas fa-globe me-2 text-warning"></i>Online Payment (PayPal, etc.)
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-4">
                                            <label for="transactionId" class="form-label">Transaction ID (Optional)</label>
                                            <input type="text" class="form-control" id="transactionId" name="transaction_id"
                                                   placeholder="For bank transfers or online payments">
                                            <div class="form-text">
                                                If you're using bank transfer or online payment, please enter the transaction reference.
                                            </div>
                                        </div>
                                        
                                        <div class="form-check mb-4">
                                            <input class="form-check-input" type="checkbox" id="agreeTerms" required>
                                            <label class="form-check-label" for="agreeTerms">
                                                I agree with the <a href="terms.php" target="_blank">terms and conditions</a>
                                            </label>
                                        </div>
                                        
                                        <div class="d-grid gap-2">
                                            <button type="submit" class="btn btn-primary btn-lg">
                                                <i class="fas fa-lock me-2"></i>Make Secure Payment
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Payment Summary -->
                        <div class="col-lg-4">
                            <div class="card mb-4">
                                <div class="card-header bg-white">
                                    <h5 class="mb-0">Booking Summary</h5>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex mb-3">
                                        <img src="<?php echo $booking['image_url'] ?? 'assets/images/default-car.jpg'; ?>" 
                                             class="rounded me-3" alt="<?php echo htmlspecialchars($booking['brand'] . ' ' . $booking['model']); ?>"
                                             style="width: 80px; height: 60px; object-fit: cover;">
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($booking['brand'] . ' ' . $booking['model'] . ' (' . $booking['year'] . ')'); ?></h6>
                                            <p class="text-muted mb-0">Booking #<?php echo $booking_id; ?></p>
                                        </div>
                                    </div>
                                    
                                    <hr>
                                    
                                    <div class="payment-summary mb-3">
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>Total Amount</span>
                                            <strong>LKR <?php echo number_format($booking['total_amount'], 2); ?></strong>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>Already Paid</span>
                                            <span>LKR <?php echo number_format($booking['paid_amount'], 2); ?></span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-0">
                                            <span>Remaining Balance</span>
                                            <strong>LKR <?php echo number_format($remaining_balance, 2); ?></strong>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <p class="mb-1"><strong>Pickup Date:</strong></p>
                                        <p><?php echo date('D, M d, Y \a\t h:i A', strtotime($booking['pickup_date'])); ?></p>
                                    </div>
                                    <div class="mb-3">
                                        <p class="mb-1"><strong>Return Date:</strong></p>
                                        <p><?php echo date('D, M d, Y \a\t h:i A', strtotime($booking['return_date'])); ?></p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Payment Security Information -->
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title"><i class="fas fa-shield-alt me-2 text-success"></i>Secure Payment</h5>
                                    <p class="card-text">All payments are encrypted and processed securely. Your payment information is never stored on our servers.</p>
                                    <div class="text-center mt-3">
                                        <i class="fab fa-cc-visa fa-2x mx-1"></i>
                                        <i class="fab fa-cc-mastercard fa-2x mx-1"></i>
                                        <i class="fab fa-cc-amex fa-2x mx-1"></i>
                                        <i class="fab fa-cc-paypal fa-2x mx-1"></i>
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
    <!-- Custom JS for payment form -->
    <script>
        // Form validation and interactivity can be added here
        document.addEventListener('DOMContentLoaded', function () {
            // Highlight selected payment method
            const paymentOptions = document.querySelectorAll('input[name="payment_method"]');
            
            paymentOptions.forEach(option => {
                option.addEventListener('change', function() {
                    document.querySelectorAll('.payment-options .form-check').forEach(check => {
                        check.style.borderColor = '#ddd';
                    });
                    
                    if (this.checked) {
                        this.closest('.form-check').style.borderColor = '#4e73df';
                    }
                });
            });
        });
    </script>
    <script src="js/view.js"></script>
</body>
</html>