<?php
require_once 'includes/session.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

require_faculty();

$faculty_id = get_current_user_id();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_session') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $message = display_error('Invalid security token.');
    } else {
        $course_id      = (int)($_POST['course_id'] ?? 0);
        $session_title  = trim($_POST['session_title'] ?? '');
        $session_desc   = trim($_POST['session_description'] ?? '');
        $session_date   = $_POST['session_date'] ?? '';
        $start_time     = $_POST['start_time'] ?? '';
        $end_time       = $_POST['end_time'] ?? '';
        $code_duration  = (int)($_POST['code_duration'] ?? 15); // minutes

        if ($course_id <= 0 || $session_title === '' || $session_date === '' || $start_time === '' || $end_time === '') {
            $message = display_error('All required fields must be filled.');
        } else {
            // Verify faculty owns this course
            $stmt = $conn->prepare("SELECT course_id FROM courses WHERE course_id = ? AND faculty_id = ?");
            $stmt->bind_param("ii", $course_id, $faculty_id);
            $stmt->execute();
            $owns = $stmt->get_result()->num_rows > 0;
            $stmt->close();

            if (!$owns) {
                $message = display_error('You are not allowed to manage this course.');
            } else {
                $code = generate_attendance_code();
                $code_expires_at = date('Y-m-d H:i:s', strtotime("$session_date $start_time +$code_duration minutes"));

                $stmt = $conn->prepare("
                    INSERT INTO class_sessions (
                        course_id,
                        faculty_id,
                        session_title,
                        session_description,
                        session_date,
                        start_time,
                        end_time,
                        attendance_code,
                        code_expires_at,
                        is_active
                    )
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
                ");
                $stmt->bind_param(
                    "iisssssss",
                    $course_id,
                    $faculty_id,
                    $session_title,
                    $session_desc,
                    $session_date,
                    $start_time,
                    $end_time,
                    $code,
                    $code_expires_at
                );

                if ($stmt->execute()) {
                    $message = display_success('Class session created. Code: ' . e($code));
                } else {
                    $message = display_error('Failed to create session.');
                }
                $stmt->close();
            }
        }
    }
}

$selected_course = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;

// Fetch faculty courses
$stmt = $conn->prepare("
    SELECT course_id, course_code, course_name
    FROM courses
    WHERE faculty_id = ?
    ORDER BY course_name
");
$stmt->bind_param("i", $faculty_id);
$stmt->execute();
$courses = $stmt->get_result();
$stmt->close();

// Fetch sessions for selected course
$sessions = null;
if ($selected_course > 0) {
    $stmt = $conn->prepare("
        SELECT session_id, session_title, session_date, start_time, end_time, attendance_code, code_expires_at, is_active
        FROM class_sessions
        WHERE course_id = ?
        ORDER BY session_date DESC, start_time DESC
    ");
    $stmt->bind_param("i", $selected_course);
    $stmt->execute();
    $sessions = $stmt->get_result();
    $stmt->close();
}

$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Manage Sessions - Faculty Dashboard</title>
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
      <h1>Manage Class Sessions</h1>
      <p class="subtitle">Create sessions and share attendance codes with students.</p>
    </header>

    <?php echo $message; ?>

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
      <section class="form-container">
        <h2>Create New Session</h2>
        <form method="post" action="">
          <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
          <input type="hidden" name="course_id" value="<?php echo $selected_course; ?>">
          <input type="hidden" name="action" value="create_session">

          <div class="form-group">
            <label for="session_title">Session Title</label>
            <input type="text" id="session_title" name="session_title" placeholder="e.g., Week 3 Lecture" required>
          </div>

          <div class="form-group">
            <label for="session_description">Description (optional)</label>
            <textarea id="session_description" name="session_description" rows="3" placeholder="Short description..."></textarea>
          </div>

          <div class="form-group">
            <label for="session_date">Date</label>
            <input type="date" id="session_date" name="session_date" required>
          </div>

          <div class="form-group">
            <label for="start_time">Start time</label>
            <input type="time" id="start_time" name="start_time" required>
          </div>

          <div class="form-group">
            <label for="end_time">End time</label>
            <input type="time" id="end_time" name="end_time" required>
          </div>

          <div class="form-group">
            <label for="code_duration">Code valid for (minutes)</label>
            <input type="number" id="code_duration" name="code_duration" min="1" max="240" value="15">
          </div>

          <div class="form-actions">
            <button type="submit" class="btn btn-primary">Create Session</button>
          </div>
        </form>
      </section>

      <section class="courses-section">
        <div class="section-header">
          <h2>Existing Sessions</h2>
        </div>
        <?php if ($sessions && $sessions->num_rows > 0): ?>
          <div class="table-container">
            <table class="requests-table">
              <thead>
              <tr>
                <th>Title</th>
                <th>Date</th>
                <th>Time</th>
                <th>Code</th>
                <th>Expires</th>
                <th>Status</th>
              </tr>
              </thead>
              <tbody>
              <?php while ($s = $sessions->fetch_assoc()): ?>
                <tr>
                  <td><?php echo e($s['session_title']); ?></td>
                  <td><?php echo date('M d, Y', strtotime($s['session_date'])); ?></td>
                  <td><?php echo e(substr($s['start_time'], 0, 5) . ' - ' . substr($s['end_time'], 0, 5)); ?></td>
                  <td><strong><?php echo e($s['attendance_code']); ?></strong></td>
                  <td><?php echo date('M d, Y H:i', strtotime($s['code_expires_at'])); ?></td>
                  <td>
                    <span class="badge badge-<?php echo $s['is_active'] ? 'approved' : 'rejected'; ?>">
                      <?php echo $s['is_active'] ? 'Active' : 'Closed'; ?>
                    </span>
                  </td>
                </tr>
              <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <div class="empty-state">
            <p>No sessions created yet for this course.</p>
          </div>
        <?php endif; ?>
      </section>
    <?php endif; ?>
  </main>
</div>
</body>
</html>
