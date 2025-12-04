<?php
require_once 'includes/session.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

require_student();

$student_id = get_current_user_id();

// Fetch enrolled courses
$stmt = $conn->prepare("
    SELECT c.course_id, c.course_code, c.course_name, u.full_name AS faculty_name
    FROM courses c
    JOIN enrollment_requests er ON c.course_id = er.course_id
    JOIN users u ON c.faculty_id = u.user_id
    WHERE er.student_id = ? AND er.status = 'approved'
    ORDER BY c.course_name
");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$courses = $stmt->get_result();
$stmt->close();

// Selected course from dropdown
$selected_course = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
$sessions = null;
$summary = null;

if ($selected_course > 0) {
    // Per-session attendance
    $stmt = $conn->prepare("
        SELECT cs.session_id, cs.session_date, cs.start_time, cs.end_time,
               COALESCE(ar.status, 'absent') AS status
        FROM class_sessions cs
        LEFT JOIN attendance_records ar
          ON cs.session_id = ar.session_id AND ar.student_id = ?
        WHERE cs.course_id = ?
        ORDER BY cs.session_date DESC, cs.start_time DESC
    ");
    $stmt->bind_param("ii", $student_id, $selected_course);
    $stmt->execute();
    $sessions = $stmt->get_result();
    $stmt->close();

    // Overall summary from attendance_stats
    $stmt = $conn->prepare("
        SELECT total_sessions, attended_sessions, late_sessions, absent_sessions, attendance_percentage
        FROM attendance_stats
        WHERE course_id = ? AND student_id = ?
    ");
    $stmt->bind_param("ii", $selected_course, $student_id);
    $stmt->execute();
    $summary = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Attendance Report - Student Portal</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="dashboard">
  <nav class="sidebar">
    <h2>Student Portal</h2>
    <ul>
      <li><a href="student_dashboard.php">Dashboard</a></li>
      <li><a href="join_course.php">Join Course</a></li>
      <li><a href="mark_attendance.php">Mark Attendance</a></li>
      <li><a href="attendance_report.php" class="active">Attendance Report</a></li>
      <li><a href="logout.php">Logout</a></li>
    </ul>
  </nav>

  <main class="main-content">
    <header>
      <h1>Attendance Report</h1>
      <p class="subtitle">Check your daily and overall attendance for each course.</p>
    </header>

    <section class="filter-section">
      <form method="get" action="">
        <label for="course_id">Select Course</label>
        <select id="course_id" name="course_id" onchange="this.form.submit()">
          <option value="0">Choose a course</option>
          <?php mysqli_data_seek($courses, 0); ?>
          <?php while ($c = $courses->fetch_assoc()): ?>
            <option value="<?php echo $c['course_id']; ?>"
              <?php echo $selected_course == $c['course_id'] ? 'selected' : ''; ?>>
              <?php echo e($c['course_code'] . ' - ' . $c['course_name']); ?>
            </option>
          <?php endwhile; ?>
        </select>
      </form>
    </section>

    <?php if ($selected_course > 0): ?>
      <section class="stats">
        <div class="stat-card">
          <h3><?php echo (int)($summary['total_sessions'] ?? 0); ?></h3>
          <p>Total Sessions</p>
        </div>
        <div class="stat-card">
          <h3><?php echo (int)($summary['attended_sessions'] ?? 0); ?></h3>
          <p>Sessions Attended</p>
        </div>
        <div class="stat-card highlight">
          <?php
          $percent = isset($summary['attendance_percentage'])
              ? (float)$summary['attendance_percentage']
              : 0;
          ?>
          <h3><?php echo $percent; ?>%</h3>
          <p>Attendance Rate</p>
        </div>
      </section>

      <section class="courses-section">
        <div class="section-header">
          <h2>Daily Attendance</h2>
        </div>
        <?php if ($sessions && $sessions->num_rows > 0): ?>
          <div class="table-container">
            <table class="requests-table">
              <thead>
              <tr>
                <th>Date</th>
                <th>Time</th>
                <th>Status</th>
              </tr>
              </thead>
              <tbody>
              <?php while ($s = $sessions->fetch_assoc()): ?>
                <tr>
                  <td><?php echo date('M d, Y', strtotime($s['session_date'])); ?></td>
                  <td><?php echo e(substr($s['start_time'], 0, 5) . ' - ' . substr($s['end_time'], 0, 5)); ?></td>
                  <td>
                    <span class="badge badge-<?php echo $s['status'] === 'present' ? 'approved' : 'rejected'; ?>">
                      <?php echo ucfirst($s['status']); ?>
                    </span>
                  </td>
                </tr>
              <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <div class="empty-state">
            <p>No sessions found for this course.</p>
          </div>
        <?php endif; ?>
      </section>
    <?php endif; ?>
  </main>
</div>
</body>
</html>
