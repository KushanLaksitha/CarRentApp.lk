<?php
// Start session
session_start();

// Unset all session variables
$_SESSION = array();

// Delete the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Destroy the session
session_destroy();

// If remember me cookie exists, delete it
if (isset($_COOKIE['remember_token'])) {
    require_once '../includes/db_connection.php';
    
    // Delete token from database (if you implement user_tokens table)
    $token = $_COOKIE['remember_token'];
    
    // Clear the remember_token cookie
    setcookie('remember_token', '', time() - 3600, '/', '', true, true);
}

// Redirect to login page
header("Location: ../index.php");
exit();
?>