<?php
session_start();

// Check if user is logged in and is a customer
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'customer') {
    header("Location: index.php");
    exit();
}

// Database connection
require_once 'includes/db_connection.php';

$user_id = $_SESSION['user_id'];
$review_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Check if review ID is valid and belongs to the current user
if ($review_id <= 0) {
    $_SESSION['error'] = "Invalid review ID.";
    header("Location: my_bookings.php");
    exit();
}

// Get the review details
$query = "SELECT r.*, b.booking_id, c.brand, c.model, c.year, c.image_url 
          FROM reviews r 
          JOIN bookings b ON r.booking_id = b.booking_id 
          JOIN cars c ON r.car_id = c.car_id 
          WHERE r.review_id = ? AND r.user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $review_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows != 1) {
    $_SESSION['error'] = "Review not found or you don't have permission to edit it.";
    header("Location: my_bookings.php");
    exit();
}

$review = $result->fetch_assoc();
$booking_id = $review['booking_id'];

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $rating = intval($_POST['rating']);
    $comment = trim($_POST['comment']);
    
    // Validate input
    $errors = [];
    
    if ($rating < 1 || $rating > 5) {
        $errors[] = "Rating must be between 1 and 5.";
    }
    
    if (empty($comment)) {
        $errors[] = "Please provide a comment for your review.";
    }
    
    if (empty($errors)) {
        // Update the review
        $update_query = "UPDATE reviews SET rating = ?, comment = ? WHERE review_id = ? AND user_id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("isii", $rating, $comment, $review_id, $user_id);
        
        if ($update_stmt->execute()) {
            $_SESSION['success'] = "Your review has been updated successfully.";
            header("Location: view_booking.php?id=" . $booking_id);
            exit();
        } else {
            $errors[] = "Failed to update review. Please try again later.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Review - Kushan Car Rental</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
     <link rel="stylesheet" href="css/edit_review.css">
     <link rel="stylesheet" href="css/footer.css">

    
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0 sidebar">
                <div class="p-3 text-center">
                    <img src="assets/images/logo.png" alt="Kushan Car Rental" class="company-logo">
                    <h5>KUSHAN CAR RENTAL</h5>
                </div>
                <nav class="nav flex-column">
                    <a class="nav-link" href="dashboard.php">
                        <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                    </a>
                    <a class="nav-link" href="browse_cars.php">
                        <i class="fas fa-car me-2"></i> Browse Cars
                    </a>
                    <a class="nav-link active" href="my_bookings.php">
                        <i class="fas fa-calendar-check me-2"></i> My Bookings
                    </a>
                    <a class="nav-link" href="my_profile.php">
                        <i class="fas fa-user me-2"></i> My Profile
                    </a>
                    <a class="nav-link" href="auth/logout.php">
                        <i class="fas fa-sign-out-alt me-2"></i> Logout
                    </a>
                </nav>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 px-0">
                <!-- Navbar -->
                <nav class="navbar navbar-expand-lg navbar-light">
                    <div class="container-fluid">
                        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                            <span class="navbar-toggler-icon"></span>
                        </button>
                        <div class="collapse navbar-collapse" id="navbarNav">
                            <ul class="navbar-nav ms-auto">
                                <li class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                                        <i class="fas fa-user-circle me-2"></i><?php echo htmlspecialchars($_SESSION['full_name']); ?>
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li><a class="dropdown-item" href="my_profile.php">Profile</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item" href="auth/logout.php">Logout</a></li>
                                    </ul>
                                </li>
                            </ul>
                        </div>
                    </div>
                </nav>
                
                <!-- Main Content Area -->
                <div class="main-content">
                    <!-- Flash Messages -->
                    <?php if (isset($errors) && !empty($errors)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <a href="view_booking.php?id=<?php echo $booking_id; ?>" class="btn btn-outline-primary mb-2">
                                <i class="fas fa-arrow-left me-2"></i>Back to Booking
                            </a>
                            <h1 class="mb-0">Edit Your Review</h1>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-lg-8">
                            <div class="card mb-4">
                                <div class="card-body">
                                    <div class="row mb-4">
                                        <div class="col-md-4">
                                            <img src="<?php echo $review['image_url'] ?? 'assets/images/default-car.jpg'; ?>" 
                                                 class="car-image mb-3" 
                                                 alt="<?php echo htmlspecialchars($review['brand'] . ' ' . $review['model']); ?>">
                                        </div>
                                        <div class="col-md-8">
                                            <h3><?php echo htmlspecialchars($review['brand'] . ' ' . $review['model'] . ' (' . $review['year'] . ')'); ?></h3>
                                            <p class="text-muted">You are editing your review for this car.</p>
                                        </div>
                                    </div>
                                    
                                    <form method="POST" action="">
                                        <div class="mb-4">
                                            <label class="form-label">Your Rating</label>
                                            <div class="rating-container text-center">
                                                <input type="radio" name="rating" id="star5" value="5" <?php echo ($review['rating'] == 5) ? 'checked' : ''; ?>>
                                                <label for="star5" class="fa fa-star"></label>
                                                <input type="radio" name="rating" id="star4" value="4" <?php echo ($review['rating'] == 4) ? 'checked' : ''; ?>>
                                                <label for="star4" class="fa fa-star"></label>
                                                <input type="radio" name="rating" id="star3" value="3" <?php echo ($review['rating'] == 3) ? 'checked' : ''; ?>>
                                                <label for="star3" class="fa fa-star"></label>
                                                <input type="radio" name="rating" id="star2" value="2" <?php echo ($review['rating'] == 2) ? 'checked' : ''; ?>>
                                                <label for="star2" class="fa fa-star"></label>
                                                <input type="radio" name="rating" id="star1" value="1" <?php echo ($review['rating'] == 1) ? 'checked' : ''; ?>>
                                                <label for="star1" class="fa fa-star"></label>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-4">
                                            <label for="comment" class="form-label">Your Review</label>
                                            <textarea name="comment" id="comment" class="form-control" rows="6" placeholder="Share your experience with this car..."><?php echo htmlspecialchars($review['comment']); ?></textarea>
                                        </div>
                                        
                                        <div class="d-flex justify-content-between">
                                            <a href="view_booking.php?id=<?php echo $booking_id; ?>" class="btn btn-outline-secondary">Cancel</a>
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-save me-2"></i>Update Review
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-4">
                            <!-- Tips for a Good Review -->
                            <div class="card mb-4">
                                <div class="card-header bg-white">
                                    <h5 class="mb-0">Tips for a Good Review</h5>
                                </div>
                                <div class="card-body">
                                    <ul class="list-unstyled">
                                        <li class="mb-2">
                                            <i class="fas fa-check-circle text-success me-2"></i>
                                            Share your personal experience
                                        </li>
                                        <li class="mb-2">
                                            <i class="fas fa-check-circle text-success me-2"></i>
                                            Mention car performance, comfort, and features
                                        </li>
                                        <li class="mb-2">
                                            <i class="fas fa-check-circle text-success me-2"></i>
                                            Be specific about what you liked or disliked
                                        </li>
                                        <li class="mb-2">
                                            <i class="fas fa-check-circle text-success me-2"></i>
                                            Provide helpful tips for other customers
                                        </li>
                                        <li>
                                            <i class="fas fa-check-circle text-success me-2"></i>
                                            Keep your review honest and constructive
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            
                            <!-- Need Help? -->
                            <div class="card">
                                <div class="card-body text-center">
                                    <i class="fas fa-headset fa-3x text-primary mb-3"></i>
                                    <h5>Need Help?</h5>
                                    <p>Contact our customer support team for any questions or issues.</p>
                                    <a href="contact.php" class="btn btn-outline-primary">
                                        <i class="fas fa-envelope me-2"></i>Contact Support
                                    </a>
                                </div>
                            </div>
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