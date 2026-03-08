<?php
require_once '../config/db_config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'student') {
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

$student_id = $_SESSION['user_id'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($action === 'get_request') {
    $request_id = isset($_GET['request_id']) ? (int)$_GET['request_id'] : 0;
    
    // Get request details
    $query = "SELECT dr.*, dt.document_name, dt.processing_days
              FROM document_requests dr
              JOIN documents dt ON dr.document_id = dt.document_id
              WHERE dr.request_id = ? AND dr.student_id = ?";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        die(json_encode(['success' => false, 'message' => 'Database error']));
    }
    
    $stmt->bind_param("ii", $request_id, $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $request = $result->fetch_assoc();
        echo json_encode([
            'success' => true,
            'data' => $request
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Request not found']);
    }
    $stmt->close();
}
?>