<?php
require_once '../config/db_config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'student') {
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

$student_id = $_SESSION['user_id'];

// Get POST data
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
$date_of_birth = isset($_POST['date_of_birth']) && !empty($_POST['date_of_birth']) ? trim($_POST['date_of_birth']) : null;
$yearLevel = isset($_POST['yearLevel']) && !empty($_POST['yearLevel']) ? (int)$_POST['yearLevel'] : null;

// Validate email
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die(json_encode(['success' => false, 'message' => 'Invalid email format']));
}

// Check if email is already used by another student
$check_email = "SELECT student_id FROM students WHERE email = ? AND student_id != ?";
$stmt = $conn->prepare($check_email);
if (!$stmt) {
    die(json_encode(['success' => false, 'message' => 'Database error']));
}

$stmt->bind_param("si", $email, $student_id);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    die(json_encode(['success' => false, 'message' => 'Email is already in use']));
}
$stmt->close();

// Update student information
$update_query = "UPDATE students SET email = ?, phone = ?, date_of_birth = ?, yearLevel = ?, updated_at = CURRENT_TIMESTAMP WHERE student_id = ?";
$stmt = $conn->prepare($update_query);
if (!$stmt) {
    die(json_encode(['success' => false, 'message' => 'Database error']));
}

$stmt->bind_param("sssii", $email, $phone, $date_of_birth, $yearLevel, $student_id);
if (!$stmt->execute()) {
    die(json_encode(['success' => false, 'message' => 'Failed to update profile']));
}
$stmt->close();

echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
?>