
<?php
require_once 'includes/session.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

require_faculty();

$faculty_id = $_SESSION['user_id'];
$message = '';

// Handle approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $message = display_error('Invalid security token.');
    } else {
        $request_id = (int)($_POST['request_id'] ?? 0);
        $action = $_POST['action'];
        
        if (in_array($action, ['approved', 'rejected'])) {
            // Verify faculty owns the course
            $stmt = $conn->prepare("
                SELECT er.request_id FROM enrollment_requests er
                JOIN courses c ON er.course_id = c.course_id
                WHERE er.request_id = ? AND c.faculty_id = ? AND er.status = 'pending'
            ");
            $stmt->bind_param("ii", $request_id, $faculty_id);
            $stmt->execute();
            
            if ($stmt->get_result()->num_rows > 0) {
                $stmt = $conn->prepare("UPDATE enrollment_requests SET status = ?, response_date = NOW() WHERE request_id = ?");
                $stmt->bind_param("si", $action, $request_id);
                
                if ($stmt->execute()) {
                    $message = display_success("Request " . ($action === 'approved' ? 'approved' : 'rejected') . " successfully!");
                } else {
                    $message = display_error("Failed to process request.");
                }
            }
            $stmt->close();
        }
    }
}

// Get course filter
$course_filter = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;

// Fetch courses for filter
$stmt = $conn->prepare("SELECT course_id, course_code, course_name FROM courses WHERE faculty_id = ? ORDER BY course_name");
$stmt->bind_param("i", $faculty_id);
$stmt->execute();
$courses = $stmt->get_result();
$stmt->close();

// Fetch enrollment requests
$query = "
    SELECT er.*, c.course_code, c.course_name, u.username, u.full_name, u.email
    FROM enrollment_requests er
    JOIN courses c ON er.course_id = c.course_id
    JOIN users u ON er.student_id = u.user_id
    WHERE c.faculty_id = ?
";

if ($course_filter > 0) {
    $query .= " AND c.course_id = ?";
}

$query .= " ORDER BY 
    CASE er.status 
        WHEN 'pending' THEN 1 
        WHEN 'approved' THEN 2 
        WHEN 'rejected' THEN 3 
    END,
    er.request_date DESC";

$stmt = $conn->prepare($query);
if ($course_filter > 0) {
    $stmt->bind_param("ii", $faculty_id, $course_filter);
} else {
    $stmt->bind_param("i", $faculty_id);
}
$stmt->execute();
$requests = $stmt->get_result();
$stmt->close();

$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Requests - Faculty Dashboard</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="dashboard">
        <nav class="sidebar">
            <h2>Faculty Portal</h2>
            <ul>
                <li><a href="faculty_dashboard.php">Dashboard</a></li>
                <li><a href="create_course.php">Create Course</a></li>
                <li><a href="manage_requests.php" class="active">Manage Requests</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
        
        <main class="main-content">
            <header>
                <h1>Manage Enrollment Requests</h1>
            </header>
            
            <?php echo $message; ?>
            
            <div class="filter-section">
                <form method="GET" action="">
                    <label for="course_id">Filter by Course:</label>
                    <select id="course_id" name="course_id" onchange="this.form.submit()">
                        <option value="0">All Courses</option>
                        <?php mysqli_data_seek($courses, 0); ?>
                        <?php while ($course = $courses->fetch_assoc()): ?>
                            <option value="<?php echo $course['course_id']; ?>" 
                                    <?php echo ($course_filter == $course['course_id']) ? 'selected' : ''; ?>>
                                <?php echo e($course['course_code'] . ' - ' . $course['course_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </form>
            </div>
            
            <?php if ($requests->num_rows > 0): ?>
                <div class="table-container">
                    <table class="requests-table">
                        <thead>
                            <tr>
                                <th>Student Name</th>
                                <th>Email</th>
                                <th>Course</th>
                                <th>Request Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($request = $requests->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo e($request['full_name']); ?></td>
                                    <td><?php echo e($request['email']); ?></td>
                                    <td><?php echo e($request['course_code'] . ' - ' . $request['course_name']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($request['request_date'])); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $request['status']; ?>">
                                            <?php echo ucfirst($request['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($request['status'] === 'pending'): ?>
                                            <form method="POST" action="" style="display: inline;">
                                                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                                <input type="hidden" name="request_id" value="<?php echo $request['request_id']; ?>">
                                                <button type="submit" name="action" value="approved" 
                                                        class="btn btn-small btn-success">Approve</button>
                                                <button type="submit" name="action" value="rejected" 
                                                        class="btn btn-small btn-danger">Reject</button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-muted">
                                                <?php echo ucfirst($request['status']); ?> on 
                                                <?php echo date('M d, Y', strtotime($request['response_date'])); ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <p>No enrollment requests found.</p>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>