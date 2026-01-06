<?php
/**
 * Export incidents to CSV
 */
require_once '../includes/db.php';
require_once '../includes/security.php';

// Get filter parameters
$severity = sanitizeInput($_GET['severity'] ?? '');
$status = sanitizeInput($_GET['status'] ?? '');
$system_id = intval($_GET['system_id'] ?? 0);
$date_from = sanitizeInput($_GET['date_from'] ?? '');
$date_to = sanitizeInput($_GET['date_to'] ?? '');
$search_text = sanitizeInput($_GET['search_text'] ?? '');

// Build query
$query = "SELECT i.id, i.incident_type, i.date_time, i.severity, i.status, i.resolution_notes,
                 s.name as system_name, s.type as system_type
          FROM incidents i 
          JOIN systems s ON i.affected_system_id = s.id 
          WHERE 1=1";
$params = [];
$types = "";

if (!empty($search_text)) {
    $query .= " AND (i.incident_type LIKE ? OR s.name LIKE ?)";
    $searchTerm = "%" . $search_text . "%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= "ss";
}

if (!empty($severity)) {
    $query .= " AND i.severity = ?";
    $params[] = $severity;
    $types .= "s";
}

if (!empty($status)) {
    $query .= " AND i.status = ?";
    $params[] = $status;
    $types .= "s";
}

if ($system_id > 0) {
    $query .= " AND i.affected_system_id = ?";
    $params[] = $system_id;
    $types .= "i";
}

if (!empty($date_from)) {
    $query .= " AND i.date_time >= ?";
    $params[] = $date_from;
    $types .= "s";
}

if (!empty($date_to)) {
    $query .= " AND i.date_time <= ?";
    $params[] = $date_to . " 23:59:59";
    $types .= "s";
}

$query .= " ORDER BY i.date_time DESC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=incidents_export_' . date('Y-m-d_His') . '.csv');

// Output BOM for UTF-8
echo "\xEF\xBB\xBF";

// Open output stream
$output = fopen('php://output', 'w');

// Write CSV header
fputcsv($output, ['ID', 'Incident Type', 'Date & Time', 'System Name', 'System Type', 'Severity', 'Status', 'Resolution Notes']);

// Write data rows
while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        'INC-' . $row['id'],
        $row['incident_type'],
        $row['date_time'],
        $row['system_name'],
        $row['system_type'],
        $row['severity'],
        $row['status'],
        $row['resolution_notes'] ?? ''
    ]);
}

$stmt->close();
fclose($output);
exit;

