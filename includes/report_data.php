<?php
// Shared helper: open the page controllers, not this file directly.
if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    http_response_code(404);
    exit('Page not found.');
}
// All official report outputs call the same academic functions.
function loadCourseReport($conn, $course) {
    $stmt = $conn->prepare(
        'SELECT m.*, s.student_name FROM marks m JOIN students s ON s.student_id=m.student_id WHERE m.course_id=? ORDER BY m.student_id',
    );
    $stmt->bind_param('i', $course['course_id']);
    $stmt->execute();
    $rows = [];
    $sum = 0;
    $raw = [1 => 0, 2 => 0, 3 => 0];
    $counts = ['Pass' => 0, 'Resit' => 0, 'Retake' => 0];
    $grades = array_fill_keys(['A', 'A-', 'B+', 'B', 'B-', 'C+', 'C', 'C-', 'D+', 'D', 'F'], 0);
    foreach ($stmt->get_result() as $mark) {
        if ($mark['remarks'] === null) {
            $mark['remarks'] = '';
        }
        $row = getStudentPerformance($mark, $course);
        $row['mark'] = $mark;
        $rows[] = $row;
        $sum += $row['total'];
        $counts[$row['status']]++;
        $grades[$row['grade']]++;
        for ($i = 1; $i <= 3; $i++) {
            $raw[$i] += $mark['clo' . $i . '_raw'];
        }
    }
    $total = count($rows);
    $attainment = [];
    for ($i = 1; $i <= 3; $i++) {
        $attainment[$i] = getAttainment($raw[$i], $total);
    }
    $average = 0;
    if ($total > 0) {
        $average = $sum / $total;
    }
    return [
        'student_rows' => $rows,
        'total_students' => $total,
        'pass_count' => $counts['Pass'],
        'resit_count' => $counts['Resit'],
        'retake_count' => $counts['Retake'],
        'average_mark' => number_format($average, 2),
        'clo_attainment' => $attainment,
        'grade_counts' => $grades,
    ];
}
