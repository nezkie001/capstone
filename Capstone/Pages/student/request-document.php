<?php
require_once '../../config/db_config.php';
require_once '../../api/notifications.php'; // Include notification functions

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'student') {
    header('Location: login.php');
    exit();
}

$student_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Get available document types
$doc_types_query = "SELECT * FROM documents ORDER BY document_name ASC";
$doc_types = $conn->query($doc_types_query);

if (!$doc_types) {
    error_log("Query error: " . $conn->error);
    $error = "Error loading documents. Please try again.";
}

// Get student info
$student_query = "SELECT * FROM students WHERE student_id = ?";
$stmt = $conn->prepare($student_query);

if (!$stmt) {
    error_log("Prepare error: " . $conn->error);
    $error = "Database error. Please try again.";
} else {
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $student = $stmt->get_result()->fetch_assoc();
    
    if (!$student) {
        $error = "Student profile not found.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $document_type_id = intval($_POST['document_type_id'] ?? 0);
    $quantity = intval($_POST['quantity'] ?? 0);
    $purpose = mysqli_real_escape_string($conn, $_POST['purpose'] ?? '');

    if (empty($document_type_id) || $quantity < 1) {
        $error = 'Please select a document and quantity';
    } elseif (empty($purpose)) {
        $error = 'Please specify the purpose of request';
    } else {
        // Generate unique request ID
        $request_id = 'REQ-' . date('YmdHis') . '-' . strtoupper(substr(md5(uniqid()), 0, 4));

        // Insert request
        $insert_query = "INSERT INTO document_requests (request_id, student_id, document_id, quantity, purpose, status) 
                        VALUES (?, ?, ?, ?, ?, 'processing')";
        $insert_stmt = $conn->prepare($insert_query);

        if (!$insert_stmt) {
            error_log("Insert prepare error: " . $conn->error);
            $error = 'Error submitting request. Please try again.';
        } else {
            $insert_stmt->bind_param("siiss", $request_id, $student_id, $document_type_id, $quantity, $purpose);

            if ($insert_stmt->execute()) {
                // Get the inserted request ID
                $request_db_id = $conn->insert_id;
                
                // Log transaction
                $action = 'Document request created';
                $action_by = $student_id;
                $status_from = 'new';
                $status_to = 'processing';
                $notes = 'Request submitted for ' . $quantity . ' document(s)';

                $log_query = "INSERT INTO transaction_history 
                  (request_id, student_id, action, action_by, status_from, status_to, notes) 
                  VALUES (?, ?, ?, ?, ?, ?, ?)";
                $log_stmt = $conn->prepare($log_query);

                if (!$log_stmt) {
                    error_log("Log prepare error: " . $conn->error);
                } else {
                    $log_stmt->bind_param("iisisss", $request_db_id, $student_id, $action, $action_by, $status_from, $status_to, $notes);
                    
                    if (!$log_stmt->execute()) {
                        error_log("Log execute error: " . $log_stmt->error);
                    }
                }

                // Create notification - This will now work!
                $subject = 'Document Request Submitted';
                $message = 'Your document request has been submitted successfully. Request ID: ' . $request_id . '. We will process your request and notify you once it is ready for pickup.';
                $notif_type = 'success';
                
                if (createNotification($student_id, $subject, $message, $notif_type, null, $request_id, 'system')) {
                    $success = 'Document request submitted successfully! <br/>Your Request ID is: <strong>' . htmlspecialchars($request_id) . '</strong> <br/>We will notify you once your documents are ready.';
                } else {
                    // Still show success even if notification fails
                    $success = 'Document request submitted successfully! <br/>Your Request ID is: <strong>' . htmlspecialchars($request_id) . '</strong>';
                    error_log("Failed to create notification for student: $student_id");
                }
            } else {
                error_log("Insert execute error: " . $insert_stmt->error);
                $error = 'Error submitting request. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Document - Document Request System</title>
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
                <li><a href="request-document.php" class="active"><i class="fas fa-plus-circle"></i> Request Document</a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                <li><a href="../shared/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <div class="main-content">
            <div class="main-header">
                <div>
                    <h1>Request Document</h1>
                    <p style="color: #6b7280; margin: 0; margin-top: 0.25rem;">Submit a new document request</p>
                </div>
                
            </div>

            <div class="card">
                <div class="card-header">
                    <h2>New Document Request</h2>
                </div>
                <div class="card-body" style="padding: 1.5rem;">
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i>
                            <span><?php echo $success; ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if ($error): ?>
                        <div class="alert alert-error">
                            <i class="fas fa-exclamation-circle"></i>
                            <span><?php echo htmlspecialchars($error); ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if ($student): ?>
                    <form method="POST" action="">
                        <div class="grid-2">
                            <div class="form-group">
                                <label for="first_name">First Name</label>
                                <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($student['first_name']); ?>" readonly disabled>
                            </div>
                            <div class="form-group">
                                <label for="last_name">Last Name</label>
                                <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($student['last_name']); ?>" readonly disabled>
                            </div>
                        </div>

                        <div class="grid-2">
                            <div class="form-group">
                                <label for="student_id_display">Student ID</label>
                                <input type="text" id="student_id_display" name="student_id_display" value="<?php echo htmlspecialchars($student['student_number']); ?>" readonly disabled>
                            </div>
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($student['email']); ?>" readonly disabled>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="document_type_id">Select Document Type <span style="color: #ef4444;">*</span></label>
                            <select id="document_type_id" name="document_type_id" required onchange="updateDocumentInfo()">
                                <option value="">-- Choose a document --</option>
                                <?php 
                                if ($doc_types && $doc_types->num_rows > 0):
                                    while ($doc = $doc_types->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo intval($doc['document_id']); ?>" 
                                            data-days="<?php echo intval($doc['processing_days']); ?>" 
                                            data-price="<?php echo floatval($doc['fee']); ?>">
                                        <?php echo htmlspecialchars($doc['document_name']); ?>
                                    </option>
                                <?php 
                                    endwhile;
                                else:
                                ?>
                                    <option value="">No documents available</option>
                                <?php endif; ?>
                            </select>
                        </div>

                        <div class="grid-2">
                            <div class="form-group">
                                <label for="quantity">Quantity <span style="color: #ef4444;">*</span></label>
                                <input type="number" id="quantity" name="quantity" min="1" value="1" required>
                            </div>
                            
                        </div>

                        <div class="form-group">
                            <label for="purpose">Purpose of Request <span style="color: #ef4444;">*</span></label>
                            <textarea id="purpose" name="purpose" placeholder="e.g., For school transfer, For employment, etc." required></textarea>
                        </div>

                        
                        <div class="card-footer" style="display: flex; gap: 0.5rem; justify-content: flex-start; background-color: transparent; border: none; padding: 1.5rem; margin: 0;">
                            <button type="reset" class="btn btn-secondary">Clear</button>
                            <button type="submit" class="btn btn-primary">Submit Request</button>
                        </div>
                    </form>
                    <?php else: ?>
                        <div class="alert alert-error">
                            <i class="fas fa-exclamation-circle"></i>
                            <span>Unable to load student information. Please refresh the page or contact support.</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Document Info Section -->
            <div class="grid-2" style="margin-top: 2rem;">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-info-circle"></i> Need Help?</h3>
                    </div>
                    <div class="card-body" style="padding: 1.5rem;">
                        <ul style="padding-left: 1.5rem;">
                            <li style="margin-bottom: 0.75rem;">Choose the document type you need</li>
                            <li style="margin-bottom: 0.75rem;">Enter the quantity required</li>
                            <li style="margin-bottom: 0.75rem;">Specify the purpose of request</li>
                            <li style="margin-bottom: 0;">Click Submit to send your request</li>
                        </ul>
                    </div>
                </div>

                
            </div>
        </div>
    </div>

    <script src="../../js/common.js"></script>
    <script>
        function updateDocumentInfo() {
            const select = document.getElementById('document_type_id');
            const option = select.options[select.selectedIndex];
            
            if (!option.value) {
                document.getElementById('processing_days').value = '';
                document.getElementById('processing_time').textContent = '-';
                document.getElementById('total_cost').textContent = '0.00';
                return;
            }
            
            const processingDays = option.getAttribute('data-days');
            const price = option.getAttribute('data-price');
            const quantity = parseInt(document.getElementById('quantity').value) || 1;

            document.getElementById('processing_days').value = processingDays ? processingDays + ' days' : '';
            document.getElementById('processing_time').textContent = processingDays || '-';
            
            if (price && processingDays) {
                const totalCost = (parseFloat(price) * quantity).toFixed(2);
                document.getElementById('total_cost').textContent = totalCost;
            } else {
                document.getElementById('total_cost').textContent = '0.00';
            }
        }

        document.getElementById('quantity').addEventListener('change', updateDocumentInfo);
    </script>
</body>
</html>