<?php
require_once 'includes/functions.php';
require_once 'includes/session.php';
require_once 'config/database.php';

// Require faculty login
require_faculty();

$faculty_id = $_SESSION['user_id'];
$faculty_name = $_SESSION['full_name'];

// Fetch faculty courses
$stmt = $conn->prepare("
    SELECT c.*, 
           COUNT(DISTINCT CASE WHEN er.status = 'approved' THEN er.student_id END) as enrolled_count,
           COUNT(DISTINCT CASE WHEN er.status = 'pending' THEN er.request_id END) as pending_requests
    FROM courses c
    LEFT JOIN enrollment_requests er ON c.course_id = er.course_id
    WHERE c.faculty_id = ?
    GROUP BY c.course_id
    ORDER BY c.created_at DESC
");
$stmt->bind_param("i", $faculty_id);
$stmt->execute();
$courses = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Dashboard - Course Management System</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="dashboard">
        <nav class="sidebar">
            <h2>Faculty Portal</h2>
            <ul>
                <li><a href="faculty_dashboard.php" class="active">Dashboard</a></li>
                <li><a href="create_course.php">Create Course</a></li>
                <li><a href="manage_requests.php">Manage Requests</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
        
        <main class="main-content">
            <header>
                <h1>Welcome, <?php echo e($faculty_name); ?></h1>
                <p class="subtitle">Faculty Dashboard</p>
            </header>
            
            <section class="stats">
                <div class="stat-card">
                    <h3><?php echo $courses->num_rows; ?></h3>
                    <p>Total Courses</p>
                </div>
                <div class="stat-card">
                    <h3>
                        <?php 
                        $total_enrolled = 0;
                        $total_pending = 0;
                        mysqli_data_seek($courses, 0);
                        while ($course = $courses->fetch_assoc()) {
                            $total_enrolled += $course['enrolled_count'];
                            $total_pending += $course['pending_requests'];
                        }
                        echo $total_enrolled;
                        ?>
                    </h3>
                    <p>Total Students</p>
                </div>
                <div class="stat-card highlight">
                    <h3><?php echo $total_pending; ?></h3>
                    <p>Pending Requests</p>
                </div>
            </section>
            
            <section class="courses-section">
                <div class="section-header">
                    <h2>My Courses</h2>
                    <a href="create_course.php" class="btn btn-primary">+ Create New Course</a>
                </div>
                
                <?php mysqli_data_seek($courses, 0); ?>
                <?php if ($courses->num_rows > 0): ?>
                    <div class="courses-grid">
                        <?php while ($course = $courses->fetch_assoc()): ?>
                            <div class="course-card">
                                <div class="course-header">
                                    <h3><?php echo e($course['course_name']); ?></h3>
                                    <span class="course-code"><?php echo e($course['course_code']); ?></span>
                                </div>
                                <p class="course-description"><?php echo e($course['description']); ?></p>
                                <div class="course-stats">
                                    <span> <?php echo $course['enrolled_count']; ?>/<?php echo $course['max_students']; ?> Students</span>
                                    <?php if ($course['pending_requests'] > 0): ?>
                                        <span class="badge-pending"><?php echo $course['pending_requests']; ?> Pending</span>
                                    <?php endif; ?>
                                </div>
                                <div class="course-actions">
                                    <a href="manage_requests.php?course_id=<?php echo $course['course_id']; ?>" 
                                       class="btn btn-secondary">Manage Requests</a>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <p>You haven't created any courses yet.</p>
                        <a href="create_course.php" class="btn btn-primary">Create Your First Course</a>
                    </div>
                <?php endif; ?>
            </section>
        </main>
    </div>
</body>
</html>