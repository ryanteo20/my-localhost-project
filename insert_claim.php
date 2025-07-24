<?php
require('database.php');
require('session.php');

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Use SESSION ID for employee_id
    $employee_id = $_SESSION['ID'];

    // Get the form values by the EXACT input names:
    $category = $_POST['category'] ?? '';
    $transaction_date = $_POST['transaction_date'] ?? '';
    $claim_amount = $_POST['claim_amount'] ?? 0;
    $invoice_number = $_POST['invoice_number'] ?? '';
    $notes = $_POST['notes'] ?? '';

    // Basic validation
    if (empty($category) || empty($transaction_date) || empty($claim_amount)) {
        die("Please fill in all required fields.");
    }

    // Upload file handling (optional)
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = "uploads/"; // make sure this folder exists & is writable
        $file_tmp = $_FILES['attachment']['tmp_name'];
        $file_name = basename($_FILES['attachment']['name']);
        $file_path = $upload_dir . $file_name;

        if (!move_uploaded_file($file_tmdp, $file_path)) {
            die("File upload failed!");
        }
    } else {
        $file_path = null; // or empty string if you don’t store it
    }

    // Insert into the `claims` table
    $sql = "INSERT INTO claims (employee_id, category, transaction_date, amount, invoice_number, notes, attachment)
            VALUES (?, ?, ?, ?, ?, ?, ?)";


$stmt = $con->prepare($sql);

if (!$stmt) {
    die("Prepare failed: " . $con->error);
}

    $stmt = $con->prepare($sql);
    $stmt->bind_param("isssdss", $employee_id, $category, $transaction_date, $notes, $claim_amount, $invoice_number, $file_path);

    if ($stmt->execute()) {
        echo "Claim inserted successfully!";
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
    $con->close();
} else {
    echo "Invalid request.";
}
?>
