<?php
// Update the default case to be more helpful
// Add error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

// Fix the path references - go up one directory from api/
require_once(__DIR__ . '/../database.php');
require_once(__DIR__ . '/../session.php');
require_once(__DIR__ . '/../includes/notification_service.php');

// Check if user is logged in - Fix the session key
if (!isset($_SESSION['ID'])) {  // Changed from 'id' to 'ID'
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized', 'session_debug' => $_SESSION ?? 'No session']);
    exit;
}

$notification_service = new NotificationService($conn);
$user_id = $_SESSION['ID'];  // Changed from 'id' to 'ID'
$action = $_GET['action'] ?? '';

// Debug information
if (empty($action)) {
    echo json_encode([
        'error' => 'No action specified',
        'available_actions' => ['get_unread', 'get_count', 'mark_read', 'mark_all_read'],
        'request_method' => $_SERVER['REQUEST_METHOD'],
        'get_params' => $_GET,
        'user_id' => $user_id
    ]);
    exit;
}

switch ($action) {
    case 'get_unread':
        try {
            $notifications = $notification_service->getUnreadNotifications($user_id);
            $count = $notification_service->getUnreadCount($user_id);
            
            echo json_encode([
                'success' => true,
                'notifications' => $notifications,
                'count' => $count,
                'debug' => 'API called successfully',
                'user_id' => $user_id  // Added for debugging
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
        break;
        
    case 'mark_read':
        $notification_id = $_POST['notification_id'] ?? 0;
        
        if ($notification_id > 0) {
            try {
                $success = $notification_service->markAsRead($notification_id, $user_id);
                echo json_encode(['success' => $success]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid notification ID']);
        }
        break;
        
    case 'mark_all_read':
        try {
            $success = $notification_service->markAllAsRead($user_id);
            echo json_encode(['success' => $success]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;
        
    case 'get_count':
        try {
            $count = $notification_service->getUnreadCount($user_id);
            echo json_encode([
                'success' => true,
                'count' => $count,
                'user_info' => [
                    'id' => $user_id,
                    'username' => $_SESSION['username'],
                    'role' => $_SESSION['role']
                ]
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

        case 'create_test':
    try {
        // Create a test notification
        $query = "INSERT INTO notifications (user_id, type, title, message) VALUES (?, 'general', 'Test Notification', 'This is a test notification to verify the system works!')";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $user_id);
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Test notification created',
                'notification_id' => $conn->insert_id
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to create test notification']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    break;
        
    default:
        http_response_code(400);
        echo json_encode([
            'error' => 'Invalid action', 
            'received_action' => $action,
            'available_actions' => ['get_unread', 'get_count', 'mark_read', 'mark_all_read']
        ]);
        break;
}
?>