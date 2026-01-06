<?php
require_once 'includes/db.php';
require_once 'includes/security.php';
require_once 'includes/twig.php';

$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    header("Location: incidents.php");
    exit;
}

$stmt = $conn->prepare("SELECT i.*, s.name as system_name, s.type as system_type 
                       FROM incidents i 
                       JOIN systems s ON i.affected_system_id = s.id 
                       WHERE i.id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$incident = $result->fetch_assoc();
$stmt->close();

if (!$incident) {
    header("Location: incidents.php");
    exit;
}

// Render template
echo $twig->render('incidents/view.html.twig', [
    'current_page' => 'incidents',
    'incident' => $incident
]);
