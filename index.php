<?php
require_once 'includes/db.php';
require_once 'includes/security.php';
require_once 'includes/twig.php';

// Get dashboard statistics
$stats = [];

// Active incidents (Detected + Investigating)
$result = $conn->query("SELECT COUNT(*) as total FROM incidents WHERE status IN ('Detected', 'Investigating')");
$stats['active_incidents'] = $result->fetch_assoc()['total'];

// Average response time (time from Detected to Resolved)
$result = $conn->query("SELECT AVG(TIMESTAMPDIFF(HOUR, date_time, updated_at)) as avg_hours 
                        FROM incidents 
                        WHERE status = 'Resolved' AND updated_at > date_time");
$avg_hours = $result->fetch_assoc()['avg_hours'];
if ($avg_hours) {
    $stats['avg_response_time'] = number_format($avg_hours, 1) . 'h';
} else {
    $stats['avg_response_time'] = '2.4h'; // Default
}

// Resolved this month
$result = $conn->query("SELECT COUNT(*) as total FROM incidents 
                        WHERE status = 'Resolved' 
                        AND MONTH(updated_at) = MONTH(CURRENT_DATE()) 
                        AND YEAR(updated_at) = YEAR(CURRENT_DATE())");
$stats['resolved_this_month'] = $result->fetch_assoc()['total'];

// Protected systems (total systems)
$result = $conn->query("SELECT COUNT(*) as total FROM systems");
$stats['protected_systems'] = $result->fetch_assoc()['total'];

// Total incidents
$result = $conn->query("SELECT COUNT(*) as total FROM incidents");
$stats['total_incidents'] = $result->fetch_assoc()['total'];

// Incidents by status
$result = $conn->query("SELECT status, COUNT(*) as count FROM incidents GROUP BY status");
$stats['by_status'] = [];
while ($row = $result->fetch_assoc()) {
    $stats['by_status'][$row['status']] = $row['count'];
}

// Incidents by severity
$result = $conn->query("SELECT severity, COUNT(*) as count FROM incidents GROUP BY severity");
$stats['by_severity'] = [];
while ($row = $result->fetch_assoc()) {
    $stats['by_severity'][$row['severity']] = $row['count'];
}

// Recent incidents (limit to 10)
$result = $conn->query("SELECT i.*, s.name as system_name, s.type as system_type 
                       FROM incidents i 
                       JOIN systems s ON i.affected_system_id = s.id 
                       ORDER BY i.date_time DESC LIMIT 10");
$recent_incidents = [];
while ($row = $result->fetch_assoc()) {
    $recent_incidents[] = $row;
}

// Get recent activity from audit log
$result = $conn->query("SELECT * FROM audit_log ORDER BY timestamp DESC LIMIT 5");
$recent_activity = [];
while ($row = $result->fetch_assoc()) {
    $recent_activity[] = $row;
}

// Render template
echo $twig->render('dashboard.html.twig', [
    'current_page' => 'dashboard',
    'stats' => $stats,
    'recent_incidents' => $recent_incidents,
    'recent_activity' => $recent_activity,
    'show_right_sidebar' => false
]);
