
<?php
// Simple register endpoint
require_once __DIR__ . '/../config/db_config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = trim($_POST['role'] ?? '');

    if ($username === '' || $email === '' || $password === '' || $role === '') {
        echo "Please fill all fields.";
        exit;
    }

    // check duplicate username
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        echo "Username already taken.";
        exit;
    }
    $stmt->close();

    // hash the password (simple and safe)
    $hashed = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $username, $email, $hashed, $role);
    if ($stmt->execute()) {
        // redirect to login page (frontend file)
        header("Location: /individual/index.html");
        exit;
    } else {
        echo "Error during registration.";
    }
    $stmt->close();
} else {
    echo "Invalid request method.";
}
?>
