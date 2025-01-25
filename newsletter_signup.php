<?php
// Include the database connection file
require 'databaseConnection.php';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = htmlspecialchars($_POST['email']);

    // Validate email
    if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // Check if the email already exists in the database
        $stmt = $conn->prepare("SELECT * FROM subscribers WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            echo "You are already subscribed!";
        } else {
            // Insert email into the database
            $stmt = $conn->prepare("INSERT INTO subscribers (email) VALUES (?)");
            $stmt->bind_param("s", $email);

            if ($stmt->execute()) {
                echo "Thank you for subscribing!";
            } else {
                echo "Error: Could not save your subscription. Please try again.";
            }
        }
        $stmt->close(); // Close the statement
    } else {
        echo "Please enter a valid email address.";
    }
}

// Close the database connection
$conn->close();
?>
