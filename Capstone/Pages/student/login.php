<?php
session_start();

if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'student') {
    header('Location: dashboard.php');
    exit();
}

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once '../../config/db_config.php';

    $student_number = trim($_POST['student_number'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($student_number) || empty($password)) {
        $error_message = 'Please fill in all fields';
    } else {
        $stmt = $conn->prepare("SELECT student_id, student_number, email, password, first_name, last_name, status FROM students WHERE student_number = ?");
        $stmt->bind_param("s", $student_number);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $student = $result->fetch_assoc();

            if ($student['status'] !== 'active') {
                $error_message = 'Your account is inactive. Please contact the registrar.';
            } elseif (password_verify($password, $student['password'])) {
                $_SESSION['user_id'] = $student['student_id'];
                $_SESSION['user_type'] = 'student';
                $_SESSION['user_name'] = $student['first_name'] . ' ' . $student['last_name'];
                $_SESSION['email'] = $student['email'];
                
                header('Location: dashboard.php');
                exit();
            } else {
                $error_message = 'Invalid student id or password';
            }
        } else {
            $error_message = 'Invalid student number or password';
        }

        $stmt->close();
    }

    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Login - Document Request System</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="../../css/responsive.css">
    
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <h2>Student Login</h2>
            <p>Access your document requests</p>

            <?php if ($error_message): ?>
                <div class="alert alert-error">
                    ⚠️ <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    ✓ <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="student_number">Student Id</label>
                    <input type="text" id="student_number" name="student_number" required placeholder="e.g., 2024-001">
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required placeholder="Enter your password">
                </div>

                <button type="submit" class="form-submit">Login</button>
            </form>

            <div class="form-links">
                <p>Don't have an account? <a href="signup.php">Sign up here</a></p>
                <p><a href="../../Pages/index.php">Back to Home</a></p>
            </div>
        </div>
    </div>
</body>
</html>