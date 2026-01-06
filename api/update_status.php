<?php
/**
 * AJAX endpoint for updating incident status
 */
require_once '../includes/db.php';
require_once '../includes/security.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Verify CSRF token
if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    exit;
}

$incident_id = intval($_POST['incident_id'] ?? 0);
$new_status = sanitizeInput($_POST['status'] ?? '');

if ($incident_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid incident ID']);
    exit;
}

if (!in_array($new_status, ['Detected', 'Investigating', 'Resolved'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid status']);
    exit;
}

// Get old status for audit log
$stmt = $conn->prepare("SELECT status FROM incidents WHERE id = ?");
$stmt->bind_param("i", $incident_id);
$stmt->execute();
$result = $stmt->get_result();
$incident = $result->fetch_assoc();
$stmt->close();

if (!$incident) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Incident not found']);
    exit;
}

// Update status
$stmt = $conn->prepare("UPDATE incidents SET status = ? WHERE id = ?");
$stmt->bind_param("si", $new_status, $incident_id);

if ($stmt->execute()) {
    $user_identifier = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    logAudit('incidents', $incident_id, 'UPDATE_STATUS', $user_identifier, 
             ['status' => $incident['status']], 
             ['status' => $new_status]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Status updated successfully',
        'new_status' => $new_status
    ]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to update status']);
}
$stmt->close();

