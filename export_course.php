<?php
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/functions.php';
require __DIR__ . '/includes/owned_course.php';
require __DIR__ . '/includes/report_data.php';

if (!$clos_set) {
    http_response_code(409);
    exit('Configure valid CLO weights before exporting results.');
}
$report = loadCourseReport($conn, $course);
$rows = $report['student_rows'];
$filename = preg_replace('/[^A-Za-z0-9_-]/', '_', $course['course_code']) . '-results.csv';
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
$output = fopen('php://output', 'w');
fwrite($output, "\xEF\xBB\xBF");
// Quote fields with fputcsv and neutralise text that spreadsheets may execute.
function csvSafeText($value) {
    $value = (string) $value;
    if (preg_match('/^[\\s\\x00-\\x20\\x{FEFF}]*[=+@-]/u', $value) || preg_match('/^[\\t\\r\\n]/', $value)) {
        return "'" . $value;
    }
    return $value;
}
// Course details appear once, above the student-results table.
fputcsv($output, ['Course Code:', csvSafeText($course['course_code'])], ',', '"', '');
fputcsv($output, ['Course Name:', csvSafeText($course['course_name'])], ',', '"', '');
fputcsv($output, ['Semester:', csvSafeText($course['semester'])], ',', '"', '');
fputcsv($output, [], ',', '"', '');
$headings = ['Student ID', 'Student Name', 'Attendance (%)'];
for ($i = 1; $i <= 3; $i++) {
    $headings[] = 'CLO ' . $i . ' Raw (/100)';
    $headings[] = 'CLO ' . $i . ' Weighted (' . $course['clo' . $i . '_weight'] . '%)';
}
$headings = array_merge($headings, ['Final Mark', 'Grade', 'CW', 'EX', 'Status', 'Remarks']);
fputcsv($output, $headings, ',', '"', '');
foreach ($rows as $row) {
    $m = $row['mark'];
    $values = [
        csvSafeText($m['student_id']),
        csvSafeText($m['student_name']),
        number_format($m['attendance_percentage'], 2, '.', ''),
    ];
    for ($i = 1; $i <= 3; $i++) {
        $values[] = number_format($m['clo' . $i . '_raw'], 2, '.', '');
        $values[] = number_format($row['weighted' . $i], 2, '.', '');
    }
    $values = array_merge($values, [
        number_format($row['total'], 2, '.', ''),
        $row['grade'],
        $row['cw_result'],
        $row['ex_result'],
        $row['status'],
        csvSafeText($m['remarks']),
    ]);
    fputcsv($output, $values, ',', '"', '');
}
fclose($output);
