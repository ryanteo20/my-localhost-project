<?php
require_once 'database.php';
require('session.php');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get form data
$employee_id = $_POST['employee_id'];
$claim_type = $_POST['claim_type'];
$claim_amount = $_POST['claim_amount'];
$claim_date = $_POST['claim_date'];
$claim_description = $_POST['claim_description'];

// Handle file upload
$claim_document = null;

if (isset($_FILES['claim_document']) && $_FILES['claim_document']['error'] == 0) {
  $target_dir = "uploads/";
  if (!is_dir($target_dir)) {
    mkdir($target_dir, 0777, true);
  }

  $filename = basename($_FILES["claim_document"]["name"]);
  $target_file = $target_dir . time() . "_" . $filename;

  if (move_uploaded_file($_FILES["claim_document"]["tmp_name"], $target_file)) {
    $claim_document = $target_file;
  } else {
    die("❌ File upload failed.");
  }
}

// Insert into DB
$sql = "INSERT INTO claims (employee_id, claim_type, claim_amount, claim_date, claim_description, claim_document) 
        VALUES (?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

$stmt->bind_param("isdsss", $employee_id, $claim_type, $claim_amount, $claim_date, $claim_description, $claim_document);
$stmt->execute();

echo "✅ Claim submitted successfully!";
?>
