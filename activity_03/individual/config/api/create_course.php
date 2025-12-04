
<?php
// Lecturer creates a course
session_start();
require_once __DIR__ . '/../config/db_config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'lecturer') {
    echo json_encode(['success' => false, 'message' => 'Only lecturers can create courses.']);
    exit;
}

$data = $_POST;
$title = trim($data['title'] ?? '');

if ($title === '') {
    echo json_encode(['success' => false, 'message' => 'Title required.']);
    exit;
}

$stmt = $conn->prepare("INSERT INTO courses (title, lecturer_id) VALUES (?, ?)");
$stmt->bind_param("si", $title, $_SESSION['user_id']);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Course created.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error creating course.']);
}
$stmt->close();
?>
