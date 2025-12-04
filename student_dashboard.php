
<?php
require_once 'includes/session.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

require_student();

$student_id = $_SESSION['user_id'];
$student_name = $_SESSION['full_name'];

// Fetch enrolled courses
$stmt = $conn->prepare("
    SELECT c.*, u.full_name as faculty_name, er.status, er.request_date
    FROM courses c
    JOIN enrollment_requests er ON c.course_id = er.course_id
    JOIN users u ON c.faculty_id = u.user_id
    WHERE er.student_id = ? AND er.status = 'approved'
    ORDER BY c.course_name
");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$enrolled_courses = $stmt->get_result();
$stmt->close();

// Fetch pending requests
$stmt = $conn->prepare("
    SELECT c.*, u.full_name as faculty_name, er.status, er.request_date
    FROM courses c
    JOIN enrollment_requests er ON c.course_id = er.course_id
    JOIN users u ON c.faculty_id = u.user_id
    WHERE er.student_id = ? AND er.status = 'pending'
    ORDER BY er.request_date DESC
");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$pending_requests = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - Course Management System</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="dashboard">
        <nav class="sidebar">
  <h2>Student Portal</h2>
  <ul>
    <li><a href="student_dashboard.php" class="active">Dashboard</a></li>
    <li><a href="join_course.php">Join Course</a></li>
    <li><a href="mark_attendance.php">Mark Attendance</a></li>
    <li><a href="attendance_report.php">Attendance Report</a></li>
    <li><a href="logout.php">Logout</a></li>
  </ul>
</nav>

        
        <main class="main-content">
            <header>
                <h1>Welcome, <?php echo e($student_name); ?></h1>
                <p class="subtitle">Student Dashboard</p>
            </header>
            
            <section class="stats">
                <div class="stat-card">
                    <h3><?php echo $enrolled_courses->num_rows; ?></h3>
                    <p>Enrolled Courses</p>
                </div>
                <div class="stat-card highlight">
                    <h3><?php echo $pending_requests->num_rows; ?></h3>
                    <p>Pending Requests</p>
                </div>
            </section>
            
            <?php if ($pending_requests->num_rows > 0): ?>
                <section class="courses-section">
                    <h2>Pending Requests</h2>
                    <div class="courses-grid">
                        <?php while ($request = $pending_requests->fetch_assoc()): ?>
                            <div class="course-card pending">
                                <div class="course-header">
                                    <h3><?php echo e($request['course_name']); ?></h3>
                                    <span class="badge badge-pending">Pending</span>
                                </div>
                                <p class="course-code"><?php echo e($request['course_code']); ?></p>
                                <p><strong>Faculty:</strong> <?php echo e($request['faculty_name']); ?></p>
                                <p class="text-muted">Requested on <?php echo date('M d, Y', strtotime($request['request_date'])); ?></p>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </section>
            <?php endif; ?>
            
            <section class="courses-section">
                <div class="section-header">
                    <h2>My Courses</h2>
                    <a href="join_course.php" class="btn btn-primary">+ Join New Course</a>
                </div>
                
                <?php if ($enrolled_courses->num_rows > 0): ?>
                    <div class="courses-grid">
                        <?php while ($course = $enrolled_courses->fetch_assoc()): ?>
                            <div class="course-card enrolled">
                                <div class="course-header">
                                    <h3><?php echo e($course['course_name']); ?></h3>
                                    <span class="badge badge-approved">Enrolled</span>
                                </div>
                                <p class="course-code"><?php echo e($course['course_code']); ?></p>
                                <p class="course-description"><?php echo e($course['description']); ?></p>
                                <p><strong>Faculty:</strong> <?php echo e($course['faculty_name']); ?></p>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <p>You are not enrolled in any courses yet.</p>
                        <a href="join_course.php" class="btn btn-primary">Browse Available Courses</a>
                    </div>
                <?php endif; ?>
            </section>
        </main>
    </div>
</body>
</html>