<?php
require('database.php');
session_start();

// Check if user is logged in and get their role
if (!isset($_SESSION['ID']) || !isset($_SESSION['role'])) {
    echo json_encode([]);
    exit;
}

$user_id = $_SESSION['ID'];
$user_role = $_SESSION['role'];

$notifications = [];

// If user is employer, get notifications for their employees
if ($user_role === 'employer' || $user_role === 'admin') {
    $query = "SELECT n.*, el.username as employee_name 
              FROM notifications n 
              LEFT JOIN employeelogin el ON n.employee_id = el.ID 
              WHERE n.employer_id = ? AND n.status = 'unread' 
              ORDER BY n.created_at DESC 
              LIMIT 10";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $notifications[] = $row;
    }
} else {
    // If user is employee, they might get other types of notifications
    $query = "SELECT * FROM notifications 
              WHERE employee_id = ? AND status = 'unread' 
              ORDER BY created_at DESC 
              LIMIT 10";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $notifications[] = $row;
    }
}

// Set proper content type and return JSON
header('Content-Type: application/json');
echo json_encode($notifications);
?>