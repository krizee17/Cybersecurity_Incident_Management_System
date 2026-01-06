<?php
require_once 'includes/db.php';
require_once 'includes/security.php';
require_once 'includes/twig.php';

// Get filter parameters
$severity = sanitizeInput($_GET['severity'] ?? '');
$status = sanitizeInput($_GET['status'] ?? '');
$system_id = intval($_GET['system_id'] ?? 0);
$date_from = sanitizeInput($_GET['date_from'] ?? '');
$date_to = sanitizeInput($_GET['date_to'] ?? '');
$search_text = sanitizeInput($_GET['search_text'] ?? '');

// Build query
$query = "SELECT i.*, s.name as system_name, s.type as system_type 
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

$incidents = [];
while ($row = $result->fetch_assoc()) {
    $incidents[] = $row;
}
$stmt->close();

// Handle AJAX request for systems list
if (isset($_GET['ajax']) && $_GET['ajax'] === 'systems') {
    header('Content-Type: application/json');
    $systems_result = $conn->query("SELECT id, name, type FROM systems ORDER BY name");
    $systems = [];
    while ($row = $systems_result->fetch_assoc()) {
        $systems[] = $row;
    }
    echo json_encode(['success' => true, 'systems' => $systems]);
    exit;
}

// Get all systems for filter dropdown
$systems_result = $conn->query("SELECT id, name, type FROM systems ORDER BY name");
$systems = [];
while ($row = $systems_result->fetch_assoc()) {
    $systems[] = $row;
}

// Render template
echo $twig->render('incidents/list.html.twig', [
    'current_page' => 'incidents',
    'incidents' => $incidents,
    'systems' => $systems,
    'filters' => [
        'severity' => $severity,
        'status' => $status,
        'system_id' => $system_id,
        'date_from' => $date_from,
        'date_to' => $date_to,
        'search_text' => $search_text
    ]
]);
