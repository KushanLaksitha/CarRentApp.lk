<?php
session_start();

// Check if user is logged in and is a customer
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'customer') {
    header("Location: index.php");
    exit();
}

// Check if car ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: browse_cars.php");
    exit();
}

$car_id = intval($_GET['id']);

// Database connection
require_once 'includes/db_connection.php';

// Get car details
$query = "SELECT c.*, cat.category_name 
          FROM cars c 
          LEFT JOIN car_categories cat ON c.category_id = cat.category_id 
          WHERE c.car_id = ? AND c.is_available = TRUE";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $car_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: browse_cars.php");
    exit();
}

$car = $result->fetch_assoc();

// Get reviews for this car
$reviews_query = "SELECT r.*, u.full_name 
                 FROM reviews r 
                 JOIN users u ON r.user_id = u.user_id 
                 WHERE r.car_id = ? 
                 ORDER BY r.review_date DESC";

$reviews_stmt = $conn->prepare($reviews_query);
$reviews_stmt->bind_param("i", $car_id);
$reviews_stmt->execute();
$reviews_result = $reviews_stmt->get_result();

// Calculate average rating
$avg_rating_query = "SELECT AVG(rating) as average_rating FROM reviews WHERE car_id = ?";
$avg_stmt = $conn->prepare($avg_rating_query);
$avg_stmt->bind_param("i", $car_id);
$avg_stmt->execute();
$avg_result = $avg_stmt->get_result();
$avg_row = $avg_result->fetch_assoc();
$average_rating = $avg_row['average_rating'] ? round($avg_row['average_rating'], 1) : 0;

// Check if there are any upcoming bookings for this car
$upcoming_bookings_query = "SELECT pickup_date, return_date 
                           FROM bookings 
                           WHERE car_id = ? AND booking_status IN ('confirmed', 'pending') 
                           AND return_date >= CURDATE()
                           ORDER BY pickup_date ASC";

$upcoming_stmt = $conn->prepare($upcoming_bookings_query);
$upcoming_stmt->bind_param("i", $car_id);
$upcoming_stmt->execute();
$upcoming_result = $upcoming_stmt->get_result();

// Create an array of unavailable dates
$unavailable_dates = [];
while ($booking = $upcoming_result->fetch_assoc()) {
    $start = new DateTime($booking['pickup_date']);
    $end = new DateTime($booking['return_date']);
    $interval = new DateInterval('P1D');
    $date_range = new DatePeriod($start, $interval, $end);
    
    foreach ($date_range as $date) {
        $unavailable_dates[] = $date->format('Y-m-d');
    }
    // Add the return date as well
    $unavailable_dates[] = $end->format('Y-m-d');
}

// Process booking form submission
$booking_error = '';
$booking_success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_car'])) {
    $pickup_date = $_POST['pickup_date'];
    $return_date = $_POST['return_date'];
    $pickup_location = $_POST['pickup_location'];
    $return_location = $_POST['return_location'];
    
    // Basic validation
    if (empty($pickup_date) || empty($return_date) || empty($pickup_location) || empty($return_location)) {
        $booking_error = "All fields are required";
    } else {
        $pickup_datetime = new DateTime($pickup_date);
        $return_datetime = new DateTime($return_date);
        
        if ($pickup_datetime >= $return_datetime) {
            $booking_error = "Return date must be after pickup date";
        } else {
            // Calculate number of days
            $interval = $pickup_datetime->diff($return_datetime);
            $days = $interval->days;
            
            // Calculate total amount
            $total_amount = $days * $car['daily_rate'];
            
            // Check if any of the selected dates are unavailable
            $is_available = true;
            $current_date = clone $pickup_datetime;
            
            while ($current_date <= $return_datetime) {
                if (in_array($current_date->format('Y-m-d'), $unavailable_dates)) {
                    $is_available = false;
                    break;
                }
                $current_date->modify('+1 day');
            }
            
            if (!$is_available) {
                $booking_error = "Selected dates are not available. Please choose different dates.";
            } else {
                // Insert booking
                $user_id = $_SESSION['user_id'];
                $booking_query = "INSERT INTO bookings (user_id, car_id, pickup_date, return_date, pickup_location, return_location, total_amount) 
                                VALUES (?, ?, ?, ?, ?, ?, ?)";
                
                $booking_stmt = $conn->prepare($booking_query);
                $booking_stmt->bind_param("iissssd", $user_id, $car_id, $pickup_date, $return_date, $pickup_location, $return_location, $total_amount);
                
                if ($booking_stmt->execute()) {
                    $booking_id = $booking_stmt->insert_id;
                    $booking_success = "Booking successful! Your booking ID is: " . $booking_id;
                    
                    // Redirect to payment page or booking confirmation
                    header("Location: booking_confirmation.php?id=" . $booking_id);
                    exit();
                } else {
                    $booking_error = "Error creating booking. Please try again.";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($car['brand'] . ' ' . $car['model']); ?> - Kushan Car Rental</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Flatpickr CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <!-- Custom CSS -->
     <link rel="stylesheet" href="css/car_details.css">
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
                    <a class="nav-link active" href="browse_cars.php">
                        <i class="fas fa-car me-2"></i> Browse Cars
                    </a>
                    <a class="nav-link" href="my_bookings.php">
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
                    <div class="mb-4">
                        <a href="browse_cars.php" class="btn btn-outline-primary">
                            <i class="fas fa-arrow-left me-2"></i> Back to Browse
                        </a>
                    </div>
                    
                    <div class="row">
                        <!-- Car Images and Details -->
                        <div class="col-lg-8">
                            <div class="card mb-4">
                                <div class="card-body">
                                    <img src="<?php echo $car['image_url'] ?? 'assets/images/default-car.jpg'; ?>" 
                                         class="car-image mb-4" 
                                         alt="<?php echo htmlspecialchars($car['brand'] . ' ' . $car['model']); ?>">
                                    
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h1><?php echo htmlspecialchars($car['brand'] . ' ' . $car['model']); ?></h1>
                                        <span class="price-badge">LKR <?php echo number_format($car['daily_rate'], 2); ?>/day</span>
                                    </div>
                                    
                                    <div class="d-flex align-items-center mb-3">
                                        <span class="badge bg-primary me-2"><?php echo htmlspecialchars($car['category_name']); ?></span>
                                        <div class="rating-stars ms-2">
                                            <?php for($i = 1; $i <= 5; $i++): ?>
                                                <?php if($i <= floor($average_rating)): ?>
                                                    <i class="fas fa-star"></i>
                                                <?php elseif($i - 0.5 <= $average_rating): ?>
                                                    <i class="fas fa-star-half-alt"></i>
                                                <?php else: ?>
                                                    <i class="far fa-star"></i>
                                                <?php endif; ?>
                                            <?php endfor; ?>
                                            <span class="ms-2"><?php echo $average_rating; ?> (<?php echo $reviews_result->num_rows; ?> reviews)</span>
                                        </div>
                                    </div>
                                    
                                    <div class="car-features">
                                        <div class="feature-box">
                                            <i class="fas fa-calendar feature-icon"></i>
                                            <h5><?php echo $car['year']; ?></h5>
                                            <p class="text-muted mb-0">Year</p>
                                        </div>
                                        <div class="feature-box">
                                            <i class="fas fa-users feature-icon"></i>
                                            <h5><?php echo $car['seating_capacity']; ?></h5>
                                            <p class="text-muted mb-0">Seats</p>
                                        </div>
                                        <div class="feature-box">
                                            <i class="fas fa-gas-pump feature-icon"></i>
                                            <h5><?php echo ucfirst($car['fuel_type']); ?></h5>
                                            <p class="text-muted mb-0">Fuel Type</p>
                                        </div>
                                        <div class="feature-box">
                                            <i class="fas fa-cogs feature-icon"></i>
                                            <h5><?php echo ucfirst($car['transmission']); ?></h5>
                                            <p class="text-muted mb-0">Transmission</p>
                                        </div>
                                        <div class="feature-box">
                                            <i class="fas fa-tachometer-alt feature-icon"></i>
                                            <h5><?php echo number_format($car['mileage']); ?> km</h5>
                                            <p class="text-muted mb-0">Mileage</p>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-4">
                                        <h4>Description</h4>
                                        <p><?php echo nl2br(htmlspecialchars($car['description'] ?? 'No description available.')); ?></p>
                                    </div>
                                    
                                    <div class="mt-4">
                                        <h4>Additional Information</h4>
                                        <table class="table table-striped">
                                            <tbody>
                                                <tr>
                                                    <th>Registration Number</th>
                                                    <td><?php echo htmlspecialchars($car['registration_number']); ?></td>
                                                </tr>
                                                <tr>
                                                    <th>Color</th>
                                                    <td><?php echo htmlspecialchars($car['color']); ?></td>
                                                </tr>
                                                <tr>
                                                    <th>Weekly Rate</th>
                                                    <td>LKR <?php echo number_format($car['weekly_rate'] ?? ($car['daily_rate'] * 7 * 0.9), 2); ?></td>
                                                </tr>
                                                <tr>
                                                    <th>Monthly Rate</th>
                                                    <td>LKR <?php echo number_format($car['monthly_rate'] ?? ($car['daily_rate'] * 30 * 0.8), 2); ?></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Reviews Section -->
                            <div class="card">
                                <div class="card-body">
                                    <h4>Customer Reviews (<?php echo $reviews_result->num_rows; ?>)</h4>
                                    <?php if ($reviews_result->num_rows > 0): ?>
                                        <?php while($review = $reviews_result->fetch_assoc()): ?>
                                            <div class="review-card">
                                                <div class="d-flex justify-content-between">
                                                    <h5><?php echo htmlspecialchars($review['full_name']); ?></h5>
                                                    <small class="text-muted">
                                                        <?php echo date('M d, Y', strtotime($review['review_date'])); ?>
                                                    </small>
                                                </div>
                                                <div class="rating-stars mb-2">
                                                    <?php for($i = 1; $i <= 5; $i++): ?>
                                                        <?php if($i <= $review['rating']): ?>
                                                            <i class="fas fa-star"></i>
                                                        <?php else: ?>
                                                            <i class="far fa-star"></i>
                                                        <?php endif; ?>
                                                    <?php endfor; ?>
                                                </div>
                                                <p><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                                            </div>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <p>No reviews yet. Be the first to review this car after your rental!</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Booking Form -->
                        <div class="col-lg-4">
                            <div class="card sticky-top" style="top: 20px; z-index: 100;">
                                <div class="card-body">
                                    <h4 class="mb-4">Book This Car</h4>
                                    
                                    <?php if(!empty($booking_error)): ?>
                                        <div class="alert alert-danger" role="alert">
                                            <?php echo $booking_error; ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if(!empty($booking_success)): ?>
                                        <div class="alert alert-success" role="alert">
                                            <?php echo $booking_success; ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <form action="" method="POST" id="bookingForm">
                                        <div class="mb-3">
                                            <label for="pickup_date" class="form-label">Pickup Date</label>
                                            <input type="text" class="form-control" id="pickup_date" name="pickup_date" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="return_date" class="form-label">Return Date</label>
                                            <input type="text" class="form-control" id="return_date" name="return_date" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="pickup_location" class="form-label">Pickup Location</label>
                                            <select class="form-select" id="pickup_location" name="pickup_location" required>
                                                <option value="">Select Location</option>
                                                <option value="Colombo Office">Colombo Office</option>
                                                <option value="Kandy Office">Kandy Office</option>
                                                <option value="Galle Office">Galle Office</option>
                                                <option value="Negombo Office">Negombo Office</option>
                                                <option value="Colombo Airport">Colombo Airport</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label for="return_location" class="form-label">Return Location</label>
                                            <select class="form-select" id="return_location" name="return_location" required>
                                                <option value="">Select Location</option>
                                                <option value="Colombo Office">Colombo Office</option>
                                                <option value="Kandy Office">Kandy Office</option>
                                                <option value="Galle Office">Galle Office</option>
                                                <option value="Negombo Office">Negombo Office</option>
                                                <option value="Colombo Airport">Colombo Airport</option>
                                            </select>
                                        </div>
                                        
                                        <div class="card mb-3">
                                            <div class="card-body">
                                                <h5>Price Summary</h5>
                                                <div class="d-flex justify-content-between">
                                                    <span>Daily Rate:</span>
                                                    <span>LKR <?php echo number_format($car['daily_rate'], 2); ?></span>
                                                </div>
                                                <div class="d-flex justify-content-between">
                                                    <span>Number of Days:</span>
                                                    <span id="numDays">0</span>
                                                </div>
                                                <hr>
                                                <div class="d-flex justify-content-between fw-bold">
                                                    <span>Total Amount:</span>
                                                    <span id="totalAmount">LKR 0.00</span>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <button type="submit" name="book_car" class="btn btn-primary w-100">Book Now</button>
                                    </form>
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
    <!-- Flatpickr JS -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="js/view.js"></script>
    <script>
        // Convert PHP array to JS array
        const unavailableDates = <?php echo json_encode($unavailable_dates); ?>;
        
        // Initialize date pickers with unavailable dates disabled
        const today = new Date();
        
        // Format daily rate for calculations
        const dailyRate = <?php echo $car['daily_rate']; ?>;
        
        flatpickr("#pickup_date", {
            minDate: "today",
            dateFormat: "Y-m-d",
            disable: unavailableDates,
            onChange: function(selectedDates, dateStr, instance) {
                // Set the min date of return_date to be the selected pickup date
                returnDatePicker.set("minDate", dateStr);
                
                // Clear the return date if it's before the pickup date
                if (returnDatePicker.selectedDates[0] && 
                    returnDatePicker.selectedDates[0] < selectedDates[0]) {
                    returnDatePicker.clear();
                }
                
                calculateTotal();
            }
        });
        
        const returnDatePicker = flatpickr("#return_date", {
            minDate: "today",
            dateFormat: "Y-m-d",
            disable: unavailableDates,
            onChange: function() {
                calculateTotal();
            }
        });
        
        function calculateTotal() {
            const pickupDate = document.getElementById('pickup_date').value;
            const returnDate = document.getElementById('return_date').value;
            
            if (pickupDate && returnDate) {
                const start = new Date(pickupDate);
                const end = new Date(returnDate);
                const diffTime = Math.abs(end - start);
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                
                document.getElementById('numDays').innerText = diffDays;
                
                const totalAmount = dailyRate * diffDays;
                document.getElementById('totalAmount').innerText = `LKR ${totalAmount.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",")}`;
            }
        }
        
        // Copy pickup location to return location when they're likely to be the same
        document.getElementById('pickup_location').addEventListener('change', function() {
            if (document.getElementById('return_location').value === '') {
                document.getElementById('return_location').value = this.value;
            }
        });
    </script>
</body>
</html>