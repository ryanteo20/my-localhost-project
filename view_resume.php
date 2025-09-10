<?php
require('session.php');

// Check if user is logged in
if (!isset($_SESSION['role'])) {
    header("Location: pages-login.php");
    exit();
}

$file = $_GET['file'] ?? '';

if (empty($file)) {
    http_response_code(404);
    echo "File not found";
    exit();
}

// Security: ensure file is within uploads directory and exists
$file_path = realpath($file);
$uploads_path = realpath('uploads/');

if (!$file_path || !$uploads_path || strpos($file_path, $uploads_path) !== 0) {
    http_response_code(403);
    echo "Access denied";
    exit();
}

if (!file_exists($file_path)) {
    http_response_code(404);
    echo "File not found";
    exit();
}

// Get file info
$file_info = pathinfo($file_path);
$file_extension = strtolower($file_info['extension']);

// Set appropriate content type
switch ($file_extension) {
    case 'pdf':
        header('Content-Type: application/pdf');
        break;
    case 'doc':
        header('Content-Type: application/msword');
        break;
    case 'docx':
        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        break;
    default:
        header('Content-Type: application/octet-stream');
}

// Set headers for inline viewing (not download)
header('Content-Disposition: inline; filename="' . basename($file_path) . '"');
header('Content-Length: ' . filesize($file_path));

// Output file content
readfile($file_path);
?>