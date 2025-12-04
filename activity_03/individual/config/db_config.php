
<?php
// Simple DB connection file (mysqli)
// Edit the credentials below to match your local setup (XAMPP/WAMP)
$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = ''; // often empty for local XAMPP
$DB_NAME = 'attendance_db';

// create connection
$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS);

// check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// create database if not exists
$conn->query("CREATE DATABASE IF NOT EXISTS `$DB_NAME` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");

// select database
$conn->select_db($DB_NAME);

// $conn is ready to use
?>
