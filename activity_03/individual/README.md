
# School Attendance Management System (Individual)

This simple project uses PHP + MySQL to manage users, courses, enrollments, sessions and attendance.

## Setup (local)
1. Install XAMPP / WAMP / MAMP.
2. Put this project folder under your web server folder (e.g., `htdocs/activity_03/individual`).
3. Edit `config/db_config.php` database credentials if needed.
4. Open `config/setup_database.php` in the browser (e.g. `http://localhost/activity_03/individual/config/setup_database.php`) to create the database and tables.
5. Register a lecturer and student using `register.html`.
6. Use the API endpoints in `api/` to create courses, sessions, enroll students and mark attendance.

## API notes
- `api/get_courses.php` — returns JSON list of courses.
- `api/create_course.php` — POST: `title` (lecturer only).
- `api/create_session.php` — POST: `course_id` (lecturer only).
- `api/enroll_course.php` — POST: `course_id` (logged in users).
- `api/mark_attendance.php` — POST: `session_id`, optional `user_id` (if not provided, uses logged in user).

## Other files
- `auth/register.php` and `auth/login.php` — handle user registration and login.
- `auth.php` — include at top of pages that require login.

This project is intentionally simple and is made for learning and testing.
