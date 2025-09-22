<?php
// filepath: /Applications/XAMPP/xamppfiles/htdocs/reactivate_employee.php
require('database.php');
require('session.php');

// Set the content type to JSON
header('Content-Type: application/json');

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

// Check if employeeId is provided
if (!isset($_POST['employeeId']) || empty($_POST['employeeId'])) {
    echo json_encode(['status' => 'error', 'message' => 'Employee ID is required']);
    exit;
}

$employeeId = trim($_POST['employeeId']);

// Validate that employeeId is numeric
if (!is_numeric($employeeId)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid Employee ID format']);
    exit;
}

try {
    // First, check if the employee exists and is currently inactive
    $checkQuery = "SELECT ID, username, status FROM employeelogin WHERE ID = ?";
    $checkStmt = mysqli_prepare($conn, $checkQuery);
    
    if (!$checkStmt) {
        throw new Exception('Database prepare error: ' . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($checkStmt, "i", $employeeId);
    mysqli_stmt_execute($checkStmt);
    $result = mysqli_stmt_get_result($checkStmt);
    
    if (mysqli_num_rows($result) === 0) {
        echo json_encode(['status' => 'error', 'message' => 'Employee not found']);
        exit;
    }
    
    $employee = mysqli_fetch_assoc($result);
    
    // Check if employee is already active
    if ($employee['status'] === 'active') {
        echo json_encode(['status' => 'error', 'message' => 'Employee is already active']);
        exit;
    }
    
    // Update employee status to active
    $updateQuery = "UPDATE employeelogin SET status = 'active', deleted_at = NULL WHERE ID = ?";
    $updateStmt = mysqli_prepare($conn, $updateQuery);
    
    if (!$updateStmt) {
        throw new Exception('Database prepare error: ' . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($updateStmt, "i", $employeeId);
    
    if (mysqli_stmt_execute($updateStmt)) {
        // Check if any rows were affected
        if (mysqli_stmt_affected_rows($updateStmt) > 0) {
            echo json_encode([
                'status' => 'success', 
                'message' => "Employee '{$employee['username']}' (ID: {$employeeId}) has been successfully reactivated."
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'No changes were made to the employee record']);
        }
    } else {
        throw new Exception('Failed to update employee status: ' . mysqli_stmt_error($updateStmt));
    }
    
    mysqli_stmt_close($updateStmt);
    mysqli_stmt_close($checkStmt);
    
} catch (Exception $e) {
    error_log("Reactivate Employee Error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Database error occurred: ' . $e->getMessage()]);
} finally {
    if (isset($conn)) {
        mysqli_close($conn);
    }
}
?>