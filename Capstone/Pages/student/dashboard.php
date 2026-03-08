<?php
require_once '../../config/db_config.php';

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'student') {
    header('Location: login.php');
    exit();
}

$student_id = $_SESSION['user_id'];
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';

// Get student info
$student_query = "SELECT * FROM students WHERE student_id = ?";
$stmt = $conn->prepare($student_query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

// Get pending count
$pending_query = "SELECT COUNT(*) as count FROM document_requests WHERE student_id = ? AND status = 'pending'";
$stmt = $conn->prepare($pending_query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$pending = $stmt->get_result()->fetch_assoc()['count'] ?? 0;
$stmt->close();

// Get ready count
$ready_query = "SELECT COUNT(*) as count FROM document_requests WHERE student_id = ? AND status = 'ready'";
$stmt = $conn->prepare($ready_query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$ready = $stmt->get_result()->fetch_assoc()['count'] ?? 0;
$stmt->close();

// Get processing count
$processing_query = "SELECT COUNT(*) as count FROM document_requests WHERE student_id = ? AND status = 'processing'";
$stmt = $conn->prepare($processing_query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$processing = $stmt->get_result()->fetch_assoc()['count'] ?? 0;
$stmt->close();

// Get all requests with filter
$query = "SELECT dr.*, dt.document_name as document_name, dt.processing_days 
          FROM document_requests dr
          JOIN documents dt ON dr.document_id = dt.document_id
          WHERE dr.student_id = ?";

if (!empty($filter_status)) {
    $query .= " AND dr.status = ?";
}

$query .= " ORDER BY dr.created_at DESC";

$stmt = $conn->prepare($query);
if (!empty($filter_status)) {
    $stmt->bind_param("is", $student_id, $filter_status);
} else {
    $stmt->bind_param("i", $student_id);
}
$stmt->execute();
$requests = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - Document Request System</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="../../css/responsive.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="dashboard">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-brand">
                <i class="fas fa-file-certificate"></i>
                <span>DocRequest</span>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php" class="active"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="request-document.php"><i class="fas fa-plus-circle"></i> Request Document</a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                <li><a href="../shared/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <div class="main-header">
                <div>
                    <h1>Welcome, <?php echo htmlspecialchars($student['first_name']); ?>!</h1>
                </div>
            </div>

            <!-- Statistics -->
            <div class="grid-3">
                <div class="stat-card" style="border-left-color: #2563eb;">
                    <div class="stat-label">Processing</div>
                    <div class="stat-value"><?php echo $processing; ?></div>
                </div>
                <div class="stat-card" style="border-left-color: #10b981;">
                    <div class="stat-label">Ready for Pickup</div>
                    <div class="stat-value"><?php echo $ready; ?></div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card" style="margin: 2rem 0;">
                <div class="card-body" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; padding: 1.5rem;">
                    <a href="request-document.php" class="btn btn-primary" style="justify-content: center;">
                        <i class="fas fa-plus"></i> New Request
                    </a>
                    <a href="profile.php" class="btn btn-secondary" style="justify-content: center;">
                        <i class="fas fa-user"></i> My Profile
                    </a>
                </div>
            </div>

            <!-- My Requests Section -->
            <div class="card">
                <div class="card-header">
                    <h2>My Document Requests</h2>
                </div>
                
                <!-- Filters -->
                <div style="padding: 1.5rem; border-bottom: 1px solid #e5e7eb; background-color: #f9fafb;">
                    <div style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: center;">
                        <span style="font-weight: 500;">Filter:</span>
                        <a href="dashboard.php" class="btn btn-sm <?php echo empty($filter_status) ? 'btn-primary' : 'btn-secondary'; ?>">
                            All
                        </a>
                        <a href="dashboard.php?status=pending" class="btn btn-sm <?php echo $filter_status === 'pending' ? 'btn-primary' : 'btn-secondary'; ?>">
                            Pending
                        </a>
                        <a href="dashboard.php?status=processing" class="btn btn-sm <?php echo $filter_status === 'processing' ? 'btn-primary' : 'btn-secondary'; ?>">
                            Processing
                        </a>
                        <a href="dashboard.php?status=ready" class="btn btn-sm <?php echo $filter_status === 'ready' ? 'btn-primary' : 'btn-secondary'; ?>">
                            Ready
                        </a>
                        <a href="dashboard.php?status=claimed" class="btn btn-sm <?php echo $filter_status === 'claimed' ? 'btn-primary' : 'btn-secondary'; ?>">
                            Claimed
                        </a>
                    </div>
                </div>

                <!-- Requests Table -->
                <div class="card-body">
                    <table>
                        <thead>
                            <tr>
                                <th>Request ID</th>
                                <th>Document</th>
                                <th>Qty</th>
                                <th>Status</th>
                                <th>Submitted</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($requests->num_rows > 0): ?>
                                <?php while ($request = $requests->fetch_assoc()): ?>
                                    <tr>
                                        <td data-label="Request ID">
                                            <strong><?php echo htmlspecialchars($request['request_id']); ?></strong>
                                        </td>
                                        <td data-label="Document">
                                            <?php echo htmlspecialchars($request['document_name']); ?>
                                            <?php if ($request['purpose']): ?>
                                                <br><small style="color: #6b7280;">Purpose: <?php echo htmlspecialchars(substr($request['purpose'], 0, 30)); ?>...</small>
                                            <?php endif; ?>
                                        </td>
                                        <td data-label="Qty"><?php echo $request['quantity']; ?></td>
                                        <td data-label="Status">
                                            <span class="badge badge-<?php echo $request['status']; ?>">
                                                <?php echo ucfirst($request['status']); ?>
                                            </span>
                                        </td>
                                        <td data-label="Submitted"><?php echo date('M d, Y', strtotime($request['created_at'])); ?></td>
                                        <td data-label="Actions">
                                            <button class="btn btn-sm" onclick="viewDetails('<?php echo htmlspecialchars($request['request_id']); ?>')" style="background-color: #2563eb; color: white; padding: 0.4rem 0.8rem;">
                                                <i class="fas fa-eye"></i> View
                                            </button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 3rem; color: #6b7280;">
                                        <i class="fas fa-inbox" style="font-size: 2rem; margin-bottom: 1rem;"></i><br>
                                        No requests yet. <a href="request-document.php" style="color: #2563eb; font-weight: 600;">Create one now</a>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

        <!-- View Details Modal -->
    <div class="modal" id="detailsModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Request Details</h2>
                <button class="close-btn">&times;</button>
            </div>
            <div class="modal-body" id="modalBody" style="padding: 1.5rem;">
                <!-- Content will be loaded here -->
            </div>
            <div class="card-footer" style="background-color: transparent; border: none; padding: 1.5rem; margin: 0;">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('detailsModal').classList.remove('active')">Close</button>
            </div>
        </div>
    </div>

        <script src="../../js/common.js"></script>
    <script>
        function viewDetails(requestId) {
            fetch('../../api/student_api.php?action=get_request&request_id=' + requestId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const request = data.data;
                        let html = `
                            <div style="margin-bottom: 1.5rem;">
                                <h3 style="margin-bottom: 1rem;">Request Information</h3>
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                    <div>
                                        <p style="color: #6b7280; font-size: 0.875rem; margin-bottom: 0.25rem;">Request ID</p>
                                        <p style="margin: 0; font-weight: 600;">${request.request_id}</p>
                                    </div>
                                    <div>
                                        <p style="color: #6b7280; font-size: 0.875rem; margin-bottom: 0.25rem;">Status</p>
                                        <p style="margin: 0;">
                                            <span class="badge badge-${request.status}">${request.status.charAt(0).toUpperCase() + request.status.slice(1)}</span>
                                        </p>
                                    </div>
                                    <div>
                                        <p style="color: #6b7280; font-size: 0.875rem; margin-bottom: 0.25rem;">Document</p>
                                        <p style="margin: 0; font-weight: 600;">${request.document_name}</p>
                                    </div>
                                    <div>
                                        <p style="color: #6b7280; font-size: 0.875rem; margin-bottom: 0.25rem;">Quantity</p>
                                        <p style="margin: 0; font-weight: 600;">${request.quantity}</p>
                                    </div>
                                    <div>
                                        <p style="color: #6b7280; font-size: 0.875rem; margin-bottom: 0.25rem;">Submitted</p>
                                        <p style="margin: 0;">${new Date(request.created_at).toLocaleDateString()}</p>
                                    </div>
                                    <div>
                                        <p style="color: #6b7280; font-size: 0.875rem; margin-bottom: 0.25rem;">Processing Days</p>
                                        <p style="margin: 0; font-weight: 600;">${request.processing_days} days</p>
                                    </div>
                                </div>
                                ${request.purpose ? `
                                    <div style="margin-top: 1rem;">
                                        <p style="color: #6b7280; font-size: 0.875rem; margin-bottom: 0.25rem;">Purpose</p>
                                        <p style="margin: 0;">${request.purpose}</p>
                                    </div>
                                ` : ''}
                                ${request.rejection_reason ? `
                                    <div style="margin-top: 1rem; padding: 1rem; background-color: #fee2e2; border-radius: 0.5rem; border-left: 4px solid #ef4444;">
                                        <p style="color: #991b1b; margin: 0;"><strong>Rejection Reason:</strong></p>
                                        <p style="color: #991b1b; margin: 0.5rem 0 0 0;">${request.rejection_reason}</p>
                                    </div>
                                ` : ''}
                            </div>
                        `;
                        document.getElementById('modalBody').innerHTML = html;
                        document.getElementById('detailsModal').classList.add('active');
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to load request details');
                });
        }

        // Close modal when clicking close button
        document.querySelectorAll('.close-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                this.closest('.modal').classList.remove('active');
            });
        });

        // Close modal when clicking outside
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.remove('active');
                }
            });
        });
    </script>
</body>
</html>