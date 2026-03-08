<?php
require_once '../../config/db_config.php';

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'student') {
    header('Location: login.php');
    exit();
}

$student_id = $_SESSION['user_id'];

// Get student info
$student_query = "SELECT * FROM students WHERE student_id = ?";
$stmt = $conn->prepare($student_query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

if (!$student) {
    header('Location: login.php');
    exit();
}

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

// Get recent documents
$recent_query = "SELECT dr.*, dt.document_name 
                 FROM document_requests dr
                 JOIN documents dt ON dr.document_id = dt.document_id
                 WHERE dr.student_id = ?
                 ORDER BY dr.created_at DESC
                 LIMIT 5";
$stmt = $conn->prepare($recent_query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$recent_documents = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Profile - Document Request System</title>
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
                <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="request-document.php"><i class="fas fa-plus-circle"></i> Request Document</a></li>
                <li><a href="profile.php" class="active"><i class="fas fa-user"></i> Profile</a></li>
                <li><a href="../shared/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <div class="main-header">
                <h1>My Profile</h1>
                <button id="editBtn" class="btn btn-primary" onclick="toggleEditMode()">
                    <i class="fas fa-edit"></i> Edit Profile
                </button>
            </div>

            <!-- Profile Card -->
            <div class="card">
                <div class="card-header">
                    <h2>Personal Information</h2>
                </div>
                
                <div class="card-body">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem;">
                        <!-- Left Column -->
                        <div>
                            <div style="margin-bottom: 1.5rem;">
                                <p style="color: #6b7280; font-size: 0.875rem; margin-bottom: 0.5rem; font-weight: 500;">Full Name</p>
                                <p style="margin: 0; font-size: 1.125rem; font-weight: 600;">
                                    <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>
                                </p>
                            </div>

                            <div style="margin-bottom: 1.5rem;">
                                <p style="color: #6b7280; font-size: 0.875rem; margin-bottom: 0.5rem; font-weight: 500;">Student ID</p>
                                <p style="margin: 0; font-weight: 600;">
                                    <?php echo htmlspecialchars($student['student_number']); ?>
                                </p>
                            </div>

                            <div style="margin-bottom: 1.5rem;">
                                <p style="color: #6b7280; font-size: 0.875rem; margin-bottom: 0.5rem; font-weight: 500;">Email Address</p>
                                <p style="margin: 0;">
                                    <?php echo htmlspecialchars($student['email']); ?>
                                </p>
                            </div>

                            <div style="margin-bottom: 1.5rem;">
                                <p style="color: #6b7280; font-size: 0.875rem; margin-bottom: 0.5rem; font-weight: 500;">Phone Number</p>
                                <p style="margin: 0;">
                                    <?php echo htmlspecialchars($student['phone'] ?? 'Not provided'); ?>
                                </p>
                            </div>
                        </div>

                        <!-- Right Column -->
                        <div>
                            <div style="margin-bottom: 1.5rem;">
                                <p style="color: #6b7280; font-size: 0.875rem; margin-bottom: 0.5rem; font-weight: 500;">Date of Birth</p>
                                <p style="margin: 0;">
                                    <?php echo $student['date_of_birth'] ? date('F d, Y', strtotime($student['date_of_birth'])) : 'Not provided'; ?>
                                </p>
                            </div>

                            <div style="margin-bottom: 1.5rem;">
                                <p style="color: #6b7280; font-size: 0.875rem; margin-bottom: 0.5rem; font-weight: 500;">Course</p>
                                <p style="margin: 0; font-weight: 600;">
                                    <?php echo htmlspecialchars($student['course'] ?? 'Not specified'); ?>
                                </p>
                            </div>

                            <div style="margin-bottom: 1.5rem;">
                                <p style="color: #6b7280; font-size: 0.875rem; margin-bottom: 0.5rem; font-weight: 500;">Year Level</p>
                                <p style="margin: 0;">
                                    <?php 
                                    $year_levels = [1 => '1st Year', 2 => '2nd Year', 3 => '3rd Year', 4 => '4th Year'];
                                    echo htmlspecialchars($year_levels[$student['yearLevel']] ?? 'Not specified');
                                    ?>
                                </p>
                            </div>

                            <div style="margin-bottom: 1.5rem;">
                                <p style="color: #6b7280; font-size: 0.875rem; margin-bottom: 0.5rem; font-weight: 500;">Account Status</p>
                                <p style="margin: 0;">
                                    <span class="badge badge-<?php echo $student['status']; ?>">
                                        <?php echo ucfirst($student['status']); ?>
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Edit Profile Modal -->
    <div class="modal" id="editModal">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h2>Edit Profile</h2>
                <button class="close-btn" onclick="toggleEditMode()">&times;</button>
            </div>
            <div class="modal-body" style="padding: 1.5rem;">
                <form id="profileForm">
                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: #374151;">Email Address</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($student['email']); ?>" 
                            style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 0.375rem; font-size: 0.875rem;" required>
                    </div>

                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: #374151;">Phone Number</label>
                        <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($student['phone'] ?? ''); ?>"
                            style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 0.375rem; font-size: 0.875rem;">
                    </div>

                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: #374151;">Date of Birth</label>
                        <input type="date" id="date_of_birth" name="date_of_birth" value="<?php echo htmlspecialchars($student['date_of_birth'] ?? ''); ?>"
                            style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 0.375rem; font-size: 0.875rem;">
                    </div>

                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: #374151;">Year Level</label>
                        <select id="yearLevel" name="yearLevel" 
                            style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 0.375rem; font-size: 0.875rem;">
                            <option value="">Select Year Level</option>
                            <option value="1" <?php echo $student['yearLevel'] == 1 ? 'selected' : ''; ?>>1st Year</option>
                            <option value="2" <?php echo $student['yearLevel'] == 2 ? 'selected' : ''; ?>>2nd Year</option>
                            <option value="3" <?php echo $student['yearLevel'] == 3 ? 'selected' : ''; ?>>3rd Year</option>
                            <option value="4" <?php echo $student['yearLevel'] == 4 ? 'selected' : ''; ?>>4th Year</option>
                        </select>
                    </div>

                    <div id="successMessage" style="display: none; padding: 1rem; margin-bottom: 1.5rem; background-color: #d1fae5; color: #065f46; border-radius: 0.375rem; border-left: 4px solid #10b981;">
                        Profile updated successfully!
                    </div>

                    <div id="errorMessage" style="display: none; padding: 1rem; margin-bottom: 1.5rem; background-color: #fee2e2; color: #991b1b; border-radius: 0.375rem; border-left: 4px solid #ef4444;"></div>
                </form>
            </div>
            <div class="card-footer" style="display: flex; gap: 0.5rem; justify-content: flex-end; background-color: transparent; border: none; padding: 1.5rem; margin: 0;">
                <button type="button" class="btn btn-secondary" onclick="toggleEditMode()">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveProfile()">Save Changes</button>
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
        function toggleEditMode() {
            const modal = document.getElementById('editModal');
            modal.classList.toggle('active');
            
            // Clear messages
            document.getElementById('successMessage').style.display = 'none';
            document.getElementById('errorMessage').style.display = 'none';
        }

        function saveProfile() {
            const formData = new FormData(document.getElementById('profileForm'));
            
            fetch('../../api/update_profile.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('successMessage').style.display = 'block';
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    const errorDiv = document.getElementById('errorMessage');
                    errorDiv.textContent = data.message;
                    errorDiv.style.display = 'block';
                }
            })
            .catch(error => {
                const errorDiv = document.getElementById('errorMessage');
                errorDiv.textContent = 'An error occurred. Please try again.';
                errorDiv.style.display = 'block';
                console.error('Error:', error);
            });
        }

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
                    }
                })
                .catch(error => console.error('Error:', error));
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