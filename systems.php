<?php
require_once 'includes/db.php';
require_once 'includes/security.php';
require_once 'includes/twig.php';

$result = $conn->query("SELECT s.*, COUNT(i.id) as incident_count 
                       FROM systems s 
                       LEFT JOIN incidents i ON s.id = i.affected_system_id 
                       GROUP BY s.id 
                       ORDER BY s.name");
$systems = [];
while ($row = $result->fetch_assoc()) {
    $systems[] = $row;
}

// Render template
echo $twig->render('systems/list.html.twig', [
    'current_page' => 'systems',
    'systems' => $systems
]);
