<?php
require_once 'includes/functions.php';
require_once 'includes/session.php';
require_once 'config/database.php';

require_student();

$student_id = $_SESSION['user_id'];
$message = '';

// Handle course join request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['course_id'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $message = display_error('Invalid security token.');
    } else {
        $course_id = (int)$_POST['course_id'];
        
        // Check if already requested or enrolled
        $stmt = $conn->prepare("SELECT status FROM enrollment_requests WHERE course_id = ? AND student_id = ?");
        $stmt->bind_param("ii", $course_id, $student_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $existing = $result->fetch_assoc();
            if ($existing['status'] === 'pending') {
                $message = display_error('You have already requested to join this course.');
            } elseif ($existing['status'] === 'approved') {
                $message = display_error('You are already enrolled in this course.');
            } else {
                $message = display_error('Your previous request was rejected. Please contact the faculty.');
            }
        } else {
            // Insert enrollment request
            $stmt = $conn->prepare("INSERT INTO enrollment_requests (course_id, student_id, status) VALUES (?, ?, 'pending')");
            $stmt->bind_param("ii", $course_id, $student_id);
            
            if ($stmt->execute()) {
                $message = display_success('Course join request submitted successfully! Waiting for faculty approval.');
            } else {
                $message = display_error('Failed to submit request. Please try again.');
            }
        }
        $stmt->close();
    }
}

// Fetch available courses (not already requested or enrolled)
$stmt = $conn->prepare("
    SELECT c.*, u.full_name as faculty_name,
           COUNT(CASE WHEN er2.status = 'approved' THEN 1 END) as enrolled_count
    FROM courses c
    JOIN users u ON c.faculty_id = u.user_id
    LEFT JOIN enrollment_requests er2 ON c.course_id = er2.course_id AND er2.status = 'approved'
    WHERE c.course_id NOT IN (
        SELECT course_id FROM enrollment_requests WHERE student_id = ?
    )
    GROUP BY c.course_id
    HAVING enrolled_count < c.max_students
    ORDER BY c.course_name
");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$available_courses = $stmt->get_result();
$stmt->close();

$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join Course - Student Dashboard</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="dashboard">
        <nav class="sidebar">
            <h2>Student Portal</h2>
            <ul>
                <li><a href="student_dashboard.php">Dashboard</a></li>
                <li><a href="join_course.php" class="active">Join Course</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
        
        <main class="main-content">
            <header>
                <h1>Join a Course</h1>
                <p class="subtitle">Browse available courses and request to join</p>
            </header>
            
            <?php echo $message; ?>
            
            <?php if ($available_courses->num_rows > 0): ?>
                <div class="courses-grid">
                    <?php while ($course = $available_courses->fetch_assoc()): ?>
                        <div class="course-card">
                            <div class="course-header">
                                <h3><?php echo e($course['course_name']); ?></h3>
                                <span class="course-code"><?php echo e($course['course_code']); ?></span>
                            </div>
                            <p class="course-description"><?php echo e($course['description']); ?></p>
                            <p><strong>Faculty:</strong> <?php echo e($course['faculty_name']); ?></p>
                            <div class="course-stats">
                                <span><?php echo $course['enrolled_count']; ?>/<?php echo $course['max_students']; ?> Students</span>
                            </div>
                            <form method="POST" action="">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                <input type="hidden" name="course_id" value="<?php echo $course['course_id']; ?>">
                                <button type="submit" class="btn btn-primary">Request to Join</button>
                            </form>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <p>No available courses at the moment. You may have already requested or enrolled in all available courses.</p>
                    <a href="student_dashboard.php" class="btn btn-primary">Back to Dashboard</a>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>