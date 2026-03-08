<?php
require_once '../../config/db_config.php';

if (!isset($_SESSION['teacher_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch teacher info
$teacher_id = $_SESSION['teacher_id'];
$stmt = $conn->prepare("SELECT first_name, last_name, department FROM teachers WHERE teacher_id = ?");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$teacher = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Handle document filtering
$doc_filter = $_GET['doc_filter'] ?? '';
$status_filter = $_GET['status_filter'] ?? '';

// Build WHERE clause
$where = "1=1";
$params = [];
$types = "";

if ($doc_filter) {
    $where .= " AND d.document_id = ?";
    $params[] = $doc_filter;
    $types .= "i";
}
if ($status_filter) {
    $where .= " AND dr.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

// Get documents for filter dropdown
$doc_stmt = $conn->query("SELECT document_id, document_name FROM documents WHERE status = 'available'");
$documents = $doc_stmt->fetch_all(MYSQLI_ASSOC);

// Get requests with student and document info
$request_stmt = $conn->prepare("
    SELECT dr.request_id, dr.status, dr.requested_at, dr.quantity, dr.purpose, dr.notes,
           s.student_number, s.first_name, s.last_name, s.course, s.year_level,
           d.document_name, d.document_code, d.fee
    FROM document_requests dr
    JOIN students s ON dr.student_id = s.student_id
    JOIN documents d ON dr.document_id = d.document_id
    WHERE $where
    ORDER BY dr.requested_at DESC
");
if (!empty($params)) {
    $request_stmt->bind_param($types, ...$params);
}
$request_stmt->execute();
$requests = $request_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$request_stmt->close();

// Handle student search
$search = $_GET['search'] ?? '';
$students = [];
if ($search) {
    $search_stmt = $conn->prepare("
        SELECT student_id, student_number, first_name, last_name, email, course, year_level
        FROM students
        WHERE (first_name LIKE ? OR last_name LIKE ? OR student_number LIKE ? OR email LIKE ?)
        AND status = 'active'
        LIMIT 10
    ");
    $search_term = "%$search%";
    $search_stmt->bind_param("ssss", $search_term, $search_term, $search_term, $search_term);
    $search_stmt->execute();
    $students = $search_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $search_stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Teacher Dashboard</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="../../css/responsive.css">
</head>
<body>
    <div class="dashboard">
        <aside class="sidebar">
            <h3><?= htmlspecialchars($teacher['first_name']) ?> <?= htmlspecialchars($teacher['last_name']) ?></h3>
            <p><?= htmlspecialchars($teacher['department'] ?? 'N/A') ?></p>
            <ul>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="student-search.php">Search Students</a></li>
                <li><a href="view-requests.php">View Requests</a></li>
                <li><a href="schedule-pickup.php">Schedule Pickup</a></li>
                <li><a href="send-notifications.php">Send Notifications</a></li>
                <li><a href="transaction-report.php">Transaction Report</a></li>
                <li><a href="shared/logout.php">Logout</a></li>
            </ul>
            <hr>
            <button class="btn-primary" onclick="document.getElementById('createTeacherModal').style.display='block'">Add Teacher</button>
        </aside>

        <main class="main-content">
            <h2>Dashboard</h2>

            <!-- Student Search -->
            <section class="search-section">
                <h3>Search Students</h3>
                <form method="GET" style="margin-bottom: 1rem;">
                    <input type="text" name="search" placeholder="Name, Number, Email..." value="<?= htmlspecialchars($search) ?>">
                    <button type="submit">Search</button>
                </form>
                <?php if (!empty($students)): ?>
                    <table class="table">
                        <thead><tr><th>Student No.</th><th>Name</th><th>Course</th><th>Year</th><th>Email</th></tr></thead>
                        <tbody>
                            <?php foreach ($students as $s): ?>
                                <tr>
                                    <td><?= htmlspecialchars($s['student_number']) ?></td>
                                    <td><?= htmlspecialchars($s['first_name']) . ' ' . htmlspecialchars($s['last_name']) ?></td>
                                    <td><?= htmlspecialchars($s['course']) ?></td>
                                    <td><?= $s['year_level'] ?></td>
                                    <td><?= htmlspecialchars($s['email']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </section>

            <!-- Document Requests with Filters -->
            <section class="requests-section">
                <h3>Document Requests</h3>
                <form method="GET" style="margin-bottom: 1rem; display: flex; gap: 1rem; flex-wrap: wrap;">
                    <select name="doc_filter">
                        <option value="">All Documents</option>
                        <?php foreach ($documents as $doc): ?>
                            <option value="<?= $doc['document_id'] ?>" <?= $doc_filter == $doc['document_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($doc['document_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <select name="status_filter">
                        <option value="">All Statuses</option>
                        <option value="pending" <?= $status_filter == 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="processing" <?= $status_filter == 'processing' ? 'selected' : '' ?>>Processing</option>
                        <option value="ready" <?= $status_filter == 'ready' ? 'selected' : '' ?>>Ready</option>
                        <option value="released" <?= $status_filter == 'released' ? 'selected' : '' ?>>Released</option>
                        <option value="cancelled" <?= $status_filter == 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                    <button type="submit">Filter</button>
                </form>

                <?php if (empty($requests)): ?>
                    <p>No requests found.</p>
                <?php else: ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Document</th>
                                <th>Quantity</th>
                                <th>Status</th>
                                <th>Requested</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($requests as $req): ?>
                                <tr>
                                    <td><?= htmlspecialchars($req['first_name']) . ' ' . htmlspecialchars($req['last_name']) ?><br><small><?= $req['student_number'] ?></small></td>
                                    <td><?= htmlspecialchars($req['document_name']) ?> (<?= $req['document_code'] ?>)</td>
                                    <td><?= $req['quantity'] ?></td>
                                    <td>
                                        <span class="status <?= $req['status'] ?>">
                                            <?= ucfirst($req['status']) ?>
                                        </span>
                                    </td>
                                    <td><?= date('M d, Y g:i A', strtotime($req['requested_at'])) ?></td>
                                    <td>
                                        <a href="view-requests.php?request_id=<?= $req['request_id'] ?>" class="btn-small">View</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </section>
        </main>
    </div>

    <!-- Modal for adding teacher -->
    <div id="createTeacherModal" class="modal" style="display:none;">
        <div class="modal-content">
            <span class="close" onclick="document.getElementById('createTeacherModal').style.display='none'">&times;</span>
            <h3>Add New Teacher</h3>
            <form method="POST" action="add_teacher.php">
                <input type="text" name="teacher_number" placeholder="Teacher Number" required>
                <input type="email" name="gmail" placeholder="Email" required>
                <input type="text" name="first_name" placeholder="First Name" required>
                <input type="text" name="last_name" placeholder="Last Name" required>
                <input type="text" name="department" placeholder="Department">
                <input type="password" name="password" placeholder="Password" required>
                <input type="password" name="confirm_password" placeholder="Confirm Password" required>
                <button type="submit">Create Teacher</button>
            </form>
        </div>
    </div>

    <script src="../../js/common.js"></script>
</body>
</html>