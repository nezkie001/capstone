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
    <title>Transaction Reports - Teacher Portal</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="../../css/teacher-style.css">
    <style>
        @media print {
            .no-print { display: none !important; }
            .print-only { display: block !important; }
            body { margin: 0; padding: 20px; }
            .report-container { box-shadow: none; }
        }
        .print-only { display: none; }
    </style>
</head>
<body>
    <?php include 'includes/nav.php'; ?>
    
    <div class="container">
        <h1 class="no-print">Transaction Reports</h1>
        
        <div class="report-filters no-print">
            <div class="filter-row">
                <div class="form-group">
                    <label>Report Type</label>
                    <select id="reportType">
                        <option value="summary">Summary Report</option>
                        <option value="detailed">Detailed Report</option>
                        <option value="student">By Student</option>
                        <option value="document">By Document Type</option>
                        <option value="status">By Status</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Date Range</label>
                    <input type="date" id="dateFrom" required>
                    <span> to </span>
                    <input type="date" id="dateTo" required>
                </div>
                
                <div class="form-group" id="studentSelectGroup" style="display:none;">
                    <label>Select Student</label>
                    <input type="text" id="studentSearch" placeholder="Search student...">
                </div>
                
                <button onclick="generateReport()" class="btn-primary">Generate Report</button>
                <button onclick="exportReport('csv')" class="btn-secondary">Export CSV</button>
                <button onclick="exportReport('pdf')" class="btn-secondary">Export PDF</button>
            </div>
        </div>
        
        <div id="reportContainer" class="report-container">
            <!-- Report Header (Print Only) -->
            <div class="print-only report-header">
                <h1>School Registrar System</h1>
                <h2>Transaction Report</h2>
                <p>Generated on: <span id="printDate"></span></p>
            </div>
            
            <!-- Report Content -->
            <div id="reportContent">
                <!-- Report will be generated here -->
            </div>
        </div>
        
        <div class="report-actions no-print">
            <button onclick="window.print()" class="btn-primary">Print Report</button>
            <button onclick="emailReport()" class="btn-secondary">Email Report</button>
        </div>
    </div>
    
    <script src="../../js/teacher.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Set default dates
            const today = new Date();
            const lastMonth = new Date(today.getFullYear(), today.getMonth() - 1, today.getDate());
            
            document.getElementById('dateTo').valueAsDate = today;
            document.getElementById('dateFrom').valueAsDate = lastMonth;
            
            // Handle report type change
            document.getElementById('reportType').addEventListener('change', function() {
                const studentSelect = document.getElementById('studentSelectGroup');
                studentSelect.style.display = this.value === 'student' ? 'block' : 'none';
            });
        });
        
        function generateReport() {
            const reportType = document.getElementById('reportType').value;
            const dateFrom = document.getElementById('dateFrom').value;
            const dateTo = document.getElementById('dateTo').value;
            
            if (!dateFrom || !dateTo) {
                alert('Please select date range');
                return;
            }
            
            // Set print date
            document.getElementById('printDate').textContent = new Date().toLocaleString();
            
            // Generate report based on type
            fetch('../../api/teacher_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'generate_report',
                    type: reportType,
                    date_from: dateFrom,
                    date_to: dateTo
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('reportContent').innerHTML = data.report_html;
                } else {
                    alert('Error generating report: ' + data.message);
                }
            });
        }
    </script>
</body>
</html>
