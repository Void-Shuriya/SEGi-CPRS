<?php
// Shared helper: open the page controllers, not this file directly.
if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    http_response_code(404);
    exit('Page not found.');
}
if ($_SESSION['role'] !== 'lecturer') {
    http_response_code(403);
    exit('Only assigned lecturers can access course reports.');
}
if (!isset($_GET['course_id']) || !is_string($_GET['course_id']) || !ctype_digit($_GET['course_id'])) {
    http_response_code(400);
    exit('A valid course is required.');
}
$course_id = (int) $_GET['course_id'];
$stmt = $conn->prepare('SELECT c.*, u.fullname AS lecturer_name, p.programme_code, p.programme_name, sm.study_mode_name
    FROM courses c JOIN users u ON u.user_id=c.lecturer_id JOIN study_modes sm ON sm.study_mode_id=c.study_mode_id
    JOIN programmes p ON p.programme_id=sm.programme_id WHERE c.course_id=? AND c.lecturer_id=?');
$stmt->bind_param('ii', $course_id, $_SESSION['user_id']);
$stmt->execute();
$course = $stmt->get_result()->fetch_assoc();
if (!$course) {
    http_response_code(403);
    exit('You do not have permission to view this course.');
}
for ($i = 1; $i <= 3; $i++) {
    if ($course['clo' . $i . '_description'] === null) {
        $course['clo' . $i . '_description'] = '';
    }
    if ($course['clo' . $i . '_comment'] === null) {
        $course['clo' . $i . '_comment'] = '';
    }
}
$clos_set = (int) $course['clo1_weight'] + (int) $course['clo2_weight'] + (int) $course['clo3_weight'] === 100;
