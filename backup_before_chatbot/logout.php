<?php
session_start();

function logout() {
    // Unset all session variables
    session_unset();

    // Destroy the session
    session_destroy();
}

// Call the logout function when the user clicks logout
logout();

// Redirect the user to the login page
header("Location: pages-login.php");
exit();
?>