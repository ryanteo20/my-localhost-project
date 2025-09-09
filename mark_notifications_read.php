<?php
require('database.php');
session_start();

// Set proper content type
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['ID'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$user_id = $_SESSION['ID'];
$user_role = $_SESSION['role'];

try {
    if ($user_role === 'employer' || $user_role === 'admin') {
        // Mark all notifications for this employer as read
        $query = "UPDATE notifications SET status = 'read' WHERE employer_id = ? AND status = 'unread'";
        $stmt = $con->prepare($query);
        $stmt->bind_param("i", $user_id);
    } else {
        // Mark all notifications for this employee as read
        $query = "UPDATE notifications SET status = 'read' WHERE employee_id = ? AND status = 'unread'";
        $stmt = $con->prepare($query);
        $stmt->bind_param("i", $user_id);
    }
    
    $result = $stmt->execute();
    
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $query = "UPDATE notifications SET status = 'read' WHERE status = 'unread'";
    $stmt = $con->prepare($query);
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update notifications.']);
    }
}
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>