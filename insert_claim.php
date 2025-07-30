<?php
session_start();
require('database.php');

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $employee_id = $_SESSION['ID'];

    $category = $_POST['category'] ?? '';
    $transaction_date = $_POST['transaction_date'] ?? '';
    $amount = $_POST['amount'] ?? 0;
    $invoice_number = $_POST['invoice_number'] ?? '';
    $notes = $_POST['notes'] ?? '';

    if (empty($category) || empty($transaction_date) || empty($amount)) {
        $_SESSION['error_message'] = "Please fill in all required fields.";
        header("Location: R_claim.php");
        exit;
    }

    // Upload handling
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = "uploads/";
        $file_tmp = $_FILES['attachment']['tmp_name'];
        $file_name = basename($_FILES['attachment']['name']);
        $file_path = $upload_dir . $file_name;

        if (!move_uploaded_file($file_tmp, $file_path)) {
            $_SESSION['error_message'] = "File upload failed!";
            header("Location: R_claim.php");
            exit;
        }
    } else {
        $file_path = null;
    }

    $sql = "INSERT INTO claims (employee_id, category, transaction_date, amount, invoice_number, notes, attachment, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending')";

    $stmt = $con->prepare($sql);

    if (!$stmt) {
        $_SESSION['error_message'] = "Prepare failed: " . $con->error;
        header("Location: R_claim.php");
        exit;
    }

    // ORDER: i = int, s = string, d = decimal
    $stmt->bind_param("issdsss", $employee_id, $category, $transaction_date, $amount, $invoice_number, $notes, $file_path);

    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Successfully submitted claim!";
    } else {
        $_SESSION['error_message'] = "Error: " . $stmt->error;
    }

    $stmt->close();
    $con->close();
    header("Location: R_claim.php");
    exit;
} else {
    echo "Invalid request.";
}
