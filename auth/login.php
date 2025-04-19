<?php
session_start();
require_once '../includes/db_connection.php';

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get user input
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $remember = isset($_POST['remember']) ? true : false;
    
    // Validate input
    if (empty($username) || empty($password)) {
        $_SESSION['error'] = "Please enter both username and password";
        header("Location: ../index.php");
        exit();
    }
    
    try {
        // Prepare SQL statement to prevent SQL injection
        $stmt = $conn->prepare("SELECT user_id, username, password, full_name, user_role, is_active FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);  // This is correct for MySQLi
        $stmt->execute();
        $result = $stmt->get_result();
        
        // Check if user exists
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Check if user is active
                if ($user['is_active'] != 1) {
                    $_SESSION['error'] = "Your account is inactive. Please contact administrator.";
                    header("Location: ../index.php");
                    exit();
                }
                
                // Set session variables
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['user_role'] = $user['user_role'];
                
                // For now, let's redirect all users to dashboard.php
                header("Location: ../dashboard.php");
                exit();
                
            } else {
                $_SESSION['error'] = "Invalid username or password";
                header("Location: ../index.php");
                exit();
            }
        } else {
            $_SESSION['error'] = "Invalid username or password";
            header("Location: ../index.php");
            exit();
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "An error occurred during login. Please try again.";
        header("Location: ../index.php");
        exit();
    }
    
} else {
    // If not submitted via POST, redirect to login page
    header("Location: ../index.php");
    exit();
}