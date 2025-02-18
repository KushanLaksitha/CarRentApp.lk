<?php
// Database connection settings
$servername = "localhost"; 
$username = "root";        
$password = "";            
$dbname = "car_rent_db"; 

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


$conn->set_charset("utf8");


?>
