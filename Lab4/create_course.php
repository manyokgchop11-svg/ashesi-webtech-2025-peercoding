
<?php
require_once 'includes/session.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

require_faculty();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token.';
    } else {
        $course_code = sanitize_input($_POST['course_code'] ?? '');
        $course_name = sanitize_input($_POST['course_name'] ?? '');
        $description = sanitize_input($_POST['description'] ?? '');
        $max_students = (int)($_POST['max_students'] ?? 50);
        $faculty_id = $_SESSION['user_id'];
        
        if (empty($course_code) || empty($course_name)) {
            $error = 'Course code and name are required.';
        } elseif ($max_students < 1 || $max_students > 500) {
            $error = 'Maximum students must be between 1 and 500.';
        } else {
            // Check if course code exists
            $stmt = $conn->prepare("SELECT course_id FROM courses WHERE course_code = ?");
            $stmt->bind_param("s", $course_code);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $error = 'Course code already exists.';
            } else {
                // Insert course
                $stmt = $conn->prepare("INSERT INTO courses (course_code, course_name, description, faculty_id, max_students) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("sssii", $course_code, $course_name, $description, $faculty_id, $max_students);
                
                if ($stmt->execute()) {
                    $success = 'Course created successfully!';
                    // Clear form
                    $_POST = [];
                } else {
                    $error = 'Failed to create course.';
                }
            }
            $stmt->close();
        }
    }
}

$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Course - Faculty Dashboard</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="dashboard">
        <nav class="sidebar">
            <h2>Faculty Portal</h2>
            <ul>
                <li><a href="faculty_dashboard.php">Dashboard</a></li>
                <li><a href="create_course.php" class="active">Create Course</a></li>
                <li><a href="manage_requests.php">Manage Requests</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
        
        <main class="main-content">
            <header>
                <h1>Create New Course</h1>
            </header>
            
            <?php if ($error): ?>
                <?php echo display_error($error); ?>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <?php echo display_success($success); ?>
                <p><a href="faculty_dashboard.php">Back to Dashboard</a></p>
            <?php endif; ?>
            
            <div class="form-container">
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    
                    <div class="form-group">
                        <label for="course_code">Course Code:</label>
                        <input type="text" id="course_code" name="course_code" 
                               value="<?php echo e($_POST['course_code'] ?? ''); ?>" 
                               placeholder="e.g., CS101" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="course_name">Course Name:</label>
                        <input type="text" id="course_name" name="course_name" 
                               value="<?php echo e($_POST['course_name'] ?? ''); ?>" 
                               placeholder="e.g., Introduction to Programming" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description:</label>
                        <textarea id="description" name="description" rows="4" 
                                  placeholder="Enter course description..."><?php echo e($_POST['description'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="max_students">Maximum Students:</label>
                        <input type="number" id="max_students" name="max_students" 
                               value="<?php echo e($_POST['max_students'] ?? '50'); ?>" 
                               min="1" max="500" required>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Create Course</button>
                        <a href="faculty_dashboard.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>