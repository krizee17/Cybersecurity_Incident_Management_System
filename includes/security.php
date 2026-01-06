<?php
/**
 * Security utilities for CSRF protection and XSS prevention
 */

session_start();

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCSRFToken($token) {
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Sanitize output to prevent XSS
 */
function escape($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Sanitize input
 */
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Log audit trail
 */
function logAudit($table_name, $record_id, $action, $user_identifier, $old_values = null, $new_values = null) {
    global $conn;
    
    if (!isset($conn)) {
        require_once __DIR__ . '/db.php';
    }
    
    $old_values_json = $old_values ? json_encode($old_values) : null;
    $new_values_json = $new_values ? json_encode($new_values) : null;
    
    $stmt = $conn->prepare("INSERT INTO audit_log (table_name, record_id, action, user_identifier, old_values, new_values) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sissss", $table_name, $record_id, $action, $user_identifier, $old_values_json, $new_values_json);
    $stmt->execute();
    $stmt->close();
}

