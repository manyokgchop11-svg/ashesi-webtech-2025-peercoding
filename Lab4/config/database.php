<?php
// Database Configuration
// Use 127.0.0.1 and explicit port to match local MySQL (phpMyAdmin shows 127.0.0.1:3307)
define('DB_HOST', '127.0.0.1');
define('DB_PORT', 3307);
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'course_management');

// Create connection
function getDBConnection() {
    try {
        // Pass explicit port so connections use the correct MySQL instance
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }

        // Set charset to utf8mb4
        $conn->set_charset("utf8mb4");

        return $conn;
    } catch (Exception $e) {
        error_log($e->getMessage());
        die("Database connection failed. Please try again later.");
    }
}

// Test connection
$conn = getDBConnection();
?>