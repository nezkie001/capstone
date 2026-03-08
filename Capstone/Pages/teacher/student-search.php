<?php
require_once '../../config/db_config.php';

if (!isset($_SESSION['teacher_id'])) {
    header('Location: login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Search - Teacher Portal</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="../../css/teacher-style.css">
</head>
<body>
    <?php include 'includes/nav.php'; ?>
    
    <div class="container">
        <h1>Student Information</h1>
        
        <div class="search-section">
            <div class="search-bar">
                <input type="text" id="searchInput" placeholder="Search by name, student ID, or email...">
                <button onclick="searchStudents()" class="btn-primary">Search</button>
            </div>
            
            <div class="filters">
                <select id="filterStatus">
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                    <option value="graduated">Graduated</option>
                </select>
                
                <select id="filterYear">
                    <option value="">All Years</option>
                    <option value="1">1st Year</option>
                    <option value="2">2nd Year</option>
                    <option value="3">3rd Year</option>
                    <option value="4">4th Year</option>
                </select>
                
                <select id="filterCourse">
                    <option value="">All Courses</option>
                </select>
            </div>
        </div>
        
        <div id="studentResults" class="results-section">
            <!-- Results will be loaded here -->
        </div>
        
        <!-- Student Detail Modal -->
        <div id="studentModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeStudentModal()">&times;</span>
                <h2>Student Details</h2>
                <div id="studentDetails">
                    <!-- Student details will be loaded here -->
                </div>
                
                <div class="student-requests">
                    <h3>Document Requests History</h3>
                    <div id="studentRequests">
                        <!-- Requests will be loaded here -->
                    </div>
                </div>
                
                <div class="modal-actions">
                    <button onclick="notifyStudent()" class="btn-secondary">Send Notification</button>
                    <button onclick="viewTransactions()" class="btn-primary">View All Transactions</button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="../../js/teacher.js"></script>
    <script>
        // Load students on page load
        document.addEventListener('DOMContentLoaded', function() {
            searchStudents();
            loadCourses();
        });
    </script>
</body>
</html>
