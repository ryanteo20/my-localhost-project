<?php
// process_approve_leave.php
require('database.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $leave_id = $_POST['leave_id'];
    $status = $_POST['status'];
    $reason = $_POST['reason'];

    // Check if reason is provided for rejection
    if ($status === 'Rejected' && empty($reason)) {
        echo "Error: Reason is required for rejection.";
        exit;
    }

    // Prepare query to update the leave status
    $query = "UPDATE leave_apply SET leave_review = ?, rejection_reason = ? WHERE leave_id = ?";

    // Prepare the statement
    $stmt = mysqli_prepare($conn, $query);

    // Check if the statement preparation was successful
    if ($stmt === false) {
        // Output error and stop execution
        echo "Error preparing the statement: " . mysqli_error($conn);
        exit;
    }

    // Bind the parameters
    mysqli_stmt_bind_param($stmt, 'ssi', $status, $reason, $leave_id);

    // Execute the query
    if (mysqli_stmt_execute($stmt)) {
        echo "Success";
    } else {
        echo "Error executing query: " . mysqli_stmt_error($stmt);
    }

    // Close the statement and the connection
    mysqli_stmt_close($stmt);
    mysqli_close($conn);
}
?>
