<?php
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/functions.php';

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

// Get the course ID from the URL and convert it to an integer.
$course_id = intval($_GET['course_id']);

$error_msg = '';

// Retrieve the selected course and its fixed Programme and Study Mode.
$stmt = $conn->prepare("SELECT c.*, p.programme_code, p.programme_name, sm.study_mode_name
                          FROM courses c JOIN study_modes sm ON c.study_mode_id = sm.study_mode_id
                          JOIN programmes p ON sm.programme_id = p.programme_id WHERE c.course_id = ?");
$stmt->bind_param('i', $course_id);
$stmt->execute();
$course = $stmt->get_result()->fetch_assoc();

// Return to the manage course page if the requested course does not exist.
if (!$course) {
    header('Location: manage_courses.php');
    exit();
}

// Load lecturers once for the dropdown near the bottom of the form.
$lecturers = $conn->query("SELECT user_id, fullname FROM users WHERE role='lecturer'
                             ORDER BY fullname");

// Process the submitted course updates.
if (isset($_POST['update_course'])) {
    // Normalize the editable text fields before validation and saving.
    $code = normalizeCourseCode(getPostText('course_code'));
    $name = normalizeSingleLine(getPostText('course_name'));
    $sem = normalizeSemester(getPostText('semester'));
    $lect_id = intval(getPostText('lecturer_id'));

    // Keep the submitted values visible if validation shows an error.
    $course['course_code'] = $code;
    $course['course_name'] = $name;
    $course['semester'] = $sem;
    $course['lecturer_id'] = $lect_id;

    if ($code === '' || $name === '' || $sem === '' || $lect_id === 0) {
        $error_msg = 'All course fields are required.';
    } elseif (strlen($code) > 20 || strlen($name) > 100 || strlen($sem) > 20) {
        $error_msg = 'One or more course fields are too long.';
    } else {
        // Check whether another course already uses the entered code.
        $duplicate_stmt = $conn->prepare("SELECT course_id FROM courses WHERE course_code = ?
                                            AND course_id <> ?");
        $duplicate_stmt->bind_param('si', $code, $course_id);
        $duplicate_stmt->execute();

        // Read this result before starting another query on the same connection.
        $duplicate_result = $duplicate_stmt->get_result();

        // Confirm that the new lecturer selection is valid.
        $lecturer_stmt = $conn->prepare("SELECT user_id FROM users WHERE user_id = ? AND role = 'lecturer'");
        $lecturer_stmt->bind_param('i', $lect_id);
        $lecturer_stmt->execute();
        $lecturer_result = $lecturer_stmt->get_result();

        // Stop before updating when either simple validation check fails.
        if ($duplicate_result->num_rows > 0) {
            $error_msg = 'That Course Code is already in use.';
        } elseif ($lecturer_result->num_rows == 0) {
            $error_msg = 'Please select a valid lecturer.';
        } else {
            // Update only the editable course information.
            $u_stmt = $conn->prepare(
                'UPDATE courses SET course_code=?, course_name=?, semester=?, lecturer_id=? WHERE course_id=?',
            );
            $u_stmt->bind_param('sssii', $code, $name, $sem, $lect_id, $course_id);
            $u_stmt->execute();

            $_SESSION['success_msg'] = 'Course updated successfully.';

            header('Location: manage_courses.php');
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>SEGi CPRS - Edit Course</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body class="workflow-page">

    <?php include __DIR__ . '/includes/header.php'; ?>

    <div id="main-content" tabindex="-1" role="main" class="content form-page">

        <div class="card">

            <h1 class="form-title">Edit Course</h1>

            <!-- Display the error message only when it exists. -->
            <?php if ($error_msg !== '') {
                echo "<div class='alert-danger' role='alert'>$error_msg</div>";
            } ?>

            <form method="POST"><?php echo csrfField(); ?>

                <label for="course_code">Course Code:</label>

                <input id="course_code" type="text" name="course_code" class="uppercase-input" maxlength="20"
                    value="<?php echo htmlspecialchars($course['course_code']); ?>" required>

                <label for="course_name">Course Name:</label>

                <input id="course_name" type="text" name="course_name" maxlength="100"
                    value="<?php echo htmlspecialchars($course['course_name']); ?>" required>
                <label for="programme_display">Programme:</label>

                <input id="programme_display" type="text" value="<?php echo htmlspecialchars(
                    $course['programme_code'] . ' - ' . $course['programme_name'],
                ); ?>" disabled>

                <label for="study_mode_display">Study Mode:</label>

                <input id="study_mode_display" type="text"
                    value="<?php echo htmlspecialchars($course['study_mode_name']); ?>" disabled>

                <label for="semester">Semester:</label>

                <input id="semester" type="text" name="semester" maxlength="20"
                    value="<?php echo htmlspecialchars($course['semester']); ?>" required>

                <label for="lecturer_id">Assign Lecturer:</label>

                <select id="lecturer_id" name="lecturer_id" required>

                    <!-- Display a default option for the user to select a lecturer. -->
                    <?php while ($lecturer = $lecturers->fetch_assoc()): ?>

                    <option value="<?php echo $lecturer['user_id']; ?>" <?php if (
    $lecturer['user_id'] == $course['lecturer_id']
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

                    <button type="submit" name="update_course" class="btn-primary">
                        Update Course
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>

</html>
