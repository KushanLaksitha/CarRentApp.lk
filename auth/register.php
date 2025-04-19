<?php
session_start();

// Database connection
require_once '../includes/db_connection.php';

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: ../dashboard.php");
    exit();
}

// Initialize variables
$error = "";
$success = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data and sanitize inputs
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $email = trim($_POST['email']);
    $full_name = trim($_POST['full_name']);
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : "";
    $address = isset($_POST['address']) ? trim($_POST['address']) : "";
    $nic_number = isset($_POST['nic_number']) ? trim($_POST['nic_number']) : "";
    $driving_license = isset($_POST['driving_license']) ? trim($_POST['driving_license']) : "";
    
    // Validate inputs
    if (empty($username) || empty($password) || empty($confirm_password) || empty($email) || empty($full_name)) {
        $error = "Please fill all required fields";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address";
    } else {
        try {
            // Check if username already exists
            $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error = "Username already exists. Please choose a different one.";
            } else {
                // Check if email already exists
                $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $error = "Email already registered. Please use a different email.";
                } else {
                    // Check if NIC already exists (if provided)
                    if (!empty($nic_number)) {
                        $stmt = $conn->prepare("SELECT user_id FROM users WHERE nic_number = ?");
                        $stmt->bind_param("s", $nic_number);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        
                        if ($result->num_rows > 0) {
                            $error = "NIC number already registered. Please contact support if you think this is an error.";
                        }
                    }
                    
                    // If no errors, proceed with registration
                    if (empty($error)) {
                        // Hash password
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        
                        // Insert user into database
                        $stmt = $conn->prepare("INSERT INTO users (username, password, email, full_name, phone, address, nic_number, driving_license) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmt->bind_param("ssssssss", $username, $hashed_password, $email, $full_name, $phone, $address, $nic_number, $driving_license);
                        
                        if ($stmt->execute()) {
                            $success = "Registration successful! You can now login.";
                            
                            // Optionally send welcome email
                            // sendWelcomeEmail($email, $full_name);
                            
                            // Clear form data after successful registration
                            $username = $email = $full_name = $phone = $address = $nic_number = $driving_license = "";
                        } else {
                            $error = "Registration failed. Please try again: " . $conn->error;
                        }
                    }
                }
            }
        } catch (Exception $e) {
            $error = "An error occurred during registration. Please try again.";
            // For debugging: $error = "Error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kushan Car Rental - Register</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
     <link rel="stylesheet" href="../css/register.css">
</head>
<body>
    <div class="container">
        <div class="register-container">
            <div class="register-header">
                <img src="../assets/images/logo.png" alt="Kushan Car Rental" class="company-logo">
                <h1>KUSHAN CAR RENTAL</h1>
                <p>Register for a new account</p>
            </div>
            
            <div class="register-body">
                <?php if (!empty($error)): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                <div class="success-message">
                    <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                </div>
                <?php endif; ?>
                
                <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="registrationForm">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="username" class="required-label">Username</label>
                                <input type="text" class="form-control" id="username" name="username" value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="email" class="required-label">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="full_name" class="required-label">Full Name</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo isset($full_name) ? htmlspecialchars($full_name) : ''; ?>" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="password" class="required-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                                <small class="form-text text-muted">Password must be at least 8 characters long</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="confirm_password" class="required-label">Confirm Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="phone">Phone Number</label>
                                <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo isset($phone) ? htmlspecialchars($phone) : ''; ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="nic_number">NIC Number</label>
                                <input type="text" class="form-control" id="nic_number" name="nic_number" value="<?php echo isset($nic_number) ? htmlspecialchars($nic_number) : ''; ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="driving_license">Driving License Number</label>
                        <input type="text" class="form-control" id="driving_license" name="driving_license" value="<?php echo isset($driving_license) ? htmlspecialchars($driving_license) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="address">Address</label>
                        <textarea class="form-control" id="address" name="address" rows="2"><?php echo isset($address) ? htmlspecialchars($address) : ''; ?></textarea>
                    </div>
                    
                    <div class="form-check mb-3">
                        <input type="checkbox" class="form-check-input" id="terms" name="terms" required>
                        <label class="form-check-label" for="terms">
                            I agree to the <a href="terms.php" target="_blank">Terms and Conditions</a> and <a href="privacy.php" target="_blank">Privacy Policy</a>
                        </label>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100">Register</button>
                </form>
                
                <div class="login-link mt-3">
                    Already have an account? <a href="../index.php">Login here</a>
                </div>
            </div>
            
            <div class="footer">
                &copy; <?php echo date('Y'); ?> Kushan Car Rental. All Rights Reserved.
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS and dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/register.js"></script>
</body>
</html>
