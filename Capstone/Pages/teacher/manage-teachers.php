<?php
session_start();
require_once '../../config/db_config.php';

// Check authorization - Only admin or registrar can access
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'teacher' || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$teacher_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Get teacher info
$teacher_query = "SELECT * FROM teachers WHERE teacher_id = ?";
$stmt = $conn->prepare($teacher_query);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$teacher = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get all teachers
$teachers_query = "SELECT * FROM teachers ORDER BY created_at DESC";
$all_teachers = $conn->query($teachers_query);

// Create new teacher account
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_teacher') {
    $teacher_number = mysqli_real_escape_string($conn, $_POST['teacher_number'] ?? '');
    $gmail = mysqli_real_escape_string($conn, $_POST['gmail'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $first_name = mysqli_real_escape_string($conn, $_POST['first_name'] ?? '');
    $last_name = mysqli_real_escape_string($conn, $_POST['last_name'] ?? '');
    $department = mysqli_real_escape_string($conn, $_POST['department'] ?? '');
    $phone = mysqli_real_escape_string($conn, $_POST['phone'] ?? '');
    $role = $_POST['role'] ?? 'teacher';

    // Validation
    if (empty($teacher_number) || empty($gmail) || empty($password) || empty($first_name) || empty($last_name)) {
        $error = 'All required fields must be filled';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (!filter_var($gmail, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address';
    } else {
        // Check if teacher_number or gmail already exists
        $check_query = "SELECT teacher_id FROM teachers WHERE teacher_number = ? OR gmail = ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("ss", $teacher_number, $gmail);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        $check_stmt->close();

        if ($check_result->num_rows > 0) {
            $error = 'Teacher number or email already exists';
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);

            // Insert new teacher
            $insert_query = "INSERT INTO teachers (teacher_number, gmail, password, first_name, last_name, department, phone, role, status) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')";
            $insert_stmt = $conn->prepare($insert_query);
            $insert_stmt->bind_param("ssssssss", $teacher_number, $gmail, $hashed_password, $first_name, $last_name, $department, $phone, $role);

            if ($insert_stmt->execute()) {
                $success = 'Teacher account created successfully!';
                // Refresh teachers list
                $all_teachers = $conn->query($teachers_query);
            } else {
                error_log("Insert error: " . $insert_stmt->error);
                $error = 'Error creating teacher account. Please try again.';
            }
            $insert_stmt->close();
        }
    }
}

// Delete teacher
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_teacher') {
    $delete_id = intval($_POST['teacher_id'] ?? 0);

    if ($delete_id > 0 && $delete_id !== $teacher_id) {
        $delete_query = "DELETE FROM teachers WHERE teacher_id = ?";
        $delete_stmt = $conn->prepare($delete_query);
        $delete_stmt->bind_param("i", $delete_id);

        if ($delete_stmt->execute()) {
            $success = 'Teacher account deleted successfully!';
            $all_teachers = $conn->query($teachers_query);
        } else {
            $error = 'Error deleting teacher account.';
        }
        $delete_stmt->close();
    } else {
        $error = 'Cannot delete your own account or invalid teacher.';
    }
}

// Update teacher status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $update_id = intval($_POST['teacher_id'] ?? 0);
    $new_status = $_POST['status'] ?? 'active';

    if ($update_id > 0 && in_array($new_status, ['active', 'inactive'])) {
        $update_query = "UPDATE teachers SET status = ? WHERE teacher_id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("si", $new_status, $update_id);

        if ($update_stmt->execute()) {
            $success = 'Teacher status updated successfully!';
            $all_teachers = $conn->query($teachers_query);
        } else {
            $error = 'Error updating teacher status.';
        }
        $update_stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Teachers - Document Request System</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="../../css/responsive.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="dashboard">
        <aside class="sidebar">
            <div class="sidebar-brand">
                <i class="fas fa-file-certificate"></i>
                <span>DocRequest</span>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="view-requests.php"><i class="fas fa-inbox"></i> View Requests</a></li>
                <li><a href="student-search.php"><i class="fas fa-search"></i> Search Students</a></li>
                <li><a href="schedule-pickup.php"><i class="fas fa-calendar"></i> Schedule Pickup</a></li>
                <li><a href="send-notifications.php"><i class="fas fa-bell"></i> Send Notifications</a></li>
                <li><a href="transaction-report.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
                <?php if ($_SESSION['role'] === 'admin'): ?>
                    <li><a href="manage-teachers.php" class="active"><i class="fas fa-users"></i> Manage Teachers</a></li>
                <?php endif; ?>
                <li><a href="../shared/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <div class="main-content">
            <div class="main-header">
                <div>
                    <h1>Manage Teachers</h1>
                    <p style="color: #6b7280; margin: 0; margin-top: 0.25rem;">Create and manage teacher accounts</p>
                </div>
                <div class="user-avatar"><?php echo strtoupper(substr($teacher['first_name'], 0, 1)); ?></div>
            </div>

            <div class="grid-2">
                <!-- Create New Teacher -->
                <div class="card">
                    <div class="card-header">
                        <h2>Create New Teacher Account</h2>
                    </div>
                    <div class="card-body" style="padding: 1.5rem;">
                        <?php if ($success): ?>
                            <div class="alert alert-success" style="margin-bottom: 1.5rem;">
                                <i class="fas fa-check-circle"></i>
                                <span><?php echo htmlspecialchars($success); ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if ($error): ?>
                            <div class="alert alert-error" style="margin-bottom: 1.5rem;">
                                <i class="fas fa-exclamation-circle"></i>
                                <span><?php echo htmlspecialchars($error); ?></span>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <input type="hidden" name="action" value="create_teacher">

                            <div class="form-group">
                                <label for="teacher_number">Teacher Number</label>
                                <input type="text" id="teacher_number" name="teacher_number" placeholder="e.g., T-2024-001" required>
                            </div>

                            <div class="form-group">
                                <label for="first_name">First Name</label>
                                <input type="text" id="first_name" name="first_name" required>
                            </div>

                            <div class="form-group">
                                <label for="last_name">Last Name</label>
                                <input type="text" id="last_name" name="last_name" required>
                            </div>

                            <div class="form-group">
                                <label for="department">Department</label>
                                <input type="text" id="department" name="department" placeholder="e.g., Registrar Office">
                            </div>

                            <div class="form-group">
                                <label for="phone">Phone Number</label>
                                <input type="tel" id="phone" name="phone" placeholder="+63 (0) 123-456-7890">
                            </div>

                            <div class="form-group">
                                <label for="gmail">Gmail Address</label>
                                <input type="email" id="gmail" name="gmail" placeholder="teacher@gmail.com" required>
                            </div>

                            <div class="form-group">
                                <label for="password">Password</label>
                                <input type="password" id="password" name="password" placeholder="At least 8 characters" required>
                            </div>

                            <div class="form-group">
                                <label for="confirm_password">Confirm Password</label>
                                <input type="password" id="confirm_password" name="confirm_password" required>
                            </div>

                            <div class="form-group">
                                <label for="role">Role</label>
                                <select id="role" name="role">
                                    <option value="teacher">Teacher</option>
                                    <option value="registrar">Registrar</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>

                            <div class="card-footer" style="background-color: transparent; border: none; padding: 0; margin: 0;">
                                <button type="reset" class="btn btn-secondary">Clear</button>
                                <button type="submit" class="btn btn-primary">Create Account</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Teachers List -->
                <div class="card">
                    <div class="card-header">
                        <h2>All Teachers (<?php echo $all_teachers->num_rows; ?>)</h2>
                    </div>
                    <div class="card-body" style="padding: 0;">
                        <div style="max-height: 600px; overflow-y: auto;">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $all_teachers->data_seek(0);
                                    while ($t = $all_teachers->fetch_assoc()): 
                                    ?>
                                        <tr>
                                            <td data-label="Name">
                                                <strong><?php echo htmlspecialchars($t['first_name'] . ' ' . $t['last_name']); ?></strong>
                                                <?php if ($t['teacher_id'] === $teacher_id): ?>
                                                    <span class="badge badge-info" style="margin-left: 0.5rem;">You</span>
                                                <?php endif; ?>
                                            </td>
                                            <td data-label="Email"><?php echo htmlspecialchars($t['gmail']); ?></td>
                                            <td data-label="Role">
                                                <span class="badge badge-info"><?php echo ucfirst($t['role']); ?></span>
                                            </td>
                                            <td data-label="Status">
                                                <span class="badge badge-<?php echo $t['status']; ?>">
                                                    <?php echo ucfirst($t['status']); ?>
                                                </span>
                                            </td>
                                            <td data-label="Actions">
                                                <?php if ($t['teacher_id'] !== $teacher_id): ?>
                                                    <form method="POST" action="" style="display: inline;">
                                                        <input type="hidden" name="action" value="update_status">
                                                        <input type="hidden" name="teacher_id" value="<?php echo $t['teacher_id']; ?>">
                                                        <input type="hidden" name="status" value="<?php echo $t['status'] === 'active' ? 'inactive' : 'active'; ?>">
                                                        <button type="submit" class="btn btn-sm" style="background-color: #8b5cf6; color: white; padding: 0.4rem 0.8rem;">
                                                            <?php echo $t['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>
                                                        </button>
                                                    </form>
                                                    <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this teacher?');">
                                                        <input type="hidden" name="action" value="delete_teacher">
                                                        <input type="hidden" name="teacher_id" value="<?php echo $t['teacher_id']; ?>">
                                                        <button type="submit" class="btn btn-sm" style="background-color: #ef4444; color: white; padding: 0.4rem 0.8rem;">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <span style="color: #6b7280; font-size: 0.875rem;">Current user</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../../js/common.js"></script>
    <script>
        // Password strength indicator
        document.getElementById('password')?.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;

            if (password.length >= 8) strength++;
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
            if (/\d/.test(password)) strength++;
            if (/[@$!%*?&]/.test(password)) strength++;

            const confirmInput = document.getElementById('confirm_password');
            if (confirmInput.value && password === confirmInput.value) {
                confirmInput.style.borderColor = '#10b981';
            } else if (confirmInput.value) {
                confirmInput.style.borderColor = '#ef4444';
            }
        });

        document.getElementById('confirm_password')?.addEventListener('input', function() {
            const password = document.getElementById('password').value;
            if (this.value === password) {
                this.style.borderColor = '#10b981';
            } else {
                this.style.borderColor = '#ef4444';
            }
        });
    </script>
</body>
</html>