<?php
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/db.php';

// Ensure that only lecturer users can access this page.
if ($_SESSION['role'] !== 'lecturer') {
    header('Location: index.php');
    exit();
}

$lecturer_id = $_SESSION['user_id'];

// Fetch all courses assigned to the logged-in lecturer.
$stmt = $conn->prepare("SELECT c.*, p.programme_code, sm.study_mode_name, (SELECT COUNT(*) FROM marks m WHERE m.course_id=c.course_id) AS student_count FROM courses c
                        JOIN study_modes sm ON c.study_mode_id = sm.study_mode_id
                        JOIN programmes p ON sm.programme_id = p.programme_id
                        WHERE c.lecturer_id = ? ORDER BY c.course_code");
$stmt->bind_param('i', $lecturer_id);
$stmt->execute();

// Get the result set of assigned courses.
$courses = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>SEGi CPRS - Assigned Courses</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body class="workflow-page">

    <?php include __DIR__ . '/includes/header.php'; ?>

    <div id="main-content" tabindex="-1" role="main" class="content content-medium">

        <h1>Your Assigned Courses</h1>

        <div data-course-list>
            <div class="card filter-bar">
                <div class="filter-search">
                    <label for="course-search">Search your courses</label>
                    <input type="search" id="course-search" placeholder="Course code or name">
                </div>
                <div><label for="course-programme">Programme</label><select id="course-programme">
                        <option value="">All programmes</option>
                    </select></div>
                <div><label for="course-mode">Study Mode</label><select id="course-mode">
                        <option value="">All study modes</option>
                    </select></div>
                <button type="button" id="reset-courses" class="btn-secondary">Reset</button>
            </div>
            <p id="course-count" role="status"></p>
            <?php if ($courses->num_rows === 0): ?>
            <p>No courses assigned to you yet.</p>
            <?php else: ?>
            <p id="course-empty" hidden>No courses match your search.</p>
            <div class="card table-wrapper">
                <table class="course-list-table lecturer-course-table">
                    <caption class="sr-only">Courses assigned to you</caption>
                    <colgroup>
                        <col class="course-code-col">
                        <col>
                        <col class="programme-col">
                        <col class="mode-col">
                        <col class="semester-col">
                        <col class="students-col">
                        <col class="open-course-col">
                    </colgroup>
                    <thead>
                        <tr>
                            <th scope="col">Code</th>
                            <th scope="col">Name</th>
                            <th scope="col">Programme</th>
                            <th scope="col">Study Mode</th>
                            <th scope="col">Semester</th>
                            <th scope="col">Students</th>
                            <th scope="col">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($course = $courses->fetch_assoc()): ?>
                        <tr data-course-row data-programme="<?php echo htmlspecialchars(
                            $course['programme_code'],
                            ENT_QUOTES,
                        ); ?>" data-mode="<?php echo htmlspecialchars(
    $course['study_mode_name'],
    ENT_QUOTES,
); ?>" data-search="<?php echo htmlspecialchars(strtolower($course['course_code'] . ' ' . $course['course_name'])); ?>">
                            <td><?php echo htmlspecialchars($course['course_code']); ?></td>
                            <td><?php echo htmlspecialchars($course['course_name']); ?></td>
                            <td><?php echo htmlspecialchars($course['programme_code']); ?></td>
                            <td><?php echo htmlspecialchars($course['study_mode_name']); ?></td>
                            <td><?php echo htmlspecialchars($course['semester']); ?></td>
                            <td><?php echo (int) $course['student_count']; ?></td>
                            <td><a href="manage_course_performance.php?course_id=<?php echo (int) $course[
                                'course_id'
                            ]; ?>" class="btn-primary" aria-label="Open Course: <?php echo htmlspecialchars(
    $course['course_code'],
); ?>">Open Course</a></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <script src="js/course_filters.js" defer></script>
</body>

</html>
