<?php
session_start();
require '../includes/databaseConnection.php';

// Check if user is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Handle car operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $car_name = filter_input(INPUT_POST, 'car_name', FILTER_SANITIZE_STRING);
                $stmt = $conn->prepare("INSERT INTO cars (car_name) VALUES (?)");
                $stmt->bind_param("s", $car_name);
                $stmt->execute();
                $stmt->close();
                break;

            case 'update':
                $car_id = filter_input(INPUT_POST, 'car_id', FILTER_SANITIZE_NUMBER_INT);
                $car_name = filter_input(INPUT_POST, 'car_name', FILTER_SANITIZE_STRING);
                $available = isset($_POST['available']) ? 1 : 0;
                $stmt = $conn->prepare("UPDATE cars SET car_name = ?, available = ? WHERE id = ?");
                $stmt->bind_param("sii", $car_name, $available, $car_id);
                $stmt->execute();
                $stmt->close();
                break;

            case 'delete':
                $car_id = filter_input(INPUT_POST, 'car_id', FILTER_SANITIZE_NUMBER_INT);
                $stmt = $conn->prepare("DELETE FROM cars WHERE id = ?");
                $stmt->bind_param("i", $car_id);
                $stmt->execute();
                $stmt->close();
                break;
        }
        header("Location: cars.php");
        exit();
    }
}

// Fetch all cars
$result = $conn->query("SELECT * FROM cars ORDER BY created_at DESC");
$cars = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cars Management - Car Rental System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/cars.css">
    
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
                        <a class="nav-link" href="dashboard.php">
                            <i class="bi bi-speedometer2 me-2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="bookings.php">
                            <i class="bi bi-calendar-check me-2"></i> Bookings
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="cars.php">
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
                <!-- Page Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Cars Management</h2>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCarModal">
                        <i class="bi bi-plus-lg me-2"></i>Add New Car
                    </button>
                </div>

                <!-- Cars Grid -->
                <div class="row">
                    <?php foreach ($cars as $car): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="car-card p-4">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="mb-0"><?php echo htmlspecialchars($car['car_name']); ?></h5>
                                    <span class="status-badge <?php echo $car['available'] ? 'available' : 'unavailable'; ?>">
                                        <?php echo $car['available'] ? 'Available' : 'Unavailable'; ?>
                                    </span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">Added: <?php echo date('M d, Y', strtotime($car['created_at'])); ?></small>
                                    <div>
                                        <button class="btn btn-sm btn-outline-primary me-2" 
                                                onclick="editCar(<?php echo $car['id']; ?>, '<?php echo htmlspecialchars($car['car_name']); ?>', <?php echo $car['available']; ?>)">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" 
                                                onclick="deleteCar(<?php echo $car['id']; ?>, '<?php echo htmlspecialchars($car['car_name']); ?>')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Car Modal -->
    <div class="modal fade" id="addCarModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Car</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label for="car_name" class="form-label">Car Name</label>
                            <input type="text" class="form-control" id="car_name" name="car_name" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Car</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Car Modal -->
    <div class="modal fade" id="editCarModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Car</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="car_id" id="edit_car_id">
                        <div class="mb-3">
                            <label for="edit_car_name" class="form-label">Car Name</label>
                            <input type="text" class="form-control" id="edit_car_name" name="car_name" required>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="edit_available" name="available">
                                <label class="form-check-label" for="edit_available">Available for Booking</label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Car Modal -->
    <div class="modal fade" id="deleteCarModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Car</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="car_id" id="delete_car_id">
                        <p>Are you sure you want to delete <span id="delete_car_name"></span>?</p>
                        <p class="text-danger">This action cannot be undone.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete Car</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/cars.js"></script>
</body>
</html>