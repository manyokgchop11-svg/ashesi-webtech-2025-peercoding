<?php
require_once 'includes/session.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

require_student();

$student_id = get_current_user_id();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $message = display_error('Invalid security token.');
    } else {
        $code = strtoupper(trim($_POST['attendance_code'] ?? ''));
        if ($code === '') {
            $message = display_error('Please enter the attendance code.');
        } else {
            $today = date('Y-m-d');

            // Find active session by code for today
            $stmt = $conn->prepare("
                SELECT session_id, course_id
                FROM class_sessions
                WHERE attendance_code = ?
                  AND session_date = ?
                  AND is_active = 1
                  AND code_expires_at >= NOW()
            ");
            $stmt->bind_param("ss", $code, $today);
            $stmt->execute();
            $res = $stmt->get_result();

            if ($res->num_rows === 0) {
                $message = display_error('Invalid or expired attendance code.');
            } else {
                $session = $res->fetch_assoc();
                $session_id = (int)$session['session_id'];
                $course_id  = (int)$session['course_id'];
                $stmt->close();

                // Check enrollment
                $stmt = $conn->prepare("
                    SELECT request_id
                    FROM enrollment_requests
                    WHERE course_id = ?
                      AND student_id = ?
                      AND status = 'approved'
                ");
                $stmt->bind_param("ii", $course_id, $student_id);
                $stmt->execute();
                $enrolled = $stmt->get_result()->num_rows > 0;
                $stmt->close();

                if (!$enrolled) {
                    $message = display_error('You are not enrolled in this course.');
                } else {
                    // Insert or update attendance as present
                    $stmt = $conn->prepare("
                        INSERT INTO attendance_records (session_id, student_id, status)
                        VALUES (?, ?, 'present')
                        ON DUPLICATE KEY UPDATE status = 'present'
                    ");
                    $stmt->bind_param("ii", $session_id, $student_id);

                    if ($stmt->execute()) {
                        $message = display_success('Attendance marked successfully.');
                        // Update stats table
                        update_course_attendance_summary($conn, $course_id, $student_id);
                    } else {
                        $message = display_error('Failed to mark attendance.');
                    }
                    $stmt->close();
                }
            }
        }
    }
}

$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Mark Attendance - Student Portal</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="dashboard">
  <nav class="sidebar">
    <h2>Student Portal</h2>
    <ul>
      <li><a href="student_dashboard.php">Dashboard</a></li>
      <li><a href="join_course.php">Join Course</a></li>
      <li><a href="mark_attendance.php" class="active">Mark Attendance</a></li>
      <li><a href="attendance_report.php">Attendance Report</a></li>
      <li><a href="logout.php">Logout</a></li>
    </ul>
  </nav>

  <main class="main-content">
    <header>
      <h1>Mark Attendance</h1>
      <p class="subtitle">Enter the attendance code from your instructor.</p>
    </header>

    <?php echo $message; ?>

    <div class="form-container">
      <form method="post" action="">
        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
        <div class="form-group">
          <label for="attendance_code">Attendance Code</label>
          <input type="text" id="attendance_code" name="attendance_code"
                 placeholder="e.g., A7K2QP" required>
        </div>
        <div class="form-actions">
          <button type="submit" class="btn btn-primary">Submit</button>
        </div>
      </form>
    </div>
  </main>
</div>
</body>
</html>
