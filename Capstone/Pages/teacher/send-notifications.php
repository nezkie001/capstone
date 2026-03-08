<?php
require_once '../../config/db_config.php';
require_once '../../api/email.php'; // We'll create this next

if (!isset($_SESSION['teacher_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_ids = $_POST['students'] ?? [];
    $type = $_POST['type'] ?? 'status_update';
    $subject = $_POST['subject'] ?? '';
    $message = $_POST['message'] ?? '';

    if (empty($student_ids) || empty($subject) || empty($message)) {
        $error = "Please select students and fill all fields.";
    } else {
        $success_count = 0;
        foreach ($student_ids as $student_id) {
            $stmt = $conn->prepare("SELECT email, first_name FROM students WHERE student_id = ?");
            $stmt->bind_param("i", $student_id);
            $stmt->execute();
            $student = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($student && sendNotificationEmail($student['email'], $student['first_name'], $subject, $message)) {
                // Log notification
                $noti_stmt = $conn->prepare("
                    INSERT INTO notifications (student_id, teacher_id, type, subject, message, sent_via, status, sent_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                $noti_stmt->bind_param("iisssss", $student_id, $_SESSION['teacher_id'], $type, $subject, $message, 'email', 'sent');
                $noti_stmt->execute();
                $success_count++;
            }
        }
        $success = "Successfully sent $success_count notifications.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Send Notifications</title>
    <link rel="stylesheet" href="../../css/style.css">
</head>
<body>
    <div class="container">
        <a href="dashboard.php" class="back-btn">&larr; Back</a>
        <h2>Send Notification to Students</h2>

        <?php if (isset($error)): ?>
            <div class="alert error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if (isset($success)): ?>
            <div class="alert success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form method="POST">
            <label>Select Students:</label>
            <select name="students[]" multiple size="8" required>
                <?php
                $stmt = $conn->query("SELECT student_id, first_name, last_name, email FROM students WHERE status = 'active'");
                while ($s = $stmt->fetch_assoc()):
                ?>
                    <option value="<?= $s['student_id'] ?>">
                        <?= htmlspecialchars($s['first_name'] . ' ' . $s['last_name'] . ' (' . $s['email'] . ')') ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <label>Notification Type:</label>
            <select name="type" required>
                <option value="missing_document">Missing Document</option>
                <option value="pickup_schedule">Pickup Schedule</option>
                <option value="status_update">Status Update</option>
            </select>

            <label>Subject:</label>
            <input type="text" name="subject" required placeholder="e.g., Your Document is Ready">

            <label>Message:</label>
            <textarea name="message" rows="6" required placeholder="Write your message here..."></textarea>

            <button type="submit" class="btn-primary">Send Email</button>
        </form>
    </div>
</body>
</html>