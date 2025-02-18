<?php
// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect form data
    $name = htmlspecialchars($_POST['name']);
    $email = htmlspecialchars($_POST['email']);
    $subject = htmlspecialchars($_POST['subject']);
    $message = htmlspecialchars($_POST['message']);

    // Validate required fields
    if (!empty($name) && !empty($email) && !empty($subject) && !empty($message)) {
        // Define the recipient and email content
        $to = "kushanlaksitha32@gmail.com"; 
        $email_subject = "Contact Form: $subject";
        $email_body = "Name: $name\n";
        $email_body .= "Email: $email\n";
        $email_body .= "Message:\n$message\n";

        $headers = "From: $email\r\n";
        $headers .= "Reply-To: $email\r\n";

        // Send the email
        if (mail($to, $email_subject, $email_body, $headers)) {
            echo "Thank you for contacting us, $name. We will get back to you shortly!";
        } else {
            echo "Error: Unable to send your message. Please try again later.";
        }
    } else {
        echo "All fields are required. Please fill out the form completely.";
    }
} else {
    echo "Invalid request method.";
}
?>
