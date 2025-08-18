<?php
session_start();

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

// Get the current file name
$current_page = basename($_SERVER['PHP_SELF']);

if (!isset($_SESSION['ID']) && $current_page != 'pages-login.php') {
    header("Location: pages-login.php");
    exit();
}
}
?>
