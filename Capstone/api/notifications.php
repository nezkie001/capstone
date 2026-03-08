<?php
/**
 * Notifications Management Utility
 * Handles all notification operations
 */

require_once __DIR__ . '/../config/db_config.php';

/**
 * Create notification
 * @param int $student_id
 * @param string $subject
 * @param string $message
 * @param string $type (info, warning, error, success)
 * @param int $teacher_id (optional)
 * @param string $request_id (optional)
 * @param string $sent_via (system, email, sms, push)
 * @return bool
 */
function createNotification($student_id, $subject, $message, $type = 'info', $teacher_id = null, $request_id = null, $sent_via = 'system') {
    global $conn;
    
    // Validate inputs
    if (empty($student_id) || empty($subject) || empty($message)) {
        error_log("createNotification: Missing required parameters");
        return false;
    }
    
    $status = 'sent';
    
    // Build query with optional teacher_id
    if ($teacher_id !== null) {
        $query = "INSERT INTO notifications (student_id, teacher_id, type, subject, message, request_id, sent_via, status, sent_at) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            error_log("Notification prepare error: " . $conn->error);
            return false;
        }
        
        $stmt->bind_param("iisssss", $student_id, $teacher_id, $type, $subject, $message, $request_id, $sent_via, $status);
    } else {
        $query = "INSERT INTO notifications (student_id, type, subject, message, request_id, sent_via, status, sent_at) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            error_log("Notification prepare error: " . $conn->error);
            return false;
        }
        
        $stmt->bind_param("issssss", $student_id, $type, $subject, $message, $request_id, $sent_via, $status);
    }
    
    if (!$stmt->execute()) {
        error_log("Notification execute error: " . $stmt->error);
        return false;
    }
    
    $stmt->close();
    return true;
}

/**
 * Get student notifications
 * @param int $student_id
 * @param int $limit
 * @return array
 */
function getStudentNotifications($student_id, $limit = 10) {
    global $conn;
    
    $query = "SELECT * FROM notifications 
              WHERE student_id = ? 
              ORDER BY created_at DESC 
              LIMIT ?";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        error_log("Get notifications prepare error: " . $conn->error);
        return [];
    }
    
    $stmt->bind_param("ii", $student_id, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $notifications = [];
    while ($row = $result->fetch_assoc()) {
        $notifications[] = $row;
    }
    
    $stmt->close();
    return $notifications;
}

/**
 * Mark notification as read
 * @param int $notification_id
 * @return bool
 */
function markNotificationAsRead($notification_id) {
    global $conn;
    
    $query = "UPDATE notifications SET status = 'read' WHERE notification_id = ?";
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        error_log("Mark read prepare error: " . $conn->error);
        return false;
    }
    
    $stmt->bind_param("i", $notification_id);
    $result = $stmt->execute();
    $stmt->close();
    
    return $result;
}

/**
 * Get unread notification count
 * @param int $student_id
 * @return int
 */
function getUnreadNotificationCount($student_id) {
    global $conn;
    
    $query = "SELECT COUNT(*) as count FROM notifications WHERE student_id = ? AND status != 'read'";
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        error_log("Unread count prepare error: " . $conn->error);
        return 0;
    }
    
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    $stmt->close();
    
    return intval($data['count'] ?? 0);
}

/**
 * Delete notification
 * @param int $notification_id
 * @param int $student_id
 * @return bool
 */
function deleteNotification($notification_id, $student_id) {
    global $conn;
    
    $query = "DELETE FROM notifications WHERE notification_id = ? AND student_id = ?";
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        error_log("Delete notification prepare error: " . $conn->error);
        return false;
    }
    
    $stmt->bind_param("ii", $notification_id, $student_id);
    $result = $stmt->execute();
    $stmt->close();
    
    return $result;
}

/**
 * Get all unread notifications for student
 * @param int $student_id
 * @return array
 */
function getUnreadNotifications($student_id) {
    global $conn;
    
    $query = "SELECT * FROM notifications WHERE student_id = ? AND status != 'read' ORDER BY created_at DESC";
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        error_log("Get unread prepare error: " . $conn->error);
        return [];
    }
    
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $notifications = [];
    while ($row = $result->fetch_assoc()) {
        $notifications[] = $row;
    }
    
    $stmt->close();
    return $notifications;
}

/**
 * Send notification to multiple students
 * @param array $student_ids
 * @param string $subject
 * @param string $message
 * @param string $type
 * @param int $teacher_id
 * @return int - Number of notifications created
 */
function sendNotificationToMultiple($student_ids, $subject, $message, $type = 'info', $teacher_id = null) {
    $count = 0;
    
    if (!is_array($student_ids) || empty($student_ids)) {
        return $count;
    }
    
    foreach ($student_ids as $sid) {
        if (createNotification(intval($sid), $subject, $message, $type, $teacher_id, null, 'system')) {
            $count++;
        }
    }
    
    return $count;
}
?>