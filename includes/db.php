<?php
/**
 * Database Connection File
 * Handles connection to MySQL database using MySQLi
 */

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'incident_management_system');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Create MySQLi connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    // Log error and display user-friendly message
    error_log("Database Connection Error: " . $conn->connect_error);
    die("Connection failed. Please try again later.");
}

// Set charset
$conn->set_charset(DB_CHARSET);

