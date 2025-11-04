
<?php
// Run this once (or anytime) to create the required tables.
// Put this file in config/ and open it in browser or run via php CLI.

require_once __DIR__ . '/db_config.php';

// USERS table
$users_sql = "CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) NOT NULL UNIQUE,
  email VARCHAR(200) NOT NULL,
  password VARCHAR(255) NOT NULL,
  role VARCHAR(50) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB";

$conn->query($users_sql);

// COURSES table
$courses_sql = "CREATE TABLE IF NOT EXISTS courses (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  lecturer_id INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (lecturer_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB";

$conn->query($courses_sql);

// ENROLLMENTS table
$enroll_sql = "CREATE TABLE IF NOT EXISTS enrollments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  course_id INT NOT NULL,
  enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
  UNIQUE (user_id, course_id)
) ENGINE=InnoDB";

$conn->query($enroll_sql);

// SESSIONS table (attendance sessions)
$sessions_sql = "CREATE TABLE IF NOT EXISTS sessions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  course_id INT NOT NULL,
  session_date DATETIME DEFAULT CURRENT_TIMESTAMP,
  is_open TINYINT(1) DEFAULT 1,
  FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
) ENGINE=InnoDB";

$conn->query($sessions_sql);

// ATTENDANCE table
$attendance_sql = "CREATE TABLE IF NOT EXISTS attendance (
  id INT AUTO_INCREMENT PRIMARY KEY,
  session_id INT NOT NULL,
  user_id INT NOT NULL,
  status VARCHAR(20) DEFAULT 'present',
  marked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (session_id) REFERENCES sessions(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  UNIQUE (session_id, user_id)
) ENGINE=InnoDB";

$conn->query($attendance_sql);

echo "Database and tables are set up. You can delete or keep this file.";
?>
