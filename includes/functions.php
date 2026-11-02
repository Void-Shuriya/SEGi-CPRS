<?php
// Shared helper: open the page controllers, not this file directly.
if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    http_response_code(404);
    exit('Page not found.');
}
// Return one submitted text field safely and remove surrounding spaces.
function getPostText($field_name) {
    if (!isset($_POST[$field_name]) || !is_string($_POST[$field_name])) {
        return '';
    }

    return trim($_POST[$field_name]);
}

// Remove repeated spaces from text that must stay on one line.
function normalizeSingleLine($value) {
    return preg_replace('/\s+/', ' ', trim($value));
}

// Course codes are always stored and displayed in uppercase.
function normalizeCourseCode($value) {
    return strtoupper(normalizeSingleLine($value));
}

// Keep common semester entries consistent, such as "Semester 1".
function normalizeSemester($value) {
    return ucwords(strtolower(normalizeSingleLine($value)));
}

// Convert a raw mark out of 100 into its weighted course mark.
function getWeightedMark($raw_mark, $weight) {
    // Formula: raw mark / 100 multiplied by the CLO weight.
    return ($raw_mark / 100) * $weight;
}

// Check a mark or attendance value before it is saved.
function isValidPercentage($value) {
    return is_numeric($value) && $value >= 0 && $value <= 100;
}

// Assign a grade based on the student's final mark.
function getGrade($mark) {
    if ($mark >= 80) {
        return 'A';
    }
    if ($mark >= 75) {
        return 'A-';
    }
    if ($mark >= 70) {
        return 'B+';
    }
    if ($mark >= 65) {
        return 'B';
    }
    if ($mark >= 60) {
        return 'B-';
    }
    if ($mark >= 55) {
        return 'C+';
    }
    if ($mark >= 50) {
        return 'C';
    }
    if ($mark >= 45) {
        return 'C-';
    }
    if ($mark >= 40) {
        return 'D+';
    }
    if ($mark >= 35) {
        return 'D';
    }

    return 'F';
}

// Determine whether a coursework or examination component is passed.
function getComponentResult($earned, $available) {
    // Return P when at least half of the available component marks were earned.
    if ($available == 0) {
        return 'P';
    }
    if ($earned >= $available / 2) {
        return 'P';
    }

    return 'F';
}

// Determine the academic status from the coursework and examination results.
function getStatus($cw_result, $ex_result) {
    // Coursework failure has priority and requires a Retake.
    if ($cw_result == 'F') {
        return 'Retake';
    }

    // Examination failure requires a Resit.
    if ($ex_result == 'F') {
        return 'Resit';
    }

    return 'Pass';
}

// Calculate the average CLO attainment percentage for the class.
function getAttainment($total_raw_marks, $total_students) {
    // CLO attainment uses average raw marks rather than weighted marks.
    if ($total_students > 0) {
        return $total_raw_marks / $total_students;
    }

    return 0;
}

// Return the CSS class used to display the CLO attainment level.
function getAttainmentClass($percentage) {
    if ($percentage >= 50) {
        return 'attainment-high';
    }

    return 'attainment-low';
}

// Calculate all performance values for one student's marks row.
function getStudentPerformance($mark, $course) {
    // Calculate the weighted mark for each CLO.
    $weighted1 = getWeightedMark($mark['clo1_raw'], $course['clo1_weight']);

    $weighted2 = getWeightedMark($mark['clo2_raw'], $course['clo2_weight']);

    $weighted3 = getWeightedMark($mark['clo3_raw'], $course['clo3_weight']);

    // Calculate the student's final mark.
    $total = $weighted1 + $weighted2 + $weighted3;

    // CLO1 and CLO2 represent coursework, while CLO3 represents examination.
    $cw_result = getComponentResult($weighted1 + $weighted2, $course['clo1_weight'] + $course['clo2_weight']);

    $ex_result = getComponentResult($weighted3, $course['clo3_weight']);

    $status = getStatus($cw_result, $ex_result);

    // Return all calculated values for display on the page.
    return [
        'weighted1' => $weighted1,
        'weighted2' => $weighted2,
        'weighted3' => $weighted3,
        'total' => $total,
        'grade' => getGrade($total),
        'cw_result' => $cw_result,
        'ex_result' => $ex_result,
        'status' => $status,
    ];
}
