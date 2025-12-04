
<?php
// returns JSON list of courses
require_once __DIR__ . '/../config/db_config.php';

header('Content-Type: application/json');

$result = $conn->query("SELECT c.id, c.title, c.lecturer_id, u.username AS lecturer
                        FROM courses c
                        LEFT JOIN users u ON c.lecturer_id = u.id
                        ORDER BY c.id DESC");

$courses = [];
while ($row = $result->fetch_assoc()) {
    $courses[] = $row;
}

echo json_encode(['success' => true, 'courses' => $courses]);
?>
