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

// First, verify that the booking belongs to the logged-in user and is in 'confirmed' status
$stmt = $conn->prepare("SELECT b.*, c.is_available FROM bookings b 
                        JOIN cars c ON b.car_id = c.car_id 
                        WHERE b.booking_id = ? AND b.user_id = ?");
$stmt->bind_param("ii", $booking_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows != 1) {
    $_SESSION['error'] = "Booking not found or you don't have permission to cancel it.";
    header("Location: my_bookings.php");
    exit();
}

$booking = $result->fetch_assoc();

// Check if booking is in a status that can be cancelled
if ($booking['booking_status'] != 'confirmed' && $booking['booking_status'] != 'pending') {
    $_SESSION['error'] = "This booking cannot be cancelled because it is " . $booking['booking_status'] . ".";
    header("Location: my_bookings.php");
    exit();
}

// Check if the pickup date is in the future (at least 24 hours ahead for policy compliance)
$pickup_timestamp = strtotime($booking['pickup_date']);
$current_timestamp = time();
$hours_difference = ($pickup_timestamp - $current_timestamp) / 3600;

if ($hours_difference < 24) {
    $_SESSION['error'] = "Bookings can only be cancelled at least 24 hours before the pickup time.";
    header("Location: my_bookings.php");
    exit();
}

// Start transaction
$conn->begin_transaction();

try {
    // Update booking status to 'cancelled'
    $stmt = $conn->prepare("UPDATE bookings SET booking_status = 'cancelled' WHERE booking_id = ?");
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    
    // Update car availability
    $stmt = $conn->prepare("UPDATE cars SET is_available = TRUE WHERE car_id = ?");
    $stmt->bind_param("i", $booking['car_id']);
    $stmt->execute();
    
    // Handle refund if payment has been made
    if ($booking['payment_status'] == 'paid' || $booking['payment_status'] == 'partial') {
        // Create a record in payments table for the refund
        // The actual refund process would typically integrate with a payment gateway
        $refund_amount = $booking['paid_amount']; // Refund the paid amount
        $notes = "Refund for cancelled booking #" . $booking_id;
        
        $stmt = $conn->prepare("INSERT INTO payments (booking_id, amount, payment_method, payment_status, transaction_id) 
                               VALUES (?, ?, 'refund', 'success', ?)");
        $refund_transaction_id = 'REFUND-' . uniqid();
        $stmt->bind_param("ids", $booking_id, $refund_amount, $refund_transaction_id);
        $stmt->execute();
        
        // Update booking payment status if needed
        if ($booking['payment_status'] == 'paid') {
            $stmt = $conn->prepare("UPDATE bookings SET payment_status = 'refunded' WHERE booking_id = ?");
            $stmt->bind_param("i", $booking_id);
            $stmt->execute();
        }
    }
    
    // Log the cancellation (optional)
    // You could create a booking_logs table for this purpose
    
    // Commit transaction
    $conn->commit();
    
    $_SESSION['success'] = "Your booking has been successfully cancelled. ";
    if ($booking['payment_status'] == 'paid' || $booking['payment_status'] == 'partial') {
        $_SESSION['success'] .= "A refund of LKR " . number_format($booking['paid_amount'], 2) . " has been initiated.";
    }
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    $_SESSION['error'] = "An error occurred while cancelling your booking. Please try again or contact support.";
}

header("Location: my_bookings.php");
exit();
?>