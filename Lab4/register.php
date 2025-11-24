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
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token. Please try again.';
    } else {
        // Sanitize inputs
        $username = sanitize_input($_POST['username'] ?? '');
        $email = sanitize_input($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $full_name = sanitize_input($_POST['full_name'] ?? '');
        $user_type = sanitize_input($_POST['user_type'] ?? '');
        
        // Validation
        if (empty($username) || empty($email) || empty($password) || empty($full_name) || empty($user_type)) {
            $error = 'All fields are required.';
        } elseif (strlen($username) < 3 || strlen($username) > 50) {
            $error = 'Username must be between 3 and 50 characters.';
        } elseif (!validate_email($email)) {
            $error = 'Invalid email address.';
        } elseif (!validate_password($password)) {
            $error = 'Password must be at least 8 characters with uppercase, lowercase, number, and special character.';
        } elseif ($password !== $confirm_password) {
            $error = 'Passwords do not match.';
        } elseif (!in_array($user_type, ['student', 'faculty'])) {
            $error = 'Invalid user type.';
        } else {
            // Check if username or email already exists
            $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
            $stmt->bind_param("ss", $username, $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error = 'Username or email already exists.';
            } else {
                // Hash password and insert user
                $password_hash = hash_password($password);
                
                $stmt = $conn->prepare("INSERT INTO users (username, email, password_hash, full_name, user_type) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("sssss", $username, $email, $password_hash, $full_name, $user_type);
                
                if ($stmt->execute()) {
                    $success = 'Registration successful! You can now log in.';
                } else {
                    $error = 'Registration failed. Please try again.';
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
    <title>Register - Course Management System</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="container">
        <div class="auth-form">
            <h1>Register</h1>
            
            <?php if ($error): ?>
                <?php echo display_error($error); ?>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <?php echo display_success($success); ?>
                <p><a href="login.php">Click here to login</a></p>
            <?php else: ?>
                <form method="POST" action="" id="registerForm">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    
                    <div class="form-group">
                        <label for="full_name">Full Name:</label>
                        <input type="text" id="full_name" name="full_name" 
                               value="<?php echo e($_POST['full_name'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="username">Username:</label>
                        <input type="text" id="username" name="username" 
                               value="<?php echo e($_POST['username'] ?? ''); ?>" 
                               pattern="[a-zA-Z0-9_]{3,50}" 
                               title="3-50 characters, letters, numbers, and underscores only" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" 
                               value="<?php echo e($_POST['email'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password:</label>
                        <input type="password" id="password" name="password" required>
                        <small>At least 8 characters with uppercase, lowercase, number, and special character</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password:</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="user_type">Register as:</label>
                        <select id="user_type" name="user_type" required>
                            <option value="">Select type</option>
                            <option value="student" <?php echo ($_POST['user_type'] ?? '') === 'student' ? 'selected' : ''; ?>>Student</option>
                            <option value="faculty" <?php echo ($_POST['user_type'] ?? '') === 'faculty' ? 'selected' : ''; ?>>Faculty</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Register</button>
                </form>
                
                <p class="text-center">Already have an account? <a href="login.php">Login here</a></p>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="assets/js/validation.js"></script>
</body>
</html>