<?php
require_once 'includes/db.php';
require_once 'includes/security.php';
require_once 'includes/twig.php';

$results = [];
$has_search = false;
$search_params = [];

if ($_SERVER['REQUEST_METHOD'] === 'GET' && !empty($_GET)) {
    $has_search = true;
    
    // Get and sanitize search parameters
    $search_params['incident_type'] = sanitizeInput($_GET['incident_type'] ?? '');
    $search_params['severity'] = sanitizeInput($_GET['severity'] ?? '');
    $search_params['status'] = sanitizeInput($_GET['status'] ?? '');
    $search_params['system_id'] = intval($_GET['system_id'] ?? 0);
    $search_params['date_from'] = sanitizeInput($_GET['date_from'] ?? '');
    $search_params['date_to'] = sanitizeInput($_GET['date_to'] ?? '');
    
    // Build query with prepared statements
    $query = "SELECT i.*, s.name as system_name, s.type as system_type 
              FROM incidents i 
              JOIN systems s ON i.affected_system_id = s.id 
              WHERE 1=1";
    $params = [];
    $types = "";
    
    if (!empty($search_params['incident_type'])) {
        $query .= " AND i.incident_type LIKE ?";
        $params[] = "%" . $search_params['incident_type'] . "%";
        $types .= "s";
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
        $results[] = $row;
    }
    $stmt->close();
}

// Get all systems for dropdown
$result = $conn->query("SELECT id, name, type FROM systems ORDER BY name");
$systems = [];
while ($row = $result->fetch_assoc()) {
    $systems[] = $row;
}

// Render template
echo $twig->render('search.html.twig', [
    'current_page' => 'search',
    'has_search' => $has_search,
    'search_params' => $search_params,
    'results' => $results,
    'systems' => $systems
]);
