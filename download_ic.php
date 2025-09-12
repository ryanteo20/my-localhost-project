<?php
require('database.php');
require('session.php');

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $document_id = intval($_GET['id']);
    
    $query = "SELECT ic_picture, filename, mime_type FROM employee_document WHERE document_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $document_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        header("Content-Type: " . $row['mime_type']);
        header("Content-Disposition: attachment; filename=\"" . $row['filename'] . "\"");
        header("Content-Length: " . strlen($row['ic_picture']));
        echo $row['ic_picture'];
    } else {
        header("HTTP/1.0 404 Not Found");
        echo "IC document not found.";
    }
} else {
    header("HTTP/1.0 400 Bad Request");
    echo "Invalid document ID.";
}

mysqli_close($conn);
?>