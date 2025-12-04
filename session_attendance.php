
<?php
require_once 'includes/session.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

require_faculty();
$facultyid = get_current_user_id();
$sessionid = isset($_GET['sessionid']) ? (int)$_GET['sessionid'] : 0;

if ($sessionid <= 0) {
    header('Location: manage_sessions.php');
    exit();
}

$stmt = $conn->prepare("
  SELECT cs.sessionid, cs.courseid, cs.session_date, cs.start_time, cs.end_time, cs.attendance_code,
         c.coursecode, c.coursename
  FROM class_sessions cs
  JOIN courses c ON cs.courseid = c.courseid
  WHERE cs.sessionid = ? AND cs.facultyid = ?
");
$stmt->bind_param("ii", $sessionid, $facultyid);
$stmt->execute();
$session = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$session) {
    header('Location: manage_sessions.php');
    exit();
}

$stmt = $conn->prepare("
  SELECT u.userid, u.fullname, u.email,
         COALESCE(ar.status, 'absent') AS status
  FROM enrollment_requests er
  JOIN users u ON er.studentid = u.userid
  LEFT JOIN attendance_records ar
    ON ar.studentid = u.userid AND ar.sessionid = ?
  WHERE er.courseid = ? AND er.status = 'approved'
  ORDER BY u.fullname
");
$stmt->bind_param("ii", $sessionid, $session['courseid']);
$stmt->execute();
$students = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Session Attendance - Faculty Dashboard</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="dashboard">
  <nav class="sidebar">
    <h2>Faculty Portal</h2>
    <ul>
      <li><a href="faculty_dashboard.php">Dashboard</a></li>
      <li><a href="create_course.php">Create Course</a></li>
      <li><a href="manage_requests.php">Manage Requests</a></li>
      <li><a href="manage_sessions.php" class="active">Sessions</a></li>
      <li><a href="logout.php">Logout</a></li>
    </ul>
  </nav>

  <main class="main-content">
    <header>
      <h1>Session Attendance</h1>
      <p class="subtitle">
        <?php echo e($session['coursecode'] . ' - ' . $session['coursename']); ?> |
        <?php echo date('M d, Y', strtotime($session['session_date'])); ?> |
        Code: <strong><?php echo e($session['attendance_code']); ?></strong>
      </p>
    </header>

    <section class="courses-section">
      <div class="section-header">
        <h2>Students</h2>
      </div>
      <?php if ($students->num_rows > 0): ?>
        <div class="table-container">
          <table class="requests-table">
            <thead>
            <tr>
              <th>Student</th>
              <th>Email</th>
              <th>Status</th>
            </tr>
            </thead>
            <tbody>
            <?php while ($s = $students->fetch_assoc()): ?>
              <tr>
                <td><?php echo e($s['fullname']); ?></td>
                <td><?php echo e($s['email']); ?></td>
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
          <p>No enrolled students for this course.</p>
        </div>
      <?php endif; ?>
    </section>
  </main>
</div>
</body>
</html>
