// Teacher Portal JavaScript

// Login functionality
document.getElementById('teacherLoginForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('action', 'login');
    
    try {
        const response = await fetch('../../api/teacher_api.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            window.location.href = data.redirect;
        } else {
            alert(data.message);
        }
    } catch (error) {
        alert('Login failed. Please try again.');
    }
});

// Search students
async function searchStudents() {
    const search = document.getElementById('searchInput')?.value || '';
    const status = document.getElementById('filterStatus')?.value || '';
    const year = document.getElementById('filterYear')?.value || '';
    const course = document.getElementById('filterCourse')?.value || '';
    
    const params = new URLSearchParams({
        action: 'search_students',
        search: search,
        status: status,
        year: year,
        course: course
    });
    
    try {
        const response = await fetch(`../../api/teacher_api.php?${params}`);
        const data = await response.json();
        
        if (data.success) {
            displayStudentResults(data.students);
        }
    } catch (error) {
        console.error('Error searching students:', error);
    }
}

// Display student results
function displayStudentResults(students) {
    const container = document.getElementById('studentResults');
    
    if (students.length === 0) {
        container.innerHTML = '<p class="no-results">No students found</p>';
        return;
    }
    
    let html = '<table class="data-table"><thead><tr>';
    html += '<th>Student ID</th><th>Name</th><th>Email</th><th>Course</th><th>Year</th><th>Status</th><th>Actions</th>';
    html += '</tr></thead><tbody>';
    
    students.forEach(student => {
        html += `
            <tr>
                <td>${student.student_number}</td>
                <td>${student.first_name} ${student.last_name}</td>
                <td>${student.email}</td>
                <td>${student.course || 'N/A'}</td>
                <td>${student.year_level || 'N/A'}</td>
                <td><span class="status-badge status-${student.status}">${student.status}</span></td>
                <td>
                    <button onclick="viewStudent(${student.student_id})" class="btn-icon">View</button>
                    <button onclick="notifyStudent(${student.student_id})" class="btn-icon">Notify</button>
                </td>
            </tr>
        `;
    });
    
    html += '</tbody></table>';
    container.innerHTML = html;
}

// View student details
async function viewStudent(studentId) {
    try {
        const response = await fetch(`../../api/teacher_api.php?action=get_student_details&student_id=${studentId}`);
        const data = await response.json();
        
        if (data.success) {
            showStudentModal(data.student, data.requests);
        }
    } catch (error) {
        console.error('Error loading student details:', error);
    }
}

// Show student modal
function showStudentModal(student, requests) {
    const modal = document.getElementById('studentModal');
    const detailsDiv = document.getElementById('studentDetails');
    const requestsDiv = document.getElementById('studentRequests');
    
    // Display student info
    detailsDiv.innerHTML = `
        <div class="student-info">
            <p><strong>Name:</strong> ${student.first_name} ${student.middle_name || ''} ${student.last_name}</p>
            <p><strong>Student ID:</strong> ${student.student_number}</p>
            <p><strong>Email:</strong> ${student.email}</p>
            <p><strong>Phone:</strong> ${student.phone || 'N/A'}</p>
            <p><strong>Course:</strong> ${student.course || 'N/A'}</p>
            <p><strong>Year Level:</strong> ${student.year_level || 'N/A'}</p>
            <p><strong>Status:</strong> <span class="status-badge status-${student.status}">${student.status}</span></p>
        </div>
    `;
    
    // Display requests
    if (requests.length > 0) {
        let requestHtml = '<table class="data-table"><thead><tr>';
        requestHtml += '<th>Request ID</th><th>Document</th><th>Status</th><th>Date</th>';
        requestHtml += '</tr></thead><tbody>';
        
        requests.forEach(request => {
            requestHtml += `
                <tr>
                    <td>#${request.request_id}</td>
                    <td>${request.document_name}</td>
                    <td><span class="status-badge status-${request.status}">${request.status}</span></td>
                    <td>${new Date(request.requested_at).toLocaleDateString()}</td>
                </tr>
            `;
        });
        
        requestHtml += '</tbody></table>';
        requestsDiv.innerHTML = requestHtml;
    } else {
        requestsDiv.innerHTML = '<p>No document requests found.</p>';
    }
    
    modal.style.display = 'block';
}

// Load document requests
async function loadRequests() {
    const document = document.getElementById('filterDocument')?.value || '';
    const status = document.getElementById('filterStatus')?.value || '';
    const dateFrom = document.getElementById('filterDateFrom')?.value || '';
    const dateTo = document.getElementById('filterDateTo')?.value || '';
    
    const params = new URLSearchParams({
        action: 'get_requests',
        document: document,
        status: status,
        date_from: dateFrom,
        date_to: dateTo
    });
    
    try {
        const response = await fetch(`../../api/teacher_api.php?${params}`);
        const data = await response.json();
        
        if (data.success) {
            displayRequests(data.requests);
        }
    } catch (error) {
        console.error('Error loading requests:', error);
    }
}

// Display requests in table
function displayRequests(requests) {
    const tbody = document.getElementById('requestsTableBody');
    
    if (requests.length === 0) {
        tbody.innerHTML = '<tr><td colspan="10" class="text-center">No requests found</td></tr>';
        return;
    }
    
    let html = '';
    requests.forEach(request => {
        html += `
            <tr>
                <td><input type="checkbox" class="request-checkbox" value="${request.request_id}"></td>
                <td>#${request.request_id}</td>
                <td>${request.first_name} ${request.last_name}<br><small>${request.student_number}</small></td>
                <td>${request.document_name}</td>
                <td>${request.quantity}</td>
                <td>${request.purpose || 'N/A'}</td>
                <td><span class="status-badge status-${request.status}">${request.status}</span></td>
                <td>${new Date(request.requested_at).toLocaleDateString()}</td>
                <td>${request.scheduled_date ? new Date(request.scheduled_date).toLocaleDateString() : 'Not scheduled'}</td>
                <td>
                    <button onclick="viewRequest(${request.request_id})" class="btn-icon">View</button>
                    <button onclick="updateStatus(${request.request_id})" class="btn-icon">Update</button>
                </td>
            </tr>
        `;
    });
    
    tbody.innerHTML = html;
}

// Update request status
async function updateRequestStatus() {
    const requestId = document.getElementById('currentRequestId')?.value;
    const status = document.getElementById('statusUpdate')?.value;
    
    const formData = new FormData();
    formData.append('action', 'update_request_status');
    formData.append('request_id', requestId);
    formData.append('status', status);
    
    try {
        const response = await fetch('../../api/teacher_api.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert('Status updated successfully');
            closeRequestModal();
            loadRequests();
        } else {
            alert('Error: ' + data.message);
        }
    } catch (error) {
        alert('Failed to update status');
    }
}

// Send notification
async function sendNotificationToStudents(studentIds, type, subject, message) {
    try {
        const response = await fetch('../../api/teacher_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'send_notification',
                student_ids: studentIds,
                type: type,
                subject: subject,
                message: message
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert(data.message);
        } else {
            alert('Error sending notification: ' + data.message);
        }
    } catch (error) {
        alert('Failed to send notification');
    }
}

// Notification form setup
function setupNotificationForm() {
    const form = document.getElementById('notificationForm');
    
    form?.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const recipientType = formData.get('recipientType');
        let studentIds = [];
        
        // Get selected students based on recipient type
        if (recipientType === 'individual') {
            const selectedStudent = document.querySelector('.selected-student');
            if (selectedStudent) {
                studentIds = [selectedStudent.dataset.studentId];
            }
        } else if (recipientType === 'multiple') {
            const checkboxes = document.querySelectorAll('.student-checkbox:checked');
            studentIds = Array.from(checkboxes).map(cb => cb.value);
        }
        
        if (studentIds.length === 0) {
            alert('Please select at least one recipient');
            return;
        }
        
        await sendNotificationToStudents(
            studentIds,
            formData.get('notificationType'),
            formData.get('subject'),
            formData.get('message')
        );
    });
    
    // Handle recipient type change
    document.querySelectorAll('input[name="recipientType"]').forEach(radio => {
        radio.addEventListener('change', function() {
            document.querySelectorAll('.recipient-selector').forEach(selector => {
                selector.style.display = 'none';
            });
            
            const targetId = this.value + 'Select';
            document.getElementById(targetId).style.display = 'block';
        });
    });
}

// Modal functions
function closeStudentModal() {
    document.getElementById('studentModal').style.display = 'none';
}

function closeRequestModal() {
    document.getElementById('requestModal').style.display = 'none';
}

// Apply filters
function applyFilters() {
    loadRequests();
}

// Reset filters
function resetFilters() {
    document.getElementById('filterDocument').value = '';
    document.getElementById('filterStatus').value = '';
    document.getElementById('filterDateFrom').value = '';
    document.getElementById('filterDateTo').value = '';
    loadRequests();
}

// Toggle select all checkboxes
function toggleSelectAll() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.request-checkbox');
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAll.checked;
    });
}

// Bulk status update
async function updateBulkStatus(newStatus) {
    const selected = document.querySelectorAll('.request-checkbox:checked');
    
    if (selected.length === 0) {
        alert('Please select at least one request');
        return;
    }
    
    const requestIds = Array.from(selected).map(cb => cb.value);
    
    for (const requestId of requestIds) {
        const formData = new FormData();
        formData.append('action', 'update_request_status');
        formData.append('request_id', requestId);
        formData.append('status', newStatus);
        
        await fetch('../../api/teacher_api.php', {
            method: 'POST',
            body: formData
        });
    }
    
    alert('Status updated for selected requests');
    loadRequests();
}

// Export report
function exportReport(format) {
    const reportContent = document.getElementById('reportContent').innerHTML;
    
    if (!reportContent) {
        alert('Please generate a report first');
        return;
    }
    
    // Implementation would depend on backend export functionality
    alert(`Export to ${format.toUpperCase()} functionality would be implemented here`);
}

// Close modals on click outside
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
    }
}
