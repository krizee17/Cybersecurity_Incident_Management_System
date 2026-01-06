/**
 * Main JavaScript for Cybersecurity Incident Management System
 */

// Refresh dashboard statistics
function refreshDashboard() {
    fetch('api/dashboard_stats.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const stats = data.data;
                
                // Update metric cards
                const activeEl = document.getElementById('active-incidents');
                if (activeEl) activeEl.textContent = stats.active_incidents || 0;
                
                const responseTimeEl = document.getElementById('avg-response-time');
                if (responseTimeEl) responseTimeEl.textContent = stats.avg_response_time || '2.4h';
                
                const resolvedEl = document.getElementById('resolved-month');
                if (resolvedEl) resolvedEl.textContent = stats.resolved_this_month || 0;
                
                const systemsEl = document.getElementById('protected-systems');
                if (systemsEl) systemsEl.textContent = stats.protected_systems || 0;
                
                // Update recent incidents table
                updateRecentIncidentsTable(stats.recent_incidents);
            }
        })
        .catch(error => {
            console.error('Error refreshing dashboard:', error);
        });
}

// Update recent incidents table
function updateRecentIncidentsTable(incidents) {
    const tbody = document.querySelector('#recent-table tbody');
    if (!tbody) return;
    
    if (incidents.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 40px; color: var(--text-secondary);">No incidents found</td></tr>';
        return;
    }
    
    tbody.innerHTML = incidents.map(incident => {
        const statusBadge = getStatusBadge(incident.status);
        const severityClass = incident.severity.toLowerCase();
        const systemInitial = (incident.system_name || 'S').charAt(0).toUpperCase();
        const dateTime = formatDateTime(incident.date_time);
        
        return `
            <tr>
                <td><input type="checkbox"></td>
                <td class="incident-id">INC-${incident.id}</td>
                <td>
                    <div class="incident-title-group">
                        <div class="incident-title">${escapeHtml(incident.incident_type)}</div>
                        <div class="incident-meta">
                            <i class="fas fa-circle" style="font-size: 4px;"></i>
                            <span>${escapeHtml(incident.system_name || '')}</span>
                            <i class="fas fa-circle" style="font-size: 4px;"></i>
                            <span>${dateTime}</span>
                        </div>
                    </div>
                </td>
                <td>
                    <span class="severity-dot severity-${severityClass}"></span>
                    <span style="font-weight: 500;">${escapeHtml(incident.severity)}</span>
                </td>
                <td>${statusBadge}</td>
                <td>
                    <div class="assigned-avatar">${systemInitial}</div>
                </td>
                <td>
                    <a href="incident_view.php?id=${incident.id}" class="action-icon">
                        <i class="fas fa-eye"></i>
                    </a>
                </td>
            </tr>
        `;
    }).join('');
}

// Get status badge HTML
function getStatusBadge(status) {
    if (status === 'Investigating') {
        return `<span class="status-badge status-investigating">
            <i class="fas fa-search" style="font-size: 10px;"></i>
            ${escapeHtml(status)}
        </span>`;
    } else if (status === 'Resolved') {
        return `<span class="status-badge status-resolved">
            <i class="fas fa-check" style="font-size: 10px;"></i>
            ${escapeHtml(status)}
        </span>`;
    } else {
        return `<span class="status-badge status-in-progress">
            <i class="fas fa-circle" style="font-size: 6px;"></i>
            In Progress
        </span>`;
    }
}

// Format date time
function formatDateTime(dateTime) {
    if (!dateTime) return '';
    const date = new Date(dateTime);
    const now = new Date();
    const diffMs = now - date;
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    
    if (diffMins < 60) {
        return `${diffMins} min ago`;
    } else if (diffHours < 24) {
        return `${diffHours} hour${diffHours > 1 ? 's' : ''} ago`;
    } else {
        return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' });
    }
}

// Update incident status via AJAX
function updateIncidentStatus(incidentId, newStatus) {
    // Get CSRF token first
    fetch('api/get_csrf_token.php')
        .then(response => response.json())
        .then(tokenData => {
            if (!tokenData.success) {
                alert('Error: Could not get security token');
                return;
            }
            
            const formData = new FormData();
            formData.append('csrf_token', tokenData.csrf_token);
            formData.append('incident_id', incidentId);
            formData.append('status', newStatus);
            
            return fetch('api/update_status.php', {
                method: 'POST',
                body: formData
            });
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update the status badge
                const statusBadge = document.querySelector(`[data-incident-id="${incidentId}"]`);
                if (statusBadge) {
                    statusBadge.textContent = newStatus;
                    statusBadge.className = `status-badge status-${newStatus.toLowerCase()}`;
                    statusBadge.setAttribute('data-current-status', newStatus);
                }
                
                // Refresh dashboard if on dashboard page
                if (document.getElementById('stats-grid')) {
                    refreshDashboard();
                }
                
                // Show success message
                showNotification('Status updated successfully', 'success');
            } else {
                alert('Error: ' + (data.error || 'Failed to update status'));
            }
        })
        .catch(error => {
            console.error('Error updating status:', error);
            alert('Error updating status. Please try again.');
        });
}

// Make status badges clickable for live updates
document.addEventListener('DOMContentLoaded', function() {
    // Add click handler to status badges
    document.querySelectorAll('.status-badge[data-incident-id]').forEach(badge => {
        badge.style.cursor = 'pointer';
        badge.title = 'Click to change status';
        
        badge.addEventListener('click', function() {
            const incidentId = this.getAttribute('data-incident-id');
            const currentStatus = this.getAttribute('data-current-status');
            
            // Determine next status
            const statuses = ['Detected', 'Investigating', 'Resolved'];
            const currentIndex = statuses.indexOf(currentStatus);
            const nextIndex = (currentIndex + 1) % statuses.length;
            const newStatus = statuses[nextIndex];
            
            if (confirm(`Change status from ${currentStatus} to ${newStatus}?`)) {
                updateIncidentStatus(incidentId, newStatus);
            }
        });
    });
});

// Utility function to escape HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Show notification
function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        background-color: ${type === 'success' ? '#27ae60' : '#3498db'};
        color: white;
        border-radius: 4px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        z-index: 1000;
        animation: slideIn 0.3s ease-out;
    `;
    
    document.body.appendChild(notification);
    
    // Remove after 3 seconds
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease-out';
        setTimeout(() => {
            document.body.removeChild(notification);
        }, 300);
    }, 3000);
}

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);

