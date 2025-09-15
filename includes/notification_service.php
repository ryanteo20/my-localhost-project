<?php
class NotificationService {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    /**
     * Create a notification record
     */
    public function createNotification($user_id, $title, $message, $type = 'info', $related_id = null) {
        $query = "INSERT INTO notifications (user_id, title, message, type, related_id, created_at, is_read) 
                  VALUES (?, ?, ?, ?, ?, NOW(), 0)";
        
        $stmt = $this->conn->prepare($query);
        if ($stmt) {
            $stmt->bind_param("isssi", $user_id, $title, $message, $type, $related_id);
            $result = $stmt->execute();
            $stmt->close();
            return $result;
        }
        return false;
    }
    
    /**
     * Notify employers about leave application
     */
    public function notifyLeaveApplication($employee_id, $leave_type, $leave_id) {
        // Get employee name
        $emp_query = "SELECT pi.full_name, el.username FROM personal_information pi 
                      JOIN employeelogin el ON pi.personal_id = el.ID 
                      WHERE el.ID = ?";
        $emp_stmt = $this->conn->prepare($emp_query);
        
        $employee_name = "Unknown Employee";
        if ($emp_stmt) {
            $emp_stmt->bind_param("i", $employee_id);
            $emp_stmt->execute();
            $emp_result = $emp_stmt->get_result()->fetch_assoc();
            $employee_name = $emp_result['full_name'] ?? $emp_result['username'] ?? "Unknown Employee";
            $emp_stmt->close();
        }
        
        // Get all employers (users with role 'employer')
        $employer_query = "SELECT ID FROM employeelogin WHERE role = 'employer'";
        $employer_result = $this->conn->query($employer_query);
        
        if ($employer_result) {
            $title = "New Leave Application";
            $message = "{$employee_name} has applied for {$leave_type} leave and requires your approval.";
            
            while ($employer = $employer_result->fetch_assoc()) {
                $this->createNotification(
                    $employer['ID'], 
                    $title, 
                    $message, 
                    'leave_application', 
                    $leave_id
                );
            }
        }
    }
    
    /**
     * Notify about claim submission
     */
    public function notifyClaimSubmission($employee_id, $category, $amount, $claim_id) {
        // Get employee name
        $emp_query = "SELECT pi.full_name, el.username FROM personal_information pi 
                      JOIN employeelogin el ON pi.personal_id = el.ID 
                      WHERE el.ID = ?";
        $emp_stmt = $this->conn->prepare($emp_query);
        
        $employee_name = "Unknown Employee";
        if ($emp_stmt) {
            $emp_stmt->bind_param("i", $employee_id);
            $emp_stmt->execute();
            $emp_result = $emp_stmt->get_result()->fetch_assoc();
            $employee_name = $emp_result['full_name'] ?? $emp_result['username'] ?? "Unknown Employee";
            $emp_stmt->close();
        }
        
        // Get all employers
        $employer_query = "SELECT ID FROM employeelogin WHERE role = 'employer'";
        $employer_result = $this->conn->query($employer_query);
        
        if ($employer_result) {
            $title = "New Claim Submission";
            $message = "{$employee_name} has submitted a {$category} claim for MYR {$amount} and requires your approval.";
            
            while ($employer = $employer_result->fetch_assoc()) {
                $this->createNotification(
                    $employer['ID'], 
                    $title, 
                    $message, 
                    'claim_submission', 
                    $claim_id
                );
            }
        }
    }
    
    /**
     * Get unread notifications for a user
     */
    public function getUnreadNotifications($user_id) {
        $query = "SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        
        if ($stmt) {
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $notifications = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return $notifications;
        }
        return [];
    }
    
    /**
     * Mark notification as read
     */
    public function markAsRead($notification_id, $user_id = null) {
        $query = "UPDATE notifications SET is_read = 1 WHERE id = ?";
        if ($user_id) {
            $query .= " AND user_id = ?";
        }
        
        $stmt = $this->conn->prepare($query);
        if ($stmt) {
            if ($user_id) {
                $stmt->bind_param("ii", $notification_id, $user_id);
            } else {
                $stmt->bind_param("i", $notification_id);
            }
            $result = $stmt->execute();
            $stmt->close();
            return $result;
        }
        return false;
    }
    
    /**
     * Mark all notifications as read for a user
     */
    public function markAllAsRead($user_id) {
        $query = "UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0";
        $stmt = $this->conn->prepare($query);
        
        if ($stmt) {
            $stmt->bind_param("i", $user_id);
            $result = $stmt->execute();
            $stmt->close();
            return $result;
        }
        return false;
    }
    
    /**
     * Get notification count for a user
     */
    public function getUnreadCount($user_id) {
        $query = "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0";
        $stmt = $this->conn->prepare($query);
        
        if ($stmt) {
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return $result['count'] ?? 0;
        }
        return 0;
    }
}
?>