<?php
require_once 'includes/db.php';
require_once 'includes/security.php';
require_once 'includes/twig.php';

$errors = [];
$form_data = [];
$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    header("Location: systems.php");
    exit;
}

// Get system
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $errors[] = "Invalid CSRF token. Please try again.";
    } else {
        // Get and sanitize input
        $form_data['name'] = sanitizeInput($_POST['name'] ?? '');
        $form_data['type'] = sanitizeInput($_POST['type'] ?? '');
        $form_data['description'] = sanitizeInput($_POST['description'] ?? '');
        
        // Validation
        if (empty($form_data['name'])) {
            $errors[] = "System name is required.";
        }
        if (empty($form_data['type']) || !in_array($form_data['type'], ['Server', 'Application', 'Database', 'Firewall', 'Mail Gateway'])) {
            $errors[] = "Please select a valid system type.";
        }
        
        if (empty($errors)) {
            // Get old values for audit log
            $old_values = [
                'name' => $system['name'],
                'type' => $system['type'],
                'description' => $system['description']
            ];
            
            // Prepare statement to prevent SQL injection
            $stmt = $conn->prepare("UPDATE systems SET name = ?, type = ?, description = ? WHERE id = ?");
            $stmt->bind_param("sssi", $form_data['name'], $form_data['type'], $form_data['description'], $id);
            
            if ($stmt->execute()) {
                $new_values = [
                    'name' => $form_data['name'],
                    'type' => $form_data['type'],
                    'description' => $form_data['description']
                ];
                
                $user_identifier = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
                logAudit('systems', $id, 'UPDATE', $user_identifier, $old_values, $new_values);
                
                header("Location: system_view.php?id=" . $id . "&updated=1");
                exit;
            } else {
                $errors[] = "Error updating system: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// Render template
echo $twig->render('systems/edit.html.twig', [
    'current_page' => 'systems',
    'errors' => $errors,
    'form_data' => $form_data,
    'system' => $system
]);
