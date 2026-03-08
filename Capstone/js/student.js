// Student-specific functions

// Get pending requests count
function getPendingCount() {
    fetch('../../api/student_api.php?action=get_notifications')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data.length > 0) {
                const unread = data.data.filter(n => !n.is_read).length;
                if (unread > 0) {
                    const badge = document.querySelector('.notification-badge');
                    if (badge) {
                        badge.textContent = unread;
                        badge.style.display = 'block';
                    }
                }
            }
        })
        .catch(error => console.error('Error:', error));
}

// Format notification message
function formatNotification(notification) {
    const date = new Date(notification.created_at);
    const timeAgo = getTimeAgo(date);
    
    return `
        <div class="notification-item ${!notification.is_read ? 'unread' : ''}">
            <div class="notification-content">
                <h4>${notification.title}</h4>
                <p>${notification.message}</p>
                <small>${timeAgo}</small>
            </div>
        </div>
    `;
}

// Get time ago format
function getTimeAgo(date) {
    const seconds = Math.floor((new Date() - date) / 1000);
    
    if (seconds < 60) return 'just now';
    if (seconds < 3600) return Math.floor(seconds / 60) + 'm ago';
    if (seconds < 86400) return Math.floor(seconds / 3600) + 'h ago';
    if (seconds < 604800) return Math.floor(seconds / 86400) + 'd ago';
    
    return date.toLocaleDateString();
}

// Initialize student dashboard
document.addEventListener('DOMContentLoaded', function() {
    // Check for pending notifications
    if (document.querySelector('.student-dashboard')) {
        getPendingCount();
        // Refresh every 30 seconds
        setInterval(getPendingCount, 30000);
    }
});