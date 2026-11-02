<?php
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/functions.php';

// Ensure that only admin users can access this page.
if ($_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit();
}

$error_msg = '';
$course_code = '';
$course_name = '';
$programme_id = 0;
$study_mode_id = 0;
$semester = '';
$lecturer_id = 0;

// Process the form when the Admin clicks Create Course.
if (isset($_POST['add_course'])) {
    // Normalize the fields that should have a consistent display format.
    $course_code = normalizeCourseCode(getPostText('course_code'));
    $course_name = normalizeSingleLine(getPostText('course_name'));
    $programme_id = intval(getPostText('programme_id'));
    $study_mode_id = intval(getPostText('study_mode_id'));
    $semester = normalizeSemester(getPostText('semester'));
    $lecturer_id = intval(getPostText('lecturer_id'));

    if (
        $course_code === '' ||
        $course_name === '' ||
        $semester === '' ||
        $programme_id === 0 ||
        $study_mode_id === 0 ||
        $lecturer_id === 0
    ) {
        $error_msg = 'Please complete all course fields.';
    } elseif (strlen($course_code) > 20 || strlen($course_name) > 100 || strlen($semester) > 20) {
        $error_msg = 'One or more course fields are too long.';
    } else {
        // Run database checks only after the required fields are present.
        $duplicate_stmt = $conn->prepare('SELECT course_id FROM courses WHERE course_code = ?');
        $duplicate_stmt->bind_param('s', $course_code);
        $duplicate_stmt->execute();
        $duplicate_result = $duplicate_stmt->get_result();

        $mode_stmt = $conn->prepare(
            'SELECT study_mode_id FROM study_modes WHERE study_mode_id = ? AND programme_id = ?',
        );
        $mode_stmt->bind_param('ii', $study_mode_id, $programme_id);
        $mode_stmt->execute();
        $mode_result = $mode_stmt->get_result();

        $lecturer_stmt = $conn->prepare("SELECT user_id FROM users WHERE user_id = ? AND role = 'lecturer'");
        $lecturer_stmt->bind_param('i', $lecturer_id);
        $lecturer_stmt->execute();
        $lecturer_result = $lecturer_stmt->get_result();

        if ($duplicate_result->num_rows > 0) {
            $error_msg = 'That Course Code is already in use.';
        } elseif ($mode_result->num_rows == 0) {
            $error_msg = 'The selected Study Mode does not belong to that Programme.';
        } elseif ($lecturer_result->num_rows == 0) {
            $error_msg = 'Please select a valid lecturer.';
        } else {
            // Save the course after all validation checks pass.
            $save_stmt = $conn->prepare(
                'INSERT INTO courses (course_code, course_name, study_mode_id, semester, lecturer_id) VALUES (?, ?, ?, ?, ?)',
            );
            $save_stmt->bind_param('ssisi', $course_code, $course_name, $study_mode_id, $semester, $lecturer_id);
            $save_stmt->execute();

            $_SESSION['success_msg'] = 'Course Created Successfully.';

            header('Location: manage_courses.php');
            exit();
        }
    }
}

// Load Programmes, Study Modes, and Lecturers for the form.
$programmes = $conn->query("SELECT programme_id, programme_code, programme_name FROM programmes
                              ORDER BY programme_code");

$study_modes = $conn->query("SELECT study_mode_id, programme_id, study_mode_name FROM study_modes
                               ORDER BY programme_id, study_mode_name");

$lecturers = $conn->query("SELECT user_id, fullname FROM users WHERE role='lecturer' ORDER BY fullname");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>SEGi CPRS - Create Course</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body class="workflow-page">

    <?php include __DIR__ . '/includes/header.php'; ?>

    <div id="main-content" tabindex="-1" role="main" class="content form-page">

        <div class="card">

            <h1 class="form-title">
                Create Course</h1>

            <!-- Display the error message only when it exists. -->
            <?php if ($error_msg !== '') {
                echo "<div class='alert-danger' role='alert'>$error_msg</div>";
            } ?>

            <form method="POST"><?php echo csrfField(); ?>

                <label for="course_code">Course Code:</label>
                <input id="course_code" type="text" name="course_code" class="uppercase-input" maxlength="20" value="<?php echo htmlspecialchars(
                    $course_code,
                ); ?>" required>

                <label for="course_name">Course Name:</label>
                <input id="course_name" type="text" name="course_name" maxlength="100" value="<?php echo htmlspecialchars(
                    $course_name,
                ); ?>" required>

                <label for="programme_id">Programme:</label>
                <select name="programme_id" id="programme_id" required>

                    <option value="">Select Programme</option>

                    <!-- Fill the dropdown with programmes from the database. -->
                    <?php while ($programme = $programmes->fetch_assoc()): ?>

                    <option value="<?php echo $programme['programme_id']; ?>" <?php if (
    $programme['programme_id'] == $programme_id
) {
    echo 'selected';
} ?>>
                        <?php echo htmlspecialchars(
                            $programme['programme_code'] . ' - ' . $programme['programme_name'],
                        ); ?>
                    </option>

                    <?php endwhile; ?>

                </select>

                <label for="study_mode_id">Study Mode:</label>

                <!-- The Study Mode dropdown is disabled until a Programme is selected. -->
                <select name="study_mode_id" id="study_mode_id" required disabled>

                    <option value="">Select Study Mode</option>

                    <!-- Fill the dropdown with study modes from the database. -->
                    <?php while ($mode = $study_modes->fetch_assoc()): ?>

                    <option value="<?php echo $mode['study_mode_id']; ?>"
                        data-programme-id="<?php echo $mode['programme_id']; ?>" <?php if (
    $mode['study_mode_id'] == $study_mode_id
) {
    echo 'selected';
} ?>>
                        <?php echo htmlspecialchars($mode['study_mode_name']); ?>
                    </option>

                    <?php endwhile; ?>
                </select>

                <label for="semester">Semester:</label>

                <input id="semester" type="text" name="semester" maxlength="20" placeholder="e.g. Semester 1" value="<?php echo htmlspecialchars(
                    $semester,
                ); ?>" required>

                <label for="lecturer_id">Assign Lecturer:</label>

                <select id="lecturer_id" name="lecturer_id" required>

                    <option value="">Select Lecturer</option>

                    <!-- Fill the dropdown with lecturers from the database. -->
                    <?php while ($lecturer = $lecturers->fetch_assoc()): ?>

                    <option value="<?php echo $lecturer['user_id']; ?>" <?php if (
    $lecturer['user_id'] == $lecturer_id
) {
    echo 'selected';
} ?>>
                        <?php echo htmlspecialchars($lecturer['fullname']); ?>
                    </option>

                    <?php endwhile; ?>
                </select>

                <div class="form-actions">

                    <a href="manage_courses.php" class="btn-secondary">
                        &larr; Back
                    </a>

                    <button type="submit" name="add_course" class="btn-primary">
                        Create Course
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="js/create_course.js"></script>
</body>

</html>
