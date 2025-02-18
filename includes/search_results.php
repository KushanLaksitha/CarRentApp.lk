<?php
require 'databaseConnection.php';

// Receive search parameters
$pickup_location = $_POST['pickup_location'] ?? '';
$drop_location = $_POST['drop_location'] ?? '';
$pickup_date = $_POST['pickup_date'] ?? '';
$pickup_time = $_POST['pickup_time'] ?? '';
$car_select = $_POST['car_select'] ?? '';

// Prepare SQL query
$sql = "SELECT * FROM cars WHERE available = TRUE";

// Add conditions based on search parameters
$conditions = [];
if (!empty($car_select)) {
    $conditions[] = "car_name LIKE '%$car_select%'";
}

// Add conditions to SQL query
if (!empty($conditions)) {
    $sql .= " AND " . implode(" AND ", $conditions);
}

$result = $conn->query($sql);

$cars = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        // Hardcoded details for demonstration
        // In a real application, these would come from a more comprehensive database
        $car = [
            'name' => $row['car_name'],
            'image' => 'path/to/car/image.jpg', // Replace with actual image path
            'year' => '2022',
            'transmission' => 'AUTO',
            'mileage' => '25K',
            'price' => '20,000'
        ];
        $cars[] = $car;
    }
}
header('Content-Type: application/json');
echo json_encode($cars);
?>
