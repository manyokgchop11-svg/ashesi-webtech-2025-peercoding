
<?php
// Simple login endpoint
session_start();
require_once __DIR__ . '/../config/db_config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        echo "Please fill all fields.";
        exit;
    }

    $stmt = $conn->prepare("SELECT id, password, role FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->bind_result($id, $hashed, $role);
    if ($stmt->fetch()) {
        if (password_verify($password, $hashed)) {
            // login ok
            $_SESSION['user_id'] = $id;
            $_SESSION['username'] = $username;
            $_SESSION['role'] = $role;
            // redirect to a dashboard (you can create dashboard later)
            echo "success"; // frontend can handle or you can redirect
        } else {
            echo "Wrong username or password.";
        }
    } else {
        echo "Wrong username or password.";
    }
    $stmt->close();
} else {
    echo "Invalid request method.";
}
?>
