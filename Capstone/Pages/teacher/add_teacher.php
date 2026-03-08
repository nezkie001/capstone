<?php
require_once '../../config/db_config.php';

if (!isset($_SESSION['teacher_id'])) {
    die(json_encode(['success' => false, 'message' => 'Unauthorized access.']));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $teacher_number = trim($_POST['teacher_number'] ?? '');
    $gmail = trim($_POST['gmail'] ?? '');
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($password !== $confirm_password) {
        echo "<script>alert('Passwords do not match.'); window.history.back();</script>";
        exit();
    }

    if (empty($teacher_number) || empty($gmail) || empty($first_name) || empty($last_name) || empty($password)) {
        echo "<script>alert('All fields are required.'); window.history.back();</script>";
        exit();
    }

    // Check if teacher number or email exists
    $stmt = $conn->prepare("SELECT teacher_id FROM teachers WHERE teacher_number = ? OR gmail = ?");
    $stmt->bind_param("ss", $teacher_number, $gmail);
    $stmt->execute();
    $exists = $stmt->get_result()->num_rows > 0;
    $stmt->close();

    if ($exists) {
        echo "<script>alert('Teacher number or email already exists.'); window.history.back();</script>";
        exit();
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $insert = $conn->prepare("INSERT INTO teachers (teacher_number, gmail, first_name, last_name, department, password) VALUES (?, ?, ?, ?, ?, ?)");
    $insert->bind_param("ssssss", $teacher_number, $gmail, $first_name, $last_name, $department, $hashed_password);

    if ($insert->execute()) {
        echo "<script>alert('Teacher account created successfully.'); window.location.href='dashboard.php';</script>";
    } else {
        echo "<script>alert('Failed to create account: " . $conn->error . "'); window.history.back();</script>";
    }
    $insert->close();
}