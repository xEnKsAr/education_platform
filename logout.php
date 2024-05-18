<?php
session_start(); // Start the session

if (isset($_SESSION['user'])) {
    $userType   = 'user';
} elseif (isset($_SESSION['student'])) {

    $userType   = 'student';
}
// Unset all of the session variables
session_unset();

// Destroy the session
session_destroy();

// Redirect to the login page or any other page after logout
if ($userType === 'user') {
    return header("Location: ./admin/login.php");
} else {
    return header("Location: ./index.php");
}
exit; // Ensure script execution stops after redirection
