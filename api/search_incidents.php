<?php
/**
 * AJAX endpoint for searching incidents
 */
require_once '../includes/db.php';
require_once '../includes/security.php';

header('Content-Type: application/json');

$results = [];
$search_params = [];

// Get search parameters
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $search_params['severity'] = sanitizeInput($_GET['severity'] ?? '');
    $search_params['status'] = sanitizeInput($_GET['status'] ?? '');
    $search_params['system_id'] = intval($_GET['system_id'] ?? 0);
    $search_params['date_from'] = sanitizeInput($_GET['date_from'] ?? '');
    $search_params['date_to'] = sanitizeInput($_GET['date_to'] ?? '');
    $search_params['search_text'] = sanitizeInput($_GET['search_text'] ?? '');
}

// Build query
$query = "SELECT i.*, s.name as system_name, s.type as system_type 
          FROM incidents i 
          JOIN systems s ON i.affected_system_id = s.id 
          WHERE 1=1";
$params = [];
$types = "";

if (!empty($search_params['search_text'])) {
    $query .= " AND (i.incident_type LIKE ? OR s.name LIKE ?)";
    $searchTerm = "%" . $search_params['search_text'] . "%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= "ss";
}

if (!empty($search_params['severity'])) {
    $query .= " AND i.severity = ?";
    $params[] = $search_params['severity'];
    $types .= "s";
}

if (!empty($search_params['status'])) {
    $query .= " AND i.status = ?";
    $params[] = $search_params['status'];
    $types .= "s";
}

if ($search_params['system_id'] > 0) {
    $query .= " AND i.affected_system_id = ?";
    $params[] = $search_params['system_id'];
    $types .= "i";
}

if (!empty($search_params['date_from'])) {
    $query .= " AND i.date_time >= ?";
    $params[] = $search_params['date_from'];
    $types .= "s";
}

if (!empty($search_params['date_to'])) {
    $query .= " AND i.date_time <= ?";
    $params[] = $search_params['date_to'] . " 23:59:59";
    $types .= "s";
}

$query .= " ORDER BY i.date_time DESC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $results[] = [
        'id' => intval($row['id']),
        'incident_type' => $row['incident_type'],
        'date_time' => $row['date_time'],
        'system_name' => $row['system_name'],
        'system_type' => $row['system_type'],
        'severity' => $row['severity'],
        'status' => $row['status']
    ];
}
$stmt->close();

echo json_encode(['success' => true, 'data' => $results, 'count' => count($results)]);

