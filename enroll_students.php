<?php
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/db.php';

// Ensure that only admin users can access this page.
if ($_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit();
}

// Ensure that a numeric course ID was passed in the URL.
if (!isset($_GET['course_id']) || !is_string($_GET['course_id']) || !ctype_digit($_GET['course_id'])) {
    header('Location: manage_courses.php');
    exit();
}

$course_id = intval($_GET['course_id']);

$error_msg = '';
$success_msg = '';

$selected_students = [];

// Retrieve session message that is saved when the page redirects.
if (isset($_SESSION['success_msg'])) {
    $success_msg = $_SESSION['success_msg'];

    // Clear the session message.
    unset($_SESSION['success_msg']);
}

// Retrieve the selected course together with its Programme and Study Mode.
$course_stmt = $conn->prepare("SELECT c.*, sm.study_mode_name, p.programme_id, p.programme_code, p.programme_name
                                           FROM courses c JOIN study_modes sm ON c.study_mode_id = sm.study_mode_id
                                           JOIN programmes p ON sm.programme_id = p.programme_id WHERE c.course_id = ?");

$course_stmt->bind_param('i', $course_id);
$course_stmt->execute();
$course = $course_stmt->get_result()->fetch_assoc();

// Return to Manage Courses if the requested course does not exist.
if (!$course) {
    header('Location: manage_courses.php');
    exit();
}

$course_study_mode_id = intval($course['study_mode_id']);

// Remove enrollment only from the Disenroll form.
if (isset($_POST['unenroll_student'])) {
    if (
        !isset($_POST['student_id']) ||
        !is_string($_POST['student_id']) ||
        $_POST['student_id'] === '' ||
        strlen($_POST['student_id']) > 20
    ) {
        $error_msg = 'Invalid student selection.';
    } else {
        $student_id = $_POST['student_id'];
        $delete_stmt = $conn->prepare('DELETE FROM marks WHERE student_id = ? AND course_id = ?');
        $delete_stmt->bind_param('si', $student_id, $course_id);
        $delete_stmt->execute();

        if ($delete_stmt->affected_rows > 0) {
            $_SESSION['success_msg'] = 'Student Removed from Course Successfully.';

            header('Location: enroll_students.php?course_id=' . $course_id);
            exit();
        } else {
            $error_msg = 'The selected enrollment was not found.';
        }
    }
}

// Load students who match the course and are not enrolled yet.
$available_stmt = $conn->prepare("SELECT s.student_id, s.student_name, s.intake, p.programme_code, sm.study_mode_name
                                  FROM students s JOIN study_modes sm ON s.study_mode_id = sm.study_mode_id
                                  JOIN programmes p ON sm.programme_id = p.programme_id
                                  LEFT JOIN marks m ON m.student_id = s.student_id AND m.course_id = ?
                                  WHERE s.study_mode_id = ? AND m.mark_id IS NULL ORDER BY s.intake, s.student_name");

$available_stmt->bind_param('ii', $course_id, $course_study_mode_id);
$available_stmt->execute();
$available_students = $available_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$available_student_ids = array_column($available_students, 'student_id');

// Process the students selected for enrollment.
if (isset($_POST['enroll_selected'])) {
    if (isset($_POST['student_ids']) && is_array($_POST['student_ids'])) {
        // Keep only unique student IDs from the eligible list.
        foreach ($_POST['student_ids'] as $student_id) {
            if (
                is_string($student_id) &&
                in_array($student_id, $available_student_ids, true) &&
                !in_array($student_id, $selected_students, true)
            ) {
                $selected_students[] = $student_id;
            }
        }
    }

    if (!isset($_POST['enrollment_complete']) || $_POST['enrollment_complete'] !== '1') {
        $error_msg =
            'The enrollment form was incomplete. No students were enrolled. Reduce the selection and try again.';
    } elseif (count($selected_students) == 0) {
        $error_msg = 'Please Select at Least One Student to Enroll.';
    } else {
        try {
            $conn->begin_transaction();
            // Parent lock serialises enrollment with course edits and marks saves.
            $lock = $conn->prepare('SELECT course_id FROM courses WHERE course_id=? FOR UPDATE');
            $lock->bind_param('i', $course_id);
            $lock->execute();
            $lock->get_result();
            $insert_stmt = $conn->prepare(
                "INSERT INTO marks (student_id,course_id,clo1_raw,clo2_raw,clo3_raw,attendance_percentage,remarks) VALUES (?,?,0,0,0,100,'')",
            );
            foreach ($selected_students as $student_id) {
                $insert_stmt->bind_param('si', $student_id, $course_id);
                $insert_stmt->execute();
            }
            $conn->commit();
            $_SESSION['success_msg'] = count($selected_students) . ' student(s) enrolled successfully.';
            header('Location: enroll_students.php?course_id=' . $course_id);
            exit();
        } catch (mysqli_sql_exception $exception) {
            $conn->rollback();
            error_log($exception->getMessage());
            $error_msg =
                'Enrollment could not be completed. No new students were enrolled. Reload the page and try again.';
        }
    }
}

// Build the intake dropdown from the students that can still be enrolled.
$students_by_intake = [];

foreach ($available_students as $student) {
    if (!isset($students_by_intake[$student['intake']])) {
        $students_by_intake[$student['intake']] = [];
    }

    $students_by_intake[$student['intake']][] = $student;
}

$available_intakes = array_keys($students_by_intake);

// Load all students currently enrolled in the selected course.
$enrolled_stmt = $conn->prepare("SELECT s.student_id, s.student_name, s.intake, p.programme_code, sm.study_mode_name
                                 FROM marks m JOIN students s ON m.student_id = s.student_id
                                 JOIN study_modes sm ON s.study_mode_id = sm.study_mode_id
                                 JOIN programmes p ON sm.programme_id = p.programme_id WHERE m.course_id = ?
                                 ORDER BY s.intake, s.student_name");

$enrolled_stmt->bind_param('i', $course_id);
$enrolled_stmt->execute();
$enrolled_students = $enrolled_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>SEGi CPRS - Enroll Students</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body class="workflow-page">

    <?php include __DIR__ . '/includes/header.php'; ?>

    <div id="main-content" tabindex="-1" role="main" class="content content-medium">

        <div class="card">

            <div class="page-title-row">

                <h1>
                    Enroll Students into:
                    <?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']); ?>
                </h1>

                <a href="manage_courses.php" class="btn-secondary">
                    &larr; Back
                </a>
            </div>

            <p>
                <b>Programme:</b>

                <?php echo htmlspecialchars($course['programme_code'] . ' - ' . $course['programme_name']); ?>
                &nbsp;

                <b>Study Mode:</b>
                <?php echo htmlspecialchars($course['study_mode_name']); ?>
            </p>

            <!-- Display the error message only when it exists. -->
            <?php if ($error_msg !== '') {
                echo "<div class='alert-danger' role='alert'>$error_msg</div>";
            } ?>

            <!-- Display the success message only when it exists. -->
            <?php if ($success_msg !== '') {
                echo "<div class='alert-success' role='status'>$success_msg</div>";
            } ?>
        </div>

        <div class="card">

            <h3>
                Enroll Students by Intake
            </h3>

            <form method="POST"><?php echo csrfField(); ?><input type="hidden" name="enroll_selected" value="1">

                <?php if (count($available_students) === 0): ?>

                <p>No matching students were found.</p>

                <?php else: ?>

                <div class="filter-bar enrollment-filters">
                    <div class="filter-search"><label for="student-search">Search eligible students by name or
                            ID</label><input id="student-search" type="search"></div>
                    <div class="intake-filter">

                        <label for="intake-filter"><b>Show Intake:</b></label>

                        <select id="intake-filter">

                            <option value="">All Intakes</option>

                            <!-- Fill the intake dropdown with available intakes. -->
                            <?php foreach ($available_intakes as $intake): ?>

                            <option value="<?php echo htmlspecialchars($intake); ?>">
                                <?php echo htmlspecialchars($intake); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                </div>
                <div class="selection-tools"><label class="select-all-choice"><input id="select-all-students"
                            type="checkbox"> Select all</label></div>
                <p id="students-empty" hidden>No students match your search.</p>
                <div class="student-list">

                    <?php foreach ($students_by_intake as $intake => $intake_students): ?>

                    <section class="intake-group" data-intake="<?php echo htmlspecialchars($intake); ?>">

                        <h4 class="intake-heading"><?php echo htmlspecialchars($intake); ?></h4>

                        <?php foreach ($intake_students as $student): ?>

                        <label class="student-choice">

                            <input type="checkbox" name="student_ids[]"
                                value="<?php echo htmlspecialchars($student['student_id']); ?>" <?php if (
    in_array($student['student_id'], $selected_students)
) {
    echo 'checked';
} ?>>

                            <!-- Display the student details next to the checkbox. -->
                            <span>
                                <b><?php echo htmlspecialchars($student['student_id']); ?></b>

                                - <?php echo htmlspecialchars($student['student_name']); ?><br>

                                <small>
                                    <?php echo htmlspecialchars(
                                        $student['programme_code'] .
                                            ' | ' .
                                            $student['study_mode_name'] .
                                            ' | ' .
                                            $student['intake'],
                                    ); ?>
                                </small>
                            </span>

                        </label>
                        <?php endforeach; ?>
                    </section>
                    <?php endforeach; ?>
                </div>

                <input type="hidden" name="enrollment_complete" value="1"><button type="submit" id="enroll-selected"
                    class="btn-primary enroll-selected-button">
                    👤 Enroll Selected
                </button>
                <?php endif; ?>
            </form>
        </div>

        <div class="card">

            <h3>
                Currently Enrolled
            </h3>

            <?php if (count($enrolled_students) === 0): ?>

            <p>No students are enrolled in this course yet.</p>

            <?php else: ?>

            <div class="enrolled-table-wrapper">

                <table>
                    <thead>
                        <tr>
                            <th scope="col">Student ID</th>
                            <th scope="col">Name</th>
                            <th scope="col">Programme</th>
                            <th scope="col">Study Mode</th>
                            <th scope="col">Intake</th>
                            <th scope="col">Action</th>
                        </tr>
                    </thead>

                    <tbody>

                        <!-- Loop through each enrolled student and display their details -->
                        <?php foreach ($enrolled_students as $student): ?>

                        <tr>
                            <td>
                                <?php echo htmlspecialchars($student['student_id']); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($student['student_name']); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($student['programme_code']); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($student['study_mode_name']); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($student['intake']); ?>
                            </td>

                            <td>
                                <form method="POST" class="inline-form" data-confirm="Disenroll <?php echo htmlspecialchars(
                                    $student['student_name'] . ' (' . $student['student_id'] . ')',
                                    ENT_QUOTES,
                                ); ?> and permanently remove their marks?"><?php echo csrfField(); ?>
                                    <input type="hidden" name="student_id" value="<?php echo htmlspecialchars(
                                        $student['student_id'],
                                    ); ?>">

                                    <button type="submit" name="unenroll_student" class="btn-danger">
                                        🗑️ Disenroll
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="js/enroll_students.js"></script>
</body>

</html>
