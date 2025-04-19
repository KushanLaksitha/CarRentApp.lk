<?php
// includes/db_connection.php

// Database connection settings
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "car_rental_system";

// Create connection using MySQLi
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset
$conn->set_charset("utf8mb4");
?>