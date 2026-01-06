<?php
/**
 * AJAX endpoint to get CSRF token
 */
require_once '../includes/security.php';

header('Content-Type: application/json');

$token = generateCSRFToken();
echo json_encode(['success' => true, 'csrf_token' => $token]);

