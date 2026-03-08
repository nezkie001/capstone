<?php
require_once '../../config/db_config.php';

if (!isset($_SESSION['teacher_id'])) {
    header("Location: login.php");
    exit();
}

$request_id = $_GET['request_id'] ?? 0;

$stmt = $conn->prepare("
    SELECT dr.*, s.first_name, s.last_name, s.student_number, s.email, s.course, s.year_level,
           d.document_name, d.document_code, d.fee
    FROM document_requests dr
    JOIN students s ON dr.student_id = s.student_id
    JOIN documents d ON dr.document_id = d.document_id
    WHERE dr.request_id = ?
");
$stmt->bind_param("i", $request_id);
$stmt->execute();
$request = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$request) {
    die("Request not found.");
}

// Update status if form submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $new_status = $_POST['status'];
    $notes = $_POST['notes'] ?? '';

    $stmt = $conn->prepare("UPDATE document_requests SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE request_id = ?");
    $stmt->bind_param("si", $new_status, $request_id);
    $stmt->execute();

    // Log transaction
    $stmt = $conn->prepare("INSERT INTO transaction_history (request_id, student_id, action, action_by, status_from, status_to, notes) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iisssss", $request_id, $request['student_id'], 'Status Updated', $_SESSION['teacher_name'], $request['status'], $new_status, $notes);
    $stmt->execute();

    // Redirect to refresh
    header("Location: view-requests.php?request_id=$request_id");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Request</title>
    <link rel="stylesheet" href="../../css/style.css">
</head>
<body>
    <div class="container">
        <a href="dashboard.php" class="back-btn">&larr; Back to Dashboard</a>
        <h2>Document Request Details</h2>

        <div class="card">
            <h3><?= htmlspecialchars($request['document_name']) ?> (<?= htmlspecialchars($request['document_code']) ?>)</h3>
            <p><strong>Student:</strong> <?= htmlspecialchars($request['first_name']) . ' ' . htmlspecialchars($request['last_name']) ?> <br>
               <small><?= htmlspecialchars($request['student_number']) ?> | <?= htmlspecialchars($request['email']) ?> | <?= htmlspecialchars($request['course']) ?> - Year <?= $request['year_level'] ?></small></p>

            <p><strong>Quantity:</strong> <?= $request['quantity'] ?></p>
            <p><strong>Purpose:</strong> <?= htmlspecialchars($request['purpose'] ?? 'N/A') ?></p>
            <p><strong>Notes:</strong> <?= htmlspecialchars($request['notes'] ?? 'N/A') ?></p>
            <p><strong>Requested:</strong> <?= date('M d, Y g:i A', strtotime($request['requested_at'])) ?></p>
            <p><strong>Fee:</strong> <?= number_format($request['fee'], 2) ?> ₱</p>

            <form method="POST" style="margin-top: 1.5rem;">
                <label for="status">Update Status:</label>
                <select name="status" id="status" required>
                    <option value="">-- Select Status --</option>
                    <option value="pending" <?= $request['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="processing" <?= $request['status'] === 'processing' ? 'selected' : '' ?>>Processing</option>
                    <option value="ready" <?= $request['status'] === 'ready' ? 'selected' : '' ?>>Ready</option>
                    <option value="released" <?= $request['status'] === 'released' ? 'selected' : '' ?>>Released</option>
                    <option value="cancelled" <?= $request['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                </select>

                <label for="notes">Notes:</label>
                <textarea name="notes" id="notes" rows="3" placeholder="Optional notes for record..."></textarea>

                <button type="submit" name="update_status" class="btn-primary">Update Status</button>
            </form>
        </div>

        <h3>Transaction History</h3>
        <table class="table">
            <thead><tr><th>Date</th><th>Action</th><th>From</th><th>To</th><th>Notes</th></tr></thead>
            <tbody>
                <?php
                $hist_stmt = $conn->prepare("SELECT * FROM transaction_history WHERE request_id = ? ORDER BY created_at DESC");
                $hist_stmt->bind_param("i", $request_id);
                $hist_stmt->execute();
                $history = $hist_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                $hist_stmt->close();

                foreach ($history as $h):
                ?>
                <tr>
                    <td><?= date('M d, Y g:i A', strtotime($h['created_at'])) ?></td>
                    <td><?= htmlspecialchars($h['action']) ?></td>
                    <td><?= htmlspecialchars($h['status_from']) ?></td>
                    <td><?= htmlspecialchars($h['status_to']) ?></td>
                    <td><?= htmlspecialchars($h['notes'] ?? '-') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>