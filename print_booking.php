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
                c.transmission, c.seating_capacity, c.daily_rate,
                u.full_name, u.email, u.phone, u.address, u.nic_number, u.driving_license
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
$payment_query = "SELECT * FROM payments WHERE booking_id = ? ORDER BY payment_date ASC";
$stmt = $conn->prepare($payment_query);
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$payments_result = $stmt->get_result();

// Format dates for display
$formatted_pickup = date('D, M d, Y \a\t h:i A', $pickup_timestamp);
$formatted_return = date('D, M d, Y \a\t h:i A', $return_timestamp);
$booking_date = date('M d, Y \a\t h:i A', strtotime($booking['booking_date']));
$current_date = date('M d, Y');

// Calculate remaining balance
$remaining_balance = $booking['total_amount'] - $booking['paid_amount'];

// Company information
$company_name = "Kushan Car Rental";
$company_address = "123 Galle Road, Colombo 03, Sri Lanka";
$company_phone = "+94 112 345 678";
$company_email = "info@kushancarrental.lk";
$company_website = "www.kushancarrental.lk";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Receipt #<?php echo $booking_id; ?> - <?php echo $company_name; ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom Print CSS -->
     <link rel="stylesheet" href="css/print_booking.css">
   
</head>
<body>
    <div class="receipt-container position-relative">
        <!-- Print Button - Only visible on screen -->
        <div class="mb-4 no-print">
            <button class="btn btn-primary" onclick="window.print();">
                <i class="fas fa-print me-2"></i>Print Receipt
            </button>
            <a href="view_booking.php?id=<?php echo $booking_id; ?>" class="btn btn-outline-secondary ms-2">
                <i class="fas fa-arrow-left me-2"></i>Back to Booking
            </a>
        </div>
        
        <!-- Payment Status Stamp -->
        <?php if ($booking['payment_status'] == 'paid'): ?>
            <div class="paid-stamp">PAID</div>
        <?php elseif ($booking['payment_status'] == 'pending' || $booking['payment_status'] == 'partial'): ?>
            <div class="pending-stamp">PENDING</div>
        <?php endif; ?>
        
        <!-- Receipt Header -->
        <div class="receipt-header d-flex justify-content-between align-items-center">
            <div>
                <img src="assets/images/logo.png" alt="<?php echo $company_name; ?>" class="company-logo mb-2">
                <h1 class="receipt-title"><?php echo $company_name; ?></h1>
                <p class="mb-0"><?php echo $company_address; ?></p>
                <p class="mb-0"><?php echo $company_phone; ?> | <?php echo $company_email; ?></p>
                <p class="mb-0"><?php echo $company_website; ?></p>
            </div>
            <div class="text-end">
                <div class="receipt-subtitle">BOOKING RECEIPT</div>
                <p class="mb-0"><strong>Receipt #:</strong> B<?php echo str_pad($booking_id, 6, '0', STR_PAD_LEFT); ?></p>
                <p class="mb-0"><strong>Date:</strong> <?php echo $current_date; ?></p>
                <p class="mb-0">
                    <strong>Status:</strong> 
                    <?php echo ucfirst($booking['booking_status']); ?>
                </p>
            </div>
        </div>
        
        <!-- Customer Information -->
        <div class="receipt-info">
            <h2 class="section-title">Customer Information</h2>
            <table class="table-info">
                <tr>
                    <th>Customer Name:</th>
                    <td><?php echo htmlspecialchars($booking['full_name']); ?></td>
                </tr>
                <tr>
                    <th>Email:</th>
                    <td><?php echo htmlspecialchars($booking['email']); ?></td>
                </tr>
                <tr>
                    <th>Phone:</th>
                    <td><?php echo htmlspecialchars($booking['phone']); ?></td>
                </tr>
                <?php if (!empty($booking['address'])): ?>
                <tr>
                    <th>Address:</th>
                    <td><?php echo htmlspecialchars($booking['address']); ?></td>
                </tr>
                <?php endif; ?>
                <?php if (!empty($booking['nic_number'])): ?>
                <tr>
                    <th>NIC Number:</th>
                    <td><?php echo htmlspecialchars($booking['nic_number']); ?></td>
                </tr>
                <?php endif; ?>
                <?php if (!empty($booking['driving_license'])): ?>
                <tr>
                    <th>Driving License:</th>
                    <td><?php echo htmlspecialchars($booking['driving_license']); ?></td>
                </tr>
                <?php endif; ?>
            </table>
        </div>
        
        <!-- Vehicle Information -->
        <div class="receipt-info">
            <h2 class="section-title">Vehicle Information</h2>
            <table class="table-info">
                <tr>
                    <th>Vehicle:</th>
                    <td><?php echo htmlspecialchars($booking['brand'] . ' ' . $booking['model'] . ' (' . $booking['year'] . ')'); ?></td>
                </tr>
                <tr>
                    <th>Registration Number:</th>
                    <td><?php echo htmlspecialchars($booking['registration_number']); ?></td>
                </tr>
                <tr>
                    <th>Color:</th>
                    <td><?php echo htmlspecialchars($booking['color']); ?></td>
                </tr>
                <tr>
                    <th>Seating Capacity:</th>
                    <td><?php echo $booking['seating_capacity']; ?> Passengers</td>
                </tr>
                <tr>
                    <th>Fuel Type:</th>
                    <td><?php echo ucfirst($booking['fuel_type']); ?></td>
                </tr>
                <tr>
                    <th>Transmission:</th>
                    <td><?php echo ucfirst($booking['transmission']); ?></td>
                </tr>
                <tr>
                    <th>Daily Rate:</th>
                    <td>LKR <?php echo number_format($booking['daily_rate'], 2); ?></td>
                </tr>
            </table>
        </div>
        
        <!-- Booking Details -->
        <div class="receipt-info">
            <h2 class="section-title">Booking Details</h2>
            <table class="table-info">
                <tr>
                    <th>Booking Date:</th>
                    <td><?php echo $booking_date; ?></td>
                </tr>
                <tr>
                    <th>Pickup Date & Time:</th>
                    <td><?php echo $formatted_pickup; ?></td>
                </tr>
                <tr>
                    <th>Return Date & Time:</th>
                    <td><?php echo $formatted_return; ?></td>
                </tr>
                <tr>
                    <th>Duration:</th>
                    <td><?php echo $duration_days; ?> day<?php echo $duration_days > 1 ? 's' : ''; ?></td>
                </tr>
                <tr>
                    <th>Pickup Location:</th>
                    <td><?php echo htmlspecialchars($booking['pickup_location']); ?></td>
                </tr>
                <tr>
                    <th>Return Location:</th>
                    <td><?php echo htmlspecialchars($booking['return_location']); ?></td>
                </tr>
                <?php if (!empty($booking['notes'])): ?>
                <tr>
                    <th>Notes:</th>
                    <td><?php echo nl2br(htmlspecialchars($booking['notes'])); ?></td>
                </tr>
                <?php endif; ?>
            </table>
        </div>
        
        <!-- Payment Summary -->
        <div class="receipt-info">
            <h2 class="section-title">Payment Summary</h2>
            <table class="table-info">
                <tr>
                    <th>Total Rental Amount:</th>
                    <td>LKR <?php echo number_format($booking['total_amount'], 2); ?></td>
                </tr>
                <tr>
                    <th>Amount Paid:</th>
                    <td>LKR <?php echo number_format($booking['paid_amount'], 2); ?></td>
                </tr>
                <?php if ($booking['payment_status'] != 'paid'): ?>
                <tr>
                    <th>Balance Due:</th>
                    <td><strong>LKR <?php echo number_format($remaining_balance, 2); ?></strong></td>
                </tr>
                <?php endif; ?>
                <tr>
                    <th>Payment Status:</th>
                    <td>
                        <?php 
                        $statusClass = 'text-secondary';
                        switch ($booking['payment_status']) {
                            case 'paid':
                                $statusClass = 'text-success';
                                break;
                            case 'partial':
                                $statusClass = 'text-warning';
                                break;
                            case 'pending':
                                $statusClass = 'text-danger';
                                break;
                        }
                        ?>
                        <span class="<?php echo $statusClass; ?>">
                            <strong><?php echo ucfirst($booking['payment_status']); ?></strong>
                        </span>
                    </td>
                </tr>
            </table>
        </div>
        
        <!-- Payment History -->
        <?php if ($payments_result->num_rows > 0): ?>
        <div class="receipt-info">
            <h2 class="section-title">Payment History</h2>
            <table class="table-payment">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Method</th>
                        <th>Transaction ID</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($payment = $payments_result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo date('M d, Y', strtotime($payment['payment_date'])); ?></td>
                        <td><?php echo str_replace('_', ' ', ucfirst($payment['payment_method'])); ?></td>
                        <td><?php echo !empty($payment['transaction_id']) ? htmlspecialchars($payment['transaction_id']) : 'N/A'; ?></td>
                        <td>LKR <?php echo number_format($payment['amount'], 2); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
        
        <!-- Terms and Conditions -->
        <div class="receipt-info">
            <h2 class="section-title">Terms and Conditions</h2>
            <ol style="padding-left: 20px; font-size: 14px;">
                <li>Customer must present valid identification and driving license at the time of vehicle pickup.</li>
                <li>Security deposit may be required at the time of pickup.</li>
                <li>Fuel policy: Vehicle will be provided with a full tank and should be returned with a full tank.</li>
                <li>Late returns will incur additional charges at the current daily rate.</li>
                <li>Cancellation policy: Full refund if cancelled 48 hours prior to pickup time. 50% refund if cancelled between 24-48 hours. No refund for cancellations less than 24 hours.</li>
                <li>Customer is responsible for any traffic violations, fines, or penalties incurred during the rental period.</li>
                <li>Vehicle should be returned in the same condition as provided, subject to normal wear and tear.</li>
                <li>For additional terms and conditions, please refer to the rental agreement.</li>
            </ol>
        </div>
        
        <!-- Footer -->
        <div class="receipt-footer">
            <p class="mb-1">Thank you for choosing <?php echo $company_name; ?>!</p>
            <p class="mb-0">This is a computer-generated receipt and does not require a signature.</p>
            <p class="mb-0">For any queries, please contact us at <?php echo $company_phone; ?> or <?php echo $company_email; ?></p>
        </div>
    </div>
    
    <!-- Bootstrap JS and dependencies (only needed for on-screen controls) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>