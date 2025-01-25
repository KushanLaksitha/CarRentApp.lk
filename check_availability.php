<?php
require 'databaseConnection.php';


// Retrieve form data
$pickup_date = $_POST['pickup_date'];
$pickup_time = $_POST['pickup_time'];
$car_id = $_POST['car_id'];

// Check if the car is already booked for the selected date and time
$sql = "SELECT * FROM car_bookings WHERE car_id = ? AND pickup_date = ? AND pickup_time = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iss", $car_id, $pickup_date, $pickup_time);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo json_encode([
        "success" => false,
        "message" => "The selected car is already booked at the specified date and time.",
    ]);
} else {
    echo json_encode([
        "success" => true,
        "message" => "The car is available for booking.",
    ]);
}

$stmt->close();
$conn->close();
?>
