
<?php
// Lecturer creates an attendance session for a course
session_start();
require_once __DIR__ . '/../config/db_config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'lecturer') {
    echo json_encode(['success' => false, 'message' => 'Only lecturers can create sessions.']);
    exit;
}

$course_id = intval($_POST['course_id'] ?? 0);

if ($course_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Course ID required.']);
    exit;
}

// Quick check: is lecturer the owner of the course?
$stmt = $conn->prepare("SELECT lecturer_id FROM courses WHERE id = ?");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$stmt->bind_result($lecturer_id);
if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Course not found.']);
    exit;
}
$stmt->close();

if ($lecturer_id != $_SESSION['user_id']) {
    echo json_encode(['success' => false, 'message' => 'You are not the lecturer of this course.']);
    exit;
}

// create session
$stmt = $conn->prepare("INSERT INTO sessions (course_id, session_date, is_open) VALUES (?, NOW(), 1)");
$stmt->bind_param("i", $course_id);
if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Session created.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error creating session.']);
}
$stmt->close();
?>
