<?php
require_once 'includes/db.php';
require_once 'includes/security.php';
require_once 'includes/twig.php';

$errors = [];
$form_data = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $errors[] = "Invalid CSRF token. Please try again.";
    } else {
        // Get and sanitize input
        $form_data['incident_type'] = sanitizeInput($_POST['incident_type'] ?? '');
        $form_data['date_time'] = sanitizeInput($_POST['date_time'] ?? '');
        $form_data['affected_system_id'] = intval($_POST['affected_system_id'] ?? 0);
        $form_data['severity'] = sanitizeInput($_POST['severity'] ?? '');
        $form_data['resolution_notes'] = sanitizeInput($_POST['resolution_notes'] ?? '');
        
        // Validation
        if (empty($form_data['incident_type'])) {
            $errors[] = "Incident type is required.";
        }
        if (empty($form_data['date_time'])) {
            $errors[] = "Date and time is required.";
        }
        if ($form_data['affected_system_id'] <= 0) {
            $errors[] = "Please select an affected system.";
        }
        if (empty($form_data['severity']) || !in_array($form_data['severity'], ['Low', 'Medium', 'High', 'Critical'])) {
            $errors[] = "Please select a valid severity level.";
        }
        
        if (empty($errors)) {
            // Prepare statement to prevent SQL injection
            $stmt = $conn->prepare("INSERT INTO incidents (incident_type, date_time, affected_system_id, severity, resolution_notes, status) VALUES (?, ?, ?, ?, ?, 'Detected')");
            $stmt->bind_param("ssiss", $form_data['incident_type'], $form_data['date_time'], $form_data['affected_system_id'], $form_data['severity'], $form_data['resolution_notes']);
            
            if ($stmt->execute()) {
                $incident_id = $conn->insert_id;
                $user_identifier = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
                logAudit('incidents', $incident_id, 'CREATE', $user_identifier, null, [
                    'incident_type' => $form_data['incident_type'],
                    'date_time' => $form_data['date_time'],
                    'affected_system_id' => $form_data['affected_system_id'],
                    'severity' => $form_data['severity']
                ]);
                header("Location: incidents.php?created=1");
                exit;
            } else {
                $errors[] = "Error creating incident: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// Get all systems for dropdown
$result = $conn->query("SELECT id, name, type FROM systems ORDER BY name");
$systems = [];
while ($row = $result->fetch_assoc()) {
    $systems[] = $row;
}

// Render template
echo $twig->render('incidents/create.html.twig', [
    'current_page' => 'incidents',
    'errors' => $errors,
    'form_data' => $form_data,
    'systems' => $systems
]);
