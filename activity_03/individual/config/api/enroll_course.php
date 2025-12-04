
<?php
// Student enrolls into a course
session_start();
require_once __DIR__ . '/../config/db_config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please log in.']);
    exit;
}

$course_id = intval($_POST['course_id'] ?? 0);
$user_id = $_SESSION['user_id'];

if ($course_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Course ID required.']);
    exit;
}

// insert with check for duplicates
$stmt = $conn->prepare("INSERT INTO enrollments (user_id, course_id) VALUES (?, ?)");
$stmt->bind_param("ii", $user_id, $course_id);
if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Enrolled successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Could not enroll (maybe already enrolled).']);
}
$stmt->close();
?>
