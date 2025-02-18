<?php
session_start();
require '../includes/databaseConnection.php';

// Check if user is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch statistics
$stats = [
    'total_bookings' => 0,
    'total_cars' => 0,
    'total_subscribers' => 0,
    'recent_bookings' => []
];

// Get total bookings
$result = $conn->query("SELECT COUNT(*) as count FROM bookings");
$stats['total_bookings'] = $result->fetch_assoc()['count'];

// Get total cars
$result = $conn->query("SELECT COUNT(*) as count FROM cars");
$stats['total_cars'] = $result->fetch_assoc()['count'];

// Get total subscribers
$result = $conn->query("SELECT COUNT(*) as count FROM subscribers");
$stats['total_subscribers'] = $result->fetch_assoc()['count'];

// Get recent bookings
$result = $conn->query("SELECT * FROM bookings ORDER BY booking_timestamp DESC LIMIT 5");
$stats['recent_bookings'] = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Car Rental System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/dashboard.css">

</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0 position-fixed sidebar">
                <div class="text-center mb-4">
                    <h4>Car Rental Admin</h4>
                </div>
                <ul class="nav flex-column px-3">
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php">
                            <i class="bi bi-speedometer2 me-2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="bookings.php">
                            <i class="bi bi-calendar-check me-2"></i> Bookings
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="cars.php">
                            <i class="bi bi-car-front me-2"></i> Cars
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="subscribers.php">
                            <i class="bi bi-people me-2"></i> Subscribers
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="settings.php">
                            <i class="bi bi-gear me-2"></i> Settings
                        </a>
                    </li>
                    <li class="nav-item mt-3">
                        <a class="nav-link text-danger" href="logout.php">
                            <i class="bi bi-box-arrow-right me-2"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 ms-auto px-4 py-3">
               

                <!-- Welcome Banner -->
                <div class="welcome-banner">
                    <h2>Welcome, <?php echo htmlspecialchars($_SESSION['admin_username']); ?>!</h2>
                    <p class="mb-0">Here's what's happening with your car rental system today.</p>
                </div>

                <!-- Statistics Cards -->
                <div class="row g-4 mb-4">
                    <div class="col-md-4">
                        <div class="stat-card bookings">
                            <h3 class="fs-5">Total Bookings</h3>
                            <h2 class="display-5 mb-0"><?php echo $stats['total_bookings']; ?></h2>
                            <i class="bi bi-calendar-check position-absolute top-50 end-0 translate-middle-y opacity-25 fs-1 me-3"></i>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card cars">
                            <h3 class="fs-5">Available Cars</h3>
                            <h2 class="display-5 mb-0"><?php echo $stats['total_cars']; ?></h2>
                            <i class="bi bi-car-front position-absolute top-50 end-0 translate-middle-y opacity-25 fs-1 me-3"></i>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card subscribers">
                            <h3 class="fs-5">Total Subscribers</h3>
                            <h2 class="display-5 mb-0"><?php echo $stats['total_subscribers']; ?></h2>
                            <i class="bi bi-people position-absolute top-50 end-0 translate-middle-y opacity-25 fs-1 me-3"></i>
                        </div>
                    </div>
                </div>

                <!-- Recent Bookings -->
                <div class="recent-bookings">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h3>Recent Bookings</h3>
                        <a href="bookings.php" class="btn btn-primary btn-sm">View All</a>
                    </div>
                    <?php foreach ($stats['recent_bookings'] as $booking): ?>
                        <div class="booking-item">
                            <div class="row align-items-center">
                                <div class="col-md-3">
                                    <strong><?php echo htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']); ?></strong>
                                </div>
                                <div class="col-md-3">
                                    <i class="bi bi-calendar me-2"></i>
                                    <?php echo date('M d, Y', strtotime($booking['pickup_date'])); ?>
                                </div>
                                <div class="col-md-3">
                                    <i class="bi bi-clock me-2"></i>
                                    <?php echo date('h:i A', strtotime($booking['pickup_time'])); ?>
                                </div>
                                <div class="col-md-3 text-end">
                                    <span class="badge bg-success">Confirmed</span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script scr="../js/adminNav.js"></script>
    
</body>
</html>