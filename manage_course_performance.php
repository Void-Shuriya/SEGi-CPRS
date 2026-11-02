<?php
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/functions.php';
require __DIR__ . '/includes/owned_course.php';
require __DIR__ . '/includes/report_data.php';
$error_msg = '';
$success_msg = '';
if (isset($_SESSION['success_msg'])) {
    $success_msg = $_SESSION['success_msg'];
    unset($_SESSION['success_msg']);
}
$setup_course = $course;
$submitted_marks = [];
$entry_errors = [];
extract(loadCourseReport($conn, $course));

if (isset($_POST['save_clos'])) {
    $weights = [];
    for ($i = 1; $i <= 3; $i++) {
        $setup_course['clo' . $i . '_name'] = normalizeSingleLine(getPostText('clo' . $i . '_name'));
        $setup_course['clo' . $i . '_description'] = getPostText('clo' . $i . '_description');
        $setup_course['clo' . $i . '_weight'] = getPostText('clo' . $i . '_weight');
        $value = $setup_course['clo' . $i . '_weight'];
        if (
            $setup_course['clo' . $i . '_name'] === '' ||
            strlen($setup_course['clo' . $i . '_name']) > 50 ||
            !ctype_digit($value) ||
            !isValidPercentage($value) ||
            strlen($setup_course['clo' . $i . '_description']) > 10000
        ) {
            $error_msg =
                'Enter a name of up to 50 characters and a whole-number weight from 0 to 100 for each CLO. Descriptions may contain up to 10,000 bytes.';
        }
        $weights[] = intval($value);
    }
    if ($error_msg === '' && array_sum($weights) !== 100) {
        $error_msg = 'Unable to save CLOs. Current total: ' . array_sum($weights) . '%. Required total: 100%.';
    }
    if ($error_msg === '') {
        try {
            $stmt = $conn->prepare(
                'UPDATE courses SET clo1_name=?,clo1_description=?,clo1_weight=?,clo2_name=?,clo2_description=?,clo2_weight=?,clo3_name=?,clo3_description=?,clo3_weight=? WHERE course_id=? AND lecturer_id=?',
            );
            $stmt->bind_param(
                'ssississiii',
                $setup_course['clo1_name'],
                $setup_course['clo1_description'],
                $weights[0],
                $setup_course['clo2_name'],
                $setup_course['clo2_description'],
                $weights[1],
                $setup_course['clo3_name'],
                $setup_course['clo3_description'],
                $weights[2],
                $course_id,
                $_SESSION['user_id'],
            );
            $stmt->execute();
            $_SESSION['success_msg'] = 'CLO Weights saved.';
            header('Location: manage_course_performance.php?course_id=' . $course_id);
            exit();
        } catch (mysqli_sql_exception $exception) {
            error_log($exception->getMessage());
            $error_msg = 'CLO Weights could not be saved. Please try again.';
        }
    }
} elseif (isset($_POST['save_clo_comments'])) {
    $comments = [];
    for ($i = 1; $i <= 3; $i++) {
        $comments[$i] = getPostText('clo' . $i . '_comment');
        $course['clo' . $i . '_comment'] = $comments[$i];
        if (strlen($comments[$i]) > 10000) {
            $error_msg = 'Each CLO comment must be no longer than 10,000 bytes.';
        }
    }
    if ($error_msg === '') {
        try {
            $stmt = $conn->prepare(
                'UPDATE courses SET clo1_comment=?,clo2_comment=?,clo3_comment=? WHERE course_id=? AND lecturer_id=?',
            );
            $stmt->bind_param('sssii', $comments[1], $comments[2], $comments[3], $course_id, $_SESSION['user_id']);
            $stmt->execute();
            $_SESSION['success_msg'] = 'CLO comments saved.';
            header('Location: manage_course_performance.php?course_id=' . $course_id);
            exit();
        } catch (mysqli_sql_exception $exception) {
            error_log($exception->getMessage());
            $error_msg = 'Comments could not be saved. Please try again.';
        }
    }
} elseif (isset($_POST['save_marks'])) {
    if (isset($_POST['marks']) && is_array($_POST['marks'])) {
        $submitted_marks = $_POST['marks'];
    }
    $expected_ids = [];
    foreach ($student_rows as $row) {
        $expected_ids[] = (string) $row['mark']['mark_id'];
    }
    $received_ids = array_map('strval', array_keys($submitted_marks));
    sort($expected_ids);
    sort($received_ids);
    if (!$clos_set) {
        $error_msg = 'Save valid CLO weights before entering marks.';
    } elseif (
        !isset($_POST['marks_complete']) ||
        $_POST['marks_complete'] !== '1' ||
        !$total_students ||
        $expected_ids !== $received_ids
    ) {
        $error_msg =
            'The class list changed or the submitted form was incomplete. No marks were saved. Review your entries and reload the course if needed.';
    }
    foreach ($submitted_marks as $id => $values) {
        if (!is_array($values)) {
            $error_msg = 'Invalid marks submission. No records were saved.';
            continue;
        }
        foreach (['clo1', 'clo2', 'clo3', 'attendance'] as $field) {
            if (!isset($values[$field]) || !is_string($values[$field]) || !isValidPercentage($values[$field])) {
                $entry_errors[$id][$field] = 'Enter a number from 0 to 100.';
            }
        }
        if (isset($values['remarks']) && (!is_string($values['remarks']) || strlen($values['remarks']) > 10000)) {
            $entry_errors[$id]['remarks'] = 'Use no more than 10,000 bytes of text.';
        }
    }
    if (count($entry_errors) > 0) {
        $error_msg = 'Check the highlighted entries. No marks were saved; your entries are shown below.';
    }
    if ($error_msg === '') {
        try {
            $conn->begin_transaction();
            // Serialise against course reassignment and recheck ownership at save time.
            $lock = $conn->prepare(
                'SELECT lecturer_id,clo1_weight,clo2_weight,clo3_weight FROM courses WHERE course_id=? FOR UPDATE',
            );
            $lock->bind_param('i', $course_id);
            $lock->execute();
            $current = $lock->get_result()->fetch_assoc();
            if (!$current || (int) $current['lecturer_id'] !== (int) $_SESSION['user_id']) {
                throw new RuntimeException('Course assignment changed.');
            }
            $fingerprint = implode(',', [$current['clo1_weight'], $current['clo2_weight'], $current['clo3_weight']]);
            if (getPostText('saved_weights') !== $fingerprint) {
                throw new RuntimeException('CLO weights changed. Reload the course before saving.');
            }
            $lock = $conn->prepare('SELECT mark_id FROM marks WHERE course_id=? FOR UPDATE');
            $lock->bind_param('i', $course_id);
            $lock->execute();
            $current_ids = array_map('strval', array_column($lock->get_result()->fetch_all(MYSQLI_ASSOC), 'mark_id'));
            sort($current_ids);
            if ($current_ids !== $expected_ids) {
                throw new RuntimeException('Enrollment changed. Reload the course before saving.');
            }
            $stmt = $conn->prepare(
                'UPDATE marks SET clo1_raw=?,clo2_raw=?,clo3_raw=?,attendance_percentage=?,remarks=? WHERE mark_id=? AND course_id=?',
            );
            foreach ($submitted_marks as $id => $values) {
                $remarks = '';
                if (isset($values['remarks'])) {
                    $remarks = trim($values['remarks']);
                }
                $stmt->bind_param(
                    'ddddsii',
                    $values['clo1'],
                    $values['clo2'],
                    $values['clo3'],
                    $values['attendance'],
                    $remarks,
                    $id,
                    $course_id,
                );
                $stmt->execute();
            }
            $conn->commit();
            $_SESSION['success_msg'] = 'All student marks, attendance and remarks saved.';
            header('Location: manage_course_performance.php?course_id=' . $course_id);
            exit();
        } catch (Throwable $exception) {
            $conn->rollback();
            error_log($exception->getMessage());
            $error_msg =
                'No marks were saved. The course may have changed or a database error occurred. Review your entries before retrying.';
        }
    }
}
// Repopulate editable values on an invalid submission without changing saved reports.
foreach ($student_rows as &$row) {
    $id = $row['mark']['mark_id'];
    if (isset($submitted_marks[$id]) && is_array($submitted_marks[$id])) {
        foreach (
            [
                'clo1' => 'clo1_raw',
                'clo2' => 'clo2_raw',
                'clo3' => 'clo3_raw',
                'attendance' => 'attendance_percentage',
                'remarks' => 'remarks',
            ]
            as $field => $column
        ) {
            if (isset($submitted_marks[$id][$field]) && is_string($submitted_marks[$id][$field])) {
                $row['mark'][$column] = $submitted_marks[$id][$field];
            }
        }
    }
}
unset($row);
$marks_table_class = 'marks-table';
if (!$clos_set) {
    $marks_table_class .= ' marks-table-disabled';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>SEGi CPRS - Course Performance</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body class="workflow-page">

    <div class="screen-only">

        <?php include __DIR__ . '/includes/header.php'; ?>

        <div id="main-content" tabindex="-1" role="main" class="content content-wide">

            <div class="page-title-row">

                <h1 class="page-heading">
                    <?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']); ?>
                </h1>

                <div class="title-actions">

                    <a href="view_assigned_courses.php" class="btn-secondary">
                        &larr; Back
                    </a>

                    <a class="btn-primary" href="course_dashboard.php?course_id=<?php echo $course_id; ?>">View
                        Dashboard</a>
                </div>

            </div>

            <!-- Display the error message only when it exists. -->
            <?php if ($error_msg !== '') {
                echo "<div class='alert-danger' role='alert'>$error_msg</div>";
            } ?>

            <!-- Display the success message only when it exists. -->
            <?php if ($success_msg !== '') {
                echo "<div class='alert-success' role='status'>$success_msg</div>";
            } ?>

            <div class="card">

                <h3 class="section-title">
                    1. Set Assessment CLO Weights (Must Total 100%)
                </h3>

                <form method="POST"><?php echo csrfField(); ?>

                    <div class="clo-setup-grid">

                        <?php for ($i = 1; $i <= 3; $i++): ?>

                        <div class="clo-setup-item">
                            <label for="clo<?php echo $i; ?>_name" class="clo-field-label">CLO <?php echo $i; ?> Name
                                &amp; Weight (%):</label>
                            <div class="clo-form">
                                <input id="clo<?php echo $i; ?>_name" type="text" class="clo-name-input"
                                    name="clo<?php echo $i; ?>_name"
                                    value="<?php echo htmlspecialchars($setup_course['clo' . $i . '_name']); ?>"
                                    placeholder="CLO name" maxlength="50" required>
                                <input id="clo<?php echo $i; ?>_weight" type="number" class="clo-weight-input"
                                    data-weight name="clo<?php echo $i; ?>_weight"
                                    value="<?php echo htmlspecialchars($setup_course['clo' . $i . '_weight']); ?>"
                                    aria-label="CLO <?php echo $i; ?> weight percentage" min="0" max="100" required>
                            </div>

                            <label class="sr-only" for="clo<?php echo $i; ?>_description">CLO <?php echo $i; ?>
                                description</label><textarea id="clo<?php echo $i; ?>_description"
                                name="clo<?php echo $i; ?>_description"
                                placeholder="CLO <?php echo $i; ?> description"><?php echo htmlspecialchars(
     $setup_course['clo' . $i . '_description'],
 ); ?></textarea>
                        </div>
                        <?php endfor; ?>
                    </div>

                    <button type="submit" name="save_clos" class="btn-primary clo-save-button">
                        Save CLOs
                    </button>
                </form>
            </div>

            <div class="card">

                <h3 class="section-title">
                    2. Student Marks Entry
                </h3>

                <?php if (!$clos_set): ?>

                <p class="marks-disabled-message">
                    Please configure and save your CLO weights above to enable the Student Marks Entry table.
                </p>

                <?php endif; ?>

                <form method="POST" id="marks-form"><?php echo csrfField(); ?><input type="hidden" name="save_marks"
                        value="1"><input type="hidden" name="saved_weights" value="<?php echo htmlspecialchars(
                            $course['clo1_weight'] . ',' . $course['clo2_weight'] . ',' . $course['clo3_weight'],
                        ); ?>">
                    <fieldset disabled>
                        <legend class="sr-only">Student marks, attendance and remarks</legend>

                        <div class="marks-table-wrapper" tabindex="0" role="region"
                            aria-label="Student marks, scroll for more students and columns">
                            <table class="<?php echo $marks_table_class; ?>">
                                <caption class="sr-only">Enter student marks and attendance. Weighted marks and results
                                    are calculated automatically.</caption>
                                <colgroup>
                                    <col class="student-col">
                                    <col class="attendance-col">
                                    <?php for ($i = 1; $i <= 3; $i++): ?>
                                    <col class="raw-col">
                                    <col class="weighted-col"><?php endfor; ?>
                                    <col class="total-col">
                                    <col class="grade-col">
                                    <col class="component-col">
                                    <col class="component-col">
                                    <col class="status-col">
                                    <col class="remarks-col">
                                </colgroup>
                                <thead>
                                    <tr>
                                        <th rowspan="2" scope="col">Student</th>
                                        <th scope="col" rowspan="2">Attendance (%)</th>

                                        <?php for ($i = 1; $i <= 3; $i++): ?>

                                        <th scope="colgroup" colspan="2" class="text-center">
                                            CLO <?php echo $i; ?>: <?php echo htmlspecialchars(
    $course['clo' . $i . '_name'],
); ?>
                                        </th>

                                        <?php endfor; ?>

                                        <th scope="col" rowspan="2">Final Mark</th>
                                        <th scope="col" rowspan="2" class="compact-mark-heading">Grade</th>
                                        <th scope="col" rowspan="2" class="compact-mark-heading">CW</th>
                                        <th scope="col" rowspan="2" class="compact-mark-heading">EX</th>
                                        <th scope="col" rowspan="2">Status</th>
                                        <th scope="col" rowspan="2">Remarks</th>
                                    </tr>

                                    <tr>
                                        <?php for ($i = 1; $i <= 3; $i++): ?>

                                        <th class="raw-mark-heading">Raw (/100)</th>

                                        <th class="weighted-mark-heading">Wtd (<?php echo $course[
                                            'clo' . $i . '_weight'
                                        ]; ?>%)</th>

                                        <?php endfor; ?>
                                    </tr>

                                </thead>
                                <tbody> <?php if (count($student_rows) === 0): ?>

                                    <tr>
                                        <td colspan="14">No students are enrolled in this course.</td>
                                    </tr>

                                    <?php else: ?>

                                    <?php foreach ($student_rows as $row): ?>

                                    <?php
                                    $mark = $row['mark'];
                                    $status_class = strtolower($row['status']);
                                    ?>
                                    <tr class="student-row" data-w1="<?php echo $course['clo1_weight']; ?>"
                                        data-w2="<?php echo $course['clo2_weight']; ?>"
                                        data-w3="<?php echo $course['clo3_weight']; ?>">
                                        <th scope="row" class="student-identity"><span class="student-name"><?php echo htmlspecialchars(
                                            $mark['student_name'],
                                        ); ?></span><small><?php echo htmlspecialchars(
    $mark['student_id'],
); ?></small></th>
                                        <td>
                                            <input type="number" min="0" max="100" step="0.01"
                                                id="attendance-<?php echo $mark['mark_id']; ?>" class="attendance-input"
                                                name="marks[<?php echo $mark['mark_id']; ?>][attendance]"
                                                value="<?php echo htmlspecialchars(
                                                    $mark['attendance_percentage'],
                                                ); ?>" <?php if (isset($entry_errors[$mark['mark_id']]['attendance'])) {
    echo 'aria-invalid="true" aria-describedby="attendance-' . $mark['mark_id'] . '-error"';
} ?> aria-label="Attendance percentage for <?php echo htmlspecialchars(
     $mark['student_name'] . ' ' . $mark['student_id'],
 ); ?>" required>
                                            <?php if (
                                                isset($entry_errors[$mark['mark_id']]['attendance'])
                                            ): ?><span class="field-error" id="attendance-<?php echo $mark[
    'mark_id'
]; ?>-error">Enter 0 to 100.</span><?php endif; ?>
                                        </td>
                                        <?php for ($i = 1; $i <= 3; $i++): ?>
                                        <td>
                                            <input type="number" step="0.01" min="0" max="100" id="mark-<?php echo $mark[
                                                'mark_id'
                                            ]; ?>-<?php echo $i; ?>" class="mark-input raw<?php echo $i; ?>"
                                                name="marks[<?php echo $mark['mark_id']; ?>][clo<?php echo $i; ?>]"
                                                value="<?php echo htmlspecialchars(
                                                    $mark['clo' . $i . '_raw'],
                                                ); ?>" <?php if (isset($entry_errors[$mark['mark_id']]['clo' . $i])) {
    echo 'aria-invalid="true" aria-describedby="mark-' . $mark['mark_id'] . '-' . $i . '-error"';
} ?> aria-label="CLO <?php echo $i; ?> raw mark for <?php echo htmlspecialchars(
     $mark['student_name'] . ' ' . $mark['student_id'],
 ); ?>" required>
                                            <?php if (isset($entry_errors[$mark['mark_id']]['clo' . $i])): ?><span
                                                class="field-error" id="mark-<?php echo $mark[
                                                    'mark_id'
                                                ]; ?>-<?php echo $i; ?>-error">Enter 0 to 100.</span><?php endif; ?>
                                        </td>
                                        <td class="weighted-result wtd<?php echo $i; ?>">
                                            <?php echo number_format($row['weighted' . $i], 2); ?>
                                        </td>

                                        <?php endfor; ?>

                                        <td class="total-result">
                                            <b class="total-mark"><?php echo number_format($row['total'], 2); ?></b>
                                        </td>

                                        <td class="text-center">
                                            <b class="grade-text">
                                                <?php echo $row['grade']; ?>
                                            </b>
                                        </td>

                                        <td class="component-result cw-result component-<?php echo strtolower(
                                            $row['cw_result'],
                                        ); ?>">
                                            <?php echo $row['cw_result']; ?>
                                        </td>

                                        <td class="component-result ex-result component-<?php echo strtolower(
                                            $row['ex_result'],
                                        ); ?>">
                                            <?php echo $row['ex_result']; ?>
                                        </td>

                                        <td class="status-container text-center">
                                            <span class="badge badge-<?php echo $status_class; ?>">
                                                <?php echo $row['status']; ?>
                                            </span>
                                        </td>

                                        <td>
                                            <textarea id="remarks-<?php echo $mark[
                                                'mark_id'
                                            ]; ?>" class="student-remark" name="marks[<?php echo $mark[
    'mark_id'
]; ?>][remarks]" aria-label="Remarks for <?php echo htmlspecialchars(
    $mark['student_id'],
); ?>"><?php echo htmlspecialchars($mark['remarks']); ?></textarea>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>

                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <?php if (count($entry_errors) > 0): ?><ul class="field-error" role="alert"><?php foreach (
    $entry_errors
    as $id => $errors
):
    foreach ($errors as $field => $message): ?><li><?php echo htmlspecialchars(
    'Student record ' . $id . ' ' . $field . ': ' . $message,
); ?></li><?php endforeach;
endforeach; ?></ul><?php endif; ?>
                        <input type="hidden" name="marks_complete" value="1">
                    </fieldset>
                    <?php if ($clos_set && $total_students > 0): ?>
                    <button type="submit" id="save-marks" disabled class="btn-primary save-marks-button">
                        Save Marks
                    </button>
                    <?php endif; ?>

                </form>
            </div>

            <?php if ($clos_set): ?>

            <div class="card">

                <h3 class="section-title">
                    3. CLO Attainment
                </h3>

                <form method="POST"><?php echo csrfField(); ?>

                    <!-- CLO attainment is the average RAW mark for each CLO, not the weighted mark. -->
                    <div class="attainment-grid">

                        <?php for ($i = 1; $i <= 3; $i++): ?>

                        <section class="attainment-card" data-clo="<?php echo $i; ?>">

                            <h4>
                                CLO <?php echo $i; ?>: <?php echo htmlspecialchars($course['clo' . $i . '_name']); ?>
                            </h4>

                            <?php if (trim($course['clo' . $i . '_description']) !== ''): ?>
                            <p>
                                <?php echo nl2br(htmlspecialchars($course['clo' . $i . '_description'])); ?>
                            </p>
                            <?php endif; ?>

                            <div class="attainment-track" aria-hidden="true">
                                <div class="attainment-bar <?php echo getAttainmentClass(
                                    $clo_attainment[$i],
                                ); ?>" style="width: <?php echo number_format(
    $clo_attainment[$i],
    2,
    '.',
    '',
); ?>%;"></div>
                            </div>
                            <p class="attainment-value"><?php echo number_format($clo_attainment[$i], 2); ?>%</p>

                            <label for="clo<?php echo $i; ?>_comment">
                                CLO <?php echo $i; ?> Comment:
                            </label>

                            <textarea id="clo<?php echo $i; ?>_comment" name="clo<?php echo $i; ?>_comment"
                                placeholder="Enter comment for CLO <?php echo $i; ?>"><?php echo htmlspecialchars(
    $course['clo' . $i . '_comment'],
); ?></textarea>

                        </section>
                        <?php endfor; ?>
                    </div>

                    <button type="submit" name="save_clo_comments" class="btn-primary">
                        Save CLO Comments
                    </button>
                </form>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="js/manage_course_performance.js"></script>
</body>

</html>
