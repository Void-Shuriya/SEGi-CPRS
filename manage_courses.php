<?php
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/db.php';

// Ensure that only admin users can access this page.
if ($_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit();
}

$error_msg = '';
$success_msg = '';

// Retrieve session message that is saved when the page redirects.
if (isset($_SESSION['success_msg'])) {
    $success_msg = $_SESSION['success_msg'];

    // Clear the session message.
    unset($_SESSION['success_msg']);
}

// Process course deletion only from the Delete form.
if (isset($_POST['delete_course'])) {
    if (!isset($_POST['delete_id']) || !is_string($_POST['delete_id']) || !ctype_digit($_POST['delete_id'])) {
        $error_msg = 'Invalid course selection.';
    } else {
        $delete_id = intval($_POST['delete_id']);

        // Check whether student enrollment records exist for this course.
        $related_check = $conn->prepare('SELECT COUNT(*) AS total FROM marks WHERE course_id = ?');
        $related_check->bind_param('i', $delete_id);
        $related_check->execute();
        $related_row = $related_check->get_result()->fetch_assoc();

        if ($related_row['total'] > 0) {
            $error_msg = 'This course cannot be deleted because students are enrolled.';
        } else {
            // Delete the course only when it has no enrollment records.
            $delete_stmt = $conn->prepare('DELETE FROM courses WHERE course_id = ?');
            $delete_stmt->bind_param('i', $delete_id);
            $delete_stmt->execute();

            if ($delete_stmt->affected_rows > 0) {
                $_SESSION['success_msg'] = 'Course Deleted Successfully.';

                header('Location: manage_courses.php');
                exit();
            } else {
                $error_msg = 'The selected course was not found.';
            }
        }
    }
}

// Store the courses information.
$courses = $conn->query("SELECT c.*, u.fullname, p.programme_code, sm.study_mode_name FROM courses c
                           JOIN study_modes sm ON c.study_mode_id = sm.study_mode_id
                           JOIN programmes p ON sm.programme_id = p.programme_id
                           JOIN users u ON c.lecturer_id = u.user_id ORDER BY c.course_code");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>SEGi CPRS - Manage Courses</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body class="workflow-page">

    <?php include __DIR__ . '/includes/header.php'; ?>

    <div id="main-content" tabindex="-1" role="main" class="content content-medium">

        <div class="page-title-row">

            <h1 class="page-heading">
                Manage Courses
            </h1>

            <a href="create_course.php" class="btn-primary">
                + Create Course
            </a>
        </div>

        <!-- Display the error message only when it exists. -->
        <?php if ($error_msg !== '') {
            echo "<div class='alert-danger' role='alert'>$error_msg</div>";
        } ?>

        <!-- Display the success message only when it exists. -->
        <?php if ($success_msg !== '') {
            echo "<div class='alert-success' role='status'>$success_msg</div>";
        } ?>

        <div class="card" data-course-list>

            <h3>Course List</h3>
            <div class="filter-bar">
                <div class="filter-search"><label for="course-search">Search course code or name</label><input
                        type="search" id="course-search"></div>
                <div><label for="course-programme">Programme</label><select id="course-programme">
                        <option value="">All programmes</option>
                    </select></div>
                <div><label for="course-lecturer">Lecturer</label><select id="course-lecturer">
                        <option value="">All lecturers</option>
                    </select></div>
                <button type="button" id="reset-courses" class="btn-secondary">Reset Filters</button>
            </div>
            <p id="course-count" role="status"></p>
            <p id="course-empty" hidden>No courses match these filters.</p>

            <div class="table-wrapper">

                <table class="course-list-table admin-course-table">
                    <colgroup>
                        <col class="course-code-col">
                        <col>
                        <col class="programme-col">
                        <col class="mode-col">
                        <col class="semester-col">
                        <col class="lecturer-col">
                        <col class="admin-actions-col">
                    </colgroup>
                    <thead>
                        <tr>
                            <th scope="col">Code</th>
                            <th scope="col">Name</th>
                            <th scope="col">Programme</th>
                            <th scope="col">Study Mode</th>
                            <th scope="col">Semester</th>
                            <th scope="col">Lecturer</th>
                            <th scope="col">Action</th>
                        </tr>
                    </thead>

                    <tbody>

                        <!-- Loop through the courses and display them in the table. -->
                        <?php if ($courses->num_rows === 0): ?>

                        <tr>
                            <td colspan="7">No courses have been created yet.</td>
                        </tr>

                        <?php else: ?>

                        <?php while ($course = $courses->fetch_assoc()): ?>

                        <?php $course_id = intval($course['course_id']); ?>

                        <tr data-course-row data-search="<?php echo htmlspecialchars(
                            strtolower($course['course_code'] . ' ' . $course['course_name']),
                        ); ?>" data-programme="<?php echo htmlspecialchars(
    $course['programme_code'],
); ?>" data-lecturer="<?php echo htmlspecialchars($course['fullname']); ?>">
                            <td>
                                <?php echo htmlspecialchars($course['course_code']); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($course['course_name']); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($course['programme_code']); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($course['study_mode_name']); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($course['semester']); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($course['fullname']); ?>
                            </td>

                            <td class="course-actions">
                                <div class="course-action-group">
                                    <a href="enroll_students.php?course_id=<?php echo $course_id; ?>"
                                        class="btn-success course-action">
                                        <span aria-hidden="true">👤</span><span>Enroll</span>
                                    </a>

                                    <a href="edit_course.php?course_id=<?php echo $course_id; ?>"
                                        class="btn-warning course-action">
                                        <span aria-hidden="true">✏️</span><span>Edit</span>
                                    </a>

                                    <form method="POST" class="inline-form" data-confirm="Delete course <?php echo htmlspecialchars(
                                        $course['course_code'],
                                        ENT_QUOTES,
                                    ); ?>? This cannot be undone."><?php echo csrfField(); ?>
                                        <input type="hidden" name="delete_id" value="<?php echo $course_id; ?>">

                                        <button type="submit" name="delete_course" class="btn-danger course-action">
                                            <span aria-hidden="true">🗑️</span><span>Delete</span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>

                        <?php endif; ?>
                    </tbody>
                </table>

            </div>
        </div>
    </div>
    <script src="js/course_filters.js" defer></script>
</body>

</html>
