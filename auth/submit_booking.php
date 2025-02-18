<?php
// Database connection
require '../includes/databaseConnection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and validate input
    $first_name = filter_input(INPUT_POST, 'first_name', FILTER_SANITIZE_STRING);
    $last_name = filter_input(INPUT_POST, 'last_name', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $mobile = filter_input(INPUT_POST, 'mobile', FILTER_SANITIZE_STRING);
    
    $pickup_location = $_POST['pickup_location'];
    $dropoff_location = $_POST['dropoff_location'];
    $pickup_date = $_POST['pickup_date'];
    $pickup_time = $_POST['pickup_time'];
    $adults = $_POST['adults'];
    $children = $_POST['children'];
    $special_request = filter_input(INPUT_POST, 'special_request', FILTER_SANITIZE_STRING);
    $payment_method = $_POST['payment'];

    // Prepare SQL statement
    $sql = "INSERT INTO bookings 
            (first_name, last_name, email, mobile, pickup_location, 
            dropoff_location, pickup_date, pickup_time, adults, 
            children, special_request, payment_method) 
            VALUES 
            (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssssssss", 
        $first_name, $last_name, $email, $mobile, 
        $pickup_location, $dropoff_location, $pickup_date, 
        $pickup_time, $adults, $children, $special_request, 
        $payment_method);

    if ($stmt->execute()) {
        echo json_encode([
            'status' => 'success', 
            'message' => 'Booking submitted successfully!'
        ]);
    } else {
        echo json_encode([
            'status' => 'error', 
            'message' => 'Error: ' . $stmt->error
        ]);
    }

    $stmt->close();
    $conn->close();
} else {
    http_response_code(405);
    echo json_encode([
        'status' => 'error', 
        'message' => 'Method Not Allowed'
    ]);
}
?>
