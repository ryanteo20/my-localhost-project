<?php
require('database.php');
require('session.php');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['employeeId'])) {
    $employee_id = intval($_POST['employeeId']);
    
    if ($employee_id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid employee ID']);
        exit;
    }
    
    // Check if employee exists and is not already deleted
    $check_query = "SELECT ID, username FROM employeelogin WHERE ID = ? AND (deleted_at IS NULL OR deleted_at = '')";
    $check_stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($check_stmt, "i", $employee_id);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    
    if (mysqli_num_rows($check_result) === 0) {
        echo json_encode(['status' => 'error', 'message' => 'Employee not found or already deleted']);
        exit;
    }
    
    $employee = mysqli_fetch_assoc($check_result);
    
    // Perform soft delete
    $delete_query = "UPDATE employeelogin SET deleted_at = NOW() WHERE ID = ?";
    $delete_stmt = mysqli_prepare($conn, $delete_query);
    mysqli_stmt_bind_param($delete_stmt, "i", $employee_id);
    
    if (mysqli_stmt_execute($delete_stmt)) {
        echo json_encode([
            'status' => 'success', 
            'message' => 'Employee "' . $employee['username'] . '" has been successfully deleted'
        ]);
    } else {
        echo json_encode([
            'status' => 'error', 
            'message' => 'Error deleting employee: ' . mysqli_stmt_error($delete_stmt)
        ]);
    }
    
    mysqli_stmt_close($delete_stmt);
    mysqli_stmt_close($check_stmt);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method or missing employee ID']);
}

mysqli_close($conn);
?>