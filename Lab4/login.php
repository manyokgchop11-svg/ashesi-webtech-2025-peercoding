<?php
// IMPORTANT: Load functions.php BEFORE session.php
require_once 'includes/functions.php';
require_once 'includes/session.php';
require_once 'config/database.php';

// Redirect if already logged in
if (is_logged_in()) {
    $redirect = is_faculty() ? 'faculty_dashboard.php' : 'student_dashboard.php';
    header("Location: $redirect");
    exit();
}

$error = '';
$timeout_message = isset($_GET['timeout']) ? 'Your session has expired. Please login again.' : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token. Please try again.';
    } else {
        // Sanitize inputs
        $username = sanitize_input($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        // Validation
        if (empty($username) || empty($password)) {
            $error = 'Username and password are required.';
        } else {
            // Query user
            $stmt = $conn->prepare("SELECT user_id, username, password_hash, user_type, full_name FROM users WHERE username = ? OR email = ?");
            $stmt->bind_param("ss", $username, $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                
                // Verify password
                if (verify_password($password, $user['password_hash'])) {
                    // Regenerate session ID to prevent session fixation
                    session_regenerate_id(true);
                    
                    // Set session variables
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['user_type'] = $user['user_type'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['last_activity'] = time();
                    
                    // Redirect based on user type
                    $redirect = ($user['user_type'] === 'faculty') ? 'faculty_dashboard.php' : 'student_dashboard.php';
                    header("Location: $redirect");
                    exit();
                } else {
                    $error = 'Invalid username or password.';
                }
            } else {
                $error = 'Invalid username or password.';
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
    <title>Login - Course Management System</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="container">
        <div class="auth-form">
            <h1>Login</h1>
            
            <?php if ($timeout_message): ?>
                <?php echo display_error($timeout_message); ?>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <?php echo display_error($error); ?>
            <?php endif; ?>
            
            <form method="POST" action="" id="loginForm">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                
                <div class="form-group">
                    <label for="username">Username or Email:</label>
                    <input type="text" id="username" name="username" 
                           value="<?php echo e($_POST['username'] ?? ''); ?>" required autofocus>
                </div>
                
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" class="btn btn-primary">Login</button>
            </form>
            
            <p class="text-center">Don't have an account? <a href="register.php">Register here</a></p>
        </div>
    </div>
    
    <script src="assets/js/validation.js"></script>
</body>
</html>