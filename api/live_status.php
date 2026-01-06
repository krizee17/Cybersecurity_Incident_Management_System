<?php
/**
 * AJAX endpoint for live status tracking
 * Calculates percentage of systems affected by incidents
 */
require_once '../includes/db.php';
require_once '../includes/security.php';

header('Content-Type: application/json');

$status = [];

// Get total counts by system type
$result = $conn->query("SELECT type, COUNT(*) as total FROM systems GROUP BY type");
$total_by_type = [];
while ($row = $result->fetch_assoc()) {
    $total_by_type[$row['type']] = intval($row['total']);
}

// Get affected systems (systems with active incidents)
$result = $conn->query("SELECT DISTINCT s.type, COUNT(DISTINCT s.id) as affected_count
                        FROM systems s
                        INNER JOIN incidents i ON s.id = i.affected_system_id
                        WHERE i.status IN ('Detected', 'Investigating')
                        GROUP BY s.type");
$affected_by_type = [];
while ($row = $result->fetch_assoc()) {
    $affected_by_type[$row['type']] = intval($row['affected_count']);
}

// Calculate percentages
$status['web_servers'] = [
    'total' => $total_by_type['Server'] ?? 0,
    'affected' => $affected_by_type['Server'] ?? 0,
    'percentage' => 0,
    'status' => 'operational',
    'description' => 'All systems operational.'
];

$status['databases'] = [
    'total' => $total_by_type['Database'] ?? 0,
    'affected' => $affected_by_type['Database'] ?? 0,
    'percentage' => 0,
    'status' => 'operational',
    'description' => 'All systems operational.'
];

// Calculate percentages
if ($status['web_servers']['total'] > 0) {
    $affected = $status['web_servers']['affected'];
    $total = $status['web_servers']['total'];
    $status['web_servers']['percentage'] = round((($total - $affected) / $total) * 100);
    
    if ($status['web_servers']['percentage'] < 50) {
        $status['web_servers']['status'] = 'critical';
        $status['web_servers']['description'] = 'Critical alert.';
    } elseif ($status['web_servers']['percentage'] < 90) {
        $status['web_servers']['status'] = 'warning';
        $status['web_servers']['description'] = 'Minor issues detected.';
    }
}

if ($status['databases']['total'] > 0) {
    $affected = $status['databases']['affected'];
    $total = $status['databases']['total'];
    $status['databases']['percentage'] = round((($total - $affected) / $total) * 100);
    
    if ($status['databases']['percentage'] < 50) {
        $status['databases']['status'] = 'critical';
        $status['databases']['description'] = 'Critical alert.';
    } elseif ($status['databases']['percentage'] < 90) {
        $status['databases']['status'] = 'warning';
        $status['databases']['description'] = 'Minor issues detected.';
    }
}

// If no systems exist, set defaults
if ($status['web_servers']['total'] == 0) {
    $status['web_servers']['percentage'] = 100;
}

if ($status['databases']['total'] == 0) {
    $status['databases']['percentage'] = 100;
}

echo json_encode(['success' => true, 'data' => $status]);

