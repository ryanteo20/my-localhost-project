<?php
require('database.php');
require('session.php');
error_reporting(E_ALL);
ini_set('display_errors', 1);

$claim_id = $_POST['claim_id'] ?? null;
$status   = $_POST['status']   ?? null;
$reason   = $_POST['reason']   ?? null;

if (!$claim_id || !$status) {
    http_response_code(400);
    exit("Invalid request: missing claim_id or status.");
}

if (!in_array($status, ['Approved','Rejected'], true)) {
    http_response_code(400);
    exit("Invalid status value.");
}

if ($status === 'Rejected' && empty($reason)) {
    http_response_code(400);
    exit("Rejection reason required.");
}

if ($status === 'Approved') {
    // Only 2 placeholders
    $sql = "UPDATE claims 
            SET status = ?, rejection_reason = NULL 
            WHERE claim_id = ?";
    $stmt = mysqli_prepare($con, $sql) or die("Prepare failed: " . mysqli_error($con));
    mysqli_stmt_bind_param($stmt, "si", $status, $claim_id);

} else { // Rejected
    // 3 placeholders
    $sql = "UPDATE claims 
            SET status = ?, rejection_reason = ? 
            WHERE claim_id = ?";
    $stmt = mysqli_prepare($con, $sql) or die("Prepare failed: " . mysqli_error($con));
    mysqli_stmt_bind_param($stmt, "ssi", $status, $reason, $claim_id);
}

if (mysqli_stmt_execute($stmt)) {
    echo "Success";
} else {
    echo "Database error: " . mysqli_stmt_error($stmt);
}

mysqli_stmt_close($stmt);
mysqli_close($con);
