<?php

if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'student') {
    header('Location: dashboard.php');
    exit();
}

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once '../../config/db_config.php';

    $student_number = trim($_POST['student_number'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $course = trim($_POST['course'] ?? '');
    $yearLevel = intval($_POST['yearLevel'] ?? 0);

    if (empty($student_number) || empty($email) || empty($password) || empty($first_name) || empty($last_name)) {
        $error_message = 'Please fill in all required fields';
    } elseif ($password !== $confirm_password) {
        $error_message = 'Passwords do not match';
    } elseif (strlen($password) < 6) {
        $error_message = 'Password must be at least 6 characters';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Invalid email format';
    } else {
        // Check if student number already exists
        $stmt = $conn->prepare("SELECT student_id FROM students WHERE student_number = ? OR email = ?");
        $stmt->bind_param("ss", $student_number, $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $error_message = 'Student number or email already exists';
        } else {
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);

            $stmt = $conn->prepare("INSERT INTO students (student_number, email, password, first_name, last_name, course, yearLevel) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssi", $student_number, $email, $hashed_password, $first_name, $last_name, $course, $yearLevel);

            if ($stmt->execute()) {
                $success_message = 'Account created successfully! Please log in.';
                // Clear form
                $_POST = [];
            } else {
                $error_message = 'Error creating account: ' . $conn->error;
            }
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
    <title>Student Signup - Document Request System</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="../../css/responsive.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <h2>Student Sign Up</h2>
            <p>Create your account to request documents</p>

            <?php if ($error_message): ?>
                <div class="alert alert-error">
                    ⚠️ <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    ✓ <?php echo htmlspecialchars($success_message); ?>
                </div>
                <div class="text-center">
                    <a href="login.php" class="btn btn-primary">Go to Login</a>
                </div>
            <?php else: ?>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="student_number">Student Id</label>
                    <input type="text" id="student_number" name="student_number" required placeholder="e.g., 2024-001" value="<?php echo htmlspecialchars($_POST['student_number'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required placeholder="your@email.com" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="first_name">First Name</label>
                    <input type="text" id="first_name" name="first_name" required placeholder="John" value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="last_name">Last Name</label>
                    <input type="text" id="last_name" name="last_name" required placeholder="Doe" value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="course">Select Course:</label>
                    <select id="course" name="course" onchange="updateYearLevel()">
                        <option value="">Select Course</option>
                        <option value="ACT">ACT</option>
                        <option value="BSOA">BSOA</option>
                        <option value="HM">HM</option>
                        <option value="CT">CT</option>
                    </select>

                    <label for="yearLevel">Select Year Level:</label>
                    <select id="yearLevel" name="yearLevel"></select>
                </div>

                <script>
                function updateYearLevel() {
                    const course = document.getElementById('course').value;
                    const yearLevelSelect = document.getElementById('yearLevel');
                    yearLevelSelect.innerHTML = '';

                    let yearLevels;
                    switch(course) {
                        case 'ACT':
                            yearLevels = ['1st Year', '2nd Year'];
                            break;
                        case 'BSOA':
                            yearLevels = ['1st Year', '2nd Year', '3rd Year', '4th Year'];
                            break;
                        case 'HM':
                            yearLevels = ['1st Year', '2nd Year', '3rd Year', '4th Year'];
                            break;
                        case 'CT':
                            yearLevels = ['1st Year', '2nd Year'];
                            break;
                    }

                    yearLevels.forEach(level => {
                        const option = document.createElement('option');
                        option.value = level;
                        option.textContent = level;
                        yearLevelSelect.appendChild(option);
                    });
                }
                </script>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required placeholder="At least 6 characters">
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required placeholder="Confirm your password">
                </div>

                <button type="submit" class="form-submit">Sign Up</button>
            </form>

            <div class="form-links">
                <p>Already have an account? <a href="login.php">Login here</a></p>
                <p><a href="../../index.php">Back to Home</a></p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>