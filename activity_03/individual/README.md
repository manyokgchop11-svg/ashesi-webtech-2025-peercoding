
# School Attendance Management System (Individual)

This simple project uses PHP + MySQL to manage users, courses, enrollments, sessions and attendance.

- `api/get_courses.php` this returns JSON list of courses.
- `api/create_course.php`  POST: `title` (lecturer only).
- `api/create_session.php` POST: `course_id` (lecturer only).
- `api/enroll_course.php` POST: `course_id` (logged in users).
- `api/mark_attendance.php` POST: `session_id`, optional `user_id`.

Other files
- `auth/register.php` and `auth/login.php`, these ones here handle user registration and login.
- `auth.php` :  include at top of pages that require login.


