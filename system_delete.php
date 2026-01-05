<?php
require_once 'includes/db.php';
require_once 'includes/security.php';

$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    header("Location: systems.php");
    exit;
}

// Get system for audit log
$stmt = $conn->prepare("SELECT * FROM systems WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$system = $result->fetch_assoc();
$stmt->close();

if (!$system) {
    header("Location: systems.php");
    exit;
}

// Check if system has incidents
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM incidents WHERE affected_system_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$count = $result->fetch_assoc()['count'];
$stmt->close();

if ($count > 0) {
    header("Location: systems.php?error=cannot_delete");
    exit;
}

// Delete system
$stmt = $conn->prepare("DELETE FROM systems WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    $user_identifier = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    logAudit('systems', $id, 'DELETE', $user_identifier, [
        'name' => $system['name'],
        'type' => $system['type'],
        'description' => $system['description']
    ], null);
    
    header("Location: systems.php?deleted=1");
} else {
    header("Location: systems.php?error=1");
}
$stmt->close();
exit;

