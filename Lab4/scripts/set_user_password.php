<?php
// CLI utility to set a user's password (hashes using bcrypt)
// Usage: php scripts/set_user_password.php <username_or_userid_or_email> <new_password>

if (php_sapi_name() !== 'cli') {
    echo "This script must be run from the command line.\n";
    exit(1);
}

if ($argc < 3) {
    echo "Usage: php scripts/set_user_password.php <username_or_userid_or_email> <new_password>\n";
    exit(1);
}

$identifier = $argv[1];
$newPassword = $argv[2];

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

$conn = getDBConnection();

// Find user by id, username or email
if (is_numeric($identifier)) {
    $stmt = $conn->prepare("SELECT user_id, username FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $identifier);
} else {
    $stmt = $conn->prepare("SELECT user_id, username FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $identifier, $identifier);
}

if (!$stmt->execute()) {
    echo "Error querying user: " . $conn->error . "\n";
    exit(1);
}

$result = $stmt->get_result();
if ($result->num_rows === 0) {
    echo "No user found matching '{$identifier}'.\n";
    exit(1);
}

$user = $result->fetch_assoc();
$stmt->close();

// Basic password strength check (same as site policy)
if (!validate_password($newPassword)) {
    echo "Password does not meet strength requirements:\n";
    echo " - Minimum 8 characters\n";
    echo " - At least one uppercase letter, one lowercase letter, one digit and one special character\n";
    exit(1);
}

$hashed = hash_password($newPassword);

$stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
$stmt->bind_param("si", $hashed, $user['user_id']);

if ($stmt->execute()) {
    echo "Password updated for user '{$user['username']}' (id: {$user['user_id']}).\n";
} else {
    echo "Failed to update password: " . $conn->error . "\n";
}

$stmt->close();
$conn->close();

exit(0);
