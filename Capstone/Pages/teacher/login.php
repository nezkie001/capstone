<?php
require_once '../../config/db_config.php';

if (isset($_SESSION['teacher_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $gmail = trim($_POST['gmail'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($gmail) || empty($password)) {
        $error = "Email and password are required.";
    } else {
        $stmt = $conn->prepare("SELECT teacher_id, password, first_name, last_name, status FROM teachers WHERE gmail = ?");
        $stmt->bind_param("s", $gmail);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            if ($row['status'] !== 'active') {
                $error = "Your account is inactive. Contact administrator.";
            } elseif (password_verify($password, $row['password'])) {
                $_SESSION['teacher_id'] = $row['teacher_id'];
                $_SESSION['teacher_name'] = $row['first_name'] . ' ' . $row['last_name'];
                $_SESSION['last_activity'] = time();
                header("Location: dashboard.php");
                exit();
            } else {
                $error = "Invalid password.";
            }
        } else {
            $error = "No teacher found with that email.";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Teacher Login</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="../../css/responsive.css">
    <link rel="stylesheet" href="../../css/teacher-style.css">
</head>
<body class="auth-bg">
    <div class="auth-container">
        <h2>Teacher Login</h2>
        <?php if ($error): ?>
            <div class="alert error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST" action="">
            <input type="email" name="gmail" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Login</button>
        </form>
        <p class="note">Only registered teachers can login. Students must use the student portal.</p>
    </div>
</body>
</html>