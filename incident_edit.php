<?php
require_once 'includes/db.php';
require_once 'includes/security.php';
require_once 'includes/twig.php';

$errors = [];
$form_data = [];
$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    header("Location: incidents.php");
    exit;
}

// Get incident
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
        $form_data['status'] = sanitizeInput($_POST['status'] ?? '');
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
        if (empty($form_data['status']) || !in_array($form_data['status'], ['Detected', 'Investigating', 'Resolved'])) {
            $errors[] = "Please select a valid status.";
        }
        
        if (empty($errors)) {
            // Get old values for audit log
            $old_values = [
                'incident_type' => $incident['incident_type'],
                'date_time' => $incident['date_time'],
                'affected_system_id' => $incident['affected_system_id'],
                'severity' => $incident['severity'],
                'status' => $incident['status'],
                'resolution_notes' => $incident['resolution_notes']
            ];
            
            // Prepare statement to prevent SQL injection
            $stmt = $conn->prepare("UPDATE incidents SET incident_type = ?, date_time = ?, affected_system_id = ?, severity = ?, status = ?, resolution_notes = ? WHERE id = ?");
            $stmt->bind_param("ssisssi", $form_data['incident_type'], $form_data['date_time'], $form_data['affected_system_id'], $form_data['severity'], $form_data['status'], $form_data['resolution_notes'], $id);
            
            if ($stmt->execute()) {
                $new_values = [
                    'incident_type' => $form_data['incident_type'],
                    'date_time' => $form_data['date_time'],
                    'affected_system_id' => $form_data['affected_system_id'],
                    'severity' => $form_data['severity'],
                    'status' => $form_data['status'],
                    'resolution_notes' => $form_data['resolution_notes']
                ];
                
                $user_identifier = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
                logAudit('incidents', $id, 'UPDATE', $user_identifier, $old_values, $new_values);
                
                header("Location: incident_view.php?id=" . $id . "&updated=1");
                exit;
            } else {
                $errors[] = "Error updating incident: " . $stmt->error;
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
echo $twig->render('incidents/edit.html.twig', [
    'current_page' => 'incidents',
    'errors' => $errors,
    'form_data' => $form_data,
    'incident' => $incident,
    'systems' => $systems
]);
