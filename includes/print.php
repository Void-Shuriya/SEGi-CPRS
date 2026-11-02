<?php
// Shared helper: open the page controllers, not this file directly.
if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    http_response_code(404);
    exit('Page not found.');
}
// This file receives prepared report data from course_dashboard.php.
if (!isset($course, $clo_attainment, $student_rows)) {
    header('Location: index.php');
    exit();
}
?>

<!-- This section is hidden on screen and shown only when the lecturer prints. -->
<main class="print-report print-only">

    <header class="report-header">
        <h2>
            SEGi Course Performance Report (CPR)
        </h2>

        <p>
            <b>Department of Information Technology</b>
        </p>
    </header>

    <!-- Basic course information for the printed report. -->
    <section class="report-meta">
        <div>
            <b>Course Code:</b>
            <?php echo htmlspecialchars($course['course_code']); ?>
        </div>

        <div>
            <b>Course Name:</b>
            <?php echo htmlspecialchars($course['course_name']); ?>
        </div>

        <div>
            <b>Programme:</b>
            <?php echo htmlspecialchars($course['programme_code'] . ' - ' . $course['programme_name']); ?>
        </div>

        <div>
            <b>Study Mode:</b>
            <?php echo htmlspecialchars($course['study_mode_name']); ?>
        </div>

        <div>
            <b>Semester:</b>
            <?php echo htmlspecialchars($course['semester']); ?>
        </div>

        <div>
            <b>Lecturer:</b>
            <?php echo htmlspecialchars($course['lecturer_name']); ?>
        </div>
    </section>

    <!-- Print the three CLO attainment values and lecturer comments. -->
    <section class="print-section">

        <h3>
            CLO Attainment
        </h3>

        <div class="print-attainment-grid">

            <?php for ($i = 1; $i <= 3; $i++): ?>

            <div class="print-attainment-card">

                <h4>
                    CLO <?php echo $i; ?>: <?php echo htmlspecialchars($course['clo' . $i . '_name']); ?>
                </h4>

                <?php if (trim($course['clo' . $i . '_description']) !== ''): ?>
                <p>
                    <?php echo nl2br(htmlspecialchars($course['clo' . $i . '_description'])); ?>
                </p>
                <?php endif; ?>

                <p>
                    <b>Attainment:</b>
                    <?php echo number_format($clo_attainment[$i], 2); ?>%
                </p>

                <p>
                    <b>CLO <?php echo $i; ?> Comment:</b>
                </p>

                <div class="print-comment"><?php echo htmlspecialchars($course['clo' . $i . '_comment']); ?></div>
            </div>
            <?php endfor; ?>
        </div>
    </section>

    <!-- Reuse the calculated student rows from the main performance page. -->
    <section class="print-section">

        <h3>
            Student Results
        </h3>

        <table class="report-results">
            <thead>
                <tr>
                    <th scope="col">Student</th>
                    <th>Attendance (%)</th>

                    <?php for ($i = 1; $i <= 3; $i++): ?>
                    <th>
                        CLO <?php echo $i; ?> Wtd (<?php echo $course['clo' . $i . '_weight']; ?>%)</th>
                    <?php endfor; ?>

                    <th>Total</th>
                    <th>Grade</th>
                    <th>CW</th>
                    <th>EX</th>
                    <th>Status</th>
                    <th class="remarks-column">Remarks</th>
                </tr>
            </thead>
            <tbody>

                <?php if (count($student_rows) === 0): ?>
                <tr>
                    <td colspan="11">No enrolled students.</td>
                </tr>
                <?php else: ?>

                <?php foreach ($student_rows as $row_index => $row):
                    $mark = $row['mark']; ?>
                <?php if ($row_index === count($student_rows) - 1): ?>
            </tbody>
            <tbody class="report-tail"><?php endif; ?>

                <tr>
                    <td>
                        <?php echo htmlspecialchars($mark['student_id']); ?><br><?php echo htmlspecialchars(
    $mark['student_name'],
); ?>
                    </td>

                    <td>
                        <?php echo number_format($mark['attendance_percentage'], 2); ?>
                    </td>

                    <?php for ($i = 1; $i <= 3; $i++): ?>
                    <td>
                        <?php echo number_format($mark['clo' . $i . '_raw'], 2); ?> / <?php echo number_format(
     $row['weighted' . $i],
     2,
 ); ?>
                    </td>
                    <?php endfor; ?>

                    <td>
                        <?php echo number_format($row['total'], 2); ?>
                    </td>

                    <td>
                        <?php echo $row['grade']; ?>
                    </td>

                    <td class="component-result component-<?php echo strtolower($row['cw_result']); ?>"><?php echo $row[
    'cw_result'
]; ?>
                    </td>

                    <td class="component-result component-<?php echo strtolower($row['ex_result']); ?>"><?php echo $row[
    'ex_result'
]; ?>
                    </td>

                    <td>
                        <span class="badge badge-<?php echo strtolower($row['status']); ?>"><?php echo $row[
    'status'
]; ?></span>
                    </td>

                    <td class="remarks-column"><?php echo nl2br(htmlspecialchars($mark['remarks'])); ?>
                    </td>
                </tr>
                <?php
                endforeach; ?>
                <?php endif; ?>

                <tr class="report-summary-row">
                    <td colspan="11" class="report-summary-cell">
                        <!-- A short class summary appears below the printed results. -->
                        <div class="report-summary">
                            <span>
                                <b>Total Students:</b>
                                <?php echo $total_students; ?>
                            </span>

                            <span>
                                <b>Pass:</b>
                                <?php echo $pass_count; ?>
                            </span>

                            <span>
                                <b>Resit:</b>
                                <?php echo $resit_count; ?>
                            </span>

                            <span>
                                <b>Retake:</b>
                                <?php echo $retake_count; ?>
                            </span>

                            <span>
                                <b>Class Average:</b>
                                <?php echo $average_mark; ?>%
                            </span>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </section>
</main>
