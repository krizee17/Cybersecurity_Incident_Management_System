<?php
require_once 'includes/db.php';
require_once 'includes/security.php';
require_once 'includes/twig.php';

$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    header("Location: systems.php");
    exit;
}

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

// Get incidents for this system
$stmt = $conn->prepare("SELECT * FROM incidents WHERE affected_system_id = ? ORDER BY date_time DESC");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$incidents = [];
while ($row = $result->fetch_assoc()) {
    $incidents[] = $row;
}
$stmt->close();

// Render template
echo $twig->render('systems/view.html.twig', [
    'current_page' => 'systems',
    'system' => $system,
    'incidents' => $incidents
]);
