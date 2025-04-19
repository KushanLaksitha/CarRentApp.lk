<?php
session_start();

// Check if user is logged in and is a customer
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'customer') {
    header("Location: index.php");
    exit();
}

// Database connection
require_once 'includes/db_connection.php';

// Get filter parameters
$category_filter = isset($_GET['category']) ? intval($_GET['category']) : '';
$brand_filter = isset($_GET['brand']) ? $_GET['brand'] : '';
$fuel_filter = isset($_GET['fuel']) ? $_GET['fuel'] : '';
$transmission_filter = isset($_GET['transmission']) ? $_GET['transmission'] : '';
$search_query = isset($_GET['search']) ? $_GET['search'] : '';

// Build query with filters
$query = "SELECT c.*, cat.category_name 
          FROM cars c 
          LEFT JOIN car_categories cat ON c.category_id = cat.category_id 
          WHERE c.is_available = TRUE";

$params = [];
$param_types = "";

// Apply filters
if (!empty($category_filter)) {
    $query .= " AND c.category_id = ?";
    $params[] = $category_filter;
    $param_types .= "i";
}

if (!empty($brand_filter)) {
    $query .= " AND c.brand = ?";
    $params[] = $brand_filter;
    $param_types .= "s";
}

if (!empty($fuel_filter)) {
    $query .= " AND c.fuel_type = ?";
    $params[] = $fuel_filter;
    $param_types .= "s";
}

if (!empty($transmission_filter)) {
    $query .= " AND c.transmission = ?";
    $params[] = $transmission_filter;
    $param_types .= "s";
}

if (!empty($search_query)) {
    $query .= " AND (c.brand LIKE ? OR c.model LIKE ? OR c.description LIKE ?)";
    $search_like = "%$search_query%";
    $params[] = $search_like;
    $params[] = $search_like;
    $params[] = $search_like;
    $param_types .= "sss";
}

$query .= " ORDER BY c.daily_rate ASC";

if (!empty($params)) {
    $stmt = $conn->prepare($query);
    $stmt->bind_param($param_types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($query);
}

// Get car categories for filter dropdown
$categories_result = $conn->query("SELECT * FROM car_categories ORDER BY category_name");

// Get brands for filter dropdown
$brands_result = $conn->query("SELECT DISTINCT brand FROM cars ORDER BY brand");

// Get fuel types for filter dropdown  
$fuel_types_result = $conn->query("SELECT DISTINCT fuel_type FROM cars ORDER BY fuel_type");

// Get transmission types for filter dropdown
$transmission_types_result = $conn->query("SELECT DISTINCT transmission FROM cars ORDER BY transmission");

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Cars - Kushan Car Rental</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
     <link rel="stylesheet" href="css/browse_cars.css">
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
                    <h1>Browse Cars</h1>
                    <p class="text-muted">Find the perfect car for your journey</p>
                    
                    <!-- Filter Section -->
                    <div class="filter-section">
                        <form action="" method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label for="search" class="form-label">Search</label>
                                <input type="text" class="form-control" id="search" name="search" placeholder="Search cars..." value="<?php echo htmlspecialchars($search_query); ?>">
                            </div>
                            <div class="col-md-2">
                                <label for="category" class="form-label">Category</label>
                                <select class="form-select" id="category" name="category">
                                    <option value="">All Categories</option>
                                    <?php while($category = $categories_result->fetch_assoc()): ?>
                                        <option value="<?php echo $category['category_id']; ?>" <?php echo $category_filter == $category['category_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($category['category_name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="brand" class="form-label">Brand</label>
                                <select class="form-select" id="brand" name="brand">
                                    <option value="">All Brands</option>
                                    <?php while($brand = $brands_result->fetch_assoc()): ?>
                                        <option value="<?php echo $brand['brand']; ?>" <?php echo $brand_filter == $brand['brand'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($brand['brand']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="fuel" class="form-label">Fuel Type</label>
                                <select class="form-select" id="fuel" name="fuel">
                                    <option value="">All Types</option>
                                    <?php while($fuel = $fuel_types_result->fetch_assoc()): ?>
                                        <option value="<?php echo $fuel['fuel_type']; ?>" <?php echo $fuel_filter == $fuel['fuel_type'] ? 'selected' : ''; ?>>
                                            <?php echo ucfirst(htmlspecialchars($fuel['fuel_type'])); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="transmission" class="form-label">Transmission</label>
                                <select class="form-select" id="transmission" name="transmission">
                                    <option value="">All Types</option>
                                    <?php while($trans = $transmission_types_result->fetch_assoc()): ?>
                                        <option value="<?php echo $trans['transmission']; ?>" <?php echo $transmission_filter == $trans['transmission'] ? 'selected' : ''; ?>>
                                            <?php echo ucfirst(htmlspecialchars($trans['transmission'])); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-1 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">Filter</button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Cars Grid -->
                    <div class="row">
                        <?php if ($result->num_rows > 0): ?>
                            <?php while($car = $result->fetch_assoc()): ?>
                                <div class="col-md-6 col-lg-4">
                                    <div class="card">
                                        <div class="position-relative">
                                            <img src="<?php echo $car['image_url'] ?? 'assets/images/default-car.jpg'; ?>" 
                                                 class="car-image" 
                                                 alt="<?php echo htmlspecialchars($car['brand'] . ' ' . $car['model']); ?>">
                                            <span class="badge bg-primary category-badge">
                                                <?php echo htmlspecialchars($car['category_name']); ?>
                                            </span>
                                        </div>
                                        <div class="card-body">
                                            <h5 class="card-title"><?php echo htmlspecialchars($car['brand'] . ' ' . $car['model']); ?></h5>
                                            <p class="card-text text-muted mb-2">
                                                <i class="fas fa-calendar me-2"></i><?php echo $car['year']; ?> |
                                                <i class="fas fa-users me-2 ms-2"></i><?php echo $car['seating_capacity']; ?> Seats |
                                                <i class="fas fa-gas-pump me-2 ms-2"></i><?php echo ucfirst($car['fuel_type']); ?>
                                            </p>
                                            <p class="card-text mb-3">
                                                <i class="fas fa-cogs me-2"></i><?php echo ucfirst($car['transmission']); ?> |
                                                <i class="fas fa-tachometer-alt me-2 ms-2"></i><?php echo number_format($car['mileage']); ?> km
                                            </p>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <span class="price-badge">LKR <?php echo number_format($car['daily_rate'], 2); ?>/day</span>
                                                </div>
                                                <a href="car_details.php?id=<?php echo $car['car_id']; ?>" class="btn btn-primary">View Details</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-body text-center py-5">
                                        <i class="fas fa-car-side fa-3x text-muted mb-3"></i>
                                        <h3>No cars available</h3>
                                        <p class="text-muted">There are no cars matching your search criteria. Please try different filters.</p>
                                        <a href="browse_cars.php" class="btn btn-primary mt-3">Clear Filters</a>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
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