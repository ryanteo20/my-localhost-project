<?php
require('database.php');

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $query = "SELECT ic_picture, filename, mime_type FROM employee_document WHERE document_id = ?";
    $stmt = mysqli_prepare($con, $query);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    mysqli_stmt_bind_result($stmt, $fileData, $fileName, $mimeType);

    if (mysqli_stmt_fetch($stmt)) {
        header("Content-Type: $mimeType");
        header("Content-Disposition: attachment; filename=\"$fileName\"");
        echo $fileData;
        exit;
    } else {
        echo "File not found.";
    }
}
?>
