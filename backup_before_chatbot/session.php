<?php
session_start();

// Check if user is logged in (do this BEFORE logout function)
$current_page = basename($_SERVER['PHP_SELF']);
if (!isset($_SESSION['ID']) && $current_page != 'pages-login.php') {
    header("Location: pages-login.php");
    exit();
}

function logout() {
    // Unset all session variables
    $_SESSION = [];
    
    // Destroy the session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destroy the session
    session_destroy();
    
    // Redirect to login page after logout
    header("Location: pages-login.php");
    exit();
}

// Call logout function when needed (e.g., when logout button is clicked)
if (isset($_GET['logout']) || isset($_POST['logout'])) {
    logout();
}
?>