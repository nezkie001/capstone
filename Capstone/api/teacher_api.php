 <?php
require_once '../config/db_config.php';

header('Content-Type: application/json');

// Check if teacher is logged in for most actions
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action !== 'login' && !isset($_SESSION['teacher_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

switch ($action) {
    case 'login':
        handleLogin();
        break;
    case 'search_students':
        searchStudents();
        break;
    case 'get_student_details':
        getStudentDetails();
        break;
    case 'get_requests':
        getRequests();
        break;
    case 'update_request_status':
        updateRequestStatus();
        break;
    case 'send_notification':
        sendNotification();
        break;
    case 'generate_report':
        generateReport();
        break;
    case 'create_teacher':
        createTeacher();
        break;
    case 'get_transaction_history':
        getTransactionHistory();
        break;
    case 'schedule_pickup':
        schedulePickup();
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

function handleLogin() {
    global $conn;
    
    $gmail = filter_var($_POST['gmail'], FILTER_SANITIZE_EMAIL);
    $teacher_number = filter_var($_POST['teacher_number'], FILTER_SANITIZE_STRING);
    $password = $_POST['password'];
    
    // Validate Gmail domain
    if (!preg_match('/@gmail\.com$/', $gmail)) {
        echo json_encode(['success' => false, 'message' => 'Please use a Gmail address']);
        return;
    }
    
    $stmt = $conn->prepare("SELECT * FROM teachers WHERE gmail = ? AND teacher_number = ? AND status = 'active'");
    $stmt->bind_param("ss", $gmail, $teacher_number);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($teacher = $result->fetch_assoc()) {
        if (password_verify($password, $teacher['password'])) {
            $_SESSION['teacher_id'] = $teacher['teacher_id'];
            $_SESSION['teacher_name'] = $teacher['first_name'] . ' ' . $teacher['last_name'];
            $_SESSION['teacher_role'] = $teacher['role'];
            
            echo json_encode([
                'success' => true, 
                'message' => 'Login successful',
                'redirect' => 'dashboard.php'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Account not found or inactive']);
    }
}

function searchStudents() {
    global $conn;
    
    $search = $_GET['search'] ?? '';
    $status = $_GET['status'] ?? '';
    $year = $_GET['year'] ?? '';
    $course = $_GET['course'] ?? '';
    
    $query = "SELECT * FROM students WHERE 1=1";
    $params = [];
    $types = "";
    
    if ($search) {
        $query .= " AND (first_name LIKE ? OR last_name LIKE ? OR student_number LIKE ? OR email LIKE ?)";
        $searchParam = "%$search%";
        $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
        $types .= "ssss";
    }
    
    if ($status) {
        $query .= " AND status = ?";
        $params[] = $status;
        $types .= "s";
    }
    
    if ($year) {
        $query .= " AND year_level = ?";
        $params[] = $year;
        $types .= "i";
    }
    
    if ($course) {
        $query .= " AND course = ?";
        $params[] = $course;
        $types .= "s";
    }
    
    $query .= " ORDER BY last_name, first_name";
    
    $stmt = $conn->prepare($query);
    if ($params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    $students = [];
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
    
    echo json_encode(['success' => true, 'students' => $students]);
}

function getStudentDetails() {
    global $conn;
    
    $student_id = $_GET['student_id'] ?? 0;
    
    // Get student info
    $stmt = $conn->prepare("SELECT * FROM students WHERE student_id = ?");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $student = $stmt->get_result()->fetch_assoc();
    
    // Get student's requests
    $stmt = $conn->prepare("
        SELECT dr.*, d.document_name, d.document_code 
        FROM document_requests dr
        JOIN documents d ON dr.document_id = d.document_id
        WHERE dr.student_id = ?
        ORDER BY dr.requested_at DESC
    ");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $requests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode([
        'success' => true, 
        'student' => $student,
        'requests' => $requests
    ]);
}

function getRequests() {
    global $conn;
    
    $document = $_GET['document'] ?? '';
    $status = $_GET['status'] ?? '';
    $date_from = $_GET['date_from'] ?? '';
    $date_to = $_GET['date_to'] ?? '';
    
    $query = "
        SELECT dr.*, s.first_name, s.last_name, s.student_number, s.email,
               d.document_name, d.document_code,
               ps.scheduled_date
        FROM document_requests dr
        JOIN students s ON dr.student_id = s.student_id
        JOIN documents d ON dr.document_id = d.document_id
        LEFT JOIN pickup_schedules ps ON dr.request_id = ps.request_id
        WHERE 1=1
    ";
    
    $params = [];
    $types = "";
    
    if ($document) {
        $query .= " AND d.document_code = ?";
        $params[] = $document;
        $types .= "s";
    }
    
    if ($status) {
        $query .= " AND dr.status = ?";
        $params[] = $status;
        $types .= "s";
    }
    
    if ($date_from && $date_to) {
        $query .= " AND DATE(dr.requested_at) BETWEEN ? AND ?";
        $params[] = $date_from;
        $params[] = $date_to;
        $types .= "ss";
    }
    
    $query .= " ORDER BY dr.requested_at DESC";
    
    $stmt = $conn->prepare($query);
    if ($params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    $requests = [];
    while ($row = $result->fetch_assoc()) {
        $requests[] = $row;
    }
    
    echo json_encode(['success' => true, 'requests' => $requests]);
}

function updateRequestStatus() {
    global $conn;
    
    $request_id = $_POST['request_id'];
    $new_status = $_POST['status'];
    $notes = $_POST['notes'] ?? '';
    
    // Get current status
    $stmt = $conn->prepare("SELECT status, student_id FROM document_requests WHERE request_id = ?");
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $current = $stmt->get_result()->fetch_assoc();
    
    // Update request status
    $stmt = $conn->prepare("UPDATE document_requests SET status = ?, updated_at = NOW() WHERE request_id = ?");
    $stmt->bind_param("si", $new_status, $request_id);
    
    if ($stmt->execute()) {
        // Log transaction history
        $stmt = $conn->prepare("
            INSERT INTO transaction_history (request_id, student_id, action, action_by, status_from, status_to, notes)
            VALUES (?, ?, 'Status Update', ?, ?, ?, ?)
        ");
        $action_by = $_SESSION['teacher_name'];
        $stmt->bind_param("iissss", 
            $request_id, 
            $current['student_id'], 
            $action_by,
            $current['status'], 
            $new_status, 
            $notes
        );
        $stmt->execute();
        
        echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update status']);
    }
}

function sendNotification() {
    global $conn;
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    $student_ids = $data['student_ids'] ?? [];
    $type = $data['type'];
    $subject = $data['subject'];
    $message = $data['message'];
    $teacher_id = $_SESSION['teacher_id'];
    
    $success_count = 0;
    
    foreach ($student_ids as $student_id) {
        // Insert notification record
        $stmt = $conn->prepare("
            INSERT INTO notifications (student_id, teacher_id, type, subject, message, status)
            VALUES (?, ?, ?, ?, ?, 'pending')
        ");
        $stmt->bind_param("iisss", $student_id, $teacher_id, $type, $subject, $message);
        
        if ($stmt->execute()) {
            // Get student email
            $stmt = $conn->prepare("SELECT email, first_name FROM students WHERE student_id = ?");
            $stmt->bind_param("i", $student_id);
            $stmt->execute();
            $student = $stmt->get_result()->fetch_assoc();
            
            // Send email (using mail() function - in production, use PHPMailer)
            $to = $student['email'];
            $headers = "From: School Registrar <noreply@school.edu>\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            
            $email_body = "
                <html>
                <head><title>$subject</title></head>
                <body>
                    <p>Dear {$student['first_name']},</p>
                    <p>$message</p>
                    <p>Best regards,<br>School Registrar Office</p>
                </body>
                </html>
            ";
            
            if (mail($to, $subject, $email_body, $headers)) {
                // Update notification status
                $conn->query("UPDATE notifications SET status = 'sent', sent_at = NOW() 
                             WHERE notification_id = " . $conn->insert_id);
                $success_count++;
            }
        }
    }
    
    echo json_encode([
        'success' => true, 
        'message' => "Notifications sent to $success_count students"
    ]);
}

function generateReport() {
    global $conn;
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    $type = $data['type'];
    $date_from = $data['date_from'];
    $date_to = $data['date_to'];
    $student_id = $data['student_id'] ?? null;
    
    $report_html = '';
    
    switch ($type) {
        case 'summary':
            $report_html = generateSummaryReport($date_from, $date_to);
            break;
        case 'detailed':
            $report_html = generateDetailedReport($date_from, $date_to);
            break;
        case 'student':
            $report_html = generateStudentReport($student_id, $date_from, $date_to);
            break;
        case 'document':
            $report_html = generateDocumentReport($date_from, $date_to);
            break;
        case 'status':
            $report_html = generateStatusReport($date_from, $date_to);
            break;
    }
    
    echo json_encode(['success' => true, 'report_html' => $report_html]);
}

function generateSummaryReport($date_from, $date_to) {
    global $conn;
    
    // Get summary statistics
    $stmt = $conn->prepare("
        SELECT 
            COUNT(DISTINCT dr.request_id) as total_requests,
            COUNT(DISTINCT dr.student_id) as total_students,
            SUM(CASE WHEN dr.status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN dr.status = 'processing' THEN 1 ELSE 0 END) as processing,
            SUM(CASE WHEN dr.status = 'ready' THEN 1 ELSE 0 END) as ready,
            SUM(CASE WHEN dr.status = 'released' THEN 1 ELSE 0 END) as released,
            SUM(CASE WHEN dr.status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
        FROM document_requests dr
        WHERE DATE(requested_at) BETWEEN ? AND ?
    ");
    $stmt->bind_param("ss", $date_from, $date_to);
    $stmt->execute();
    $summary = $stmt->get_result()->fetch_assoc();
    
    // Get document type breakdown
    $stmt = $conn->prepare("
        SELECT d.document_name, COUNT(*) as count
        FROM document_requests dr
        JOIN documents d ON dr.document_id = d.document_id
        WHERE DATE(dr.requested_at) BETWEEN ? AND ?
        GROUP BY d.document_id
        ORDER BY count DESC
    ");
    $stmt->bind_param("ss", $date_from, $date_to);
    $stmt->execute();
    $documents = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    $html = "
        <div class='report-summary'>
            <h3>Transaction Summary Report</h3>
            <p><strong>Period:</strong> $date_from to $date_to</p>
            
            <div class='summary-stats'>
                <div class='stat-item'>
                    <label>Total Requests:</label>
                    <span>{$summary['total_requests']}</span>
                </div>
                <div class='stat-item'>
                    <label>Total Students:</label>
                    <span>{$summary['total_students']}</span>
                </div>
            </div>
            
            <h4>Status Breakdown</h4>
            <table class='report-table'>
                <tr>
                    <th>Status</th>
                    <th>Count</th>
                    <th>Percentage</th>
                </tr>
                <tr>
                    <td>Pending</td>
                    <td>{$summary['pending']}</td>
                    <td>" . round(($summary['pending']/$summary['total_requests'])*100, 1) . "%</td>
                </tr>
                <tr>
                    <td>Processing</td>
                    <td>{$summary['processing']}</td>
                    <td>" . round(($summary['processing']/$summary['total_requests'])*100, 1) . "%</td>
                </tr>
                <tr>
                    <td>Ready</td>
                    <td>{$summary['ready']}</td>
                    <td>" . round(($summary['ready']/$summary['total_requests'])*100, 1) . "%</td>
                </tr>
                <tr>
                    <td>Released</td>
                    <td>{$summary['released']}</td>
                    <td>" . round(($summary['released']/$summary['total_requests'])*100, 1) . "%</td>
                </tr>
                <tr>
                    <td>Cancelled</td>
                    <td>{$summary['cancelled']}</td>
                    <td>" . round(($summary['cancelled']/$summary['total_requests'])*100, 1) . "%</td>
                </tr>
            </table>
            
            <h4>Document Type Breakdown</h4>
            <table class='report-table'>
                <tr>
                    <th>Document Type</th>
                    <th>Requests</th>
                </tr>";
    
    foreach ($documents as $doc) {
        $html .= "
                <tr>
                    <td>{$doc['document_name']}</td>
                    <td>{$doc['count']}</td>
                </tr>";
    }
    
    $html .= "
            </table>
        </div>
    ";
    
    return $html;
}

function createTeacher() {
    global $conn;
    
    // Check if logged in teacher has permission
    if ($_SESSION['teacher_role'] !== 'admin' && $_SESSION['teacher_role'] !== 'registrar') {
        echo json_encode(['success' => false, 'message' => 'Insufficient permissions']);
        return;
    }
    
    $teacher_number = filter_var($_POST['teacher_number'], FILTER_SANITIZE_STRING);
    $gmail = filter_var($_POST['gmail'], FILTER_SANITIZE_EMAIL);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $first_name = filter_var($_POST['first_name'], FILTER_SANITIZE_STRING);
    $last_name = filter_var($_POST['last_name'], FILTER_SANITIZE_STRING);
    $department = filter_var($_POST['department'], FILTER_SANITIZE_STRING);
    $role = $_POST['role'];
    
    $stmt = $conn->prepare("
        INSERT INTO teachers (teacher_number, gmail, password, first_name, last_name, department, role)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("sssssss", $teacher_number, $gmail, $password, $first_name, $last_name, $department, $role);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Teacher account created successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error creating account']);
    }
}
?>
