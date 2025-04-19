<?php
session_start();

// Database connection
require_once 'includes/db_connection.php';

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kushan Car Rental - Login</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/index.css">
</head>
<body>
    <div class="container">
        <div class="login-container">
            <div class="login-header">
                <img src="assets/images/logo.png" alt="Kushan Car Rental" class="company-logo">
                <h1>KUSHAN CAR RENTAL</h1>
                <p>The Most Trusted Car Rental Service in Sri Lanka</p>
            </div>
            
            <div class="login-body">
                <?php if (!empty($error)): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
                <?php endif; ?>
                
                <form method="post" action="auth/login.php">
                    <div class="form-group">
                        <i class="fas fa-user"></i>
                        <input type="text" class="form-control" id="username" name="username" placeholder="Username">
                    </div>
                    
                    <div class="form-group">
                        <i class="fas fa-lock"></i>
                        <input type="password" class="form-control" id="password" name="password" placeholder="Password">
                    </div>
                    
                    <div class="form-check mb-3">
                        <input type="checkbox" class="form-check-input" id="remember" name="remember">
                        <label class="form-check-label" for="remember">Remember Me</label>
                        <div class="float-end forgot-link">
                            <a href="#">Forgot Password?</a>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100">Login</button>
                </form>
                
                <div class="divider">OR</div>
                
                <div class="register-link text-center mb-3">
                    Don't have an account? <a href="auth/register.php">Register Now</a>
                </div>

            </div>
            
            <div class="footer">
                &copy; <?php echo date('Y'); ?> Kushan Car Rental. All Rights Reserved.
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS and dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/index.js"></script>
</body>
</html>