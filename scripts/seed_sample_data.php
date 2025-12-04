<?php
// Seed script to create sample users and a course for testing (CLI only)
// Usage: php scripts/seed_sample_data.php

if (php_sapi_name() !== 'cli') {
    echo "This script must be run from the command line.\n";
    exit(1);
}

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

$conn = getDBConnection();

// Helper to insert user if not exists
function insert_user($conn, $username, $email, $password, $full_name, $user_type) {
    // check exists
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows > 0) {
        $row = $res->fetch_assoc();
        echo "User '{$username}' already exists (id: {$row['user_id']}).\n";
        $stmt->close();
        return $row['user_id'];
    }
    $stmt->close();

    $hash = hash_password($password);
    $stmt = $conn->prepare("INSERT INTO users (username, email, password_hash, full_name, user_type, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("sssss", $username, $email, $hash, $full_name, $user_type);
    if ($stmt->execute()) {
        $id = $stmt->insert_id;
        echo "Inserted user '{$username}' (id: {$id}).\n";
        $stmt->close();
        return $id;
    } else {
        echo "Failed to insert user '{$username}': " . $conn->error . "\n";
        $stmt->close();
        return null;
    }
}

// Create sample faculty and student
$faculty_password = 'Prof$12345';
$student_password = 'Stud!2345';

$faculty_id = insert_user($conn, 'prof_smith', 'smith@university.edu', $faculty_password, 'Dr. John Smith', 'faculty');
$student_id = insert_user($conn, 'student_jane', 'jane@university.edu', $student_password, 'Jane Doe', 'student');

// Insert a sample course for the faculty (if not exists)
if ($faculty_id) {
    $course_code = 'CS341';
    $course_name = 'Web Technologies';
    // check exists
    $stmt = $conn->prepare("SELECT course_id FROM courses WHERE course_code = ? AND faculty_id = ?");
    $stmt->bind_param("si", $course_code, $faculty_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows > 0) {
        $row = $res->fetch_assoc();
        echo "Course '{$course_code}' already exists (id: {$row['course_id']}).\n";
        $stmt->close();
    } else {
        $stmt->close();
        $stmt = $conn->prepare("INSERT INTO courses (course_code, course_name, description, faculty_id, max_students, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $desc = 'Sample course for CS341 assignment.';
        $max = 50;
        $stmt->bind_param("sssii", $course_code, $course_name, $desc, $faculty_id, $max);
        if ($stmt->execute()) {
            echo "Inserted course '{$course_code}' for faculty id {$faculty_id}.\n";
        } else {
            echo "Failed to insert course: " . $conn->error . "\n";
        }
        $stmt->close();
    }
}

echo "\nSeeding completed. Sample credentials:\n";
echo " Faculty: username='prof_smith' password='{$faculty_password}'\n";
echo " Student: username='student_jane' password='{$student_password}'\n";

$conn->close();

exit(0);
