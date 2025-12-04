
<?php
// Mark attendance for a student in an open session
session_start();
require_once __DIR__ . '/../config/db_config.php';

header('Content-Type: application/json');

// Allow both lecturer (mark many) and student (self-mark) but keep simple:
// Request: session_id, user_id (optional). If user not provided and session is open, use session user.

$session_id = intval($_POST['session_id'] ?? 0);
$target_user_id = intval($_POST['user_id'] ?? 0);

if ($session_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Session ID required.']);
    exit;
}

// check session exists and is open
$stmt = $conn->prepare("SELECT course_id, is_open FROM sessions WHERE id = ?");
$stmt->bind_param("i", $session_id);
$stmt->execute();
$stmt->bind_result($course_id, $is_open);
if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Session not found.']);
    exit;
}
$stmt->close();

if (!$is_open) {
    echo json_encode(['success' => false, 'message' => 'Session is closed.']);
    exit;
}

// if no user_id provided, try current logged in user
if ($target_user_id === 0) {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Please log in or provide user_id.']);
        exit;
    }
    $target_user_id = $_SESSION['user_id'];
}

// optionally you can check enrollment: simple check
$stmt = $conn->prepare("SELECT id FROM enrollments WHERE user_id = ? AND course_id = ?");
$stmt->bind_param("ii", $target_user_id, $course_id);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows === 0) {
    // not enrolled
    echo json_encode(['success' => false, 'message' => 'User is not enrolled in the course.']);
    exit;
}
$stmt->close();

// insert or update attendance
$stmt = $conn->prepare("INSERT INTO attendance (session_id, user_id, status) VALUES (?, ?, 'present') 
                       ON DUPLICATE KEY UPDATE status = VALUES(status), marked_at = CURRENT_TIMESTAMP");
$stmt->bind_param("ii", $session_id, $target_user_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Attendance recorded.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Could not record attendance.']);
}
$stmt->close();
?>
