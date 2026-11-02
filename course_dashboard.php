<?php
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/functions.php';
require __DIR__ . '/includes/owned_course.php';
require __DIR__ . '/includes/report_data.php';
extract(loadCourseReport($conn, $course));
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SEGi CPRS - Course Dashboard</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body class="workflow-page">
    <div class="screen-only">
        <?php include __DIR__ . '/includes/header.php'; ?>
        <main id="main-content" tabindex="-1" class="content content-wide">
            <div class="page-title-row dashboard-heading">
                <h1><?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']); ?></h1>
                <div class="title-actions">
                    <a href="manage_course_performance.php?course_id=<?php echo $course_id; ?>"
                        class="btn-secondary">Back to Course Performance</a>
                    <?php if ($clos_set): ?>
                    <button type="button" class="btn-primary" id="print-report">Print</button>
                    <a id="export-all" class="btn-primary"
                        href="export_course.php?course_id=<?php echo $course_id; ?>">Export CSV</a>
                    <?php endif; ?>
                </div>
                <p class="course-meta"><span>Programme: <?php echo htmlspecialchars(
                    $course['programme_code'],
                ); ?></span><span>Study Mode: <?php echo htmlspecialchars(
    $course['study_mode_name'],
); ?></span><span>Semester: <?php echo htmlspecialchars(
    preg_replace('/^Semester\s+/i', '', $course['semester']),
); ?></span><span>Lecturer: <?php echo htmlspecialchars($course['lecturer_name']); ?></span></p>
            </div>
            <?php if (!$clos_set): ?>
            <div class="notice" role="status">Configure and save CLO weights totaling 100% on the Course Performance
                page to view results.</div>
            <?php else: ?>
            <section class="summary-grid" aria-label="Whole-class summary">
                <div class="metric"><span>Total students</span><strong><?php echo $total_students; ?></strong></div>
                <div class="metric"><span>Class average</span><strong><?php echo $average_mark; ?>%</strong></div>
                <?php foreach (
                    ['Pass' => $pass_count, 'Resit' => $resit_count, 'Retake' => $retake_count]
                    as $status => $count
                ): ?>
                <a class="metric status-metric <?php echo strtolower(
                    $status,
                ); ?>" data-status="<?php echo $status; ?>" href="#student-results"
                    aria-label="Filter students with <?php echo $status; ?> status">
                    <span><?php echo $status; ?></span><strong><?php echo $count; ?></strong><small>View students
                        →</small>
                </a>
                <?php endforeach; ?>
            </section>
            <section class="chart-grid" aria-label="Whole-class charts">
                <div class="card">
                    <h2>Grade distribution</h2>
                    <p class="chart-description">Number of students in each grade.</p>
                    <div class="grade-chart">
                        <?php foreach ($grade_counts as $grade => $count):

                            $width = 0;
                            if ($total_students > 0) {
                                $width = ($count / $total_students) * 100;
                            }
                            ?>
                        <div class="chart-row"><span class="chart-label"><?php echo $grade; ?></span>
                            <div class="chart-track" aria-hidden="true"><span style="width:<?php echo number_format(
                                $width,
                                2,
                                '.',
                                '',
                            ); ?>%"></span></div><span class="chart-value"><?php echo $count; ?> <span class="sr-only">students</span></span>
                        </div>
                        <?php
                        endforeach; ?>
                    </div>
                </div>
                <div class="card clo-chart-card">
                    <h2>CLO attainment</h2>
                    <p class="chart-description">Average raw mark for each CLO.</p>
                    <div class="clo-chart-bars">
                        <?php for ($i = 1; $i <= 3; $i++): ?>
                        <div class="clo-chart-item">
                            <div class="chart-title"><strong><?php echo htmlspecialchars(
                                'CLO ' . $i . ': ' . $course['clo' . $i . '_name'],
                            ); ?></strong><span><?php echo number_format($clo_attainment[$i], 2); ?>%</span></div>
                            <div class="chart-track" aria-hidden="true"><span class="<?php echo getAttainmentClass(
                                $clo_attainment[$i],
                            ); ?>" style="width:<?php echo number_format(
    $clo_attainment[$i],
    2,
    '.',
    '',
); ?>%"></span></div>
                            <p class="clo-chart-comment"><strong>Comment:</strong> <span><?php echo trim(
                                $course['clo' . $i . '_comment'],
                            ) !== ''
                                ? htmlspecialchars($course['clo' . $i . '_comment'])
                                : 'No comment added.'; ?></span></p>
                        </div>
                        <?php endfor; ?>
                    </div>
                </div>
            </section>
            <section class="card" id="student-results" aria-labelledby="results-title">
                <h2 id="results-title" tabindex="-1">Student results</h2>
                <form method="GET" id="dashboard-filters" class="filter-bar">
                    <input type="hidden" name="course_id" value="<?php echo $course_id; ?>">
                    <div class="filter-search"><label for="result-search">Search name or ID</label><input
                            id="result-search" type="search" name="search" maxlength="100"></div>
                    <div><label for="status-filter">Status</label><select id="status-filter" name="status">
                            <?php foreach (
                                ['' => 'All statuses', 'Pass' => 'Pass', 'Resit' => 'Resit', 'Retake' => 'Retake']
                                as $value => $label
                            ): ?><option value="<?php echo $value; ?>"><?php echo $label; ?></option><?php endforeach; ?>
                        </select></div>

                    <div><label for="result-sort">Sort by</label><select id="result-sort" name="sort">
                            <?php foreach (
                                [
                                    'name' => 'Name A–Z',
                                    'id' => 'Student ID',
                                    'mark_desc' => 'Final mark: highest first',
                                    'mark_asc' => 'Final mark: lowest first',
                                    'attendance' => 'Attendance: lowest first',
                                ]
                                as $value => $label
                            ): ?><option value="<?php echo $value; ?>"><?php echo $label; ?></option><?php endforeach; ?>
                        </select></div>
                    <button type="button" id="reset-results" class="btn-secondary">Reset Filters</button>
                </form>
                <p id="result-count" role="status">Showing <?php echo $total_students; ?> of
                    <?php echo $total_students; ?> students</p>
                <div class="table-wrapper results-scroll" tabindex="0" role="region"
                    aria-label="Student results, scroll horizontally for all columns">
                    <table id="results-table">
                        <caption class="sr-only">Saved student performance results. CW means coursework; EX means
                            examination.</caption>
                        <thead>
                            <tr>
                                <th scope="col">Student</th>
                                <th scope="col">Attendance</th>
                                <?php for (
                                    $i = 1;
                                    $i <= 3;
                                    $i++
                                ): ?><th scope="col">CLO <?php echo $i; ?><br><small>Raw / Weighted</small></th><?php endfor; ?>
                                <th scope="col">Final mark</th>
                                <th scope="col">Grade</th>
                                <th scope="col"><span title="Coursework">CW</span></th>
                                <th scope="col"><span title="Examination">EX</span></th>
                                <th scope="col">Status</th>
                                <th scope="col">Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($student_rows as $row):
                                $mark = $row['mark']; ?>
                            <tr data-result-row data-name="<?php echo htmlspecialchars(
                                $mark['student_name'],
                                ENT_QUOTES,
                            ); ?>" data-id="<?php echo htmlspecialchars(
    $mark['student_id'],
    ENT_QUOTES,
); ?>" data-status="<?php echo $row['status']; ?>" data-mark="<?php echo $row[
    'total'
]; ?>" data-attendance="<?php echo $mark['attendance_percentage']; ?>">
                                <th scope="row" class="student-identity"><span class="student-name"><?php echo htmlspecialchars(
                                    $mark['student_name'],
                                ); ?></span><small><?php echo htmlspecialchars($mark['student_id']); ?></small></th>
                                <td><?php echo number_format($mark['attendance_percentage'], 2); ?>%</td>
                                <?php for ($i = 1; $i <= 3; $i++): ?><td><?php echo number_format(
    $mark['clo' . $i . '_raw'],
    2,
) .
    ' / ' .
    number_format($row['weighted' . $i], 2); ?></td><?php endfor; ?>
                                <td><strong><?php echo number_format($row['total'], 2); ?></strong></td>
                                <td><?php echo $row['grade']; ?></td>
                                <td><?php echo $row['cw_result']; ?></td>
                                <td><?php echo $row['ex_result']; ?></td>
                                <td><span class="badge badge-<?php echo strtolower($row['status']); ?>"><?php echo $row[
    'status'
]; ?></span></td>
                                <td class="remarks-cell"><?php echo htmlspecialchars($mark['remarks']); ?></td>
                            </tr>
                            <?php
                            endforeach; ?>
                            <tr id="results-empty" <?php if ($total_students > 0) {
                                echo 'hidden';
                            } ?>>
                                <td colspan="11">No students match these filters.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
            <?php endif; ?>
        </main>
    </div>
    <?php if ($clos_set) {
        include __DIR__ . '/includes/print.php';
    } ?>
    <script src="js/dashboard.js" defer></script>
</body>

</html>
