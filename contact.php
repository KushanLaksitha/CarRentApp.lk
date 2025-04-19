<?php
session_start();

// Database connection
require_once 'includes/db_connection.php';

$success_message = '';
$error_message = '';

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);
    
    // Basic validation
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error_message = "Please fill all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please enter a valid email address.";
    } else {
        // Prepare SQL statement to insert into contact_messages table
        // Note: You would need to create this table in your database
        $query = "INSERT INTO contact_messages (name, email, subject, message, status) VALUES (?, ?, ?, ?, 'unread')";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssss", $name, $email, $subject, $message);
        
        if ($stmt->execute()) {
            // Send notification email to admin (optional)
            $admin_email = "admin@kushan.lk";
            $mail_subject = "New Contact Form Submission: " . $subject;
            $mail_body = "Name: " . $name . "\n";
            $mail_body .= "Email: " . $email . "\n";
            $mail_body .= "Subject: " . $subject . "\n\n";
            $mail_body .= "Message:\n" . $message;
            
            // Uncomment to enable email sending
            // mail($admin_email, $mail_subject, $mail_body);
            
            $success_message = "Your message has been sent successfully. We will contact you soon!";
            
            // Clear form fields after successful submission
            $name = $email = $subject = $message = '';
        } else {
            $error_message = "Failed to send your message. Please try again later.";
        }
    }
}

// Get user information if logged in
$user = [];
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $query = "SELECT * FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - Kushan Car Rental</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
     <link rel="stylesheet" href="css/contact.css">
     <link rel="stylesheet" href="css/footer.css">

    
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php if (isset($_SESSION['user_id'])): ?>
            <div class="col-md-3 col-lg-2 px-0 sidebar">
                <div class="p-3 text-center">
                    <img src="assets/images/logo.png" alt="Kushan Car Rental" class="company-logo">
                    <h5>KUSHAN CAR RENTAL</h5>
                </div>
                <nav class="nav flex-column">
                    <a class="nav-link" href="dashboard.php">
                        <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                    </a>
                    <?php if (isset($user['user_role']) && $user['user_role'] == 'admin'): ?>
                    <a class="nav-link" href="cars.php">
                        <i class="fas fa-car me-2"></i> Cars Management
                    </a>
                    <a class="nav-link" href="bookings.php">
                        <i class="fas fa-calendar-alt me-2"></i> Bookings
                    </a>
                    <a class="nav-link" href="users.php">
                        <i class="fas fa-users me-2"></i> Users
                    </a>
                    <a class="nav-link" href="payments.php">
                        <i class="fas fa-money-bill me-2"></i> Payments
                    </a>
                    <a class="nav-link" href="reports.php">
                        <i class="fas fa-chart-bar me-2"></i> Reports
                    </a>
                    <?php elseif (isset($user['user_role']) && $user['user_role'] == 'customer'): ?>
                    <a class="nav-link" href="browse_cars.php">
                        <i class="fas fa-car me-2"></i> Browse Cars
                    </a>
                    <a class="nav-link" href="my_bookings.php">
                        <i class="fas fa-calendar-check me-2"></i> My Bookings
                    </a>
                    <a class="nav-link" href="my_profile.php">
                        <i class="fas fa-user me-2"></i> My Profile
                    </a>
                    <?php endif; ?>
                    <a class="nav-link active" href="contact.php">
                        <i class="fas fa-envelope me-2"></i> Contact Us
                    </a>
                    <a class="nav-link" href="auth/logout.php">
                        <i class="fas fa-sign-out-alt me-2"></i> Logout
                    </a>
                </nav>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 px-0">
            <?php else: ?>
            <!-- Main Content for non-logged in users -->
            <div class="col-12 px-0">
            <?php endif; ?>
                <!-- Navbar -->
                <nav class="navbar navbar-expand-lg navbar-light">
                    <div class="container-fluid">
                        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                            <span class="navbar-toggler-icon"></span>
                        </button>
                        <div class="collapse navbar-collapse" id="navbarNav">
                            <ul class="navbar-nav ms-auto">
                                <?php if (isset($_SESSION['user_id'])): ?>
                                <li class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                                        <i class="fas fa-user-circle me-2"></i><?php echo htmlspecialchars($user['full_name']); ?>
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li><a class="dropdown-item" href="dashboard.php">Dashboard</a></li>
                                        <li><a class="dropdown-item" href="my_profile.php">Profile</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item" href="auth/logout.php">Logout</a></li>
                                    </ul>
                                </li>
                                <?php else: ?>
                                <li class="nav-item">
                                    <a class="nav-link" href="auth/login.php">Login</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="auth/register.php">Register</a>
                                </li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                </nav>
                
                <!-- Main Content Area -->
                <div class="main-content">
                    <h1>Contact Us</h1>
                    <p class="text-muted mb-4">We'd love to hear from you. Please fill out the form below or use our contact information.</p>
                    
                    <!-- Display success or error messages -->
                    <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php endif; ?>
                    
                    <div class="row">
                        <!-- Contact Information -->
                        <div class="col-lg-4">
                            <div class="card mb-4">
                                <div class="card-body text-center">
                                    <div class="contact-icon mx-auto">
                                        <i class="fas fa-map-marker-alt"></i>
                                    </div>
                                    <h5>Our Address</h5>
                                    <p>123 Galle Road, Colombo 03<br>Sri Lanka</p>
                                </div>
                            </div>
                            
                            <div class="card mb-4">
                                <div class="card-body text-center">
                                    <div class="contact-icon mx-auto">
                                        <i class="fas fa-phone"></i>
                                    </div>
                                    <h5>Call Us</h5>
                                    <p>+94 11 234 5678<br>+94 77 123 4567</p>
                                </div>
                            </div>
                            
                            <div class="card mb-4">
                                <div class="card-body text-center">
                                    <div class="contact-icon mx-auto">
                                        <i class="fas fa-envelope"></i>
                                    </div>
                                    <h5>Email Us</h5>
                                    <p>info@kushan.lk<br>support@kushan.lk</p>
                                </div>
                            </div>
                            
                            <div class="card mb-4">
                                <div class="card-body text-center">
                                    <div class="contact-icon mx-auto">
                                        <i class="fas fa-clock"></i>
                                    </div>
                                    <h5>Business Hours</h5>
                                    <p>Monday - Friday: 8:00 AM - 8:00 PM<br>
                                    Saturday - Sunday: 9:00 AM - 5:00 PM</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Contact Form -->
                        <div class="col-lg-8">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="m-0">Send Us a Message</h5>
                                </div>
                                <div class="card-body">
                                    <form method="POST" action="contact.php">
                                        <div class="row mb-3">
                                            <div class="col-md-6 mb-3 mb-md-0">
                                                <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="name" name="name" value="<?php echo isset($name) ? htmlspecialchars($name) : (isset($user['full_name']) ? htmlspecialchars($user['full_name']) : ''); ?>" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                                                <input type="email" class="form-control" id="email" name="email" value="<?php echo isset($email) ? htmlspecialchars($email) : (isset($user['email']) ? htmlspecialchars($user['email']) : ''); ?>" required>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="subject" class="form-label">Subject <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="subject" name="subject" value="<?php echo isset($subject) ? htmlspecialchars($subject) : ''; ?>" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="message" class="form-label">Message <span class="text-danger">*</span></label>
                                            <textarea class="form-control" id="message" name="message" rows="5" required><?php echo isset($message) ? htmlspecialchars($message) : ''; ?></textarea>
                                        </div>
                                        
                                        <div class="text-end">
                                            <button type="submit" class="btn btn-primary px-4">
                                                <i class="fas fa-paper-plane me-2"></i>Send Message
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Map -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5 class="m-0">Find Us</h5>
                        </div>
                        <div class="card-body">
                            <iframe 
                                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3961.575851533956!2d79.85301571472179!3d6.9170305950008!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3ae25926b85d1b33%3A0x2df04e53b43f1d35!2sGalle%20Rd%2C%20Colombo!5e0!3m2!1sen!2slk!4v1637234567890!5m2!1sen!2slk" 
                                class="contact-map" 
                                allowfullscreen="" 
                                loading="lazy">
                            </iframe>
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
    <script src="js/view.js"></script>
</body>
</html>