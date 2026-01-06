<?php
require_once 'includes/db.php';
require_once 'includes/security.php';

$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    header("Location: incidents.php");
    exit;
}

// Get incident for audit log
$stmt = $conn->prepare("SELECT * FROM incidents WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$incident = $result->fetch_assoc();
$stmt->close();

if (!$incident) {
    header("Location: incidents.php");
    exit;
}

// Delete incident
$stmt = $conn->prepare("DELETE FROM incidents WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    $user_identifier = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    logAudit('incidents', $id, 'DELETE', $user_identifier, [
        'incident_type' => $incident['incident_type'],
        'date_time' => $incident['date_time'],
        'affected_system_id' => $incident['affected_system_id'],
        'severity' => $incident['severity'],
        'status' => $incident['status']
    ], null);
    
    header("Location: incidents.php?deleted=1");
} else {
    header("Location: incidents.php?error=1");
}
$stmt->close();
exit;

