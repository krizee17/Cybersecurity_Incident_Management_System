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
            // Prepare statement to prevent SQL injection
            $stmt = $conn->prepare("INSERT INTO systems (name, type, description) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $form_data['name'], $form_data['type'], $form_data['description']);
            
            if ($stmt->execute()) {
                $system_id = $conn->insert_id;
                $user_identifier = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
                logAudit('systems', $system_id, 'CREATE', $user_identifier, null, [
                    'name' => $form_data['name'],
                    'type' => $form_data['type'],
                    'description' => $form_data['description']
                ]);
                header("Location: systems.php?created=1");
                exit;
            } else {
                $errors[] = "Error creating system: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// Render template
echo $twig->render('systems/create.html.twig', [
    'current_page' => 'systems',
    'errors' => $errors,
    'form_data' => $form_data
]);
