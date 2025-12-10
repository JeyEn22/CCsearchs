/**
 * Notification management functions
 */

function markAsRead(notificationID) {
    const path = window.location.pathname.includes('/notification/') ? 'mark_read.php' : '../notification/mark_read.php';
    fetch(path, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'notificationID=' + encodeURIComponent(notificationID)
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update UI
                const notificationRow = document.querySelector(`[data-id="${notificationID}"]`);
                if (notificationRow) {
                    notificationRow.classList.remove('unread');
                    const markReadBtn = notificationRow.querySelector('.mark-read-btn');
                    if (markReadBtn) {
                        markReadBtn.remove();
                    }
                }
                updateNotificationBadge();
            } else {
                alert('Failed to mark notification as read: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while marking notification as read');
        });
}

function deleteNotification(notificationID) {
    if (!confirm('Are you sure you want to delete this notification?')) {
        return;
    }

    const path = window.location.pathname.includes('/notification/') ? 'delete_notification.php' : '../notification/delete_notification.php';
    fetch(path, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'notificationID=' + encodeURIComponent(notificationID)
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Remove from UI
                const notificationRow = document.querySelector(`[data-id="${notificationID}"]`);
                if (notificationRow) {
                    notificationRow.remove();

                    // Check if section is now empty
                    const section = notificationRow.closest('.notif-section');
                    if (section) {
                        const remainingRows = section.querySelectorAll('.notif-row');
                        if (remainingRows.length === 0) {
                            section.remove();
                        }
                    }
                }
                updateNotificationBadge();
            } else {
                alert('Failed to delete notification: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while deleting the notification');
        });
}

function updateNotificationBadge() {
    // Update the notification badge in the navigation
    const path = window.location.pathname.includes('/notification/') ? 'get_unread_count.php' : '../notification/get_unread_count.php';
    fetch(path)
        .then(response => response.json())
        .then(data => {
            const badge = document.querySelector('.notification-badge');
            if (data.count > 0) {
                const count = data.count > 99 ? '99+' : data.count;
                if (badge) {
                    badge.textContent = count;
                } else {
                    // Create badge if it doesn't exist
                    const navLink = document.querySelector('a[href*="notification.php"]');
                    if (navLink) {
                        const newBadge = document.createElement('span');
                        newBadge.className = 'notification-badge';
                        newBadge.textContent = count;
                        navLink.appendChild(newBadge);
                    }
                }
            } else {
                // Remove badge if no unread notifications
                if (badge) {
                    badge.remove();
                }
            }
        })
        .catch(error => {
            console.error('Error updating notification badge:', error);
        });
}

// Hover effects are handled by CSS, but we can add additional functionality here if needed
document.addEventListener('DOMContentLoaded', function () {
    // Update badge on page load
    updateNotificationBadge();
});
