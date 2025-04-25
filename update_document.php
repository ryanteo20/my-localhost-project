<?php
require('database.php');

if (isset($_POST['editdocumentid'], $_POST['editdocumentfile'], $_FILES['editdocumentvalue'])) {
    $documentid = intval($_POST['editdocumentid']);  // Use intval to sanitize document ID
    $documentField = $_POST['editdocumentfile'];

    // Check if the field being updated is 'ic_picture' and if the file upload was successful
    if ($documentField === 'ic_picture' && $_FILES['editdocumentvalue']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['editdocumentvalue'];
        $fileName = basename($file['name']);
        $fileTmpName = $file['tmp_name'];
        $fileType = mime_content_type($fileTmpName);
        $fileSize = $file['size'];
        
        // Allowed file types
        $allowedTypes = [
            'image/jpeg',
            'image/png',
            'image/gif',
            'application/pdf'
        ];

        // Validate the file type
        if (in_array($fileType, $allowedTypes)) {
            // Validate file size (e.g., maximum 5MB)
            $maxFileSize = 5 * 1024 * 1024; // 5MB
            if ($fileSize <= $maxFileSize) {
                // Read file content
                $fileContent = file_get_contents($fileTmpName);

                // Prepare and execute the SQL query using prepared statements to update the specified field
                $stmt = $con->prepare("UPDATE employee_document SET $documentField = ?, file_name = ?, file_type = ? WHERE document_id = ?");
                $stmt->bind_param('bssi', $null, $fileName, $fileType, $documentid);

                // Send the BLOB data
                $stmt->send_long_data(0, $fileContent);

                if ($stmt->execute()) {
                    echo "Data updated successfully";
                } else {
                    echo "Error updating data: " . $stmt->error;
                }

                $stmt->close();
            } else {
                echo "File size exceeds the 5MB limit.";
            }
        } else {
            echo "Invalid file type. Only JPEG, PNG, GIF images, and PDFs are allowed.";
        }
    } else {
        echo "Invalid field or no valid file uploaded.";
    }
} else {
    echo "Employee ID, field to update, or new value not provided.";
}

mysqli_close($con);
?>
