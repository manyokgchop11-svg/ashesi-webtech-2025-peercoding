<?php
/**
 * Helper Functions for Course Management System
 * Contains security, validation, and utility functions
 */

// ==================== SECURITY FUNCTIONS ====================

/**
 * Sanitize input data to prevent XSS attacks
 */
function sanitize_input($data) {
    if (is_array($data)) {
        return array_map('sanitize_input', $data);
    }
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Validate email address
 */
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate password strength
 * Requirements: At least 8 characters, 1 uppercase, 1 lowercase, 1 number, 1 special char
 */
function validate_password($password) {
    if (strlen($password) < 8) {
        return false;
    }
    
    // Check for uppercase letter
    if (!preg_match('/[A-Z]/', $password)) {
        return false;
    }
    
    // Check for lowercase letter
    if (!preg_match('/[a-z]/', $password)) {
        return false;
    }
    
    // Check for number
    if (!preg_match('/\d/', $password)) {
        return false;
    }
    
    // Check for special character
    if (!preg_match('/[@$!%*?&#]/', $password)) {
        return false;
    }
    
    return true;
}

/**
 * Hash password using bcrypt
 */
function hash_password($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
}

/**
 * Verify password against hash
 */
function verify_password($password, $hash) {
    return password_verify($password, $hash);
}

// ==================== CSRF PROTECTION ====================

/**
 * Generate CSRF token
 */
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verify_csrf_token($token) {
    if (!isset($_SESSION['csrf_token']) || !isset($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Regenerate CSRF token (call after sensitive operations)
 */
function regenerate_csrf_token() {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}

// ==================== AUTHENTICATION FUNCTIONS ====================

/**
 * Check if user is logged in
 */
function is_logged_in() {
    return isset($_SESSION['user_id']) && 
           isset($_SESSION['user_type']) && 
           isset($_SESSION['username']);
}

/**
 * Check if user is faculty
 */
function is_faculty() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'faculty';
}

/**
 * Check if user is student
 */
function is_student() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'student';
}

/**
 * Require user to be logged in (redirect if not)
 */
function require_login() {
    if (!is_logged_in()) {
        header("Location: login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
        exit();
    }
}

/**
 * Require user to be faculty (redirect if not)
 */
function require_faculty() {
    require_login();
    if (!is_faculty()) {
        header("Location: student_dashboard.php");
        exit();
    }
}

/**
 * Require user to be student (redirect if not)
 */
function require_student() {
    require_login();
    if (!is_student()) {
        header("Location: faculty_dashboard.php");
        exit();
    }
}

/**
 * Get current user ID
 */
function get_current_user_id() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current user type
 */
function get_current_user_type() {
    return $_SESSION['user_type'] ?? null;
}

/**
 * Get current username
 */
function get_current_username() {
    return $_SESSION['username'] ?? null;
}

/**
 * Get current user's full name
 */
function get_current_user_fullname() {
    return $_SESSION['full_name'] ?? null;
}

// ==================== MESSAGE DISPLAY FUNCTIONS ====================

/**
 * Display error message
 */
function display_error($message) {
    return '<div class="alert alert-error">' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</div>';
}

/**
 * Display success message
 */
function display_success($message) {
    return '<div class="alert alert-success">' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</div>';
}

/**
 * Display info message
 */
function display_info($message) {
    return '<div class="alert alert-info">' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</div>';
}

/**
 * Display warning message
 */
function display_warning($message) {
    return '<div class="alert alert-warning">' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</div>';
}

// ==================== OUTPUT ESCAPING ====================

/**
 * Escape output for HTML
 */
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Escape output for HTML attributes
 */
function escape_attr($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Escape output for JavaScript
 */
function escape_js($string) {
    return json_encode($string, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
}

// ==================== VALIDATION FUNCTIONS ====================

/**
 * Validate username format
 */
function validate_username($username) {
    // 3-50 characters, letters, numbers, and underscores only
    return preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username);
}

/**
 * Validate course code format
 */
function validate_course_code($code) {
    // 2-20 characters, letters and numbers
    return preg_match('/^[a-zA-Z0-9]{2,20}$/', $code);
}

/**
 * Validate integer within range
 */
function validate_int_range($value, $min, $max) {
    $value = filter_var($value, FILTER_VALIDATE_INT);
    if ($value === false) {
        return false;
    }
    return ($value >= $min && $value <= $max);
}

// ==================== UTILITY FUNCTIONS ====================

/**
 * Redirect to a specific page
 */
function redirect($url) {
    header("Location: " . $url);
    exit();
}

/**
 * Get current page URL
 */
function current_url() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    return $protocol . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

/**
 * Check if request is POST
 */
function is_post_request() {
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

/**
 * Check if request is GET
 */
function is_get_request() {
    return $_SERVER['REQUEST_METHOD'] === 'GET';
}

/**
 * Format date for display
 */
function format_date($date, $format = 'M d, Y') {
    return date($format, strtotime($date));
}

/**
 * Format datetime for display
 */
function format_datetime($datetime, $format = 'M d, Y h:i A') {
    return date($format, strtotime($datetime));
}

/**
 * Calculate time ago
 */
function time_ago($datetime) {
    $time = strtotime($datetime);
    $diff = time() - $time;
    
    if ($diff < 60) {
        return 'just now';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . ' minute' . ($mins > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 2592000) {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return date('M d, Y', $time);
    }
}

/**
 * Truncate string to specified length
 */
function truncate_string($string, $length = 100, $append = '...') {
    if (strlen($string) <= $length) {
        return $string;
    }
    return substr($string, 0, $length) . $append;
}

/**
 * Generate random string
 */
function generate_random_string($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Check if string starts with
 */
function starts_with($haystack, $needle) {
    return substr($haystack, 0, strlen($needle)) === $needle;
}

/**
 * Check if string ends with
 */
function ends_with($haystack, $needle) {
    return substr($haystack, -strlen($needle)) === $needle;
}

// ==================== DATABASE HELPER FUNCTIONS ====================

/**
 * Execute a prepared statement and return result
 */
function db_query($conn, $sql, $params = [], $types = '') {
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        error_log("Database prepare error: " . $conn->error);
        return false;
    }
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    if (!$stmt->execute()) {
        error_log("Database execute error: " . $stmt->error);
        return false;
    }
    
    $result = $stmt->get_result();
    $stmt->close();
    
    return $result;
}

/**
 * Get single row from database
 */
function db_fetch_one($conn, $sql, $params = [], $types = '') {
    $result = db_query($conn, $sql, $params, $types);
    
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return null;
}

/**
 * Get all rows from database
 */
function db_fetch_all($conn, $sql, $params = [], $types = '') {
    $result = db_query($conn, $sql, $params, $types);
    $rows = [];
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
    }
    
    return $rows;
}

/**
 * Insert record and return insert ID
 */
function db_insert($conn, $table, $data) {
    $fields = array_keys($data);
    $values = array_values($data);
    $placeholders = array_fill(0, count($fields), '?');
    
    $sql = "INSERT INTO $table (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Database prepare error: " . $conn->error);
        return false;
    }
    
    // Create types string based on value types
    $types = '';
    foreach ($values as $value) {
        if (is_int($value)) {
            $types .= 'i';
        } elseif (is_double($value)) {
            $types .= 'd';
        } else {
            $types .= 's';
        }
    }
    
    $stmt->bind_param($types, ...$values);
    
    if ($stmt->execute()) {
        $insert_id = $conn->insert_id;
        $stmt->close();
        return $insert_id;
    }
    
    error_log("Database execute error: " . $stmt->error);
    $stmt->close();
    return false;
}

// ==================== SESSION FLASH MESSAGES ====================

/**
 * Set flash message
 */
function set_flash($key, $message) {
    $_SESSION['flash'][$key] = $message;
}

/**
 * Get and clear flash message
 */
function get_flash($key) {
    if (isset($_SESSION['flash'][$key])) {
        $message = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $message;
    }
    return null;
}

/**
 * Check if flash message exists
 */
function has_flash($key) {
    return isset($_SESSION['flash'][$key]);
}

// ==================== ERROR LOGGING ====================

/**
 * Log error to file
 */
function log_error($message, $context = []) {
    $log_message = date('Y-m-d H:i:s') . ' - ' . $message;
    if (!empty($context)) {
        $log_message .= ' - Context: ' . json_encode($context);
    }
    error_log($log_message . PHP_EOL, 3, __DIR__ . '/../logs/error.log');
}

/**
 * Log activity
 */
function log_activity($user_id, $action, $details = '') {
    global $conn;
    
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    
    $sql = "INSERT INTO activity_logs (user_id, action, details, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("issss", $user_id, $action, $details, $ip_address, $user_agent);
        $stmt->execute();
        $stmt->close();
    }
}

?>