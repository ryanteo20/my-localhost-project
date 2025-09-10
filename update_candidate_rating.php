<?php
// Prevent any output before JSON
ob_start();

require('database.php');
require('session.php');

// Clear any previous output
ob_clean();

// Set JSON header
header('Content-Type: application/json');

try {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!$data || !isset($data['candidate_id']) || !isset($data['rating'])) {
        throw new Exception('Missing required data');
    }

    $rating = (int)$data['rating'];
    if ($rating < 0 || $rating > 3) {
        throw new Exception('Invalid rating value');
    }

    $stmt = $conn->prepare("UPDATE job_applications SET rating = ? WHERE id = ?");
    
    if (!$stmt) {
        throw new Exception('Database prepare error: ' . $conn->error);
    }

    $stmt->bind_param("ii", $rating, $data['candidate_id']);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception('Database execute error: ' . $stmt->error);
    }

    $stmt->close();

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}

// End output buffering
ob_end_flush();
?>